<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\PageBreakpointService;
use BsbFlysystem\Service\AdapterManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PageBreakpointServiceFactory
{

    public function __invoke(ContainerInterface $container): PageBreakpointService
    {
        $templatesPathStack = [];
        $assetsManagerPaths = [];

        $config = $container->get('config');

        if (isset($config['template_path_stack'])) {
            $templatesPathStack = $config['template_path_stack'];
        }

        if (isset($config['asset_manager']['resolver_configs']['paths'])) {
            $assetsManagerPaths = $config['asset_manager']['resolver_configs']['paths'];
        }
        $templateRenderer = $container->get(TemplateRendererInterface::class);
        
        return new PageBreakpointService($templateRenderer, $templatesPathStack, $assetsManagerPaths);
    }
}
