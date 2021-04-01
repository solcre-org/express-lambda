<?php

declare(strict_types = 1);

namespace SolcreExpressLambda;

use SolcreExpressLambda\Service;
use SolcreExpressLambda\Service\Factory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                Service\TemplateService::class       => Factory\TemplateServiceFactory::class,
                Service\PageService::class           => Factory\PageServiceFactory::class,
                Service\ApiService::class            => Factory\ApiServiceFactory::class,
                Service\PageAssetService::class      => Factory\PageAssetServiceFactory::class,
                Service\PageBreakpointService::class => Factory\PageBreakpointServiceFactory::class,
                Service\S3Service::class             => Factory\S3ServiceFactory::class,
                Service\LogService::class            => Factory\LogServiceFactory::class,
                Service\PageHandlerService::class    => Factory\PageHandlerServiceFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'templates' => ['templates/'],
            ],
        ];
    }
}
