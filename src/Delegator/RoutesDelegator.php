<?php

namespace SolcreExpressLambda\Delegator;

use App\Handler\HomePageHandler;
use Mezzio\Application;
use Psr\Container\ContainerInterface;

class RoutesDelegator
{
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $callback
    ): Application {
        /** @var $app Application */
        $app = $callback();

        // Setup routes:
        $app->get('/', HomePageHandler::class, 'home');
        $app->get('/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', HomePageHandler::class, 'get-page');
        $app->get('/{lang: espanol}/', HomePageHandler::class, 'get-root-spanish-page');
        $app->get('/{lang: english}/', HomePageHandler::class, 'get-root-english-page');
        $app->get('{lang: portugues}/', HomePageHandler::class, 'get-root-portugues-page');
        $app->get('/{lang: espanol}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', HomePageHandler::class, 'get-spanish-page');
        $app->get('/{lang: english}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', HomePageHandler::class, 'get-english-page');
        $app->get('/{lang: portugues}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', HomePageHandler::class, 'get-portugues-page');

        return $app;
    }
}
