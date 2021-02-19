<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\S3Service;
use Aws\S3\S3Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class S3ServiceFactory
{
    public function __invoke(ContainerInterface $container): S3Service
    {
        $config = $container->get('config');
        $columnisS3Config = $config['columnis']['s3_config'];

        $key = $columnisS3Config['credentials']['key'] ?? null;
        $secret = $columnisS3Config['credentials']['secret'] ?? null;
        $bucket = $columnisS3Config['bucket'] ?? null;

        if ($key === null || $secret === null || $bucket === null) {
            throw new RuntimeException('There are empty parameters to create S3 Client');
        }

        $client = new S3Client([
            'credentials' => [
                'key'    => $key,
                'secret' => $secret
            ],
            'region'      => 'us-east-1',
            'version'     => 'latest',
        ]);

        return new S3Service($client, $bucket);
    }
}
