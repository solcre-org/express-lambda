<?php

namespace SolcreExpressLambda\Service;


use SolcreExpressLambda\Entity\Template;
use SolcreExpressLambda\Exception\Templates\TemplateNameNotSetException;
use Exception;
use function file_exists;

class TemplateService
{
    public const DEFINITION_FILE = 'def.json';
    public const MAIN_FILE = 'main';
    private static string $templateInfoFile = 'def.json';
    private static string $commonInfoFile = 'common.json';
    private array $templatesPathStack;
    private PageAssetService $pageAssetService;
    private string $extension;

    /**
     * TemplateService constructor.
     *
     * @param array $templatesPathStack
     * @param PageAssetService $pageAssetService
     * @param string $extension
     */
    public function __construct(
        array $templatesPathStack,
        PageAssetService $pageAssetService,
        string $extension
    ) {
        $this->templatesPathStack = $templatesPathStack;
        $this->pageAssetService = $pageAssetService;
        $this->extension = $extension;
    }

    public function getTemplates(): array
    {
        $templatesPath = $this->getTemplatesPath();
        $templateData = [
            'templates' => [],
            'common'    => []
        ];
        if (is_dir($templatesPath)) {
            $dirContent = scandir($templatesPath, SCANDIR_SORT_NONE);
            foreach ($dirContent as $templateDir) {
                $contentFile = $templatesPath . '/' . $templateDir;
                if ($this->validTemplate($contentFile)) {
                    $templateInfo = json_decode(file_get_contents($contentFile . '/' . self::$templateInfoFile), true, 512, JSON_THROW_ON_ERROR);
                    $templateInfo->id = $templateDir;
                    $templateInfo->name = ucfirst(str_replace('_', ' ', $templateDir));
                    $templateData['templates'][] = $templateInfo;
                }
            }
            if ($this->validCommonTemplate($templatesPath)) {
                $templateData['common'] = json_decode(file_get_contents($templatesPath . '/' . self::$commonInfoFile), true, 512, JSON_THROW_ON_ERROR);
            }
        }
        return $templateData;
    }

    private function getTemplatesPath(): string
    {
        return $this->templatesPathStack[0];
    }

    private function validTemplate(string $completeName): bool
    {
        $def = $completeName . DIRECTORY_SEPARATOR . self::$templateInfoFile;
        return is_dir($completeName) && file_exists($def);
    }

    private function validCommonTemplate(string $completeName): bool
    {
        $def = $completeName . DIRECTORY_SEPARATOR . self::$commonInfoFile;
        return is_dir($completeName) && file_exists($def);
    }

    /**
     * Creats a Template instance from an array with page Data.
     *
     * @param array $data
     *
     * @return Template
     * @throws Exception
     *
     */
    public function createFromData(array $data): Template
    {
        if (isset($data['template']) && ! empty($data['template'])) {
            $templateName = $data['template'];
        } else {
            throw new TemplateNameNotSetException('Template not set in page response.');
        }

        if (isset($data['template_path']) && ! empty($data['template_path'])) {
            $path = $data['template_path'];
        } else {
            $path = $this->getExistantTemplatePath($templateName);
            if ($path === null) {
                throw new TemplateNameNotSetException('Template not set in page response.');
            }
        }

        $template = new Template();
        $template->setName($templateName);
        $template->setPath($path);
        return $template;
    }

    /**
     * Return the FIRST paths that contain a template with the specified name
     * (There should not be more than one possible template path)
     *
     * @param string $templateName
     *
     * @return string
     */
    public function getExistantTemplatePath(string $templateName): ?string
    {
        $paths = $this->getTemplatesPathStack();
        foreach ($paths as $path) {
            $templatePath = $path . DIRECTORY_SEPARATOR . $templateName;
            if ($this->validTemplate($templatePath)) {
                return $templatePath;
            }
        }
        return null;
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
     * Returns the defined assets + assets in the template folder
     *
     * @param Template $template
     *
     * @return array
     */
    public function getAssets(Template $template): array
    {
        $templateAssets = $this->pageAssetService->getAssets($template->getPath());
        $fixedAssets = $this->pageAssetService->getFixedAssets();

        //Merge fixed with templates
        $cssAssets = array_merge($fixedAssets['css'] ?? [], $templateAssets['css'] ?? []);
        $jsAssets = array_merge($fixedAssets['js'] ?? [], $templateAssets['js'] ?? []);

        return [
            'css' => $cssAssets,
            'js'  => $jsAssets,
        ];
    }

    /**
     * Returns the path to the definition file
     *
     * @param string $path
     *
     * @return string
     */
    public static function getDefinitionFile(string $path): string
    {
        return $path . DIRECTORY_SEPARATOR . self::DEFINITION_FILE;
    }

    /**
     * Returns the path to the main file
     *
     * @param Template $template
     * @param boolean $withPath if false, will return just the main file
     *
     * @return string
     */
    public function getMainFile(Template $template, $withPath = true): string
    {
        return ($withPath ? $template->getPath() : $template->getName()) . DIRECTORY_SEPARATOR . self::MAIN_FILE;
    }

    public function getTemplateMainName(string $name): string
    {
        return $name . DIRECTORY_SEPARATOR . self::MAIN_FILE . $this->extension;
    }
}
