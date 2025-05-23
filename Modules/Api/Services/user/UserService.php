<?php

namespace Modules\Api\Services\user;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\WebConfig;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\Humorous;
use Modules\Api\Models\IncomeApply;
use Modules\Api\Models\Invitation;
use Modules\Api\Models\Level;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\PicDiagram;
use Modules\Api\Models\PicForecast;
use Modules\Api\Models\User;
use Modules\Api\Models\UserActivity;
use Modules\Api\Models\UserAdvice;
use Modules\Api\Models\UserBlacklist;
use Modules\Api\Models\UserCollect;
use Modules\Api\Models\UserComment;
use Modules\Api\Models\UserDiscovery;
use Modules\Api\Models\UserFocuson;
use Modules\Api\Models\UserFollow;
use Modules\Api\Models\UserGrowthScore;
use Modules\Api\Models\UserMushin;
use Modules\Api\Models\UserPlatform;
use Modules\Api\Models\UserPlatRecharge;
use Modules\Api\Models\UserPlatWithdraw;
use Modules\Api\Models\UserSignin;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\auth\LoginService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\UserGoldRecord;
use Modules\Common\Models\UserWelfare;

class UserService extends BaseApiService
{
    /**
     * 获取用户信息
     * @return JsonResponse
     */
    public function getUserInfo(): JsonResponse
    {
        $userInfo = User::getUserInfoById(request()->userinfo->id)->toArray();
        // 是否设置支付密码
        if (request()->userinfo->fund_password) {
            $userInfo['isset_fund_password'] = 1;
        } else {
            $userInfo['isset_fund_password'] = 0;
        }
        $userInfo['platforms_count'] = UserPlatform::where([
            'user_id' => request()->userinfo->id, 'status' => 1
        ])->count(); //绑定平台数
        $userInfo['level_grade'] = $this->userLevel($this->userinfo()->growth_score)['level_grade']; //用户等级
        $userMushin = UserMushin::where([['user_id', request()->userinfo->id]])
            ->where(function ($query) {
                $query->where('mushin_end_date', '>', date('Y-m-d H:i:s', time()))
                    ->orWhere('is_forever', 1);
            })
            ->first();
        $userInfo['is_mushin'] = $userMushin ? 1 : 0; //是否在小黑屋

        return $this->apiSuccess('成功', $userInfo);
    }

    /**
     * 修改用户信息
     * @param array $data
     * @return JsonResponse|null
     */
    public function editUserInfo(array $data): ?JsonResponse
    {
        $updateData = [];
        if (!empty($data['nickname'])) {
            $data['nickname'] = trim($data['nickname']);
            if (DB::table('users')->where('nickname', $data['nickname'])->where('id', '<>', request()->userinfo->id)->exists()) {
                return response()->json(['message' => '昵称已存在', 'status' => 40000], 400);
            }
            if (empty($data['nickname'])) {
                return response()->json(['message' => '昵称不能为空', 'status' => 40000], 400);
            }
            $not_allow_str = ['微信', '威信', 'weixin', 'qq', '薇信', '客服', '管理员', '财务'];
            if (Str::contains($data['nickname'], $not_allow_str)) {
                return response()->json(['message' => '昵称中存在违规字符', 'status' => 40000], 400);
            }
            $updateData['nickname'] = $data['nickname'];
        }
        $message = '修改成功';
        if (!empty($data['avatar'])) {
            $status = $this->getCheckStatus(16);
            if ($status == 1) {   // 开启审核
                $message = '图像上传成功，等待审核中';
                $updateData['avatar_is_check'] = 0;
            } else {
                $updateData['avatar_is_check'] = 1;
                $updateData['avatar'] = $data['avatar'];
            }
            $updateData['new_avatar'] = $data['avatar'];
        }

        return $this->commonUpdate(User::query(), request()->userinfo->id, $updateData, $message);
    }


    /**
     * 三方修改用户信息
     * @param array $data
     * @return JsonResponse|null
     * @throws CustomException
     */
    public function editUserInfo_3(array $data): ?JsonResponse
    {
        $updateData = [];
        if (!empty($data['nickname'])) {
            $not_allow_str = ['微信', '威信', 'weixin', 'qq', '薇信'];
            if (Str::contains($data['nickname'], $not_allow_str)) {
                return response()->json(['message' => '昵称中存在违规字符', 'status' => 40000], 400);
            }
            $updateData['nickname'] = $data['nickname'];
        }
        $message = '修改成功';
        if (!empty($data['avatar'])) {
            // 获取图像前缀
            $web_id = User::query()->where('id', auth('user')->id())->value('web_id');
            if (!$web_id) {
                return response()->json(['message' => '此用户不属于第三方用户', 'status' => 40000], 400);
            }
            try {

                $webConfig = WebConfig::query()->where('status', 1)->findOrFail($web_id, ['avatar_prefix_url']);
            } catch (ModelNotFoundException $exception) {
                return response()->json(['message' => '站点不存在或已关闭', 'status' => 40000], 400);
            }

//            $status = $this->getCheckStatus(16);
//            if ($status==1) {   // 开启审核
//                $message = '图像上传成功，等待审核中';
//                $updateData['avatar_is_check'] = 0;
//            } else {
//
//            }
            $updateData['avatar_is_check'] = 1;
            $updateData['avatar'] = $webConfig['avatar_prefix_url'] . $data['avatar'];
            $updateData['new_avatar'] = $webConfig['avatar_prefix_url'] . $data['avatar'];
        }

        return $this->commonUpdate(User::query(), auth('user')->id(), $updateData, $message);
    }

    /**
     * 修改密码
     * @param array $data
     * @return JsonResponse
     */
    public function editUserPass(array $data): JsonResponse
    {
        $this->userinfo()->fill([
            'password' => bcrypt($data['password']),
            'chat_pwd' => md5($data['password'])
        ])->save();
        // 登陆聊天服务器
        $params['change'] = true;
        $params['new_password'] = md5($data['password']);      // 新密码
        $params['password'] = md5($data['password_current']);  // 老密码
        (new LoginService())->loginChat($params);

        return $this->apiSuccess('修改成功');
    }

    /**
     * 设置资金密码
     * @param array $data
     * @return JsonResponse
     */
    public function setFundPassword(array $data): JsonResponse
    {
        $this->userinfo()->fill([
            'fund_password' => bcrypt($this->_fund_password_salt . $data['password'])
        ])->save();
        return $this->apiSuccess('设置成功');
    }

    /**
     * 修改资金密码
     * @param array $data
     * @return JsonResponse
     */
    public function editFundPassword(array $data): JsonResponse
    {
        $this->userinfo()->fill([
            'fund_password' => bcrypt($this->_fund_password_salt . $data['password'])
        ])->save();
        return $this->apiSuccess('修改成功');
    }

    /**
     * 个人主页信息
     * @param $id
     * @return JsonResponse
     * @throws CustomException
     */
    public function getUserIndex($id): JsonResponse
    {
        $whereArr = [];
        if ($id) { //他人访问个人主页
            $whereArr['id'] = intval($id);
            $isFocuson = 0;
            $isShield = 0;
            if ($this->userinfo()) {
                if (UserFocuson::where([
                    'user_id' => $this->userinfo()->id, 'to_userid' => $whereArr['id']
                ])->value('id')) {
                    $isFocuson = 1;
                }
                if (UserBlacklist::where([
                    'user_id' => $this->userinfo()->id, 'to_userid' => $whereArr['id']
                ])->value('id')) {
                    $isShield = 1;
                }
            }
        } else { // 自己访问主页
            if ($this->userinfo()) {
                $whereArr['id'] = $this->userinfo()->id;
            } else {
                throw new CustomException(['message' => '请登录！']);
            }
        }
        $userInfo = User::select([
            'id', 'nickname', 'avatar', 'avatar_is_check', 'likes', 'fans', 'follows', 'releases', 'growth_score'
        ])->where($whereArr)->first()->toArray();
        if (isset($isFocuson)) {
            $userInfo['isFocuson'] = $isFocuson;
            $userInfo['isShield'] = $isShield;
        }
        // 是否支持打赏
        $income = IncomeApply::query()
            ->where('user_id', $id)
            ->where('status', 1)
            ->exists();
        $userInfo['isIncome'] = $income ? 1 : 0;

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $userInfo);
    }

    /**
     * 用户发布的数据
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function release(array $data): JsonResponse
    {
        $whereArr = [];
        if (!empty($data['id'])) { //他人访问个人主页
            $userid = intval($data['id']);
        } else { // 自己访问主页
            if ($this->userinfo()) {
                $userid = $this->userinfo()->id;
            } else {
                throw new CustomException(['message' => '请登录！']);
            }
        }
        if (!isset($data['type'])) {
            throw new CustomException(['message' => '类型错误']);
        }
        if (!isset($data['lotteryType'])) {
            throw new CustomException(['message' => '彩种错误']);
        } else {
            if ($data['lotteryType'] > 0) {
                $whereArr[] = ['lotteryType', $data['lotteryType']];
            }
        }
        if (!empty($data['keyword'])) {
            $whereArr[] = ['title', 'like', '%' . $data['keyword'] . '%'];
        }
        switch ($data['type']) {
            //高手论坛
            case 1:
                $whereArr[] = ['user_id', $userid];
                $data = Discuss::where($whereArr)
                    ->with(['user:id,nickname,avatar', 'images:imageable_id,img_url,width,height'])
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //发现
            case 2:
                $whereArr[] = ['user_id', $userid];
                $data = UserDiscovery::where($whereArr)
                    ->with(['user:id,nickname,avatar', 'images:imageable_id,img_url,width,height'])
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //资料大全
            case 3:
                $tableIdx = CorpusType::where('user_id', $userid)->value('table_idx');
                if ($tableIdx) {
                    $whereArr[] = ['ca.user_id', $userid];
                    $data = DB::table('corpus_articles' . $tableIdx . ' as ca')
                        ->select([
                            'ca.id', 'ca.corpusTypeId', 'ca.title', 'ca.content', 'ca.clickCount', 'ca.commentCount',
                            'ca.thumbUpCount', 'ca.created_at', 'users.nickname', 'users.avatar', 'ct.lotteryType'
                        ])
                        ->where($whereArr)
                        ->leftJoin('users', 'users.id', '=', 'ca.user_id')
                        ->leftJoin('corpus_types as ct', 'ca.corpusTypeId', '=', 'ct.id')
                        ->orderByDesc('created_at')
                        ->paginate(25)
                        ->toArray();
                    foreach ($data['data'] as $item) {
                        $item->content = strip_tags($item->content);
                        $item->content = preg_replace('/\n/', '', $item->content);
                        $item->content = preg_replace('/ | {2}/', '', $item->content);
                        $item->content = preg_replace('/&nbsp;/i', '', $item->content);
                    }

                } else {
                    $data = [
                        'last_page'    => 1,
                        'current_page' => 1,
                        'total'        => 0,
                        'data'         => [],
                    ];
                }
                break;

            //图解小组
            case 4:
                $whereArr[] = ['user_id', $userid];
                $data = PicDiagram::where($whereArr)
                    ->with('user:id,nickname,avatar')
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //竞猜小组
            case 5:
                $whereArr[] = ['user_id', $userid];
                $data = PicForecast::where($whereArr)
                    ->with('user:id,nickname,avatar')
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            default:
                throw new CustomException(['message' => '类型错误']);

        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $data['last_page'],
            'current_page' => $data['current_page'],
            'total'        => $data['total'],
            'list'         => $data['data']
        ]);
    }

    /**
     * 反馈列表
     * @return JsonResponse
     */
    public function getAdviceList(): JsonResponse
    {
        $adviceList = UserAdvice::where(['user_id' => request()->userinfo->id])
            ->orderBy('created_at', 'desc')
            ->with(['images:img_url,imageable_id,open'])
            ->paginate(15)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $adviceList['last_page'],
            'current_page' => $adviceList['current_page'],
            'total'        => $adviceList['total'],
            'list'         => $adviceList['data']
        ]);
    }

    /**
     * 意见反馈
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function addAdvice(array $data): JsonResponse
    {
        $data['user_id'] = request()->userinfo->id;
        $userAdvice = UserAdvice::query()->create($data);
        if ($images = request()->input('images')) {
            foreach ($images as $val) {
                $addImages = ['img_url' => $val];
                $imageInfo = (new PictureService)->getImageInfoWithOutHttp($val);
                $addImages['height'] = $imageInfo['height'];
                $addImages['width'] = $imageInfo['width'];
                $addImages['mime'] = $imageInfo['mime'];
                $userAdvice->images()->create($addImages);
            }
        }
        return $this->apiSuccess(ApiMsgData::POST_API_SUCCESS);
    }

    /**
     * 拉黑用户
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function setBlacklist(array $data): JsonResponse
    {
        if (request()->userinfo->id == $data['id']) {
            throw new CustomException(['message' => '禁止拉黑自己！']);
        }
        $userBlacklist = UserBlacklist::where([
            'user_id' => request()->userinfo->id, 'to_userid' => intval($data['id'])
        ])->first();
        if ($userBlacklist) {
            $userBlacklist->delete();
            return $this->apiSuccess(ApiMsgData::BLACKLIST_API_CANCEL);
        } else {
            $userBlacklist = new UserBlacklist;
            $userBlacklist->user_id = request()->userinfo->id;
            $userBlacklist->to_userid = $data['id'];
            $userBlacklist->save();
            return $this->apiSuccess(ApiMsgData::BLACKLIST_API_SUCCESS);
        }
    }

    /**
     * 拉黑列表
     * @param array $data
     * @return JsonResponse
     */
    public function getBlacklist(array $data): JsonResponse
    {
        $userBlacklist = UserBlacklist::select(['to_userid', 'created_at'])
            ->where(['user_id' => request()->userinfo->id])
            ->with([
                'user' => function ($query) {
                    return $query->select(['id', 'nickname', 'avatar']);
                }
            ]);
        if (isset($data['keywords'])) {
            $userBlacklist = $userBlacklist->whereHas('user', function (Builder $query) use ($data) {
                $query->where('nickname', 'like', '%' . $data['keywords'] . '%');
            });
        }
        $userBlacklist = $userBlacklist->orderBy('created_at', 'desc')
            ->paginate(15)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userBlacklist['last_page'],
            'current_page' => $userBlacklist['current_page'],
            'total'        => $userBlacklist['total'],
            'list'         => $userBlacklist['data']
        ]);
    }

    /**
     * 我的点赞
     * @param array $data
     * @return JsonResponse
     */
    public function getFollows(array $data): JsonResponse
    {
        $data['type'] = $data['type'] ?? 1;
        $userFollow = UserFollow::select([
            'id', 'followable_id', 'created_at'
        ])->where(['user_id' => request()->userinfo->id]);
        switch (intval($data['type'])) {
            //发现
            case 1:
                $userFollow = $userFollow->where(['followable_type' => UserDiscovery::class])
                    ->with([
                        'userDiscovery' => function ($query) {
                            $query->select(['id', 'title', 'user_id', 'issue', 'lotteryType', 'type'])
                                ->with(['user:id,nickname,avatar']);
                        }
                    ])
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //幽默猜测
            case 2:
                $userFollow = $userFollow->where(['followable_type' => Humorous::class])
                    ->with([
                        'humorou' => function ($query) {
                            $query->select([
                                'id', 'guessId', 'year', 'issue', 'title', 'imageUrl', 'width', 'height', 'lotteryType'
                            ]);
                        }
                    ])
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //高手论坛
            case 3:
                $userFollow = $userFollow->where(['followable_type' => Discuss::class])
                    ->with([
                        'discuss' => function ($query) {
                            $query->select(['id', 'title', 'user_id', 'lotteryType'])
                                ->with(['user:id,nickname,avatar']);
                        }
                    ])
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                break;

            //资料大全
            case 4:
                $userFollow = $userFollow->select([
                    'followable_type', 'followable_id', 'created_at'
                ])->where('followable_type', 'like', 'Modules\\\Api\\\Models\\\CorpusArticle%')
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userFollow['data'] as $key => &$item) {
                    $tableIdx = str_replace('Modules\Api\Models\CorpusArticle', '', $item['followable_type']);
                    if ($tableIdx < 1) {
                        unset($userFollow['data'][$key]);
                        continue;
                    }
                    $article = DB::table('corpus_articles' . $tableIdx . ' as ca')
                        ->select([
                            'ca.id', 'ca.corpusTypeId', 'ca.title', 'ct.lotteryType', 'users.nickname', 'users.avatar'
                        ])
                        ->where('ca.id', $item['followable_id'])
                        ->leftJoin('users', 'users.id', '=', 'ca.user_id')
                        ->leftJoin('corpus_types as ct', 'ca.corpusTypeId', '=', 'ct.id')
                        ->first();
                    $article->created_at = $item['created_at'];
                    $item = $article;
                }
                break;

            //六合图库
            case 5:
                $userFollow = $userFollow->where(['followable_type' => PicDetail::class])
                    ->with('picDetail:id,pictureTypeId,color,keyword,year,issue,pictureName,pictureId,lotteryType,created_at')
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                $userFollow['data'] = array_map(function ($data) {
                    $picDetail = &$data['pic_detail'];
                    if ($picDetail) {
                        $picDetail['picurl'] = $this->getPicUrl($picDetail['color'], $picDetail['issue'], $picDetail['keyword'], $picDetail['lotteryType'], 'jpg', $picDetail['year']);
                    }
                    return $data;
                }, $userFollow['data']);
                break;
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userFollow['last_page'],
            'current_page' => $userFollow['current_page'],
            'total'        => $userFollow['total'],
            'list'         => array_values($userFollow['data'])
        ]);
    }

    /**
     * 我的评论
     * @param array $data
     * @return JsonResponse
     */
    public function getComment(array $data): JsonResponse
    {
        $data['type'] = $data['type'] ?? 1;
        $userComment = DB::table('user_comments')->select(['user_id', 'commentable_id', 'commentable_type'])
            ->where(['user_id' => request()->userinfo->id, 'level' => 1]);
        switch (intval($data['type'])) {
            //发现
            case 1:
                $userComment = $userComment->where(['commentable_type' => UserDiscovery::class])
                    ->distinct()
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userComment['data'] as $key => &$item) {
                    $userDiscovery = UserDiscovery::select([
                        'id', 'title', 'user_id', 'issue', 'lotteryType', 'type', 'created_at'
                    ])
                        ->with('user:id,nickname,avatar')
                        ->find($item->commentable_id);
                    if (!$userDiscovery) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $userDiscovery['comment'] = UserComment::select(['id', 'user_id', 'content', 'created_at'])
                        ->where([
                            'user_id'        => request()->userinfo->id, 'level' => 1,
                            'commentable_id' => $item->commentable_id, 'commentable_type' => $item->commentable_type
                        ])
                        ->with('user:id,nickname,avatar')->get();
                    $item = $userDiscovery;
                }
                break;

            //幽默猜测
            case 2:
                $userComment = $userComment->where(['commentable_type' => Humorous::class])
                    ->distinct()
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userComment['data'] as $key => &$item) {
                    $humorous = Humorous::select([
                        'id', 'guessId', 'year', 'issue', 'title', 'imageUrl', 'lotteryType', 'created_at'
                    ])
                        ->find($item->commentable_id);
                    if (!$humorous) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $humorous['comment'] = UserComment::select(['id', 'user_id', 'content', 'created_at'])
                        ->where([
                            'user_id'        => request()->userinfo->id, 'level' => 1,
                            'commentable_id' => $item->commentable_id, 'commentable_type' => $item->commentable_type
                        ])
                        ->with('user:id,nickname,avatar')->get();
                    $item = $humorous;
                }
                break;

            //高手论坛
            case 3:
                $userComment = $userComment->where(['commentable_type' => Discuss::class])
                    ->distinct()
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userComment['data'] as $key => &$item) {
                    $discuss = Discuss::select(['id', 'title', 'user_id', 'lotteryType', 'created_at'])
                        ->with('user:id,nickname,avatar')
                        ->find($item->commentable_id);
                    if (!$discuss) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $discuss['comment'] = UserComment::select(['id', 'user_id', 'content', 'created_at'])
                        ->where([
                            'user_id'        => request()->userinfo->id, 'level' => 1,
                            'commentable_id' => $item->commentable_id, 'commentable_type' => $item->commentable_type
                        ])
                        ->with('user:id,nickname,avatar')->get();
                    $item = $discuss;
                }
                break;

            //资料大全
            case 4:
                $userComment = DB::table('user_comments')
                    ->select(['user_id', 'commentable_id', 'commentable_type'])
                    ->where(['user_id' => request()->userinfo->id, 'level' => 1])
                    ->where('commentable_type', 'like', 'Modules\\\Api\\\Models\\\CorpusArticle%')
                    ->distinct()
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userComment['data'] as $key => &$item) {
                    $tableIdx = str_replace('Modules\Api\Models\CorpusArticle', '', $item->commentable_type);
                    if ($tableIdx < 1) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $article = DB::table('corpus_articles' . $tableIdx . ' as ca')
                        ->select([
                            'ca.id', 'ca.corpusTypeId', 'ca.title', 'ca.user_id', 'ca.corpusTypeId', 'ca.created_at',
                            'users.nickname', 'users.avatar', 'ct.lotteryType'
                        ])
                        ->leftJoin('users', 'users.id', '=', 'ca.user_id')
                        ->leftJoin('corpus_types as ct', 'ca.corpusTypeId', '=', 'ct.id')
                        ->where(['ca.id' => $item->commentable_id])
                        ->first();
                    if (!$article) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $article->comment = UserComment::select(['id', 'user_id', 'content', 'created_at'])
                        ->where([
                            'user_id'        => request()->userinfo->id, 'level' => 1,
                            'commentable_id' => $item->commentable_id, 'commentable_type' => $item->commentable_type
                        ])
                        ->with('user:id,nickname,avatar')->get();
                    $item = $article;
                }
                break;

            //六合图库
            case 5:
                $userComment = $userComment->where(['commentable_type' => PicDetail::class])
                    ->distinct()
                    ->orderByDesc('created_at')
                    ->paginate(25)
                    ->toArray();
                foreach ($userComment['data'] as $key => &$item) {
                    $picDetail = PicDetail::select([
                        'id', 'pictureTypeId', 'color', 'keyword', 'year', 'issue', 'pictureName', 'pictureId',
                        'lotteryType', 'created_at'
                    ])
                        ->find($item->commentable_id);
                    if (!$picDetail) {
                        unset($userComment['data'][$key]);
                        continue;
                    }
                    $picDetail['picurl'] = $this->getPicUrl($picDetail['color'], $picDetail['issue'], $picDetail['keyword'], $picDetail['lotteryType'], 'jpg', $picDetail['year']);
                    $picDetail['comment'] = UserComment::select(['id', 'user_id', 'content', 'created_at'])
                        ->where([
                            'user_id'        => request()->userinfo->id, 'level' => 1,
                            'commentable_id' => $item->commentable_id, 'commentable_type' => $item->commentable_type
                        ])
                        ->with('user:id,nickname,avatar')->get();
                    $item = $picDetail;
                }
                break;
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userComment['last_page'],
            'current_page' => $userComment['current_page'],
            'total'        => $userComment['total'],
            'list'         => array_values($userComment['data'])
        ]);
    }

    /**
     * 我的收藏
     * @param array $data
     * @return JsonResponse
     */
    public function getCollect(array $data): JsonResponse
    {
        $data['type'] = $data['type'] ?? 1;
        $userCollec = UserCollect::select(['id', 'collectable_id', 'created_at'])
            ->where(['user_id' => request()->userinfo->id]);
        switch (intval($data['type'])) {
            //六合图库
            case 1:
                $userCollec = UserCollect::select(['pic_details.id', 'collectable_id', 'user_collects.created_at', 'pictureTypeId', 'pictureName', 'lotteryType'])
                    ->where(['user_id' => request()->userinfo->id])
                    ->where('collectable_type', 'Modules\\Api\\Models\\PicDetail');
                if (isset($data['lotteryType'])) {
                    $userCollec = $userCollec->where('pic_details.lotteryType', $data['lotteryType']);
                }
                if (isset($data['keyword'])) {
                    $userCollec = $userCollec->where('pic_details.pictureName', 'like', '%' . $data['keyword'] . '%');
                }
                $userCollec = $userCollec->join('pic_details', 'user_collects.collectable_id', '=', 'pic_details.id')
                    ->groupBy('pictureTypeId')
                    ->paginate(25)
                    ->toArray();
//                $userCollec = $userCollec->where(['collectable_type' => PicDetail::class])
//                    ->with('picDetail:id,pictureTypeId,color,keyword,year,issue,pictureName,pictureId,lotteryType,created_at')
//                    ->whereHas('picDetail', function ($query) use ($data) {
//                        if (isset($data['lotteryType'])) {
//                            $query = $query->where('lotteryType', $data['lotteryType']);
//                        }
//                        if (isset($data['keyword'])) {
//                            $query->where('pictureName', 'like', '%' . $data['keyword'] . '%');
//                        }
//                    })
//                    ->orderBy('sorts')
//                    ->paginate(25)
//                    ->toArray();
//                dd($userCollec);
                $userCollec['data'] = array_map(function ($data) {
                    if (!empty($data['pictureTypeId'])) {
                        $picDetail = PicDetail::query()->select('id', 'pictureTypeId', 'color', 'keyword', 'year', 'issue', 'pictureName', 'pictureId', 'lotteryType', 'created_at')->where(['pictureTypeId' => $data['pictureTypeId'], 'lotteryType' => $data['lotteryType']])->orderByDesc('id')->first();
                        if ($picDetail) {
                            $picDetail = $picDetail->toArray();
                            $picDetail['picurl'] = $this->getPicUrl($picDetail['color'], $picDetail['issue'], $picDetail['keyword'], $picDetail['lotteryType'], 'jpg', $picDetail['year']);
                            $data['pic_detail'] = $picDetail;
                            unset($data['pictureTypeId']);
                            unset($data['pictureName']);
                            unset($data['lotteryType']);
                        }
                    }
//                    $picDetail = &$data['pic_detail'];
//                    if ($picDetail) {
//                        $picDetail['picurl'] = $this->getPicUrl($picDetail['color'], $picDetail['issue'], $picDetail['keyword'], $picDetail['lotteryType'], 'jpg', $picDetail['year']);
//                    }
                    return $data;
                }, $userCollec['data']);
                break;

            //幽默猜测
            case 2:
                $userCollec = $userCollec->where(['collectable_type' => Humorous::class])
                    ->with('humorou:id,guessId,year,issue,title,imageUrl,width,height,lotteryType,created_at')
                    ->whereHas('humorou', function ($query) use ($data) {
                        if (isset($data['lotteryType'])) {
                            $query = $query->where('lotteryType', $data['lotteryType']);
                        }
                        if (isset($data['keyword'])) {
                            $query->where('title', 'like', '%' . $data['keyword'] . '%');
                        }
                    })
                    ->orderBy('sorts')
                    ->paginate(25)
                    ->toArray();
                break;

            //发现
            case 3:
                $userCollec = $userCollec->where(['collectable_type' => UserDiscovery::class])
                    ->with([
                        'userDiscovery' => function ($query) {
                            $query->select(['id', 'user_id', 'title', 'issue', 'lotteryType', 'type', 'created_at'])
                                ->with('images:imageable_id,img_url,width,height');
                        }
                    ])
                    ->whereHas('userDiscovery', function ($query) use ($data) {
                        if (isset($data['lotteryType'])) {
                            $query = $query->where('lotteryType', $data['lotteryType']);
                        }
                        if (isset($data['keyword'])) {
                            $query->where('title', 'like', '%' . $data['keyword'] . '%');
                        }
                    })
                    ->orderBy('sorts')
                    ->paginate(25)
                    ->toArray();
                break;

        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userCollec['last_page'],
            'current_page' => $userCollec['current_page'],
            'total'        => $userCollec['total'],
            'list'         => $userCollec['data']
        ]);
    }

    /**
     * 收藏设置
     * @param array $data
     * @return JsonResponse
     */
    public function setCollect(array $data): JsonResponse
    {
        $userCollect = UserCollect::where(['id' => $data['id'], 'user_id' => request()->userinfo->id]);
        if (isset($data['sorts'])) {
            $userCollect->update(['sorts' => $data['sorts']]);
        }
        if (isset($data['remarks'])) {
            $userCollect->update(['remarks' => $data['remarks']]);
        }
        return $this->apiSuccess(ApiMsgData::POST_API_SUCCESS);
    }

    /**
     * 关注用户
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function setFocus(array $data): JsonResponse
    {
        if (request()->userinfo->id == $data['id']) {
            throw new CustomException(['message' => '禁止关注自己！']);
        }
        $userFocuson = UserFocuson::where([
            'user_id' => request()->userinfo->id, 'to_userid' => intval($data['id'])
        ])->first();
        if ($userFocuson) {
            $userFocuson->delete();
            User::where(['id' => intval($data['id'])])->decrement('fans');
            User::where(['id' => request()->userinfo->id])->decrement('follows');
            return $this->apiSuccess(ApiMsgData::FOCUS_API_CANCEL);
        } else {
            $userFocuson = new UserFocuson;
            $userFocuson->user_id = request()->userinfo->id;
            $userFocuson->to_userid = $data['id'];
            $userFocuson->save();
            User::where(['id' => intval($data['id'])])->increment('fans');
            User::where(['id' => request()->userinfo->id])->increment('follows');
            return $this->apiSuccess(ApiMsgData::FOCUS_API_SUCCESS);
        }
    }

    /**
     * 关注列表
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function getFocus(array $data): JsonResponse
    {
        if (!empty($data['id'])) { //他人访问个人主页
            $whereArr['user_id'] = intval($data['id']);
        } else { // 自己访问主页
            if ($this->userinfo()) {
                $whereArr['user_id'] = $this->userinfo()->id;
            } else {
                throw new CustomException(['message' => '请登录！']);
            }
        }
        $userFocuson = UserFocuson::select(['to_userid', 'created_at'])
            ->where($whereArr)
            ->with([
                'user' => function ($query) {
                    return $query->select(['id', 'nickname', 'avatar']);
                }
            ]);
        if (isset($data['keywords'])) {
            $userFocuson = $userFocuson->whereHas('user', function (Builder $query) use ($data) {
                $query->where('nickname', 'like', '%' . $data['keywords'] . '%');
            });
        }
        $userFocuson = $userFocuson->orderBy('created_at', 'desc')
            ->paginate(25)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userFocuson['last_page'],
            'current_page' => $userFocuson['current_page'],
            'total'        => $userFocuson['total'],
            'list'         => $userFocuson['data']
        ]);
    }

    /**
     * 粉丝列表
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function getFans(array $data): JsonResponse
    {
        if (!empty($data['id'])) { //他人访问个人主页
            $whereArr['to_userid'] = intval($data['id']);
        } else { // 自己访问主页
            if ($this->userinfo()) {
                $whereArr['to_userid'] = $this->userinfo()->id;
            } else {
                throw new CustomException(['message' => '请登录！']);
            }
        }
        $userFocuson = UserFocuson::select(['user_id', 'to_userid', 'created_at'])
            ->where($whereArr)
            ->with([
                'fromuser' => function ($query) {
                    return $query->select(['id', 'nickname', 'avatar']);
                }
            ]);
        if (isset($data['keywords'])) {
            $userFocuson = $userFocuson->whereHas('fromuser', function (Builder $query) use ($data) {
                $query->where('nickname', 'like', '%' . $data['keywords'] . '%');
            });
        }
        $userFocuson = $userFocuson->orderBy('created_at', 'desc')
            ->paginate(25)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userFocuson['last_page'],
            'current_page' => $userFocuson['current_page'],
            'total'        => $userFocuson['total'],
            'list'         => $userFocuson['data']
        ]);
    }

    /**
     * 小黑屋列表
     * @param array $data
     * @return JsonResponse
     */
    public function getBlackHouse(array $data): JsonResponse
    {
        $userMushin = UserMushin::select([
            'user_id', 'mushin_id', 'mushin_start_date', 'mushin_end_date', 'mushin_days', 'is_forever', 'created_at'
        ])
            ->where(function ($query) {
                $query->where('mushin_end_date', '>', date('Y-m-d H:i:s'))->orWhere('is_forever', 1);
            })
            ->orderByDesc('created_at')
            ->with('user:id,nickname,avatar');
        if (isset($data['keywords'])) {
            $userMushin = $userMushin->whereHas('user', function (Builder $query) use ($data) {
                $query->where('nickname', 'like', '%' . $data['keywords'] . '%');
            });
        }
        $userMushin = $userMushin->with('mushin:id,name')
            ->paginate(25)
            ->toArray();
        $userMushin['data'] = array_map(function ($data) {
            $data['count'] = UserMushin::where('user_id', $data['user_id'])->count();
            return $data;
        }, $userMushin['data']);
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userMushin['last_page'],
            'current_page' => $userMushin['current_page'],
            'total'        => $userMushin['total'],
            'list'         => $userMushin['data']
        ]);
    }

    /**
     * 小黑屋个人记录
     * @return JsonResponse
     * @throws CustomException
     */
    public function getUserBlackHouse(): JsonResponse
    {
        $userMushin = UserMushin::select([
            'user_id', 'mushin_id', 'mushin_start_date', 'mushin_end_date', 'mushin_days', 'is_forever', 'created_at'
        ])
            ->where([['user_id', request()->userinfo->id]])
            ->where(function ($query) {
                $query->where('mushin_end_date', '>', date('Y-m-d H:i:s', time()))->orWhere('is_forever', 1);
            })
            ->with('mushin:id,name')
            ->orderByDesc('created_at')
            ->first();
        if (!$userMushin) {
            throw new CustomException(['message' => '无']);
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $userMushin->toArray());
    }

    /**
     * 用户等级
     * @return JsonResponse
     */
    public function getLevel(): JsonResponse
    {
        $level = Level::where('status', 1)->orderBy('scores')->get()->toArray();
        $userInfo = [
            'level_name'   => $this->userLevel($this->userinfo()->growth_score)['level_name'],
            'growth_score' => $this->userinfo()->growth_score
        ];
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [$userInfo, $level]);
    }

    /**
     * 成长值列表
     * @return JsonResponse
     */
    public function getGrowthScore(): JsonResponse
    {
        $userGrowthScore = UserGrowthScore::where('user_id', request()->userinfo->id)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->toArray();
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page'    => $userGrowthScore['last_page'],
            'current_page' => $userGrowthScore['current_page'],
            'total'        => $userGrowthScore['total'],
            'growth_score' => $this->userinfo()->growth_score,
            'list'         => $userGrowthScore['data']
        ]);
    }

    /**
     * 签到
     * @return JsonResponse
     * @throws CustomException
     */
    public function signIn(): JsonResponse
    {
        DB::beginTransaction();
        $signIn = UserSignin::query()->where([
                    ['user_id', request()->userinfo->id], ['created_at', '>', date('Y-m-d')]
                ])
            ->lockForUpdate()
            ->first();
        if (!$signIn) {
            $signInDaySpecies = json_decode(AuthActivityConfig::where('k', 'signin_day_species')->value('v'), true);
            $number = 1;
            $flowers = $signInDaySpecies[0];
            $prevSignIn = UserSignin::where([
                ['user_id', request()->userinfo->id], ['created_at', '>', date('Y-m-d', strtotime('-1 day'))]
            ])->first();
            if ($prevSignIn) {
                $number = $prevSignIn->number + 1;
                if ($number > count($signInDaySpecies)) {
                    $number = 1;
                    $key = 0;
                } else {
                    $key = $number - 1;
                }
                $flowers = $signInDaySpecies[$key];
            }
            UserSignin::create(['user_id' => request()->userinfo->id, 'number' => $number]);
            User::query()->where('id', request()->userinfo->id)->increment('account_balance', $flowers);
            if ($flowers) {
                (new ActivityService())->modifyAccount(request()->userinfo->id, 'user_sign', $flowers);
            }
        } else {
            DB::rollBack();
            throw new CustomException(['message' => '今日已签到']);
        }
        DB::commit();
        return $this->apiSuccess('签到成功');
    }

    /**
     * 分享排行榜
     * @return JsonResponse
     */
    public function shareList()
    {
        $userList = User::select(['id', 'nickname', 'avatar', 'forward'])
            ->where(['is_lock' => 2, 'status' => 1, 'system' => 0])
            ->orderByDesc('forward')
            ->limit(100)
            ->get()
            ->toArray();
        $userInfo = [];
        if ($this->userinfo()) {
            $userInfo = [
                'id'       => $this->userinfo()->id,
                'nickname' => $this->userinfo()->nickname,
                'avatar'   => $this->userinfo()->avatar,
                'forward'  => $this->userinfo()->forward,
                'rank'     => 0
            ];
            foreach ($userList as $key => $item) {
                if ($item['id'] == $userInfo['id']) {
                    $userInfo['rank'] = $key + 1;
                    break;
                }
            }
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['info' => $userInfo, 'list' => $userList]);
    }

    /**
     * 粉丝排行榜
     * @return JsonResponse
     */
    public function fanList(): JsonResponse
    {
        $userList = User::select(['id', 'nickname', 'avatar', 'fans'])
            ->where(['is_lock' => 2, 'status' => 1, 'system' => 0])
            ->orderByDesc('fans')
            ->limit(100)
            ->get()
            ->toArray();

        $userInfo = [];
        if ($this->userinfo()) {
            $userInfo = [
                'id'       => $this->userinfo()->id,
                'nickname' => $this->userinfo()->nickname,
                'avatar'   => $this->userinfo()->avatar,
                'fans'     => $this->userinfo()->fans,
                'rank'     => 0
            ];
            foreach ($userList as $key => $item) {
                if ($item['id'] == $userInfo['id']) {
                    $userInfo['rank'] = $key + 1;
                    break;
                }
            }
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['info' => $userInfo, 'list' => $userList]);
    }

    /**
     * 等级排行榜
     * @return JsonResponse
     */
    public function rankList(): JsonResponse
    {
        $userList = User::select(['id', 'nickname', 'avatar', 'growth_score'])
            ->where(['is_lock' => 2, 'status' => 1, 'system' => 0])
            ->orderByDesc('growth_score')
            ->limit(100)
            ->get()
            ->toArray();
        $userInfo = [];
        if ($this->userinfo()) {
            $userInfo = [
                'id'           => $this->userinfo()->id,
                'nickname'     => $this->userinfo()->nickname,
                'avatar'       => $this->userinfo()->avatar,
                'growth_score' => $this->userLevel($this->userinfo()->growth_score)['level_name'],
                'rank'         => 0
            ];
            foreach ($userList as $key => &$item) {
                if ($item['id'] == $userInfo['id']) {
                    $userInfo['rank'] = $key + 1;
                }
                $item['growth_score'] = $this->userLevel($item['growth_score'])['level_name'];
            }
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['info' => $userInfo, 'list' => $userList]);

    }

    /**
     * 礼物排行榜
     * @return JsonResponse
     */
    public function goldList(): JsonResponse
    {
        $userList = User::select(['id', 'nickname', 'avatar', 'account_balance'])
            ->where(['is_lock' => 2, 'status' => 1, 'system' => 0])
            ->orderByDesc('account_balance')
            ->limit(100)
            ->get()
            ->toArray();
        $userInfo = [];
        if ($this->userinfo()) {
            $userInfo = [
                'id'              => $this->userinfo()->id,
                'nickname'        => $this->userinfo()->nickname,
                'avatar'          => $this->userinfo()->avatar,
                'account_balance' => $this->userinfo()->account_balance,
                'rank'            => 0
            ];
            foreach ($userList as $key => $item) {
                if ($item['id'] == $userInfo['id']) {
                    $userInfo['rank'] = $key + 1;
                    break;
                }
            }
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['info' => $userInfo, 'list' => $userList]);
    }

    /**
     * 补充邀请码
     * @param string $invite_code
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function fillInviteCode(string $invite_code): JsonResponse
    {
        try {
            DB::beginTransaction();
            $userInfo = User::query()->where('invite_code', strtoupper($invite_code))->select(['id'])->firstOrFail();
            if ($userInfo['id'] == auth('user')->id()) {
                throw new CustomException(['message' => '不能使用自己的邀请码']);
            }
            $inviteCodeCheck = Invitation::query()
                ->where(['to_userid' => auth('user')->id()])
                ->lockForUpdate()
                ->first();
            if ($inviteCodeCheck) {
                throw new CustomException(['message' => '您的邀请码已存在']);
            }
            // 一天只能邀请5人有福利
            $counts = Invitation::query()
                ->lockForUpdate()
                ->where('user_id', $userInfo['id'])
                ->whereDate('created_at', date('Y-m-d'))
                ->count('id');
            // 给邀请人加 【福利中心】
            // 增加拉新彩金
            $register_at = User::query()->where('id', auth('user')->id())->value('register_at');
            if (Invitation::query()->where('user_id', $userInfo['id'])->count('id')<20) {
                if ((strtotime($register_at) + 24*60*60) > time() && $counts<5) {
                    $welfareData = [];
                    $welfareData['user_id'] = [$userInfo['id']];
                    $welfareData['name'] = "平台拉新福利";
                    $welfareData['is_random'] = 0;
                    $welfareData['really_money'] = 4.99;
                    $welfareData['is_limit_time'] = 1;
                    $welfareData['status'] = 0;
                    $welfareData['is_send_msg'] = 1;
                    $welfareData['msg']['title'] = '邀请得彩金';
                    $welfareData['msg']['content'] = "恭喜您在66宝典成功邀请一位新用户，66宝典已将彩金发送至您的福利中心，七日内有效哦～66宝典祝您生活愉快！";
                    $welfareData['valid_receive_date'] = [
                        \Carbon\Carbon::now()->toDateString(), Carbon::now()->addDays(6)->toDateString()
                    ];
                    (new UserWelfareService())->store($welfareData);
                }
            }

            // 给被邀请人加【存入账户】下级
            $novice_invitation_species = AuthActivityConfig::query()->where('k', 'novice_invitation_species')->value('v');
            User::query()->where('id', auth('user')->id())->increment('account_balance', $novice_invitation_species, ['filling_gift'=>1]);
            (new ActivityService())->modifyAccount(auth('user')->id(), 'filling_gift', $novice_invitation_species);
            Invitation::query()->insert([
                'user_id' => $userInfo['id'],
                'to_userid' => auth('user')->id(),
                'level' => 1,
                'money' => $novice_invitation_species
            ]);
            $this->addScore($invite_code);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw new CustomException(['message' => '邀请码不存在']);
        }
        DB::commit();
        return $this->apiSuccess(ApiMsgData::ADD_API_SUCCESS);
    }

    /**
     * 获取会员福利统计
     * @return JsonResponse
     */
    public function getUserWelfareCount(): JsonResponse
    {
        $userId = auth('user')->id();
        $result = DB::table('user_welfares')
            ->select('user_id', DB::raw('COUNT(*) as total_records'), DB::raw('SUM(really_money) as total_really_money'))
            ->where('user_id', $userId)
            ->where('status', 1)
            ->first();

        return $this->apiSuccess('', [
            'total' => $result->total_records,
            'sum'   => $result->total_really_money ?? 0
        ]);
    }

    /**
     * 获取会员福利列表
     * @param $params
     * @return JsonResponse
     */
    public function getUserWelfare($params): JsonResponse
    {
        $userId = auth('user')->id();
        $list = UserWelfare::query()
            ->where('user_id', $userId)
            ->select([
                'id', 'name', 'user_id', 'order_num', 'valid_receive_date', 'status', 'random_money', 'is_random',
                'really_money', 'created_at'
            ])
            ->latest()->simplePaginate($params['limit'])->toArray();
        foreach ($list['data'] as $k => $v) {
            if ($v['is_random'] == 1 && $v['status'] != 1) { // 随机 但 未领取
                unset($list['data'][$k]['really_money']);
            }
        }

        return $this->apiSuccess('', $list['data']);
    }

    /**
     * 会员福利领取
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function userWelfareReceive($params): JsonResponse
    {
        try {
            DB::beginTransaction();
            $userId = auth('user')->id();
            $list = UserWelfare::query()
                ->where('user_id', $userId)
                ->where('id', $params['id'])
                ->where('status', 0)
                ->select(['really_money', 'status', 'valid_receive_date'])
                ->lockForUpdate()
                ->firstOrFail()->toArray();
            $date = date('Y-m-d');
            if ($list['valid_receive_date'][0] != 0) {
                if ($list['valid_receive_date'][0] > $date) {
                    throw new CustomException(['message' => '请耐心等待，还没到领取时间']);
                }
                if ($list['valid_receive_date'][1] < $date) {
                    UserWelfare::query()->where('id', $params['id'])->update(['status' => -1]);
                    DB::commit();
                    throw new CustomException(['message' => '已过期']);
                }
            }

            UserWelfare::query()->where('id', $params['id'])->update([
                'status' => 1, 'receive_date' => date("Y-m-d H:i:s")
            ]);
            User::query()->where('id', $userId)->increment('account_balance', $list['really_money']);
            // 金币记录
            (new ActivityService())->modifyAccount($userId, 'user_welfare', $list['really_money'], $params['id']);
            DB::commit();
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '记录不存在']);
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof CustomException) {
                throw new CustomException(['message' => $exception->getMessage()]);
            } else {
                throw new CustomException(['message' => '领取失败']);
            }
        }

        return $this->apiSuccess(ApiMsgData::RECEIVE_API_SUCCESS, [
            'really_money' => $list['really_money']
        ]);
    }

    /**
     * 金币记录
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function golds($params): JsonResponse
    {
        $date = $params['date'] ?? [];
        $type = $params['type'] ?? '';
        $list = UserGoldRecord::query()->where('user_id', auth('user')->id())
            ->when($type, function ($query) use ($type) {
                $query->select('type', $type);
            })
            ->when($date, function ($query) use ($date) {
                $startDate = null;
                $endDate = null;
                if (in_array($date, [1, 7, 30, 90])) {
                    $startDate = Carbon::today()->subDays($date - 1)->setTime(00, 00, 00)->toDateTimeString();
                    $endDate = Carbon::today()->setTime(23, 59, 59)->toDateTimeString();
                } else if (is_array($date)) {
                    try {
                        $startDate = Carbon::parse($date[0])->setTime(00, 00, 00)->toDateTimeString();
                        $endDate = Carbon::parse($date[1])->setTime(23, 59, 59)->toDateTimeString();
                    } catch (\Exception $exception) {
                        throw new CustomException(['message' => ApiMsgData::INVALID_DATE]);
                    }
                }
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            })
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'account_name', 'nickname']);
                }
            ])
            ->latest()
            ->simplePaginate($params['limit'])->toArray();

        return $this->apiSuccess('', $list['data']);
    }

    /**
     * 会员交易记录
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function records($params): JsonResponse
    {
        $zhuanZhangArrType = [22, 23, 24, 25, 26, 27, 28, 29];
        $date = $params['date'] ?? [];
        if (!in_array($params['type'], [1, 2, 3, 4, 5])) {
            throw new CustomException(['message' => 'type参数不允许']);
        }
        if ($params['type'] == 1) { // 提现
            $model = UserPlatWithdraw::query();
        } else if ($params['type'] == 2) { // 充值
            $model = UserPlatRecharge::query();
        } else if ($params['type'] == 3) { // 福利
            $model = UserWelfare::query();
        } else if ($params['type'] == 5)  {    // 转账
            $model = UserGoldRecord::query()->whereIn('type', $zhuanZhangArrType);
        } else {    // 其它
            $model = UserGoldRecord::query();
        }
        $list = $model
            ->where('user_id', auth('user')->id())
            ->when($date, function ($query) use ($date, $params) {
                $startDate = null;
                $endDate = null;
                if (in_array($date, [-1, 1, 7, 30])) {
                    if ($date == -1) {
                        $startDate = Carbon::today()->subDays(+1)->setTime(00, 00, 00)->toDateTimeString();
                        $endDate = Carbon::today()->subDays(+1)->setTime(23, 59, 59)->toDateTimeString();
                    } else if ($date == 1) {
                        $startDate = Carbon::today()->subDays($date - 1)->setTime(00, 00, 00)->toDateTimeString();
                        $endDate = Carbon::today()->setTime(23, 59, 59)->toDateTimeString();
                    } else {
                        $startDate = Carbon::today()->subDays($date - 1)->setTime(00, 00, 00)->toDateTimeString();
                        $endDate = Carbon::today()->setTime(23, 59, 59)->toDateTimeString();
                    }
                } else if (is_array($date)) {
                    try {
                        $startDate = Carbon::parse($date[0])->setTime(00, 00, 00)->toDateTimeString();
                        $endDate = Carbon::parse($date[1])->setTime(23, 59, 59)->toDateTimeString();
                    } catch (\Exception $exception) {
                        throw new CustomException(['message' => ApiMsgData::INVALID_DATE]);
                    }
                }
                if ($startDate && $endDate) {
                    if ($params['type'] == 3) {
                        $query->whereBetween('receive_date', [$startDate, $endDate]);
                    } else {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                }
            })
            ->when($params['type'] == 1 || $params['type'] == 2, function ($query) {
                $query->with([
                    'plats' => function ($query) {
                        $query->select(['id', 'name']);
                    }
                ]);
            })
            ->when($params['type'] == 3, function ($query) { // 福利=>已领取
                $query->where('status', 1);
            })
            ->when($params['type'] == 4, function ($query) { // 活动=>已领取
                // 类型：1帖子点赞；2评论帖子；3转发帖子；4在线时长；5补填邀请码；6注册金币；7分享好友；8签到；9充值（平台->图库）；10提现（plat_withdraw）；11福利；12撤回【充值】
                $query
                    ->with(['bet'=>function($query) {
                        $query->select(['id', 'win_status', 'status']);
                    }])
                    ->whereIn('type', [1, 2, 3, 4, 5, 6, 7, 8, 13, 14, 15, 16, 18, 19, 20, 21]);
            })
            ->latest()
            ->simplePaginate($params['limit'])->toArray();
        if (!$list['data']) {
            return $this->apiSuccess();
        }
        $data = [];
        if (in_array($params['type'], [1, 2, 3])) {
            foreach ($list['data'] as $k => $v) {
                $data[$k]['id'] = $v['id'];
                $data[$k]['user_id'] = $v['user_id'];
                if ($params['type'] == 1) {
                    if ($v['status']==1 || $v['status']==0) {
                        $data[$k]['symbol'] = '-';
                    }else if ($v['status']==-1 || $v['status']==-2) {
                        $data[$k]['symbol'] = '+';
                    } else {
                        $data[$k]['symbol'] = '-';
                    }
                } else if ($params['type'] == 2) {
                    $data[$k]['symbol'] = '+';
                }
                if ($params['type'] == 3) {
                    $data[$k]['symbol'] = '+';
                    $data[$k]['name'] = '福利';
                    $data[$k]['sub_name'] = $v['name'];
                    $data[$k]['money'] = $v['really_money'];
                    $data[$k]['trade_no'] = $v['order_num'];
                    $data[$k]['created_at'] = $v['receive_date'];
                } else {
                    $data[$k]['name'] = $params['type'] == 1 ? '提现' : '充值';
                    $data[$k]['sub_name'] = $params['type'] == 1 ? '提现（' . $v['plats']['name'] . '）' : '充值（' . $v['plats']['name'] . '）';
                    $data[$k]['money'] = $v['final_money'];
                    $data[$k]['trade_no'] = $v['trade_no'];
                    $data[$k]['created_at'] = $v['created_at'];
                }
                $data[$k]['status'] = $v['status'];
                $data[$k]['plats'] = $v['plats'] ?? [];
            }
        } else {
            foreach ($list['data'] as $k => $v) {
                $data[$k]['id'] = $v['id'];
                $data[$k]['user_id'] = $v['user_id'];
                $data[$k]['name'] = in_array($v['type'], $zhuanZhangArrType) ? '转账' : '其它';
                if ($v['type'] == 1) {
                    $data[$k]['sub_name'] = '点赞帖子';
                } else if ($v['type'] == 2) {
                    $data[$k]['sub_name'] = '评论帖子';
                } else if ($v['type'] == 3) {
                    $data[$k]['sub_name'] = '转发帖子';
                } else if ($v['type'] == 4) {
                    $data[$k]['sub_name'] = '在线时长';
                } else if ($v['type'] == 5) {
                    $data[$k]['sub_name'] = '补填邀请码';
                } else if ($v['type'] == 6) {
                    $data[$k]['sub_name'] = '注册赠送金币';
                } else if ($v['type'] == 7) {
                    $data[$k]['sub_name'] = '分享好友';
                } else if ($v['type'] == 8) {
                    $data[$k]['sub_name'] = '签到';
                } else if ($v['type'] == 13) {
                    if (isset($v['bet'])) {
                        if ($v['bet']['status']==2) {
                            $v['symbol'] = '+';
                        }
                        $data[$k]['sub_name'] = '投注【'.($v['bet']['status'] == -1 ? '未中奖】' : ($v['bet']['status'] == 0 ? '下单】' : ($v['bet']['status'] == 2 ? '和局】' : '下单】')));
                    } else {
                        $data[$k]['sub_name'] = '投注【下单】';
                    }
                } else if ($v['type'] == 14) {
                    if (isset($v['bet'])) {
//                        $data[$k]['sub_name'] = '中奖【'.($v['bet']['win_status'] == 1 ? '未入账】' : '已入账】');
                        $data[$k]['sub_name'] = '投注【'.($v['bet']['status'] == -1 ? '未中奖】' : ($v['bet']['status'] == 2 ? '和局】' : '中奖').'【'.($v['bet']['win_status'] == 1 ? '未入账】' : ($v['bet']['win_status'] == -2 ? '和局】' : '已入账】')));
                    } else {
                        $data[$k]['sub_name'] = '投注【中奖】';
                    }
                } else if ($v['type'] == 15) {
                    $data[$k]['sub_name'] = '投注【撤单】';
                } else if ($v['type'] == 16) {
                    $data[$k]['sub_name'] = '投注【系统撤回】';
                } else if ($v['type'] == 18) {
                    $data[$k]['sub_name'] = '领取红包【聊天室】';
                } else if ($v['type'] == 19) {
                    $data[$k]['sub_name'] = '领取红包【五福红包】';
                } else if ($v['type'] == 20) {
                    $data[$k]['sub_name'] = '打赏';
                } else if ($v['type'] == 21) {
                    $data[$k]['sub_name'] = '发帖收益';
                } else if ($v['type'] == 22) {
                    $data[$k]['sub_name'] = 'PG电子转入【游戏】';
                } else if ($v['type'] == 23) {
                    $data[$k]['sub_name'] = 'PG电子转出【游戏】';
                } else if ($v['type'] == 24) {
                    $data[$k]['sub_name'] = 'IMOne电子转入【游戏】';
                } else if ($v['type'] == 25) {
                    $data[$k]['sub_name'] = 'IMOne电子转出【游戏】';
                } else if ($v['type'] == 26) {
                    $data[$k]['sub_name'] = '开元棋牌转入【游戏】';
                } else if ($v['type'] == 27) {
                    $data[$k]['sub_name'] = '开元棋牌转出【游戏】';
                } else if ($v['type'] == 28) {
                    $data[$k]['sub_name'] = 'PG2电子转入【游戏】';
                } else if ($v['type'] == 29) {
                    $data[$k]['sub_name'] = 'PG2电子转出【游戏】';
                }
                $data[$k]['money'] = $v['gold'];
                $data[$k]['trade_no'] = '';
                $data[$k]['symbol'] = $v['symbol'];
                $data[$k]['status'] = 1;
                $data[$k]['created_at'] = $v['created_at'];
                $data[$k]['plats'] = [];
            }
        }

        return $this->apiSuccess('', $data);
    }
}
