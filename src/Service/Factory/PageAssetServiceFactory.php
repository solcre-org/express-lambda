<?php

namespace SolcreExpressLambda\Service\Factory;

use SolcreExpressLambda\Service\PageAssetService;
use SolcreExpressLambda\Service\S3Service;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;

class PageAssetServiceFactory
{

    public function __invoke(ContainerInterface $container): PageAssetService
    {
        $config = $container->get('config');
        $s3Service = $container->get(S3Service::class);
        $adapter = new AwsS3V3Adapter($s3Service->getS3Client(), $s3Service->getBucket(), 'assets');
        $filesystem = new Filesystem($adapter);

        return new PageAssetService($config, $filesystem);
    }
}
