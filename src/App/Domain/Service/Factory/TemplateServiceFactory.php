<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\PageAssetService;
use App\Domain\Service\TemplateService;
use Psr\Container\ContainerInterface;

class TemplateServiceFactory
{
    public function __invoke(ContainerInterface $container): TemplateService
    {
        $templates = $container->get('config')['templates']['paths']['templates'];
        $pageAssetService = $container->get(PageAssetService::class);
        $extension = $container->get('config')['templates']['extension'];

        return new TemplateService($templates, $pageAssetService, $extension);
    }
}
