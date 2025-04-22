<?php

namespace Modules\Api\Services\discovery;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Models\User;
use Modules\Api\Models\UserDiscovery;
use Modules\Api\Models\UserRead;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\collect\CollectService;
use Modules\Api\Services\config\ConfigService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\picture\PictureService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class DiscoveryService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 发布
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create($params): JsonResponse
    {
        $params['is_49'] = $params['is_49'] ?? 0;
        $params['user_id_49'] = $params['user_id_49'] ?? 0;
//        dd($params);


//        $maxTime = DB::table('user_discoveries')->where('user_id', $user_id)->max('created_at');
//        if ($maxTime && strtotime($maxTime) + 30 > time()) {
//            throw new CustomException(['message'=>'30秒后发布']);
//        }

        try {
            Log::info('imageInfoArr: '. json_encode($params));
            if ($params['is_49'] == 1) {
                $user_id = DB::table('discusses')
                    ->where('is_49', 1)
                    ->where('user_id_49', $params['user_id_49'])
                    ->where('year', date('Y'))
                    ->where('lotteryType', $params['lotteryType'])
                    ->value('user_id');
                if (!$user_id) {
                    $user_id = DB::table('users')
                        ->whereIn('system', [1, 2])
                        ->orWhere('is_chat', 1)
                        ->inRandomOrder()
                        ->value('id');
                }
            } else {
                $user_id = auth('user')->id();
            }

            DB::beginTransaction();
            $current_year = date('Y');
            $checkStatus = $this->getCheckStatus(8);
            $issue = $this->getNextIssue($params['lotteryType']);
            $discovery = UserDiscovery::query()
                ->create([
                    'user_id'       => $user_id,
                    'lotteryType'   => $params['lotteryType'],
                    'year'          => $current_year,
                    'issue'         => $issue,
                    'title'         => strip_tags($params['title']),
                    'content'       => strip_tags($params['content']),
                    'type'          => $params['type'],
                    'videos'        => $params['videos'] ? strip_tags($params['videos']) : '',
                    'status'        => $checkStatus==1 ? 0 : 1
                ]);

            $images = [];
            if ( !is_array($params['images']) ) {
                $params['images'] = [$params['images']];
            }
            if ($params['is_49'] == 0) {
                foreach ($params['images'] as $k => $v) {
                    $imageInfo = (new PictureService())->getImageInfoWithOutHttp($v, true);
                    $images[$k]['img_url']  = $v;
                    $images[$k]['width']    = $imageInfo['width'];
                    $images[$k]['height']   = $imageInfo['height'];
                    $images[$k]['mime']     = $imageInfo['mime'];
                }
                $discovery->images()->createMany($images);
                // 加成长值
                (new UserGrowthScoreService())->growthScore($this->_grow['create_post']);
                // 加发布数
                User::query()->where('id', $user_id)->increment('releases');
            } else {
                // 下载49图片
                $imageInfoArr = [];
                foreach ($params['images'] as $k => $v) {
                    $imageInfoArr[$k] = (new PictureService())->downloadRemoteImage(config('config.49_full_srv_img_prefix') .$v);
                }
                Log::info('imageInfoArr: '. json_encode($imageInfoArr));
                if ($imageInfoArr) {
                    foreach ($imageInfoArr as $k => $v) {
                        $imageInfo = (new PictureService())->getImageInfoWithOutHttp($v, true);
                        $images[$k]['img_url']  = $v;
                        $images[$k]['width']    = $imageInfo['width'];
                        $images[$k]['height']   = $imageInfo['height'];
                        $images[$k]['mime']     = $imageInfo['mime'];
                    }
                    $discovery->images()->createMany($images);
                }

            }

            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('DiscoveryService create error: '. $exception->getMessage() . ' in '. $exception->getFile(). ':'. $exception->getLine());
            throw new CustomException(['message'=>$exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }

        return $this->apiSuccess(ApiMsgData::POST_API_SUCCESS);
    }

    /**
     * 发现列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function list($params): JsonResponse
    {
        $keyword = $params['keyword'] ?? null;
        $userId = auth('user')->id();
//        $maxIssue = UserDiscovery::query()
//            ->where('year', date('Y'))
//            ->where('lotteryType', $params['lotteryType'])
//            ->max('issue');
//        if (!$maxIssue) {
//            throw new CustomException(['message'=>'暂无数据']);
//        }
        $userDiscovery = UserDiscovery::query()
//            ->where('issue', $maxIssue)
            ->where('created_at', '>=', '2025-03-25 00:00:00')
            ->where('status', 1)
            ->where('type', $params['type'])
            ->when($params['is_rec'] != 2, function($query) use($params) {
                $query->where('is_rec', $params['is_rec']);
            })
            ->where('lotteryType', $params['lotteryType'])
            ->removeBlack()
            ->when($keyword, function($query) use ($params){
                $query->where(function ($query) use ($params) {
                    $query->orWhere('title', 'like', '%'.$params['keyword'].'%')
                        ->orWhereHas('user', function($query) use ($params){
                            $query->where('nickname', 'like', '%'.$params['keyword'].'%');
                        });
                });
            })
            ->with(['images', 'user_info'=>function($query) {
                $query->select(['id', 'nickname', 'avatar']);
            }])
            ->when($userId, function($query) use ($userId) {
                $query->with(['follow'=>function($query) use ($userId) {
                    $query->where('user_id', $userId);
                },'collect'=>function($query) use ($userId) {
                    $query->where('user_id', $userId);
                }, 'user'=>function($query) {
                    $query->select('id')->with('focus');
                }]);
            })
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
        if ($userDiscovery->isEmpty()) {
            throw new CustomException(['message' => '数据不存在']);
        }
        $userDiscovery = $userDiscovery->toArray();
        foreach ($userDiscovery['data'] as $k => $v) {
            $userDiscovery['data'][$k]['follow'] = isset($v['follow']) && $v['follow'];
            $userDiscovery['data'][$k]['collect'] = isset($v['collect']) && $v['collect'];
            $userDiscovery['data'][$k]['focus'] = isset($v['user']['focus']) && $v['user']['focus'];
            unset($userDiscovery['data'][$k]['user']);
            if($v['images']) {
                foreach ($v['images'] as $kk => $vv) {
                    $userDiscovery['data'][$k]['images'][$kk]['img_url'] = str_replace('https://api1.49tkapi8.com/', config('config.full_srv_img_prefix'), $vv['img_url']);
                }
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $userDiscovery);
    }

    /**
     * 获取发现详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        try{
            $userId = auth('user')->id();
            $userDiscovery = UserDiscovery::query()
                ->where('status', 1)
                ->with(['images', 'user_info'=>function($query) {
                    $query->select(['id', 'nickname', 'avatar']);
                }])
                ->when($userId, function($query) use ($userId) {
                    $query->with(['follow'=>function($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },'collect'=>function($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }, 'user'=>function($query) {
                        $query->select('id')->with('focus');
                    }]);
                })
                ->findOrFail($params['id']);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        if ($userDiscovery['views'] == 0) {
            $userDiscovery->increment('views', $this->getFirstViews());
        } else {
            $userDiscovery->increment('views', $this->getSecondViews());
        }
        if ($userDiscovery['images']) {
            foreach($userDiscovery['images'] as $k => $v) {
                $userDiscovery['images'][$k]['img_url'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['img_url']);
            }
        }
        $userDiscovery = $userDiscovery->toArray();
        $userDiscovery['follow'] = isset($userDiscovery['follow']) && $userDiscovery['follow'];
        $userDiscovery['collect'] = isset($userDiscovery['collect']) && $userDiscovery['collect'];
        $userDiscovery['focus'] = isset($userDiscovery['user']['focus']) && $userDiscovery['user']['focus'];
        unset($userDiscovery['user']);
        if($userId) {
            // 增加真实浏览量
            UserRead::query()->insertOrIgnore([
                'user_id'     => $userId,
                'year'        => $userDiscovery['year'],
                'lotteryType' => $userDiscovery['lotteryType'],
                'issue'       => $userDiscovery['issue'],
                'type'        => 2,
                'target_id'   => $userDiscovery['id'],
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $userDiscovery);
    }

    /**
     * 发现点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        try{
            $userCommentBuilder = UserDiscovery::query()->where('id', $params['id']);

            return (new FollowService())->follow($userCommentBuilder);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'pictureId不存在']);
        }
    }

    /**
     * 图片点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect($params): JsonResponse
    {
        try{
            $picDetailModel = UserDiscovery::query()->where('id', $params['id']);
            return (new CollectService())->collect($picDetailModel);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'Id不存在']);
        }
    }

    /**
     * 发现-视频-转发
     * @param $params
     * @return JsonResponse
     */
    public function forward($params): JsonResponse
    {
        UserDiscovery::query()->where('id', $params['id'])->increment('forwardCount');

        return $this->apiSuccess(ApiMsgData::FORWARD_API_SUCCESS);
    }

}
