<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/api', function (Request $request) {
//    return $request->user();
//});

Route::group(["prefix"=>"v1", "middleware"=>["GlobalMiddleware"]],function (){
    /***********************************Auth接口***************************************/
    Route::group(["middleware"=>["UserApiAuth"]], function(){
        // 获取用户信息
        Route::get('user/getUserInfo', 'v1\UserController@getUserInfo');
        // 修改用户信息
        Route::post('user/editUserInfo', 'v1\UserController@editUserInfo');
        // 修改密码
        Route::post('user/editUserPass', 'v1\UserController@editUserPass');
        // 设置资金密码
        Route::post('user/setFundPassword', 'v1\UserController@setFundPassword');
        // 修改资金密码
        Route::post('user/editFundPassword', 'v1\UserController@editFundPassword');
        // 反馈列表
        Route::get('user/getAdviceList', 'v1\UserController@getAdviceList');
        // 拉黑用户
        Route::post('user/setBlacklist', 'v1\UserController@setBlacklist');
        // 拉黑列表
        Route::get('user/getBlacklist', 'v1\UserController@getBlacklist');
        // 我的点赞
        Route::get('user/getFollows', 'v1\UserController@getFollows');
        // 关注用户
        Route::post('user/setFocus', 'v1\UserController@setFocus');
        // 小黑屋列表
        Route::get('user/getBlackHouse', 'v1\UserController@getBlackHouse');
        // 小黑屋个人记录
        Route::get('user/getUserBlackHouse', 'v1\UserController@getUserBlackHouse');
        // 我的评论
        Route::get('user/getComment', 'v1\UserController@getComment');
        // 我的收藏
        Route::get('user/getCollect', 'v1\UserController@getCollect');
        // 收藏设置
        Route::post('user/setCollect', 'v1\UserController@setCollect');
        // 我的推广
        Route::get('user/getInvitation', 'v1\InvitationController@getInvitation');
        // 今日推广详情
        Route::get('user/getToDayInvitation', 'v1\InvitationController@getToDayInvitation');
        // 领取推广奖励
        Route::get('user/getRewards', 'v1\InvitationController@getRewards');
        // 月度报表
        Route::get('user/report', 'v1\InvitationController@report');
        // 用户等级
        Route::get('user/getLevel', 'v1\UserController@getLevel');
        // 成长值列表
        Route::get('user/getGrowthScore', 'v1\UserController@getGrowthScore');

        // 会员福利【基础】
        Route::get('user_welfare/count', 'v1\UserController@user_welfare_count');
        // 会员福利
        Route::get('user_welfare/index', 'v1\UserController@user_welfare_index');
        // 会员福利领取
        Route::put('user_welfare/receive', 'v1\UserController@user_welfare_receive');

        // 会员金币列表
        Route::get('user/golds', 'v1\UserController@golds');
        // 会员交易列表
        Route::get('user/records', 'v1\UserController@records');

        // 我的关注
        Route::get('user/getFocus', 'v1\UserController@getFocus');
        // 我的粉丝
        Route::get('user/getFans', 'v1\UserController@getFans');

        // 消息数
        Route::get('common/getMessageBadge', 'v1\CommonController@getMessageBadge');
        // 消息已读
        Route::post('common/setMessage', 'v1\CommonController@setMessage');

        // 举报
        Route::post('complaint/add', 'v1\ComplaintController@add');
        // 举报列表
        Route::get('complaint/list', 'v1\ComplaintController@list');

        Route::post('discuss/previous', 'v1\DiscussController@previous');  // 上一期内容

        Route::post('room/join', 'v1\RoomController@join');     // 加入房间
        Route::post('room/switch', 'v1\RoomController@switch'); // 切换房间
        Route::post('room/record', 'v1\RoomController@record'); // 聊天记录
        Route::put('red_packet/receive', 'v1\RedPacketController@receive'); // 抢红包
        Route::get('red_packet/receives', 'v1\RedPacketController@receives'); // 我的红包列表

        // 聊天室
        Route::group(["middleware"=>["MessageMuted"]], function(){
//            Route::post('room/delete', 'v1\RoomController@delete'); // 删除聊天记录
            // 意见反馈
            Route::post('user/addAdvice', 'v1\UserController@addAdvice');
            // 文件上传
            Route::post('common/upload', 'v1\CommonController@upload');
            // 资料点赞
            Route::post('corpus/follow', 'v1\CorpusController@follow');
            // 用户端上传图片公共接口
            Route::post('comment/image', 'v1\CommonController@image');
            // 用户端上传视频公共接口
            Route::post('common/video', 'v1\CommonController@video');
            Route::post('common/temp_cred', 'v1\CommonController@minio_temp_cred');
            Route::post('common/video_complete', 'v1\CommonController@minio_video_complete');
            // 签到
            Route::post('user/signIn', 'v1\UserController@signIn');
            // 图片投票接口
            Route::post('picture/vote', 'v1\PictureController@vote');

            Route::group(["middleware"=>['FilterSensitive']], function() {
                // 聊天
                Route::post('room/chat', 'v1\RoomController@chat');
                // 添加评论
                Route::post('comment/create', 'v1\CommentController@create');
                // 添加图解
                Route::post('diagram/create', 'v1\DiagramController@create');
                // 发布竞猜
                Route::post('forecast/create', 'v1\ForecastController@create');
                // 发现
                Route::post('discovery/create', 'v1\DiscoveryController@create');      // 发布
                // 高手论坛
                Route::post('discuss/create', 'v1\DiscussController@create');  // 发布

                // 高手榜
                Route::post('expert/create', 'v1\ExpertController@create');  // 发布
                Route::post('expert/previous', 'v1\ExpertController@previous');
            });

            // 资金出入站
            Route::post('plat/bind', 'v1\PlatController@bind');  // 绑定平台
            Route::post('plat/recharge', 'v1\PlatController@recharge');  // 充值
            Route::post('plat/withdraw', 'v1\PlatController@withdraw');  // 提现

            // 收益相关
            Route::post('income/apple', 'v1\IncomeController@apple');  // 申请
            Route::post('income/reward', 'v1\IncomeController@reward');  // 打赏
            Route::get('income/reward', 'v1\IncomeController@reward_list');  // 打赏列表
            Route::get('income/posts', 'v1\IncomeController@posts_list');  // 发帖收益列表

        });
        // 点赞评论
        Route::post('comment/follow', 'v1\CommentController@follow');
        // 图片点赞接口
        Route::post('picture/follow', 'v1\PictureController@follow');
        // 图片收藏接口
        Route::post('picture/collect', 'v1\PictureController@collect');
        // 发现
        Route::post('discovery/follow', 'v1\DiscoveryController@follow');      // 点赞
        Route::post('discovery/collect', 'v1\DiscoveryController@collect');    // 收藏
        Route::post('discovery/forward', 'v1\DiscoveryController@forward');    // 转发
        // 图解点赞
        Route::post('diagram/follow', 'v1\DiagramController@follow');
        // 竞猜点赞
        Route::post('forecast/follow', 'v1\ForecastController@follow');
        // 幽默竞猜点赞
        Route::post('humorous/follow', 'v1\HumorousController@follow');
        // 幽默竞猜收藏
        Route::post('humorous/collect', 'v1\HumorousController@collect');
        // 幽默竞猜投票接口
        Route::post('humorous/vote', 'v1\HumorousController@vote');
        // 高手论坛点赞
        Route::post('discuss/follow', 'v1\DiscussController@follow');

        Route::get('plat/user_plat', 'v1\PlatController@user_plat');  // 用户平台列表
        Route::get('plat/list', 'v1\PlatController@list');  // 平台列表
        Route::get('plat/quotas', 'v1\PlatController@quotas');  // 额度列表
        Route::get('plat/withdraw_page', 'v1\PlatController@withdraw_page');  // 提现页面

        // 活动
        Route::get('activity/forward', 'v1\ActivityController@forward');  // 转发
        Route::get('activity/filling', 'v1\ActivityController@filling');  // 补填邀请码
        Route::get('activity/list', 'v1\ActivityController@list');        // 活动列表
        Route::post('activity/receive', 'v1\ActivityController@receive');  // 活动领取

        // 手机号验证
        Route::get('mobile/verify', 'v1\MobileController@verify');

        // 竞猜投注
        Route::post('forecast/bet', 'v1\ForecastController@bet');
        Route::post('forecast/cancel', 'v1\ForecastController@cancel');
        Route::post('forecast/bet_index', 'v1\ForecastController@bet_index');

        // 集五福
        Route::get('activity/five_receive', 'v1\ActivityController@five_receive'); //领取红包

        // 第三方游戏
        Route::post('pg/getLaunchURLHTML', 'v1\PgController@getLaunchURLHTML'); //PG电子生成HTML
        Route::post('imone/getLaunchURLHTML', 'v1\IMOneController@getLaunchURLHTML'); //IMOne生成URL
        Route::post('ky/login', 'v1\KyController@login'); //ky生成URL
        Route::post('dag/login', 'v1\Pg2Controller@login'); //dag生成URL
        Route::post('pg2/login', 'v1\Pg2Controller@login'); //pg2生成URL

//        Route::post('comment/follow_3', 'v1\CommentController@follow_3');
//        Route::post('comment/create_3', 'v1\CommentController@create_3');
//        Route::post('user/editUserInfo_3', 'v1\UserController@editUserInfo_3');

    });

    /***********************************Public接口***************************************/
    Route::group([], function(){
        // 统计失效域名
//        Route::get('statistics/api', function() {
//            dd(bcrypt('7e*ew430Njde')); // $2y$10$qCvzsXLGq.37R5BK3OgHQO4Zso9/zHlEua3QoaSKtXB3yxw1alsBm
//        });
        Route::post('statistics/domain', 'v1\StatisticsController@domain');
        // 统计
        Route::post('statistics/index', 'v1\StatisticsController@index');
        // 登陆
        Route::post('login/index', 'v1\LoginController@index');
        Route::post('login/forever', 'v1\LoginController@forever');
        Route::post('login/loginChat', 'v1\LoginController@loginChat');
        Route::get('login/mobile', 'v1\LoginController@mobile'); // 单纯校验手机号是否存在
        Route::post('login/mobileLogin', 'v1\LoginController@mobileLogin'); // 使用手机号登录
        // 注册
        Route::post('login/register', 'v1\LoginController@register');
        // 找回
        Route::post('login/forget', 'v1\LoginController@forget');
        // 验证码
        Route::get('login/captcha', 'v1\LoginController@captcha');
        // 公共配置
        Route::get('common/config', 'v1\CommonController@config'); // ->middleware('ViewsMiddleware');
        // 版本号
        Route::get('common/version', 'v1\CommonController@version');
        // 公告｜消息
        Route::get('common/getMessage', 'v1\CommonController@getMessage');
        // 分享排行榜
        Route::get('user/shareList', 'v1\UserController@shareList');
        // 粉丝排行榜
        Route::get('user/fanList', 'v1\UserController@fanList');
        // 等级排行榜
        Route::get('user/rankList', 'v1\UserController@rankList');
        // 金币排行榜
        Route::get('user/goldList', 'v1\UserController@goldList');

        // 用户发布的数据
        Route::get('user/release', 'v1\UserController@release');
        // 用户主页信息
        Route::get('user/getUserIndex', 'v1\UserController@getUserIndex');

        // 资料分类
        Route::get('corpus/listCorpusType', 'v1\CorpusController@listCorpusType');
        // 资料列表
        Route::get('corpus/listArticle', 'v1\CorpusController@listArticle');
        // 资料详情
        Route::get('corpus/infoArticle', 'v1\CorpusController@infoArticle');

        // 首页相关接口
        Route::get('index/index', 'v1\IndexController@index');
        // 启动图接口
        Route::get('init/images', 'v1\IndexController@init_img');
        // 首页图库接口
        Route::get('index/picture', 'v1\PictureController@index');
        Route::get('index/pictures', 'v1\PictureController@pictures'); // 无广告
        // 首页资料列表接口
        Route::get('index/material', 'v1\IndexController@material');
        // 首页年份接口
        Route::get('index/years', 'v1\IndexController@years');
        // 首页年份接口含黑白
        Route::get('index/years_color', 'v1\IndexController@yearsColor');
        // 首页年份接口含黑白对应彩种
        Route::get('index/lottery_years_color', 'v1\IndexController@lotteryYearsColor');
        // 彩种对应年份
        Route::get('index/lottery_years', 'v1\IndexController@lotteryYears');

        // 属性参照
        Route::get('liuhe/number_attr', 'v1\LiuheController@number_attr');
        Route::get('liuhe/number_attr2', 'v1\LiuheController@number_attr2');
        Route::get('liuhe/statistics', 'v1\LiuheController@statistics');

        // 前端地址
        Route::get('h5_url/index', 'v1\H5sController@index');

        // 获取某一期开奖号码数据
        Route::get('liuhe/numbers', 'v1\LiuheController@numbers');
        // 开奖记录详情
        Route::get('liuhe/record', 'v1\LiuheController@record');
        // 历史号码记录
        Route::get('liuhe/history', 'v1\LiuheController@history');
        // 历史推荐 + 新一期推荐
        Route::get('liuhe/recommend', 'v1\LiuheController@recommend');
        // 开奖日期
        Route::get('liuhe/open_date', 'v1\LiuheController@open_date');
        Route::get('liuhe/next', 'v1\LiuheController@next');
        // 开奖回放
        Route::get('liuhe/video', 'v1\LiuheController@video');
        // 彩种类型
        Route::get('liuhe/lottery', 'v1\LiuheController@lottery');

        // 图库分类接口
        Route::get('picture/cate', 'v1\PictureController@cates');
        // 图库详情接口
        Route::get('picture/detail', 'v1\PictureController@detail');
        Route::get('picture/details', 'v1\PictureController@details');
        Route::get('picture/ai_analyze', 'v1\PictureController@ai_analyze');
        Route::get('picture/issues', 'v1\PictureController@issues');    // 图片期数
        Route::get('picture/recommend', 'v1\PictureController@recommend');    // 图片推荐
        Route::get('picture/video', 'v1\PictureController@video');    // 图片视频解析
        Route::get('pic_series/list', 'v1\PictureController@series_list'); // 图库系列相关
        Route::get('pic_series/detail', 'v1\PictureController@series_detail'); // 图库系列相关

        // 照片墙
        Route::get('picture/flow', 'v1\PictureController@flow');

        // 图解列表
        Route::get('diagram/list', 'v1\DiagramController@list');
        // 详情
        Route::get('diagram/detail', 'v1\DiagramController@detail');

        // 竞猜小组列表
        Route::get('forecast/list', 'v1\ForecastController@list');
        // 详情
        Route::get('forecast/detail', 'v1\ForecastController@detail');
        // 参与竞猜入口
        Route::get('forecast/join', 'v1\ForecastController@join');
        // 参与竞猜入口[新]
        Route::get('forecast/newJoin', 'v1\ForecastController@newJoin');
        // 竞猜排行榜统计
        Route::get('forecast/ranking', 'v1\ForecastController@ranking');

        // 评论相关接口
        // 热门评论
        Route::get('comment/hot', 'v1\CommentController@hot');
        // 一级评论接口
        Route::get('comment/list', 'v1\CommentController@list');
        // 子评论
        Route::get('comment/children', 'v1\CommentController@children');

        // 幽默竞猜详情
        Route::get('humorous/guess', 'v1\HumorousController@guess');      // 猜测列表
        Route::get('humorous/detail', 'v1\HumorousController@detail');      // 详情

        // 玄机锦囊
        Route::get('mystery/latest', 'v1\MysteryController@latest');      // 最新期
        Route::get('mystery/history', 'v1\MysteryController@history');      // 历史详情

        // 发现
        Route::get('discovery/list', 'v1\DiscoveryController@list');      // 列表
        Route::get('discovery/detail', 'v1\DiscoveryController@detail');  // 详情

        // 寻宝
        Route::get('treasure/list', 'v1\TreasureController@list');  // 列表

        // 高手论坛
        Route::get('discuss/list', 'v1\DiscussController@list');  // 列表
        Route::get('discuss/detail', 'v1\DiscussController@detail');  // 主题详情

        Route::get('expert/list', 'v1\ExpertController@list');  // 列表
        Route::get('expert/detail', 'v1\ExpertController@detail');  // 主题详情

        Route::get('room/list', 'v1\RoomController@list');      // 聊天室房间列表
        Route::get('red_packet/list', 'v1\RedPacketController@list'); // 聊天室红包列表

        // 实时开奖ws
        Route::post('open_lottery/list', 'v1\OpenLotteryController@open');

        // 广告轮播图
        Route::get('ad/list', 'v1\AdController@list');

        Route::get('test/newLotteryNum', 'v1\TestController@newLotteryNum');
        Route::get('test/update_year_issue', 'v1\TestController@update_year_issue');
        Route::get('test/recommend', 'v1\TestController@recommend');
        Route::get('test/forecast', 'v1\TestController@forecast');
        Route::get('test/forecast_bet', 'v1\TestController@bets');

        // 图形验证码校验
        Route::get('graph/verify', 'v1\LoginController@graph_verify');

        // 邮件发送
        Route::get('mail/send', 'v1\MailController@send');
        // 手机号发送
        Route::get('mobile/send', 'v1\MobileController@send');
        Route::get('cache/index_pic', 'v1\CacheController@index_pic');

//        红包排行榜
        Route::get('red_packet/ranks', 'v1\RedPacketController@ranks'); // 我的红包列表
        Route::get('index/red', 'v1\RedPacketController@index_red'); // 首页红包

        /***************************第三方*********************/
        // 评论｜图片点赞（取消） 【第三方】
        Route::post('user/login_3', 'v1\LoginController@login_3');
        Route::get('comment/list_3', 'v1\CommentController@list_3');
        Route::get('comment/children_3', 'v1\CommentController@children_3');

        Route::post('comment/follow_3', 'v1\CommentController@follow_3');
        Route::post('comment/create_3', 'v1\CommentController@create_3');
        Route::post('user/editUserInfo_3', 'v1\UserController@editUserInfo_3');

        // 集五福
        Route::get('activity/five_schedule', 'v1\ActivityController@five_schedule');  // 进度

        // pg游戏
        Route::post('pg/verifySession', 'v1\PgController@verifySession');  //PG电子session效验
        Route::post('game/getList', 'v1\PgController@getList');  //游戏列表

        // 对外接口
        Route::get('three/queryIDByPlatAccount', 'v1\ThreeController@queryIDByPlatAccount');  // 查询user_id

        Route::get('tg/send', 'v1\ThreeController@send');


        // ai
        Route::get('ai/config', 'v1\AiController@config');
        Route::get('ai/list', 'v1\AiController@list');
        Route::get('ai/detail', 'v1\AiController@detail');
        Route::get('index/guess', 'v1\IndexController@guess');

    });
});
