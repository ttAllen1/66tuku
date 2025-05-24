<?php

namespace Modules\Api\Services\comment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Ai;
use Modules\Admin\Models\WebConfig;
use Modules\Api\Models\CorpusArticle;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\FiveBliss;
use Modules\Api\Models\Forecast;
use Modules\Api\Models\Humorous;
use Modules\Api\Models\MasterRanking;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\PicDiagram;
use Modules\Api\Models\PicForecast;
use Modules\Api\Models\User;
use Modules\Api\Models\UserComment;
use Modules\Api\Models\UserCommentThree;
use Modules\Api\Models\UserDiscovery;
use Modules\Api\Models\UserJoinActivity;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\picture\PictureService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Api\Services\user\UserService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class CommentService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 热门评论一级列表
     * @return JsonResponse
     */
    public function get_hot_list(): JsonResponse
    {
        $userId = auth('user')->id();
        $query = UserComment::query()
            ->orderByDesc('updated_at')
            ->where('is_hot', 1)
            ->where('status', 1)
            ->where('user_id', 54377)
            ->where('level', 1);
        $comments = $query
            ->with(['user'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }, 'images', 'follow'=>function($query) use ($userId){
                $query->where('user_id', $userId);
            }])
        ->get();

        if ($comments->isEmpty()) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, []);
        }
        $comments = $comments->toArray();
        foreach ($comments as $k => $comment) {
            if ($comment['images']) {
                foreach($comment['images'] as $kk => $vv) {
                    $comments[$k]['images'][$kk]['img_url'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $vv['img_url']);
                }
            }
            if ($comment['follow']) {
                $comments[$k]['follow'] = true;
            } else {
                $comments[$k]['follow'] = false;
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $comments);
    }

    /**
     * 获取一级评论【分页】
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function list($params): JsonResponse
    {
        $userId = auth('user')->id();
        switch ($params['type']){
            case self::PICDIAGRAMMODEL:
//                $tarClass = PicDiagram::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\PicDiagram');
                break;
            case self::FORECAST:
//                $tarClass = PicForecast::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\PicForecast');
                break;
            case self::CORPUSARTICES:
                return $this->corpusComment($params);
//                $tarClass = CorpusArticle::class;
                break;
            case self::COMMENTMODEL:
//                $tarClass = UserComment::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\UserComment');
                break;
            case self::DISCOVERYMODEL:
//                $tarClass = UserDiscovery::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\UserDiscovery');
                break;
            case self::HUMOROUSMODEL:
//                $tarClass = Humorous::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\Humorous');
                break;
            case self::DISCUSSMODEL:
//                $tarClass = Discuss::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\Discuss');
//                    ->where('commentable_id', $params['target_id']);
                break;
            case self::AIMODEL:
//                $tarClass = Discuss::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Admin\Models\Ai');
//                    ->where('commentable_id', $params['target_id']);
                break;
            case self::MASTERMODEL:
//                $tarClass = Discuss::class;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Admin\Models\MasterRankings');
//                    ->where('commentable_id', $params['target_id']);
                break;
            case self::PICMODEL:
            default:
//                $tarClass = PicDetail::class;
//                break;
                $tarClass = UserComment::query()
                    ->where('commentable_type', 'Modules\Api\Models\PicDetail');
//                    ->where('commentable_id', $params['target_id']);
                break;
        }
        $comments = $tarClass
//            UserComment::query()
//            ->whereHasMorph(
//                'commentable', [$tarClass],
//                function($q) use ($params){
//                    $q->where('id', $params['target_id']);
//                }
//            )
            ->where('commentable_id', $params['target_id'])
            ->with(['user'=>function($q) {
                $q->select(['id', 'name', 'nickname', 'account_name', 'avatar', 'web_id']);
            }, 'follow'=>function($query) use ($userId){
                $query->where('user_id', $userId);
            }, 'images'])
            ->where('level', 1)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
        if ($comments->isEmpty()) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, []);
        }
        $comments = $comments->toArray();
        $web_id = [];
        foreach ($comments['data'] as $k => $comment) {
            if ($comment && $comment['images']) {
                foreach ($comment['images'] as $kk => $vv) {
                    $comments['data'][$k]['images'][$kk]['img_url'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $vv['img_url']);
                }
            }
            $comments['data'][$k]['web_config'] = [];
            $comments['data'][$k]['web_config']['id'] = 0;
            $comments['data'][$k]['web_config']['color'] = '';
            $comments['data'][$k]['web_config']['web_sign'] = '';
            $comments['data'][$k]['web_config']['avatar_prefix_url'] = '';
            if ($comment['follow']) {
                $comments['data'][$k]['follow'] = true;
            } else {
                $comments['data'][$k]['follow'] = false;
            }
            $comments['data'][$k]['user']['avatar_host'] = config('config.full_srv_img_prefix');
            if ($comment['user']['web_id']) {
                $comments['data'][$k]['location'] = 2;
                if (!in_array($comment['user']['web_id'], $web_id)) {
                    $web_id[] = $comment['user']['web_id'];
                }
            } else {
                $comments['data'][$k]['location'] = 1;
            }
//            $comments['data'][$k]['content'] = nl2br($comment['content']);
        }
        if ($web_id) {
            $webConfigs = WebConfig::query()->whereIn('id', $web_id)->select(['id', 'web_sign', 'color', 'avatar_prefix_url'])->get()->toArray();
            foreach($comments['data'] as $k => $v) {
                foreach($webConfigs as $kk => $vv) {
                    if ($v['user']['web_id'] == $vv['id']) {
                        $comments['data'][$k]['web_config'] = $vv;
                    }
                }
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $comments);
    }

    /**
     * 获取资料一级评论
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function corpusComment($params): JsonResponse
    {
        $userId = auth('user')->id();
        $tableIdx = CorpusArticle::getTableIdx($params);
        $comments = UserComment::query()
            ->where('commentable_type', 'Modules\Api\Models\CorpusArticle'.$tableIdx)
            ->where('commentable_id', $params['target_id'])
            ->with(['user'=>function($q) {
                $q->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }, 'follow'=>function($query) use ($userId){
                $query->where('user_id', $userId);
            }, 'images'])
            ->where('level', 1)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
        if ($comments->isEmpty()) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, []);
        }

        $comments = $comments->toArray();
        foreach ($comments['data'] as $k => $comment) {
            $comments['data'][$k]['user']['avatar_host'] = config('config.full_srv_img_prefix');
            if ($comment && $comment['images']) {
                foreach ($comment['images'] as $kk => $vv) {
                    $comments['data'][$k]['images'][$kk]['img_url'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $vv['img_url']);
                }
            }
            if ($comment['follow']) {
                $comments['data'][$k]['follow'] = true;
            } else {
                $comments['data'][$k]['follow'] = false;
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $comments);
    }

    /**
     * 获取子评论
     * @param $params
     * @return JsonResponse
     */
    public function get_children_list($params): JsonResponse
    {
        $userId = auth('user')->id();
        $topId = $params['commentId'];
        $comments = UserComment::query()
            ->where('top_id', $topId)
            ->where('status', 1)
            ->with(['user'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }, 'upUser'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'nickname', 'account_name', 'avatar']);
            }, 'images', 'follow'=>function($query) use ($userId){
                $query->where('user_id', $userId);
            }])
            ->orderBy('created_at', 'desc')
            ->simplePaginate();

        if ($comments->isEmpty()) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, []);
        }
        $comments = $comments->toArray();
        foreach ($comments['data'] as $k => $comment) {
            $comments['data'][$k]['user']['avatar_host'] = config('config.full_srv_img_prefix');
            $comments['data'][$k]['up_user']['avatar_host'] = config('config.full_srv_img_prefix');
            if ($comment && $comment['images']) {
                foreach ($comment['images'] as $kk => $vv) {
                    $comments['data'][$k]['images'][$kk]['img_url'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $vv['img_url']);
                }
            }
            if ($comment['follow']) {
                $comments['data'][$k]['follow'] = true;
            } else {
                $comments['data'][$k]['follow'] = false;
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $comments);
    }

    /**
     * 创建评论
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create($params): JsonResponse
    {
        try{
            $msg = '';
            $userId = auth('user')->id();
            $maxTime = DB::table('user_comments')->where('user_id', $userId)->max('created_at');
            if ($maxTime && strtotime($maxTime) + 20 > time()) {
                throw new CustomException(['message'=>'20秒后评论']);
            }
            if (!$this->getUserRegisterMoreThan3()) {
                throw new CustomException(['message'=>'注册时间不满三个小时']);
            }
            DB::beginTransaction();
            $targetOrm = $this->getOrmRelationByType((int)$params['type'], (int)$params['target_id'], (int)($params['corpusTypeId'] ?? 0));

            $createData = [];
            $createData['user_id']      = $userId;
            $createData['content']      = strip_tags($params['content']);
            $createData['top_id']       = 0;
            $createData['up_id']        = 0;
            $createData['up_user_id']   = 0;
            $createData['level']        = 1;
            if ( $params['type'] == self::PICMODEL || $params['type'] == self::PICDIAGRAMMODEL || $params['type'] == self::FORECAST || $params['type'] == self::DISCOVERYMODEL || $params['type'] == self::DISCUSSMODEL || $params['type'] == self::HUMOROUSMODEL || $params['type'] == self::CORPUSARTICES || $params['type'] == self::AIMODEL || $params['type'] == self::MASTERMODEL ) {
                $targetOrm->increment('commentCount');
            }
            if ( $params['type'] == self::COMMENTMODEL ) {
                // 评论回复 根据 target_id 查出对应的上一级的评论
                $createData['top_id']       = $targetOrm['level'] ==1 ? $targetOrm['id'] : $targetOrm['top_id'];
                $createData['up_id']        = $targetOrm['id'];
                $createData['up_user_id']   = $targetOrm['user_id'];
                $createData['level']        = 2;
                UserComment::query()->where('id', $params['target_id'])->increment('subCommentCount');
            } else {
                // 此篇帖子是否第一次评论 是的话加金币
                $isFirst = $targetOrm->comments()->where('user_id', $createData['user_id'])->first();
                if (!$isFirst) {
                    // 加金币
                    $type = 'user_activity_comment';
                    (new ActivityService())->join($type, $this->_comment_num);
                }
                // 加成长值
                (new UserGrowthScoreService())->growthScore($this->_grow['reply_post']);
                // 加集五福进度
                $this->joinActivities(5);
            }
            $isInner = DB::table('user_groups')
                ->where('user_id', auth('user')->id())
                ->where('group_id', 4)
                ->value('id');
            if ($isInner) {
                $createData['status']        = 1;
            } else {
                $checkStatus = $this->getCheckStatus($params['type']);
                if ($checkStatus==1) {  // 状态：1开启审核
                    $createData['status']        = 0;
                    $msg = '评论成功，待审核通过即可展示！';
                } else {  // 2关闭审核
                    $createData['status']        = 1;
                }

            }

            $comment = $targetOrm->comments()->create($createData);

            // 处理图片
            if (!empty($params['images'])) {
                $images = [];
                if ( !is_array($params['images']) ) {
                    $params['images'] = [$params['images']];
                }
                foreach ($params['images'] as $k => $v) {
                    $imageInfo = (new PictureService())->getImageInfoWithOutHttp($v, true);
                    $images[$k]['img_url']  = $v;
                    $images[$k]['width']    = $imageInfo['width'];
                    $images[$k]['height']   = $imageInfo['height'];
                    $images[$k]['mime']     = $imageInfo['mime'];
                }
                $comment->images()->createMany($images);
            }
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException(['message'=>$exception->getMessage()]);
        }

        return $this->apiSuccess($msg ?: ApiMsgData::COMMENT_API_SUCCESS);
    }

    /**
     * 创建评论【第三方】
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create_3($params): JsonResponse
    {
        $web_id = User::query()->where('id', auth('user')->id())->value('web_id');
        if (!$web_id) {
            throw new CustomException(['message'=> '非第三方用户禁止此操作']);
        }
        try{
            WebConfig::query()->where('status', 1)->findOrFail($web_id);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=> '第三方站点被关闭']);
        }

        $checkStatus = $this->getCheckStatus(17);
        $data = [
            'user_id'           => $params['user_id'],
            'web_id'            => $web_id,
            'nick_name'         => $params['nick_name'],
            'avatar'            => $params['avatar'],
            'commentable_id'    => $params['target_id'],
            'content'           => $params['content'],
            'status'            => $checkStatus==1 ? 0 : 1,
        ];

        if ($params['three_cate'] == 1) {
            // 图片评论
            $data['commentable_type'] = 'Modules\Api\Models\PicDetail';
        } else {
            // 评论回复
            try{
                $data['level'] = 2;
                $data['commentable_type'] = 'Modules\Api\Models\UserComment';
                // 针对被回复的评论
                $res = UserCommentThree::query()->where('id', $params['target_id'])->select(['id', 'top_id', 'up_id', 'user_id', 'level', 'created_at'])->firstOrFail()->toArray();
                if ($res['level'] ==2) {
                    throw new CustomException(['message'=> '无回复权限']);
                }
                $data['top_id'] = $res['level'] ==1 ? $res['id'] : $res['top_id'];
                $data['up_id'] = $res['id'];
                $data['up_user_id'] = $res['user_id'];
                UserCommentThree::query()->where('id', $params['target_id'])->increment('subCommentCount');
            }catch (ModelNotFoundException $exception) {
                throw new CustomException(['message'=> '被回复的评论不存在']);
            }
        }
        UserCommentThree::query()->create($data);

        return $this->apiSuccess(ApiMsgData::COMMENT_API_SUCCESS);
    }

    /**
     * 评论点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $userCommentBuilder = UserComment::query()->where('id', $params['commentId']);

        return (new FollowService())->follow($userCommentBuilder);
    }

    /**
     * 评论｜图片点赞【第三方】
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow_3($params): JsonResponse
    {
        try {
            if($params['cate']==1) {
                PicDetail::query()->where('id', $params['target_id'])->increment('thumbUpCount');
            } else {
                UserCommentThree::query()->where('id', $params['target_id'])->increment('thumbUpCount');
            }

            return $this->apiSuccess(ApiMsgData::FOLLOW_API_SUCCESS);
        }catch (\Exception $exception) {

            throw new CustomException(['message'=>ApiMsgData::FOLLOW_API_FAIL]);
        }
    }

    /**
     * 获取一级评论【分页】
     * @param $params
     * @return JsonResponse
     */
    public function list_3($params): JsonResponse
    {
        if ($params['three_cate'] == 1) {
            // 图片评论列表
            $commentable_type = 'Modules\Api\Models\PicDetail';
        } else {
            $commentable_type = 'Modules\Api\Models\UserComment';
        }
        $comments1 = DB::table('user_comments')
            ->select('id', 'user_id', DB::raw('null as web_id'), DB::raw('null as nick_name'), DB::raw('null as avatar'), 'commentable_id', 'commentable_type', 'content', 'created_at', 'location', 'up_id as pid', 'thumbUpCount', 'subCommentCount', 'level', 'up_id')
            ->where('status', 1)
            ->where('commentable_id', $params['target_id'])
            ->where('commentable_type', $commentable_type);

        $comments2 = DB::table('user_comment_threes')
            ->select('id', 'user_id', 'web_id', 'nick_name', 'avatar', 'commentable_id', 'commentable_type', 'content', 'created_at', 'location', 'up_id as pid', 'thumbUpCount', 'subCommentCount', 'level', 'up_id')
            ->where('status', 1)
            ->where('commentable_id', $params['target_id'])
            ->where('commentable_type', $commentable_type);

        $allComments = $comments1->union($comments2)
            ->orderBy('created_at', 'desc')
            ->simplePaginate(15)->toArray();
        if (!$allComments['data']) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $allComments);
        }
        $allComments['data'] = array_map(function ($item) {
            return collect($item)->toArray();
        }, $allComments['data']);

        $userIds = [];
        $userInfoList = [];
        $webIds = [];
        $webConfigList = [];
        foreach ($allComments['data'] as $k => $v) {
            if ($v['web_id'] && !in_array($v['web_id'], $webIds)) {
                $webIds[] = $v['web_id'];
            }
            if ($v['location'] == 1) {
                if (!in_array($v['user_id'], $userIds)) {
                    $userIds[] = $v['user_id'];
                }
            } else {
                $allComments['data'][$k]['user']['id'] = $v['user_id'];
                $allComments['data'][$k]['user']['nickname'] = $v['nick_name'];
                $allComments['data'][$k]['user']['avatar'] = $v['avatar'];
            }
        }
        if ($userIds) {
            $userInfoList = User::query()->whereIn('id', $userIds)->select(['id', 'nickname', 'avatar'])->get()->toArray();
        }
        if ($webIds) {
            $webConfigList = WebConfig::query()->whereIn('id', $webIds)->select(['id', 'web_sign', 'color'])->get()->toArray();
        }
        foreach ($allComments['data'] as $k => $v) {
            $allComments['data'][$k]['user']['web_sign'] = '';
            $allComments['data'][$k]['user']['color'] = '';
            if ($v['location'] == 1) {
                foreach ($userInfoList as $kk => $vv) {
                    if ($v['user_id'] == $vv['id']) {
                        $vv['avatar'] = $this->getHttp() . $vv['avatar'];
                        $allComments['data'][$k]['user'] = $vv;
                        $allComments['data'][$k]['user']['web_sign'] = '';
                        $allComments['data'][$k]['user']['color'] = '';
                    }
                }
            }
            foreach ($webConfigList as $kkk => $vvv) {
                if ($v['web_id'] == $vvv['id']) {
                    $allComments['data'][$k]['user']['web_sign'] = $vvv['web_sign'];
                    $allComments['data'][$k]['user']['color'] = $vvv['color'];
                }
            }

        }

        $arr1 = [];
        $arr2 = [];
        foreach ($allComments['data'] as $k => $v) {
            if ($v['location'] == 1) {
                $arr1[$k] = $v;
            } else {
                $arr2[$k] = $v;
            }
        }
        $arr1 = $this->tree($arr1);
        $arr2 = $this->tree($arr2);
        $allComments['data'] = array_merge($arr1, $arr2);
        $this->sortByKey($allComments['data'], 'created_at', false);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $allComments);
    }

    /**
     * 获取子评论
     * @param $params
     * @return JsonResponse
     */
    public function children_3($params): JsonResponse
    {
        try{
            if ($params['location'] == 1) {
                $res = UserComment::query()
                    ->where('up_id', $params['commentId'])
                    ->where('status', 1)
                    ->select(['id', 'user_id', 'content', 'thumbUpCount', 'subCommentCount', 'created_at'])
                    ->with(['user'=>function($query) {
                        $query->select(['id', 'nickname', 'avatar']);
                    }])
                    ->simplePaginate()->toArray();
            } else {
                $res = UserCommentThree::query()
                    ->where('up_id', $params['commentId'])
                    ->where('status', 1)
                    ->select(['id', 'user_id', 'nick_name as nickname', 'avatar', 'content', 'thumbUpCount', 'subCommentCount', 'created_at'])
                    ->simplePaginate()->toArray();
                if ($res['data']) {
                    foreach ($res['data'] as $k => $v) {
                        $res['data'][$k]['user']['id'] = $v['id'];
                        $res['data'][$k]['user']['nickname'] = $v['nickname'];
                        $res['data'][$k]['user']['avatar'] = $v['avatar'];
                    }
                }
            }
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'一级评论不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 获取对应Model
     * @param $type
     * @param $target_id
     * @param int $corpusTypeId
     * @return Model
     * @throws CustomException
     */
    private function getOrmRelationByType($type, $target_id, int $corpusTypeId = 0):Model
    {
        try {
            switch ($type){
                case self::PICMODEL:     // 图片
                    $orm = PicDetail::query()->findOrFail($target_id, ['id']);
                    break;
                case self::PICDIAGRAMMODEL:     // 图解
                    $orm = PicDiagram::query()->findOrFail($target_id, ['id']);
                    break;
                case self::FORECAST:     // 图片竞猜
                    $orm = PicForecast::query()->findOrFail($target_id, ['id']);
                    break;
                case self::CORPUSARTICES:     // 资料
                    $articles = CorpusArticle::setTables(['corpusTypeId'=>$corpusTypeId]);
                    $orm = $articles->findOrFail($target_id, ['id']);
                    break;
                case self::COMMENTMODEL:     // 评论【回复】
                    $orm = UserComment::query()->findOrFail($target_id, ['id', 'top_id', 'up_id', 'user_id', 'level']);
                    break;
                case self::DISCOVERYMODEL:     // 发现
                    $orm = UserDiscovery::query()->findOrFail($target_id, ['id']);
                    break;
                case self::DISCUSSMODEL:     // 高手论坛
                    $orm = Discuss::query()->findOrFail($target_id, ['id']);
                    break;
                case self::HUMOROUSMODEL:     // 幽默竞猜
                    $orm = Humorous::query()->findOrFail($target_id, ['id']);
                    break;
                case self::AIMODEL:     // ai分析
                    $orm = Ai::query()->findOrFail($target_id, ['id']);
                    break;
                case self::MASTERMODEL:     // 高手榜
                    $orm = MasterRanking::query()->findOrFail($target_id, ['id']);
                    break;
                default:
                    $orm = new UserComment();
                    break;
            }
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'target_id不存在']);
        }

        return $orm;
    }

}
