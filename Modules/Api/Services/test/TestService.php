<?php

namespace Modules\Api\Services\test;

use Modules\Api\Services\BaseApiService;

class TestService extends BaseApiService
{
    /**
     * @param $lotteryType
     * @param $issue
     * @param $numbersInfo
     * @return bool
     */
    public function bet($lotteryType, $issue, $numbersInfo): bool
    {
        return $this->getBets($lotteryType, $issue, $numbersInfo);
    }


}
