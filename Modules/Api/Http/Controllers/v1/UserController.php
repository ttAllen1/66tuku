<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\AdviceRequest;
use Modules\Api\Http\Requests\EditFundPasswordRequest;
use Modules\Api\Http\Requests\SetFundPasswordRequest;
use Modules\Api\Http\Requests\UserBlacklistRequest;
use Modules\Api\Http\Requests\UserCollectRequest;
use Modules\Api\Http\Requests\UserEditPassRequest;
use Modules\Api\Http\Requests\UserEditRequest;
use Modules\Api\Http\Requests\UserGetRequest;
use Modules\Api\Services\user\UserService;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Common\Exceptions\CustomException;

class UserController extends BaseApiController
{
    /**
     * 获取用户信息
     * @return JsonResponse
     */
    public function getUserInfo(): JsonResponse
    {
        return (new UserService())->getUserInfo();
    }

    /**
     * 修改用户信息
     * @param UserEditRequest $request
     * @return JsonResponse|null
     */
    public function editUserInfo(UserEditRequest $request): JsonResponse
    {
        return (new UserService())->editUserInfo($request->input());
    }

    /**
     * 修改用户信息
     * @param UserEditRequest $request
     * @return JsonResponse|null
     */
    public function editUserInfo_3(Request $request): JsonResponse
    {
        return (new UserService())->editUserInfo_3($request->only(['nickname', 'avatar']));
    }

    /**
     * 修改密码
     * @param UserEditPassRequest $request
     * @return JsonResponse
     */
    public function editUserPass(UserEditPassRequest $request): JsonResponse
    {
        return (new UserService())->editUserPass($request->only(['password', 'password_current']));
    }

    /**
     * 设置资金密码
     * @param SetFundPasswordRequest $request
     * @return JsonResponse
     */
    public function setFundPassword(SetFundPasswordRequest $request): JsonResponse
    {
        return (new UserService())->setFundPassword($request->only('password'));
    }

    /**
     * 修改资金密码
     * @param SetFundPasswordRequest $request
     * @return JsonResponse
     */
    public function editFundPassword(EditFundPasswordRequest $request): JsonResponse
    {
        return (new UserService())->editFundPassword($request->only('password'));
    }

    /**
     * 个人主页信息
     * @param UserGetRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function getUserIndex(UserGetRequest $request): JsonResponse
    {
        return (new UserService())->getUserIndex($request->input('id'));
    }

    /**
     * 用户发布的数据
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function release(Request $request): JsonResponse
    {
        return (new UserService())->release($request->input());
    }

    /**
     * 反馈列表
     * @return JsonResponse
     */
    public function getAdviceList(): JsonResponse
    {
        return (new UserService())->getAdviceList();
    }

    /**
     * 意见反馈
     * @param AdviceRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function addAdvice(AdviceRequest $request): JsonResponse
    {
        return (new UserService())->addAdvice($request->only(['title', 'content']));
    }

    /**
     * 拉黑用户
     * @param UserBlacklistRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function setBlacklist(UserBlacklistRequest $request): JsonResponse
    {
        return (new UserService())->setBlacklist($request->only('id'));
    }

    /**
     * 拉黑列表
     * @param Request $request
     * @return JsonResponse
     */
    public function getBlacklist(Request $request): JsonResponse
    {
        return (new UserService())->getBlacklist($request->input());
    }

    /**
     * 我的点赞
     * @param Request $request
     * @return JsonResponse
     */
    public function getFollows(Request $request): JsonResponse
    {
        return (new UserService())->getFollows($request->only('type'));
    }

    /**
     * 我的评论
     * @param Request $request
     * @return JsonResponse
     */
    public function getComment(Request $request): JsonResponse
    {
        return (new UserService())->getComment($request->only('type'));
    }

    /**
     * 我的收藏
     * @param Request $request
     * @return JsonResponse
     */
    public function getCollect(Request $request): JsonResponse
    {
        return (new UserService())->getCollect($request->input());
    }

    /**
     * 收藏设置
     * @param UserCollectRequest $request
     * @return JsonResponse
     */
    public function setCollect(UserCollectRequest $request): JsonResponse
    {
        return (new UserService())->setCollect($request->input());
    }

    /**
     * 关注用户
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function setFocus(Request $request): JsonResponse
    {
        return (new UserService())->setFocus($request->only('id'));
    }

    /**
     * 关注列表
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function getFocus(Request $request): JsonResponse
    {
        return (new UserService())->getFocus($request->input());
    }

    /**
     * 粉丝列表
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function getFans(Request $request): JsonResponse
    {
        return (new UserService())->getFans($request->input());
    }

    /**
     * 小黑屋列表
     * @param Request $request
     * @return JsonResponse
     */
    public function getBlackHouse(Request $request): JsonResponse
    {
        return (new UserService())->getBlackHouse($request->input());
    }

    /**
     * 小黑屋个人记录
     * @return JsonResponse
     */
    public function getUserBlackHouse(): JsonResponse
    {
        return (new UserService())->getUserBlackHouse();
    }

    /**
     * 用户等级
     * @return JsonResponse
     */
    public function getLevel(): JsonResponse
    {
        return (new UserService())->getLevel();
    }

    /**
     * 成长值列表
     * @return JsonResponse
     */
    public function getGrowthScore(): JsonResponse
    {
        return (new UserService())->getGrowthScore();
    }

    /**
     * 签到
     * @return JsonResponse
     * @throws CustomException
     */
    public function signIn(): JsonResponse
    {
        return (new UserService())->signIn();
    }

    /**
     * 分享排行榜
     * @return JsonResponse
     */
    public function shareList()
    {
        return (new UserService())->shareList();
    }

    /**
     * 粉丝排行榜
     * @return JsonResponse
     */
    public function fanList(): JsonResponse
    {
        return (new UserService())->fanList();
    }

    /**
     * 等级排行榜
     * @return JsonResponse
     */
    public function rankList(): JsonResponse
    {
        return (new UserService())->rankList();
    }

    /**
     * 礼物排行榜
     * @return JsonResponse
     */
    public function goldList(): JsonResponse
    {
        return (new UserService())->goldList();
    }

    /**
     * 福利统计
     * @return JsonResponse
     */
    public function user_welfare_count(): JsonResponse
    {
        return (new UserService())->getUserWelfareCount();
    }

    /**
     * 福利列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function user_welfare_index(CommonPageRequest $request): JsonResponse
    {
        return (new UserService())->getUserWelfare($request->all());
    }

    /**
     * 福利领取
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function user_welfare_receive(Request $request): JsonResponse
    {
        return (new UserService())->userWelfareReceive($request->all());
    }

    /**
     * 会员金币记录
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function golds(CommonPageRequest $request): JsonResponse
    {
        return (new UserService())->golds($request->all());
    }

    /**
     * 会员交易记录
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function records(CommonPageRequest $request): JsonResponse
    {
        return (new UserService())->records($request->all());
    }
}
