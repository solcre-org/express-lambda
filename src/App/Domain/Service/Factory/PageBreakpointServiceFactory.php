<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\LogService;
use App\Domain\Service\PageBreakpointService;
use App\Domain\Service\S3Service;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class PageBreakpointServiceFactory
{

    public function __invoke(ContainerInterface $container): PageBreakpointService
    {
        $templatesPathStack = [];
        $assetsManagerPaths = [];

        $config = $container->get('config');

        $templatesPathStack[] = $config['templates']['paths'];
        $extension = $config['templates']['extension'];

        if (isset($config['asset_manager']['resolver_configs']['paths'])) {
            $assetsManagerPaths = $config['asset_manager']['resolver_configs']['paths'];
        }
        $templateRenderer = $container->get(TemplateRendererInterface::class);

        $s3Service = $container->get(S3Service::class);
        $adapter = new AwsS3V3Adapter($s3Service->getS3Client(), $s3Service->getBucket(), 'assets');
        $filesystem = new Filesystem($adapter);
        
        $logService = $container->get(LogService::class);

        return new PageBreakpointService(
            $templateRenderer,
            $templatesPathStack,
            $assetsManagerPaths,
            $filesystem,
            $extension,
            $logService
        );
    }
}
