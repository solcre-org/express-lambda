<?php

namespace SolcreExpressLambda\Service\Factory;

use SolcreExpressLambda\Service\ApiService;
use SolcreExpressLambda\Service\LogService;
use SolcreExpressLambda\Service\PageService;
use Psr\Container\ContainerInterface;

class PageServiceFactory
{
    public function __invoke(ContainerInterface $container): PageService
    {
        $apiService = $container->get(ApiService::class);
        $logService = $container->get(LogService::class);

        return new PageService($apiService, $logService);
    }
}
