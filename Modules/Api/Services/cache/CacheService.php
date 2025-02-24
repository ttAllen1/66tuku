<?php

namespace Modules\Api\Services\cache;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Intervention\Image\Exception\ImageException;
use Intervention\Image\Facades\Image;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Models\IndexPic;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\Vote;
use Modules\Api\Models\YearPic;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\collect\CollectService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\picture\PictureService;
use Modules\Api\Services\vote\VoteService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class CacheService extends BaseApiService
{
    private $_lotteryTypes = [1, 2, 3, 4,5];
    public function index_pic(): int
    {
        foreach($this->_lotteryTypes as $lotteryType) {
            $cache_name = 'index_pic_'.$lotteryType;
            for ($i=1; $i<=10; $i++) {
                $list = (new PictureService())->get_page_list(['page'=>$i, 'lotteryType'=>$lotteryType]);
                $res = Redis::zadd($cache_name, $i, json_encode($list));
            }
        }
        return 1;
    }
}
