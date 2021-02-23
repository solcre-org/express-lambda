<?php

declare(strict_types = 1);

namespace App\Handler;

use App\Domain\Entity\Page;
use App\Domain\Entity\Template;
use App\Domain\Exception\Page\PageWithoutTemplateException;
use App\Domain\Service\LogService;
use App\Domain\Service\PageBreakpointService;
use App\Domain\Service\PageService;
use App\Domain\Service\TemplateService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function array_key_exists;
use function is_array;

class PageHandler implements RequestHandlerInterface
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

//            try {
//                $template = $this->templateService->createFromData($pageData);
//            } catch (Exception $e) {
//                throw $e;
//            }
//
//            $page = $this->createPage($pageId, $pageData, $template);
//            $template = $page->getTemplate();

                $pageData['page']['breakpoint_file'] = $this->getBreakpoints($generatePageData);

//            if ($this->templateService->isValid($page->getTemplate())) {
//                $this->setPageAssets($page, $pageData);
//                $templateFilename = $this->getTemplateName($template);
//                $templatePath = 'templates::' . $templateFilename;
//                $template = $this->templateRenderer->render($templatePath, ['data' => $pageData]);
//                return new HtmlResponse($template);
//            }
            }
        } catch (Exception $exception) {
            $this->logService->error($exception->getMessage());
            unset($exception);
        }


        return new JsonResponse([], 404);
    }

    protected function fetchPageData(int $pageId, array $params = null): array
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

        if (is_array($viewVariables) && $viewVariables['page']) {
            $viewVariables['page']['scripts'] = $this->getPublicRelativePath($pageAssets['js']);
            $viewVariables['page']['stylesheets'] = $this->getPublicRelativePath($pageAssets['css']);
        }
    }

    private function getPublicRelativePath(array $assets = []): array
    {
        return $this->templateService->getPublicRelativePath($assets);
    }


    private function getTemplateName(Template $template): string
    {
        return $template->getName() . DIRECTORY_SEPARATOR . TemplateService::MAIN_FILE;
    }

    private function createPage(int $pageId, array $data, Template $template): Page
    {
        return $this->pageService->createPage($pageId, $data, $template);
    }

    private function getBreakpoints(array $pageData): string
    {
        $page = array_values($pageData[self::COLUMNIS_PAGE_ENDPOINT_KEY])[0];

        $breakpoints = '';
        if (is_array($page) && array_key_exists('id', $page)) {
            $breakpoints = $this->pageBreakpointService->createPageBreakpoint(
                $page['id'],
                $pageData[self::COLUMNIS_CONFIGURATION_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_BREAKPOINTS_HASH_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_PICTURES_ENDPOINT_KEY],
                $pageData[self::COLUMNIS_IMAGE_SIZE_GROUPS_ENDPOINT_KEY]
            );
        }

        return $breakpoints;
    }
}
