<?php

namespace Modules\Api\Services\ad;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Models\Ad;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CodeData;

class AdService extends BaseApiService
{
    /**
     * 获取广告列表
     * @param $params
     * @return JsonResponse
     */
    public function getAdList($params): JsonResponse
    {
        $type = $params['type'];
        $lotteryType = $params['lotteryType'] ?? 0;
        if ($type == 2) {
            $indexAdsRds = Redis::get('cache_ad_list_by_index');
            if ($indexAdsRds) {
                return response()->json(json_decode($indexAdsRds, true),CodeData::OK);
            }
        }
        $res = $this->getAdListByPoi([$type], $lotteryType);
        if ($type == 2) {
            $res_9 = $this->getAdListByPoi([9], $lotteryType);
            $res_9 = $res_9->toArray();
            $res = $res->toArray();
            foreach($res as $k => $v) {
                $res[$k]['ad_image'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['ad_image']);
            }
//            $cipher_method = env('AES_METHOD');
            $cipher_method = config('config.aes_method');
            if (request()->header('Encipher') == 'enable' && !(Str::contains(request()->getHost(), '11.48tkapi.com'))) {
                if ($res) {
                    $res = openssl_encrypt(json_encode($res),$cipher_method,config('config.aes_key'), 0, config('config.aes_iv'));
                }
                if ($res_9) {
                    $res_9 = openssl_encrypt(json_encode($res_9),$cipher_method,config('config.aes_key'), 0, config('config.aes_iv'));
                }
                $arr = [
                    'status'    => 20000,
                    'message'   => '获取成功！',
                    'data'      => $res,
                    'list'      => $res_9
                ];
            } else {
                $arr = [
                    'status'    => 20000,
                    'message'   => '获取成功！',
                    'data'      => $res,
                    'list'      => $res_9
                ];
            }

            Redis::set('cache_ad_list_by_index', json_encode($arr));
            return response()->json($arr,CodeData::OK);
        } else {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, is_array($res) ? $res : $res->toArray());
        }
    }

    public function getAdListByPoi(array $poi = [1], $lotteryType=0, $keyword='')
    {
//        $adCache = Redis::get('cache_ad_list_by_'.$poi[0]);
//        if ($adCache) {
//            return $poi == [4] ? json_decode( $adCache, true) : collect(json_decode( $adCache));
//        }
        $ad_list = Ad::query()
            ->whereIn('position', $poi)
            ->when($lotteryType, function($query) use ($lotteryType) {
                $query->where(function($query) use ($lotteryType) {
                    $query->where(function($query) use ($lotteryType) {
                        $query->where('lotteryType', $lotteryType);
                    })->orWhere('lotteryType', 0);
                });
            })
            ->when($keyword, function($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%');
            })
            ->select(['id', 'title', 'desc', 'type', 'sort', 'position', 'ad_image', 'ad_url'])
            ->orderBy('sort', 'desc')
            ->active()
            ->get();

        if ($ad_list->isEmpty()) {
            return collect([]);
        }
        if ($poi == [4]) {
            $haveImg = [];
            $withoutImg = [];
            foreach ($ad_list as $k => $v) {
                if ($v['ad_image']) {
                    $haveImg[$k] = $v;
                } else {
                    $withoutImg[$k] = $v;
                }
            }
            $haveImg = array_values($haveImg);
            $withoutImg = array_values($withoutImg);
//            Redis::set('cache_ad_list_by_'.$poi[0], json_encode(['haveImg' => $haveImg, 'withoutImg' => $withoutImg]));
            return ['haveImg' => $haveImg, 'withoutImg' => $withoutImg];
        }
//        Redis::set('cache_ad_list_by_'.$poi[0], json_encode($ad_list));
        return $ad_list;
    }

    public function getIndexAds($type=6)
    {
        $page = request()->input('page');
        $lotteryType = request()->input('lotteryType');
        $ids = Ad::query()
            ->where('ad_image', '<>', '')
            ->active()
            ->where('position', $type)->pluck('id');
        if ($ids->isEmpty()) {
            return null;
        }
        $idsArr = $ids->toArray();
        if (count($idsArr) == 1) {
            $id = $idsArr[0];
        } else {
            $randomKey = array_rand($idsArr);
            $id = $idsArr[$randomKey];
        }
        $ad = Ad::query()
            ->select(['id', 'title', 'desc', 'ad_image', 'ad_url', 'type'])
//            ->where('lotteryType', 0)
//            ->orWhere('lotteryType', $lotteryType)
//            ->offset($page-1)
                ->find($id);

//        $imageInfo = Image::make($ad['ad_image']);
//        $ad['width'] = 200;
//        $ad['height'] = $imageInfo->height();
        $ad['is_ad'] = true;
        return $ad->toArray();
    }
}
