<?php
/**
 * @Name 当前模块服务基类
 * @Description
 */

namespace Modules\Admin\Services;


use Carbon\Carbon;
use Modules\Admin\Models\User;
use Modules\Common\Services\BaseService;

class BaseApiService extends BaseService
{
    protected function getUserInviteCode()
    {
        // todo 存入redis
        do {
            $invite_code = $this->GetRandStr(8);
            $id = User::query()->where('invite_code', $invite_code)->value('id');
        } while ($id !== null);
        return $invite_code;
    }

    protected function getDateFormat($date)
    {
        return Carbon::parse($date)->toDateTimeString();
    }
}
