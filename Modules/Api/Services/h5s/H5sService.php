<?php

namespace Modules\Api\Services\h5s;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\User;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Api\Services\user\UserService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class H5sService  extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 随机前端地址
     * @param $params
     * @return JsonResponse
     */
    public function index($params): JsonResponse
    {
        try{
            $device = $params['device'] ?? '';
            $system = $params['system'] ?? '';
            $urls = AuthActivityConfig::query()
                ->where('k', 'h5_urls')
                ->value('v');
            $urls = json_decode($urls, true);
            if ($urls && is_array($urls)) {
                $index = array_rand($urls);
                return $this->apiSuccess('', [
                    'url'       => $urls[$index].'?device='.$device.'&system='.$system.'&download=false',
                    'device'    => $device,
                    'system'    => $system,
                    'download'  => false,
                ]);
            }
        }catch (\Exception $exception) {
            return $this->apiSuccess('', [
                'url'       => '',
                'device'    => $device,
                'system'    => $system,
                'download'  => false,
            ]);
        }
        return $this->apiSuccess('', [
            'url'       => '',
            'device'    => $device,
            'system'    => $system,
            'download'  => false,
        ]);
    }

}
