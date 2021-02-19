<?php

namespace App\Domain\Service\Factory;

use App\Domain\Service\ApiService;
use App\Domain\Service\PageService;
use Psr\Container\ContainerInterface;

class PageServiceFactory
{
    public function __invoke(ContainerInterface $container): PageService
    {
        $apiService = $container->get(ApiService::class);

        return new PageService($apiService);
    }
}
