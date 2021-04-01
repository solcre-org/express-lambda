<?php

namespace SolcreExpressLambda\Service;

use JsonException;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;
use function is_array;
use const DIRECTORY_SEPARATOR;

class PageAssetService
{
    private Filesystem $awsS3Adapter;
    private const ASSETS_TYPES = ['css', 'js'];
    private const FIXED_FOLDER = 'fixed';
    protected array $config;

    public function __construct(array $config, Filesystem $awsS3Adapter)
    {
        $this->awsS3Adapter = $awsS3Adapter;
        $this->config = $config;
    }

    public function getAssets(string $path): array
    {
        //TODO VER OPTIMIZACION
        $assets = [];
        $excludes = $this->getExcludes();
        foreach (self::ASSETS_TYPES as $type) {
            $assets[$type] = $this->getDefinedAssets($type, $path);
        }
        return $assets;
    }

    public function getExcludes(): array
    {
        return [];
    }

    /**
     * Returns an array with the defined assets given an extension
     *
     * @param string $extension
     *
     * @param string $path
     * @return array
     * @throws JsonException
     */
    public function getDefinedAssets(string $extension, string $path): array
    {
        $assets = [];
        $data = $this->getParsedDefinition($path);
        if (is_array($data[$extension])) {
            $assets = $data[$extension];
        }

        return $assets;
    }

    /**
     * Returns the definition of the template. If it is not parsed yet, it will call parseDefinition to parse it.
     *
     * @param string $path
     *
     * @return array
     * @throws JsonException
     */
    public function getParsedDefinition(string $path): array
    {
        $definitionFile = TemplateService::getDefinitionFile($path);
        if (file_exists($definitionFile)) {
            return json_decode(file_get_contents($definitionFile), true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    public function getFixedAssets(): array
    {
        //TODO VER EXCLUDES
        $excludes = $this->getExcludes();
        $searchedAssets = [];

        foreach (self::ASSETS_TYPES as $type) {
            $searchedAssets[$type] = $this->awsS3Adapter->listContents($type . DIRECTORY_SEPARATOR . self::FIXED_FOLDER)
                ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
                ->map(fn(StorageAttributes $attributes) => $attributes->path())
                ->toArray();
        }
        return $searchedAssets;
    }
}
