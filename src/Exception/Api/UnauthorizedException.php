<?php

/**
 * Description of UnauthorizedException
 *
 * @author matias
 */

namespace SolcreExpressLambda\Exception\Api;

use SolcreExpressLambda\Exception\Exception;

class UnauthorizedException extends \Exception implements Exception
{
    /**
     *
     * @var \Exception
     */
    private \Exception $previous;

    /**
     *
     * @param string $message
     * @param string $code
     * @param \Exception $previous
     */
    public function __construct(string $message, string $code, \Exception $previous)
    {
        parent::__construct();
        $this->message = $message;
        $this->code = $code;
        $this->previous = $previous;
    }

}
