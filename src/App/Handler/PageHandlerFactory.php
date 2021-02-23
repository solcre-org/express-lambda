<?php

declare(strict_types = 1);

namespace App\Handler;

use App\Domain\Service\LogService;
use App\Domain\Service\PageBreakpointService;
use App\Domain\Service\PageService;
use App\Domain\Service\TemplateService;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PageHandlerFactory
{
    public function __invoke(ContainerInterface $container): PageHandler
    {
        $pageService = $container->get(PageService::class);
        $templateRenderer = $container->get(TemplateRendererInterface::class);
        $templateService = $container->get(TemplateService::class);
        $pageBreakpointService = $container->get(PageBreakpointService::class);
        $logService = $container->get(LogService::class);

        return new PageHandler($pageService, $templateRenderer, $templateService, $pageBreakpointService, $logService);
    }
}
