<?php

namespace App\Domain\Service\Factory;

use App\Domain\Exception\Api\ApiBaseUrlNotSetException;
use App\Domain\Exception\Api\ClientNumberNotSetException;
use App\Domain\Service\ApiService;
use App\Domain\Service\LogService;
use App\Domain\Service\S3Service;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Psr\Container\ContainerInterface;

class ApiServiceFactory
{
    public function __invoke(ContainerInterface $container): ApiService
    {
        $config = $container->get('config');
        $columnisConfig = $config['columnis'] ?? [];
        $apiConfig = $columnisConfig['api_settings'] ?? [];

        if (! isset($apiConfig['client_number'])) {
            throw new ClientNumberNotSetException('There is no client_number set in local.php config file.');
        }
        if (! isset($apiConfig['api_base_url'])) {
            throw new ApiBaseUrlNotSetException('There is no api_base_url set in local.php config file.');
        }

        // Guzzle Configuration
        $apiUrl = $apiConfig['api_base_url'];
        $guzzleOptions = [
            'base_uri' => $apiUrl
        ];

        $this->setGuzzleCache($config['guzzle_cache'], $container->get(S3Service::class), $guzzleOptions);

        $logService = $container->get(LogService::class);

        return new ApiService(
            new GuzzleClient($guzzleOptions),
            $apiConfig['client_number'],
            $logService
        );
    }

    private function setGuzzleCache(array $guzzleConfig, S3Service $s3Service, array &$guzzleOptions): void
    {
        if ($guzzleConfig['enable']) {
            $stack = HandlerStack::create();

            $adapter = new AwsS3V3Adapter($s3Service->getS3Client(), $s3Service->getBucket(), $guzzleConfig['cache_dir']);
            $stack->push(
                new CacheMiddleware(
                    new PrivateCacheStrategy(
                        new FlysystemStorage(
                            $adapter
                        )
                    )
                ),
                'cache'
            );
            $guzzleOptions['handler'] = $stack;
        }
    }
}
