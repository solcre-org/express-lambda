<?php

/**
 * Description of PageWithoutTemplateException
 *
 * @author matias
 */

namespace SolcreExpressLambda\Exception\Page;

use SolcreExpressLambda\Exception\Exception;
use RuntimeException;

class PageWithoutTemplateException extends RuntimeException implements Exception
{
}
