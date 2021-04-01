<?php

namespace SolcreExpressLambda\Service\Factory;

use SolcreExpressLambda\Service\LogService;
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
            'credentials' => compact('key', 'secret')
        ];

        $client = new CloudWatchLogsClient($sdkParams);
        $groupName = 'express-app-logger';
        $streamName = 'express-lambda-log';
        $handler = new CloudWatch($client, $groupName, $streamName, 7, 1);
        $handler->setFormatter(new JsonFormatter());
        $log = new Logger('express-log');
        $log->pushHandler($handler);

        return new LogService($log);
    }
}
