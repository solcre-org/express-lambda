<?php

declare(strict_types = 1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * FastRoute route configuration
 *
 * @see https://github.com/nikic/FastRoute
 *
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/{id:\d+}', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */
return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    
    $app->get('/', \App\Handler\PageHandler::class, 'home');
    $app->get('/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', \App\Handler\PageHandler::class, 'get-page');
    $app->get('/{lang: espanol}/', \App\Handler\PageHandler::class, 'get-root-spanish-page');
    $app->get('/{lang: english}/', \App\Handler\PageHandler::class, 'get-root-english-page');
    $app->get('{lang: portugues}/', \App\Handler\PageHandler::class, 'get-root-portugues-page');
    $app->get('/{lang: espanol}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', \App\Handler\PageHandler::class, 'get-spanish-page');
    $app->get('/{lang: english}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', \App\Handler\PageHandler::class, 'get-english-page');
    $app->get('/{lang: portugues}/{seo:[a-zA-Z0-9-_]+}-{pageId: \d+}', \App\Handler\PageHandler::class, 'get-portugues-page');
};
