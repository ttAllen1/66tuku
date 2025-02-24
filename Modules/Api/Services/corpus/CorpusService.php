<?php

namespace Modules\Api\Services\corpus;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Models\CorpusArticle;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\UserActivity;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\follow\FollowService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class CorpusService extends BaseApiService
{
    /**
     * 平台切换
     * @return mixed
     */
    private static function getWebsite()
    {
        return AuthConfig::first()->value('collectionTypes');
    }

    /**
     * 资料分类
     * @param array $data
     * @return JsonResponse
     */
    public function listCorpusType(array $data): JsonResponse
    {
        $lotteryType = $data['lotteryType'] ?? '1';
        $corpusType = CorpusType::select('id', 'lotteryType', 'corpusTypeName')
            ->where(['lotteryType' => $lotteryType, 'website' => self::getWebsite()]);
        if (!empty($data['year']))
        {
            $corpusType->where(function (Builder $query) use($data) {
                $query->where('year', 'like', '%"year": "'.intval($data['year']).'"%')
                    ->orWhere('year', 'like', '%"year":"'.intval($data['year']).'"%');
            });
        }
        $corpusType = $corpusType->orderByDesc('id')
            ->get()
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $corpusType);
    }

    /**
     * 资料列表
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function listArticle(array $data): JsonResponse
    {
        $articlesList = CorpusArticle::setTables($data);
        $corpusTypeId = intval($data['corpusTypeId']);
        $year = $data['year'];
        $is_index = false;
        if (isset($data['is_index'])) {
            $is_index = (bool)$data['is_index'];
        }

        $whereArr = [];
        if (isset($data['userid']) && $data['userid'] > 0)
        {
            $whereArr['user_id'] = intval($data['userid']);
        } else {
            $whereArr['corpusTypeId'] = $corpusTypeId;
            if (!empty($year)) {
                $whereArr['year'] = $year;
            }
        }
        $articlesList = $articlesList->select('id', 'user_id', 'title', 'commentCount', 'thumbUpCount', 'created_at');
        $articlesList->where($whereArr)->removeBlack();
        if (!empty($data['keywords']))
        {
            $articlesList->whereHas(
                'user',
                function(Builder $query) use($data) {
                    return $query->where('nickname', 'like','%'.$data['keywords'].'%')
                        ->orWhere('title', 'like', '%'.$data['keywords'].'%');
                }
            );
        }
        if (!empty($data['userid']))
        {
            $articlesList->whereHas(
                'corpusType',
                function(Builder $query) use($data) {
                    return $query->where('website', self::getWebsite());
                }
            );
        }
        $articlesList = $articlesList->with('user:id,nickname,avatar')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($is_index ? 10 : 25)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page' => $articlesList['last_page'],
            'current_page' => $articlesList['current_page'],
            'total' => $articlesList['total'],
            'list' => $articlesList['data']
        ]);
    }

    /**
     * 资料详情
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function infoArticle(array $data): JsonResponse
    {
        $articles = CorpusArticle::setTables($data);
        $cloneArticles = clone $articles;
        // 获取 “下一篇”
        $nextPost = $cloneArticles->select('id', 'title')->where('corpusTypeId', $data['corpusTypeId'])->where('id', '<', $data['id'])->orderByDesc('id')->first();
        // 获取 “上一篇”
        $previousPost = $cloneArticles->select('id', 'title')->where('corpusTypeId', $data['corpusTypeId'])->where('id', '>', $data['id'])->orderBy('id')->first();
        // 删除多余where条件
        unset($data['corpusTypeId']);
        $articles->where($data)->increment('clickCount', rand(1,10));
        $articles = $articles->select(['id', 'title', 'content', 'user_id', 'clickCount' , 'commentCount', 'thumbUpCount', 'created_at'])
            ->where($data)
            ->isFollow()
            ->first()
            ->toArray();
        $articles['next'] = $nextPost;
        $articles['prev'] = $previousPost;
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $articles);
    }

    /**
     * 点赞
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(array $data): JsonResponse
    {
        $articles = CorpusArticle::setTables($data);
        $corpusArticle = $articles->where('id', intval($data['id']));

        return (new FollowService())->follow($corpusArticle);
    }

}
