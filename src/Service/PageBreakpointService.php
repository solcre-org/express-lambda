<?php

namespace SolcreExpressLambda\Service;

use SolcreExpressLambda\Entity\PageBreakpoint;
use SolcreExpressLambda\Exception\ConfigNotFoundException;
use CssMin;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Mezzio\Template\TemplateRendererInterface;
use function count;
use function explode;
use function is_array;
use const DIRECTORY_SEPARATOR;

class PageBreakpointService
{
    protected TemplateRendererInterface $templateRenderer;
    private Filesystem $awsS3Adapter;
    private LogService $logService;
    private string $extension;

    /**
     * Constructor
     *
     * @param TemplateRendererInterface $templateRenderer
     * @param Filesystem $awsS3Adapter
     * @param string $extension
     * @param LogService $logService
     */
    public function __construct(
        TemplateRendererInterface $templateRenderer,
        Filesystem $awsS3Adapter,
        string $extension,
        LogService $logService
    ) {
        $this->templateRenderer = $templateRenderer;
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
            $pageBreakpoint = new PageBreakpoint();
            $pageBreakpoint->setHash($hash);
            $pageBreakpoint->setIdPage($idPage);
            $pageBreakpoint->setExtraData($extraData);
            $pageBreakpoint->setImages($images);
            $pageBreakpoint->setTemplateHash($this->getBreakpointTemplateHash());
            $pageBreakpoint->setImageGroupsSizes($imageSizesGroups);
            $currentBreakpointName = $this->getCurrentBreakpointFilename($idPage);
            $breakpointChange = $currentBreakpointName !== $pageBreakpoint->getFileName();

            if ($breakpointChange) {
                //Invalidate last file
                if ($currentBreakpointName !== null) {
                    $this->invalidateCurrentBreakpointFile($currentBreakpointName);
                }

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
     * Search on breakpoints dir the current breakpoint page file
     *
     * @param int $idPage
     * @return string
     * @throws FilesystemException
     */
    private function getCurrentBreakpointFilename(int $idPage): ?string
    {
        $start = microtime(true);
        $breakpointFiles = $this->awsS3Adapter->listContents(PageBreakpoint::BREAKPOINT_DIR)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn(StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        if (is_array($breakpointFiles) && count($breakpointFiles)) {
            foreach ($breakpointFiles as $breakpointFile) {
                //TODO MEJORAR
                $filenameCleaned = str_replace('css/breakpoints/', '', $breakpointFile);
                $pageIdExploded = explode('-', $filenameCleaned);
                if (is_array($pageIdExploded) && isset($pageIdExploded[0]) && (int)$pageIdExploded[0] === $idPage) {
                    return $filenameCleaned;
                }
            }
        }
        $end = (microtime(true) - $start);
        $this->logService->info("getCurrentBreakpointFilename: $end");

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
            $fullPath = PageBreakpoint::BREAKPOINT_FILE_NAME . DIRECTORY_SEPARATOR . $currentFileName;
            if ($this->awsS3Adapter->fileExists($fullPath)) {
                $this->awsS3Adapter->delete($fullPath);
            }
        } catch (Exception $e) {
            $this->logService->error($e->getMessage());
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
            $start = microtime(true);
            $template = $this->templateRenderer->render(
                PageBreakpoint::BREAKPOINT_FILE_NAME . $this->extension, [
                    'data' => $pageBreakpoint->getData()
                ]
            );

            $this->awsS3Adapter->write(PageBreakpoint::BREAKPOINT_DIR . DIRECTORY_SEPARATOR . $pageBreakpoint->getFileName(), CssMin::minify($template), [
                'Metadata' => [
                    'page' => $idPage
                ]
            ]);

            $end = (microtime(true) - $start);
            $this->logService->info("createBreakpointFile: $end");
        } catch (Exception $exc) {
            $this->logService->error($exc->getMessage());
        }
    }
}
