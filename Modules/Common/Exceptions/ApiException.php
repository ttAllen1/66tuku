<?php
/**
 * @Name
 * @Description
 */

namespace Modules\Common\Exceptions;

use Throwable;

class ApiException extends \Exception
{
    public function __construct(array $apiErrConst, Throwable $previous = null)
    {
        parent::__construct($apiErrConst['message'],$apiErrConst['status'], $previous);
    }
}
