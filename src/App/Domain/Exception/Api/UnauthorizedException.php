<?php

/**
 * Description of UnauthorizedException
 *
 * @author matias
 */

namespace App\Domain\Exception\Api;

use App\Domain\Exception\Exception;

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
     * @param string|long $code
     * @param \Exception $previous
     */
    public function __construct (string $message, $code, \Exception $previous)
    {
        $this->message = $message;
        $this->code = $code;
        $this->previous = $previous;
    }

}
