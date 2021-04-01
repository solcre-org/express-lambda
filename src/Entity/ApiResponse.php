<?php

namespace SolcreExpressLambda\Entity;

use Exception;
use Psr\Http\Message\ResponseInterface;

class ApiResponse
{

    /**
     * The response from Guzzle
     *
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * Construct ApiResponse with GuzzleResponse
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getData(): array
    {
        $data = [];
        try {
            $data = json_decode($this->response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {

        }

        return $data;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }
}
