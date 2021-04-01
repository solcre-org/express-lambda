<?php

namespace SolcreExpressLambda\Service;

use SolcreExpressLambda\Entity\ApiResponse;
use SolcreExpressLambda\Entity\Page;
use SolcreExpressLambda\Entity\Template;
use SolcreExpressLambda\Exception\Api\ApiRequestException;
use PHPUnit\Util\Exception;

class PageService
{
    private const GENERATE_PAGE_ENDPOINT = '/pages/:pageId/generate';

    private ApiService $apiService;
    private LogService $logService;

    public function __construct(ApiService $apiService, LogService $logService)
    {
        $this->apiService = $apiService;
        $this->logService = $logService;
    }

    public function fetchPageData(int $pageId, array $params): array
    {
        try {
            $start = microtime(true);

            $endpoint = str_replace(':pageId', $pageId, self::GENERATE_PAGE_ENDPOINT);
            $uri = $this->apiService->getUri($endpoint);
            $lang = $this->apiService->parseLang($params['lang']);
            $headers = [];

            $accessToken = $params['accessToken'];
            if (! empty($accessToken)) {
                $headers['Authorization'] = sprintf('Bearer %s', $accessToken);
                unset($accessToken);
            }

            if (! empty($lang)) {
                $headers['Accept-Language'] = $lang;
            }
            $options = $this->apiService->buildOptions([], $params, $headers);
            $response = $this->apiService->request($uri, 'GET', $options);
            if (! $response instanceof ApiResponse) {
                throw new  ApiRequestException('Error al obtener la página con id: ' . $pageId);
            }


            $end = (microtime(true) - $start);
            $this->logService->info("fetchPageData: $end");
            return $response->getData();
        } catch (Exception $exception) {
            $this->logService->error($exception->getMessage());
            unset($exception);
        }

        return [];
    }

    public function createPage(int $pageId, array $data, Template $template): Page
    {
        $page = new Page();
        $page->setId($pageId);
        $page->setData($data);
        $page->setTemplate($template);

        return $page;
    }
}
