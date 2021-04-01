<?php

namespace SolcreExpressLambda\Service;

use SolcreExpressLambda\Entity\Page;
use SolcreExpressLambda\Entity\Template;
use SolcreExpressLambda\Exception\Page\PageWithoutTemplateException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Util\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PageHandlerService
{
    private PageService $pageService;
    private TemplateRendererInterface $templateRenderer;
    private TemplateService $templateService;
    private PageBreakpointService $pageBreakpointService;
    private LogService $logService;

    private const COLUMNIS_PAGE_ENDPOINT_KEY = 'columnis.rest.pages';
    private const COLUMNIS_CONFIGURATION_ENDPOINT_KEY = 'columnis.rest.configuration';
    private const COLUMNIS_BREAKPOINTS_HASH_ENDPOINT_KEY = 'breakpoints_hash';
    private const COLUMNIS_PICTURES_ENDPOINT_KEY = 'collected_pictures';
    private const COLUMNIS_IMAGE_SIZE_GROUPS_ENDPOINT_KEY = 'columnis.rest.image_sizes_groups';

    /**
     * PagesAction constructor.
     *
     * @param PageService $pageService
     * @param TemplateRendererInterface $templateRenderer
     * @param TemplateService $templateService
     * @param PageBreakpointService $pageBreakpointService
     * @param LogService $logService
     */
    public function __construct(
        PageService $pageService,
        TemplateRendererInterface $templateRenderer,
        TemplateService $templateService,
        PageBreakpointService $pageBreakpointService,
        LogService $logService
    ) {
        $this->pageService = $pageService;
        $this->templateRenderer = $templateRenderer;
        $this->templateService = $templateService;
        $this->pageBreakpointService = $pageBreakpointService;
        $this->logService = $logService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $pageId = (int)$request->getAttribute('pageId');
            $lang = $request->getAttribute('lang');
            $queryParams = $request->getQueryParams();
            $queryParams['lang'] = $lang;
            $cookies = $request->getCookieParams();
            $accessToken = null;
            if (! empty($cookies['columnis_token'])) {
                $accessToken = $cookies['columnis_token'];
            }
            $queryParams['accessToken'] = $accessToken;

            $generatePageData = $this->fetchPageData($pageId, $queryParams);

            if (! empty($generatePageData)) {
                $debug = ! empty($queryParams['debug']) ? (bool)$queryParams['debug'] : false;
                if ($debug) {
                    return new JsonResponse($generatePageData);
                }

                $pageData = array_values($generatePageData[self::COLUMNIS_PAGE_ENDPOINT_KEY])[0];

                try {
                    $template = $this->templateService->createFromData($pageData);
                } catch (\Exception $e) {
                    throw $e;
                }

                $page = $this->createPage($pageId, $pageData, $template);

                $generatePageData['page']['breakpoint_file'] = $this->getBreakpoints($generatePageData);

                $start = microtime(true);
                $this->setPageAssets($page, $generatePageData);
                $end = (microtime(true) - $start);
                $this->logService->info("setPageAssets: $end");
                $templateFilename = $this->getTemplateName($pageData['template']);
                $templatePath = 'file:' . $templateFilename;
                $template = $this->templateRenderer->render($templatePath, $generatePageData);
                return new HtmlResponse($template);
            }
        } catch (Exception $exception) {
            $this->logService->error($exception->getMessage());
            unset($exception);
        }


        return new JsonResponse([], 404);
    }

    public function fetchPageData(int $pageId, array $params = null): array
    {
        try {
            return $this->pageService->fetchPageData($pageId, $params);
        } catch (PageWithoutTemplateException $e) {
            throw $e;
        }
    }

    private function setPageAssets(Page $page, array &$viewVariables): void
    {
        $pageAssets = $this->templateService->getAssets($page->getTemplate());

        if (\is_array($viewVariables) && $viewVariables['page']) {
            $viewVariables['page']['scripts'] = $pageAssets['js'];
            $viewVariables['page']['stylesheets'] = $pageAssets['css'];
        }
    }

    private function getTemplateName(string $name): string
    {
        return $this->templateService->getTemplateMainName($name);
    }

    private function createPage(int $pageId, array $data, Template $template): Page
    {
        return $this->pageService->createPage($pageId, $data, $template);
    }

    private function getBreakpoints(array $pageData): string
    {
        $start = microtime(true);

        $page = array_values($pageData[self::COLUMNIS_PAGE_ENDPOINT_KEY])[0];

        $breakpoints = '';
        if (\is_array($page) && \array_key_exists('id', $page)) {
            $breakpoints = $this->pageBreakpointService->createPageBreakpoint(
                $page['id'],
                $pageData[self::COLUMNIS_CONFIGURATION_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_BREAKPOINTS_HASH_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_PICTURES_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_IMAGE_SIZE_GROUPS_ENDPOINT_KEY]
            );
        }

        $end = (microtime(true) - $start);
        $this->logService->info("getBreakpoints: $end");
        return $breakpoints;
    }
}
