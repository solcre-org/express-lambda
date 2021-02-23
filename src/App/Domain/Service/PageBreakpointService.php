<?php

namespace App\Domain\Service;

use App\Domain\Entity\PageBreakpoint;
use App\Domain\Exception\ConfigNotFoundException;
use App\Domain\Exception\Templates\PathNotFoundException;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use MatthiasMullie\Minify\CSS;
use Mezzio\Template\TemplateRendererInterface;
use Minify_CSSmin;
use function count;
use function is_array;

class PageBreakpointService
{
    protected array $templatesPathStack = [];
    protected array $assetsManagerPathStack = [];
    protected TemplateRendererInterface $templateRenderer;
    private Filesystem $awsS3Adapter;
    private LogService $logService;
    private string $extension;

    /**
     * Constructor
     *
     * @param TemplateRendererInterface $templateRenderer
     * @param array $templatesPathStack
     * @param array $assetsManagerPathStack
     * @param Filesystem $awsS3Adapter
     * @param string $extension
     * @param LogService $logService
     */
    public function __construct(
        TemplateRendererInterface $templateRenderer,
        array $templatesPathStack,
        array $assetsManagerPathStack,
        Filesystem $awsS3Adapter,
        string $extension,
        LogService $logService
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->templatesPathStack = $templatesPathStack;
        $this->assetsManagerPathStack = $assetsManagerPathStack;
        $this->awsS3Adapter = $awsS3Adapter;
        $this->extension = $extension;
        $this->logService = $logService;
    }

    /**
     * Creats a PageBreakpoint file if not exist
     *
     * @param int $idPage
     * @param array $extraData
     * @param string $hash
     * @param array $images
     * @param array $imageSizesGroups
     *
     * @return string
     *
     * @throws Exception|FilesystemException
     */
    public function createPageBreakpoint(int $idPage, array $extraData, string $hash, array $images, array $imageSizesGroups): string
    {
        try {
            $this->logService->info('INIT PAGE BREAKPOINT', \compact($idPage));
            $pageBreakpoint = new PageBreakpoint();
            $pageBreakpoint->setHash($hash);
            $pageBreakpoint->setIdPage($idPage);
            $pageBreakpoint->setExtraData($extraData);
            $pageBreakpoint->setImages($images);
            $pageBreakpoint->setTemplateHash($this->getBreakpointTemplateHash());
            $pageBreakpoint->setImageGroupsSizes($imageSizesGroups);
//        $this->loadBreakpointsPath($pageBreakpoint); //Sets path
            $pathExist = $this->checkBreakpointPath(); //Check path exist, if not create it
            $this->logService->info('PATH EXISTS', \compact($pathExist));
            $currentBreakpointName = $this->getCurrentBreakpointFilename($idPage);
            $breakpointChange = $currentBreakpointName !== $pageBreakpoint->getFileName();

            if (! $pathExist || $breakpointChange) {
                //Invalidate last file
                if ($currentBreakpointName !== null) {
                    $this->invalidateCurrentBreakpointFile($currentBreakpointName);
                }

                $this->logService->info('createBreakpointFile');
                //Create it if path not exist (css/breakpoint dir) or the current file are diferent with the parameters
                $this->createBreakpointFile($pageBreakpoint, $idPage);
            }

            return $pageBreakpoint->getFileName();
        } catch (Exception $e) {
            $this->logService->error($e->getMessage());
            unset($e);
        }

        return '';
    }

    /**
     * Get the template file hash
     *
     * @return string
     *
     * @throws ConfigNotFoundException
     */
    protected function getBreakpointTemplateHash(): string
    {
        $templateFile = PageBreakpoint::BREAKPOINT_FILE_NAME . $this->extension;
        $paths = $this->templateRenderer->getPaths();
        foreach ($paths as $path) {
            $fullPath = $path . $templateFile;
            if (file_exists($fullPath)) {
                return md5_file($fullPath);
            }
        }

        throw new ConfigNotFoundException('Breakpoint template not found', 404);
    }

    /**
     * Retrieve paths to templates
     *
     * @return array
     */
    public function getTemplatesPathStack(): array
    {
        return $this->templatesPathStack;
    }

    /**
     * Set the templates paths
     *
     * @param array $templatesPathStack
     */
    public function setTemplatesPathStack(array $templatesPathStack): void
    {
        $this->templatesPathStack = $templatesPathStack;
    }

    /**
     * Sets the path to pageBreakpoint object
     *
     * @param PageBreakpoint $pageBreakpoint
     *
     * @throws PathNotFoundException
     */
    protected function loadBreakpointsPath(PageBreakpoint $pageBreakpoint): void
    {
        $assetsPaths = $this->getAssetsManagerPathStack();
        if (is_array($assetsPaths)) {
            $pageBreakpoint->setPath($assetsPaths[0]);
        }
    }

    /**
     * Retrieve paths to assets manager
     *
     * @return array
     */
    public function getAssetsManagerPathStack(): array
    {
        return $this->assetsManagerPathStack;
    }

    /**
     * Set the assets manager paths
     *
     * @param array $assetsManagerPathStack
     */
    public function setAssetsManagerPathStack(array $assetsManagerPathStack): void
    {
        $this->assetsManagerPathStack = $assetsManagerPathStack;
    }

    /**
     * Check Breakpoints directory, if not exist create it
     *
     * @return boolean
     * @throws FilesystemException
     */
    private function checkBreakpointPath(): bool
    {
        if (! $this->awsS3Adapter->fileExists(PageBreakpoint::BREAKPOINT_DIR)) {
            $this->awsS3Adapter->createDirectory(PageBreakpoint::BREAKPOINT_DIR);
        }

        return true;
    }

    /**
     * Search on breakpoints dir the current breakpoint page file
     *
     * @param int $idPage
     * @return string
     * @throws FilesystemException
     */
    private function getCurrentBreakpointFilename(int $idPage): ?string
    {
        $breakpointFiles = $this->awsS3Adapter->listContents(PageBreakpoint::BREAKPOINT_DIR)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn(StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        if (is_array($breakpointFiles) && count($breakpointFiles)) {
            foreach ($breakpointFiles as $breakpointFile) {
                //TODO MEJORAR
                $filenameCleaned = str_replace('breakpoints/', '', $breakpointFile);
                $pageIdExploded = \explode('-', $filenameCleaned);
                if (is_array($pageIdExploded) && isset($pageIdExploded[0]) && (int)$pageIdExploded[0] === $idPage) {
                    return $filenameCleaned;
                }
            }
        }

        return null;
    }

    /**
     * Remove the last breakpoint file
     *
     * @param string $currentFileName
     *
     * @throws FilesystemException
     */
    protected function invalidateCurrentBreakpointFile(string $currentFileName): void
    {
        try {
            $fullPath = PageBreakpoint::BREAKPOINT_FILE_NAME . \DIRECTORY_SEPARATOR . $currentFileName;
            if ($this->awsS3Adapter->fileExists($fullPath)) {
                $this->awsS3Adapter->delete($fullPath);
            }
        } catch (Exception $exc) {

        }
    }

    /**
     * Create a new PageBreakpointFile
     *
     * @param PageBreakpoint $pageBreakpoint
     * @param int $idPage
     * @throws FilesystemException
     */
    protected function createBreakpointFile(PageBreakpoint $pageBreakpoint, int $idPage): void
    {
        try {
            $template = $this->templateRenderer->render(
                PageBreakpoint::BREAKPOINT_FILE_NAME . $this->extension, [
                    'data' => $pageBreakpoint->getData()
                ]
            );

            //TODO Lambda hack is this ok?
            if (! isset($_SERVER['DOCUMENT_ROOT'])) {
                $_SERVER['DOCUMENT_ROOT'] = '/tmp';
            }

            $this->awsS3Adapter->write(PageBreakpoint::BREAKPOINT_FILE_NAME . \DIRECTORY_SEPARATOR . $pageBreakpoint->getFileName(), Minify_CSSmin::minify($template, [
                'docRoot' => '/tmp'
            ]), [
                'Metadata' => [
                    'page' => $idPage
                ]
            ]);
        } catch (Exception $exc) {
            $this->logService->error($exc->getMessage());
        }
    }
}
