<?php

namespace App\Domain\Service\Factory;

use App\Domain\Exception\Api\ApiBaseUrlNotSetException;
use App\Domain\Exception\Api\ClientNumberNotSetException;
use App\Domain\Service\ApiService;
use Aws\S3\S3Client;
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

        $cacheEnable = $config['guzzle_cache']['enable'] ?? false;
        $this->setGuzzleCache($cacheEnable, $columnisConfig, $guzzleOptions);
        return new ApiService(new GuzzleClient($guzzleOptions), $apiConfig['client_number']);
    }

    private function setGuzzleCache(bool $cacheEnable, $columnisConfig, array &$guzzleOptions): void
    {
        $columnisS3Config = $columnisConfig['s3_config'];
        $key = $columnisS3Config['credentials']['key'] ?? null;
        $secret = $columnisS3Config['credentials']['secret'] ?? null;
        $bucket = $columnisS3Config['bucket'] ?? null;

        if ($cacheEnable && $key !== null && $secret !== null && $bucket !== null) {
            // Create default HandlerStack
            $stack = HandlerStack::create();
            // Add this middleware to the top with `push`

            $client = new S3Client([
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret
                ],
                'region'      => 'us-east-1',
                'version'     => 'latest',
            ]);

            $adapter = new AwsS3V3Adapter($client, $bucket, $columnisS3Config['cache_folder']);
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
