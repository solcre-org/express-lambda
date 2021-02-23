<?php

declare(strict_types = 1);

namespace App;

use App\Domain\Service\ApiService;
use App\Domain\Service\Factory\ApiServiceFactory;
use App\Domain\Service\Factory\LogServiceFactory;
use App\Domain\Service\Factory\PageAssetServiceFactory;
use App\Domain\Service\Factory\PageBreakpointServiceFactory;
use App\Domain\Service\Factory\PageServiceFactory;
use App\Domain\Service\Factory\S3ServiceFactory;
use App\Domain\Service\Factory\TemplateAssetsResolverFactory;
use App\Domain\Service\Factory\TemplateServiceFactory;
use App\Domain\Service\LogService;
use App\Domain\Service\PageAssetService;
use App\Domain\Service\PageBreakpointService;
use App\Domain\Service\PageService;
use App\Domain\Service\S3Service;
use App\Domain\Service\TemplateAssetsResolver;
use App\Domain\Service\TemplateService;
use App\Domain\Utils\ArrayExtension;

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
            'invokables' => [
                'ArrayExtension' => ArrayExtension::class
            ],
            'factories'  => [
                TemplateService::class        => TemplateServiceFactory::class,
                PageService::class            => PageServiceFactory::class,
                ApiService::class             => ApiServiceFactory::class,
                PageAssetService::class       => PageAssetServiceFactory::class,
                PageBreakpointService::class  => PageBreakpointServiceFactory::class,
                TemplateAssetsResolver::class => TemplateAssetsResolverFactory::class,
                S3Service::class              => S3ServiceFactory::class,
                LogService::class             => LogServiceFactory::class
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
