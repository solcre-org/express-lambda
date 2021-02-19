<?php

namespace App\Domain\Service;

use App\Domain\Entity\ApiResponse;
use App\Domain\Entity\Page;
use App\Domain\Entity\Template;
use App\Domain\Exception\Api\ApiRequestException;

class PageService
{
    private const GENERATE_PAGE_ENDPOINT = '/pages/:pageId/generate';

    private ApiService $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function fetchPageData(int $pageId, array $params): array
    {
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

        return $response->getData();
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
