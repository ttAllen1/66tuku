<?php
/**
 * @Name 会员站内信服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Modules\Admin\Models\UserMessage;
use Modules\Admin\Services\BaseApiService;

class UserMessageService extends BaseApiService
{

    public function insertToUserMsg($data, $msg_id)
    {
        $userData = [];
        foreach ($data as $k => $v) {
            $userData[$k]['user_id'] = $v;
            $userData[$k]['msg_id'] = $msg_id;
        }
        UserMessage::query()->insert($userData);
    }

    public function deleteToUserMsg($msg_id)
    {
        UserMessage::query()->whereIn('msg_id', $msg_id)->delete();
    }
}
