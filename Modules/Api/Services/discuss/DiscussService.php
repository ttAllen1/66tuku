<?php

namespace Modules\Api\Services\discuss;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Services\lottery\HistoryService;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\User;
use Modules\Api\Models\UserRead;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\picture\PictureService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class DiscussService extends BaseApiService
{
    /**
     * 论坛列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function list($params): JsonResponse
    {
        $keyword = $params['keyword'] ?? null;
        $discuss = Discuss::query()
            ->when($params['lotteryType'], function ($query) use ($params) {
                $query->where('lotteryType', $params['lotteryType']);
            })
            ->orderByDesc('year')
            ->where('status', 1)
            ->removeBlack()
            ->when($keyword, function ($query) use ($params) {
                $query->where(function ($query) use ($params) {
                    $query->orWhere('title', 'like', '%' . $params['keyword'] . '%')
                        ->orWhereHas('user', function ($query) use ($params) {
                            $query->where('nickname', 'like', '%' . $params['keyword'] . '%');
                        });
                });
            })
            ->when($params['sort'] == 1, function ($query) {
                $query->where('is_essence', 1)->orderby('created_at', 'desc');
            })
            ->when($params['sort'] == 3, function ($query) {
                $query->orderby('created_at', 'desc');
            })
            ->when($params['sort'] == 2, function ($query) {
                $query->orderBy('issue', 'desc')->orderby('thumbUpCount', 'desc')->orderby('created_at', 'desc');
            })
            ->when($params['sort'] == 4, function ($query) {
                $query->orderby('issue', 'desc')->orderby('is_top', 'desc')->orderby('is_essence', 'desc')->orderby('created_at', 'desc');
            })
            ->with([
                'images', 'user' => function ($query) {
                    $query->select(['id', 'nickname', 'avatar']);
                }, 'user.focus'  => function ($query) {
                    $query->where('user_id', auth('user')->id());
                }
            ])
            ->simplePaginate();
        if ($discuss->isEmpty()) {
            throw new CustomException(['message' => '数据不存在']);
        }
        $discuss = $discuss->toArray();
        foreach ($discuss['data'] as $k => $item) {
            if ($item && $item['images']) {
                foreach ($item['images'] as $kk => $vv) {
                    $discuss['data'][$k]['images'][$kk]['img_url'] = str_replace([
                        'api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'
                    ], ConfigService::getAdImgUrl(), $vv['img_url']);
                }
            }
            $discuss['data'][$k]['content'] = $this->custom_strip_tags(html_entity_decode($item['content']));
            $discuss['data'][$k]['issue'] = str_replace(date("Y"), '', $discuss['data'][$k]['issue']);
            $discuss['data'][$k]['issue'] = str_pad($discuss['data'][$k]['issue'], 3, 0, STR_PAD_LEFT);
            $discuss['data'][$k]['user']['focus'] = (bool)$item['user']['focus'];
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss);
    }

    /**
     * 创建
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create($params): JsonResponse
    {
        $year = date('Y');
//        $year = 2025;
        $params['is_49'] = $params['is_49'] ?? 0;
        $params['user_id_49'] = $params['user_id_49'] ?? 0;
        $isAdmin = $params['is_admin'] ?? 0;
        dd($isAdmin, $params);
        if ($params['is_49'] == 1) {
            $userId = DB::table('discusses')
                ->where('is_49', 1)
                ->where('user_id_49', $params['user_id_49'])
                ->where('year', $year)
                ->where('lotteryType', $params['lotteryType'])
                ->value('user_id');
            if (!$userId) {
                $userId = DB::table('users')
                    ->whereIn('system', [1, 2])
                    ->orWhere('is_chat', 1)
                    ->inRandomOrder()
                    ->value('id');
            }
        } else {
            $userId = auth('user')->id();
            $maxTime = DB::table('discusses')->where('user_id', $userId)->max('created_at');
            if ($maxTime && strtotime($maxTime) + 30 > time()) {
                throw new CustomException(['message'=>'30秒后发布']);
            }
        }

        $nextIssue = (int)$this->getNextIssue($params['lotteryType']);
        if ($params['is_49'] == 0) {
            if ($nextIssue!=Redis::get('lottery_real_open_issue_'.$params['lotteryType'])) {
                try {
                    (new HistoryService())->update_year_issue($params['lotteryType']);
                } catch (CustomException $e) {

                }
            }
        }
        try {
            DB::beginTransaction();
            $checkStatus = $this->getCheckStatus(10);
            $discuss = Discuss::query()->create([
                'user_id'     => $userId,
                'lotteryType' => $params['lotteryType'],
                'title'       => $isAdmin == 0 ? strip_tags($params['title']) : $params['title'],
                'content'     => $isAdmin == 0 ? strip_tags($params['content']) : str_replace(PHP_EOL, '', $params['content']),
                'word_color'  => $isAdmin == 0? strip_tags($params['word_color']) : $params['word_color'],
                'issue'       => $nextIssue,
                'year'        => $year,
                'is_49'        => $params['is_49'],
                'user_id_49'   => $params['user_id_49'],
                'status'      => $checkStatus == 1 ? 0 : 1
            ]);
            if (!empty($params['images']) && $params['is_49'] == 0) {
                $images = [];
                if (!is_array($params['images'])) {
                    $params['images'] = [$params['images']];
                }
                foreach ($params['images'] as $k => $v) {
                    // if($userId == 75454) {
                    //     dd($params, $v);
                    // }
                    $imageInfo = (new PictureService())->getImageInfoWithOutHttp($v);
                    $images[$k]['img_url'] = $v;
                    $images[$k]['width'] = $imageInfo['width'];
                    $images[$k]['height'] = $imageInfo['height'];
                    $images[$k]['mime'] = $imageInfo['mime'];
                }
                $discuss->images()->createMany($images);
            }
            if ($params['is_49'] == 0) {
                // 加成长值
                (new UserGrowthScoreService())->growthScore($this->_grow['create_post']);
                // 加发布数
                User::where('id', $userId)->increment('releases');
                // 加集五福进度
                $this->joinActivities(4);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException(['message' => $exception->getMessage()]);
        }

        return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
    }

    /**
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        try {
            $discuss = Discuss::query()
                ->where('status', 1)
                ->with('comments', 'images')
                ->findOrFail($params['id']);
            $discuss['content'] = $this->custom_strip_tags(html_entity_decode($discuss['content']));
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '数据不存在']);
        }
        if ($discuss['views'] == 0) {
            $discuss->increment('views', $this->getFirstViews());
        } else {
            $discuss->increment('views', $this->getSecondViews());
        }

        $userId = auth('user')->id();
        if ($userId) {
            $discuss['follow'] = (bool)$discuss->follow()->where('user_id', $userId)->value('id');
            // 增加真实浏览量
            UserRead::query()->insertOrIgnore([
                'user_id'     => $userId,
                'year'        => $discuss['year'],
                'lotteryType' => $discuss['lotteryType'],
                'issue'       => $discuss['issue'],
                'type'        => 1,
                'target_id'   => $discuss['id'],
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        }

        // 详情广告
//        $adList = (new AdService())->getAdListByPoi([2]);
//        $discuss['adList'] = $adList;

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss->toArray());
    }

    /**
     * 全部主题点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $discussBuilder = Discuss::query()->where('id', $params['id']);

        return (new FollowService())->follow($discussBuilder);
    }

    /**
     * 论坛上一期内容
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'];
        $userId = auth('user')->id();

        $discuss = Discuss::query()->where('user_id', $userId)
            ->where('lotteryType', $lotteryType)
            ->where('status', 1)
            ->select(['id', 'title', 'content', 'word_color', 'created_at'])
            ->latest()
            ->with(['images'])
            ->first();
        if (!$discuss) {
            throw new CustomException(['message' => '数据不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss->toArray());
    }
}
