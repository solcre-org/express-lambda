<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\LogService;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class LogServiceFactory
{
    public function __invoke(ContainerInterface $container): LogService
    {
        $config = $container->get('config');

        $key = $config['columnis']['aws_credentials']['key'] ?? null;
        $secret = $config['columnis']['aws_credentials']['secret'] ?? null;

        $sdkParams = [
            'region'      => 'us-east-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ]
        ];

        $client = new CloudWatchLogsClient($sdkParams);
        $groupName = '/aws/lambda/express-dev-app';
        $streamName = 'express-lambda-log';
        $handler = new CloudWatch($client, $groupName, $streamName, 14, 1);
        $handler->setFormatter(new JsonFormatter());
        $log = new Logger('express-log');
        $log->pushHandler($handler);

        return new LogService($log);
    }
}
