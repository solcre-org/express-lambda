<?php

namespace SolcreExpressLambda\Service;

use SolcreExpressLambda\Entity\ApiResponse;
use SolcreExpressLambda\Exception\Api\ApiRequestException;
use SolcreExpressLambda\Exception\Api\UnauthorizedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface;
use function array_key_exists;
use function is_array;

class ApiService
{
    protected GuzzleClient $httpClient;
    protected string $clientNumber;
    protected LogService $logService;

    public function __construct(GuzzleClient $httpClient, string $clientNumber, LogService $logService)
    {
        $this->httpClient = $httpClient;
        $this->clientNumber = $clientNumber;
        $this->logService = $logService;
    }

    /**
     * Performs a request to Columnis api
     *
     * @param string $uri
     * @param string $method
     * @param array|null $options
     *
     * @return ApiResponse|null
     * @throws ApiRequestException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function request(string $uri, $method = 'GET', array $options = null): ?ApiResponse
    {
        $response = null;
        $apiResponse = null;
        $mergedOptions = $this->mergeWithDefault($options);
        try {
            if ($method === 'GET') {
                $response = $this->httpClient->get($uri, $mergedOptions);
            }
            if ($response instanceof ResponseInterface) {
                $apiResponse = new ApiResponse($response);
            }

        } catch (GuzzleRequestException $e) {
            $this->logService->error($e->getMessage());
            throw $this->createException($e);
        }
        return $apiResponse;
    }

    protected function mergeWithDefault(array $options = null): array
    {
        $defaults = [
            'headers' => [
                'Accept'       => 'application/vnd.columnis.v2+json',
                'Content-Type' => 'application/json'
            ],
            'debug'   => false
        ];
        return array_replace_recursive($defaults, (is_array($options) ? $options : []));
    }

    protected function createException(GuzzleRequestException $e)
    {
        $statusCode = $e->getResponse() instanceof ResponseInterface ? $e->getResponse()->getStatusCode() : 400;

        if ($statusCode === 401) {
            $authInfo = $this->parseAuthHeader($e->getResponse()->getHeader('www-authenticate'));
            $code = array_key_exists('error', $authInfo) ? $authInfo['error'] : 401;
            $message = array_key_exists('error_description', $authInfo) ? $authInfo['error_description'] : $e->getMessage();
            return new UnauthorizedException($message, $code, $e);
        }

        return new ApiRequestException('Api Request failed: ' . $e->getMessage(), 0, $e);
    }

    protected function parseAuthHeader($header): array
    {
        $matches = [];
        $pattern = '/(?:Bearer |, )(\w+)="((?:[^\\"]+|\\.)*)"/';
        preg_match_all($pattern, $header, $matches);
        return array_combine($matches[1], $matches[2]);
    }


    public function buildOptions(array $params = null, array $queryString = null, array $headers = null): array
    {
        return [
            'headers'     => $headers,
            'query'       => $queryString,
            'form_params' => $params
        ];
    }

    /**
     * Gets the Uri for the desire enpoint
     *
     * @param string $endpoint
     *
     * @return string
     */
    public function getUri(string $endpoint): string
    {
        return $this->getClientNumber() . '/columnis' . $endpoint;
    }

    /**
     * Returns the Client Number of Columnis Api
     *
     * @return string
     */
    public function getClientNumber(): string
    {
        return $this->clientNumber;
    }

    public function parseLang($fullLang): string
    {
        $lang = 'es';

        switch ($fullLang) {
            case 'english':
                $lang = 'en';
                break;
            case 'portugues':
                $lang = 'pt';
                break;
        }

        return $lang;
    }
}
