<?php
/**
 * @Name
 * @Description
 */

namespace Modules\Common\Exceptions;

use Throwable;

class CustomException extends \Exception
{
    public function __construct(array $apiErrConst, Throwable $previous = null)
    {
        if (!empty($apiErrConst['status'])) {
            parent::__construct($apiErrConst['message'],$apiErrConst['status'], $previous);
        } else {
            parent::__construct($apiErrConst['message'],StatusData::BAD_REQUEST, $previous);
        }

    }
}
