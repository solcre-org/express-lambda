<?php

namespace SolcreExpressLambda\Service\Factory;

use SolcreExpressLambda\Service\PageAssetService;
use SolcreExpressLambda\Service\TemplateService;
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
