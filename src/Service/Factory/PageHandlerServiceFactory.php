<?php

namespace SolcreExpressLambda\Service\Factory;

use SolcreExpressLambda\Service\LogService;
use SolcreExpressLambda\Service\PageBreakpointService;
use SolcreExpressLambda\Service\PageHandlerService;
use SolcreExpressLambda\Service\PageService;
use SolcreExpressLambda\Service\TemplateService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PageHandlerServiceFactory
{
    public function __invoke(ContainerInterface $container): PageHandlerService
    {
        $pageService = $container->get(PageService::class);
        $templateRenderer = $container->get(TemplateRendererInterface::class);
        $templateService = $container->get(TemplateService::class);
        $pageBreakpointService = $container->get(PageBreakpointService::class);
        $logService = $container->get(LogService::class);

        return new PageHandlerService($pageService, $templateRenderer, $templateService, $pageBreakpointService, $logService);
    }
}
