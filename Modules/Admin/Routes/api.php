<?php
// ,"middleware"=>"AdminApiAuth"
use Illuminate\Support\Facades\Route;

Route::group(["prefix"=>"v1/admin","middleware"=>["AdminApiAuth", "CorsMiddleware"]],function (){
    //获取平台信息
    Route::get('index/getMain', 'v1\IndexController@getMain');
    //登录
    Route::post('login/login', 'v1\LoginController@login');
    //获取模块
    Route::get('index/getModel', 'v1\IndexController@getModel');
    // 获取管理员信息
    Route::get('admin/info', 'v1\IndexController@info');
    /***********************************首页***************************************/
    //刷新token
    Route::put('index/refreshToken', 'v1\IndexController@refreshToken');
    //退出登录
    Route::delete('index/logout', 'v1\IndexController@logout');
    //清除缓存
    Route::delete('index/outCache', 'v1\IndexController@outCache');
    //修改密码
    Route::put('index/upadtePwdView', 'v1\IndexController@upadtePwdView');

    //获取左侧栏
    Route::get('index/getMenu', 'v1\IndexController@getMenu');

    //单图上传
    Route::post('upload/fileImage', 'v1\UploadController@fileImage');

    //图片列表
    Route::get('upload/getImageList', 'v1\UploadController@getImageList');

    // 获取地区数据
    Route::get('index/getAreaData', 'v1\IndexController@getAreaData');
    // 转换编辑器内容
    Route::post('index/setContent', 'v1\IndexController@setContent');
    /***********************************管理员列表***************************************/
    //列表数据
    Route::get('admin/index', 'v1\AdminController@index');
    //获取权限组
    Route::get('admin/getGroupList', 'v1\AdminController@getGroupList');
    //获取项目列表
    Route::get('admin/getProjectList', 'v1\AdminController@getProjectList');
    //添加
    Route::post('admin/store', 'v1\AdminController@store');
    //编辑页面
    Route::get('admin/edit', 'v1\AdminController@edit');
    //编辑提交
    Route::put('admin/update', 'v1\AdminController@update');
    //调整状态
    Route::put('admin/status', 'v1\AdminController@status');
    //初始化密码
    Route::put('admin/updatePwd', 'v1\AdminController@updatePwd');
    //生成谷歌验证码
    Route::put('admin/generate', 'v1\AdminController@generate');

    /***********************************权限组列表***************************************/
    //列表数据
    Route::get('group/index', 'v1\GroupController@index');
    //添加
    Route::post('group/store', 'v1\GroupController@store');
    //编辑页面
    Route::get('group/edit', 'v1\GroupController@edit');
    //编辑提交
    Route::put('group/update', 'v1\GroupController@update');
    //调整状态
    Route::put('group/status', 'v1\GroupController@status');
    //分配权限
    Route::get('group/access', 'v1\GroupController@access');
    //分配权限提交
    Route::put('group/accessUpdate', 'v1\GroupController@accessUpdate');

    /***********************************菜单管理***************************************/
    //列表数据
    Route::get('rule/index', 'v1\RuleController@index');
    //添加
    Route::post('rule/store', 'v1\RuleController@store');
    // 添加子级返回父级id
    Route::get('rule/pidArr', 'v1\RuleController@pidArr');
    //编辑页面
    Route::get('rule/edit', 'v1\RuleController@edit');
    //编辑提交
    Route::put('rule/update', 'v1\RuleController@update');
    //菜单状态
    Route::put('rule/status', 'v1\RuleController@status');
    //是否验证权限
    Route::put('rule/open', 'v1\RuleController@open');
    // 固定面板
    Route::put('rule/affix', 'v1\RuleController@affix');
    //排序
    Route::put('rule/sorts', 'v1\RuleController@sorts');
    //删除
    Route::delete('rule/cDestroy', 'v1\RuleController@cDestroy');

    /***********************************系统配置***************************************/
    //系统配置
    Route::get('config/index', 'v1\ConfigController@index');
    //提交
    Route::put('config/update', 'v1\ConfigController@update');
    /***********************************系统配置（广告模块）***************************************/
    //广告列表
    Route::get('ad/index', 'v1\AdController@index');
    //修改
    Route::put('ad/update', 'v1\AdController@update');
    //修改批量
    Route::put('ad/update_batch', 'v1\AdController@update_batch');
    //新增
    Route::post('ad/store', 'v1\AdController@store');
    //删除
    Route::delete('ad/delete', 'v1\AdController@delete');
    /***********************************系统配置（禁言模块）***************************************/
    //广告列表
    Route::get('mushin/index', 'v1\MushinController@index');
    //修改
    Route::put('mushin/update', 'v1\MushinController@update');
    //新增
    Route::post('mushin/store', 'v1\MushinController@store');
    /***********************************系统配置（敏感词模块）***************************************/
    //广告列表
    Route::get('sensitives/index', 'v1\SensitivesController@index');
    //修改
    Route::put('sensitives/update', 'v1\SensitivesController@update');
    //新增
    Route::post('sensitives/store', 'v1\SensitivesController@store');
    //新增
    Route::delete('sensitives/delete', 'v1\SensitivesController@delete');
    /***********************************系统配置（用户等级模块）***************************************/
    //会员等级列表x
    Route::get('levels1/index', 'v1\LevelsController@index');
    //修改
    Route::put('levels/update', 'v1\LevelsController@update');
    //新增
    Route::post('levels/store', 'v1\LevelsController@store');
    /***********************************系统配置（审核模块）***************************************/
    //审核列表
    Route::get('checks/index', 'v1\ChecksController@index');
    //修改
    Route::put('checks/update', 'v1\ChecksController@update');
    /***********************************系统配置（图片地址前缀）***************************************/
    //审核列表
    Route::get('img_prefix/index', 'v1\ImgPrefixController@index');
    //修改
    Route::put('img_prefix/update', 'v1\ImgPrefixController@update');
    /***********************************系统配置（IP名单模块）***************************************/
    //IP列表
    Route::get('ips/index', 'v1\IpsController@index');
    //修改
    Route::put('ips/update', 'v1\IpsController@update');
    //新增
    Route::post('ips/store', 'v1\IpsController@store');
    //删除
    Route::delete('ips/delete', 'v1\IpsController@delete');
    /***********************************系统配置（h5地址模块）***************************************/
    //IP列表
    Route::get('h5s/index', 'v1\H5sController@index');
    //修改
    Route::put('h5s/update', 'v1\H5sController@update');
    //新增
    Route::post('h5s/store', 'v1\H5sController@store');
    //删除
    Route::delete('h5s/delete', 'v1\H5sController@delete');
    /***********************************系统配置（三方站点配置）***************************************/
    //列表
    Route::get('website/index', 'v1\WebsiteController@index');
    //修改
    Route::put('website/update', 'v1\WebsiteController@update');
    //批量
    Route::put('website/status', 'v1\WebsiteController@status');
    //新增
    Route::post('website/store', 'v1\WebsiteController@store');
    /***********************************系统配置（ip访问统计配置）***************************************/
    //列表
    Route::get('ip_access/index', 'v1\IpAccessController@index');
    Route::get('invalid_domains/index', 'v1\IpAccessController@invalid_domains');

    /***********************************开奖管理（历史号码）***************************************/
    //最新期数据
    Route::get('lottery_history/latest', 'v1\LotteryHistoryController@latest');
    //列表
    Route::get('lottery_history/index', 'v1\LotteryHistoryController@index');
    //修改
    Route::put('lottery_history/update', 'v1\LotteryHistoryController@update');
    //修改
    Route::put('lottery_history/real_open', 'v1\LotteryHistoryController@real_open');
    //新增
    Route::post('lottery_history/store', 'v1\LotteryHistoryController@store');
    //开启手动开奖
    Route::post('lottery_history/manually', 'v1\LotteryHistoryController@manually');
    /***********************************开奖管理（开奖日期）***************************************/
    //列表
    Route::get('liuhe_date/index', 'v1\LotteryDateController@index');
    Route::put('liuhe_date/switch', 'v1\LotteryDateController@switch');
    Route::post('liuhe_date/create', 'v1\LotteryDateController@store');
    Route::put('liuhe_date/update', 'v1\LotteryDateController@update');
    Route::delete('liuhe_date/delete', 'v1\LotteryDateController@delete');

    /***********************************论坛管理（论坛列表）***************************************/
    //列表
    Route::get('discuss/index', 'v1\DiscussController@index');
    Route::put('discuss/update', 'v1\DiscussController@update');
    Route::put('discuss/status', 'v1\DiscussController@status');
    Route::delete('discuss/delete', 'v1\DiscussController@delete');
    Route::post('discuss/store', 'v1\DiscussController@store');
    Route::get('discuss/previous', 'v1\DiscussController@previous');
    // 资料设置
    Route::get('material/list', 'v1\DiscussController@list');
    Route::post('material/update_is_index', 'v1\DiscussController@update_is_index');
    // 智能设置
    Route::get('discuss_robot/configs', 'v1\DiscussController@configs');
    Route::post('discuss_robot/update_is_index', 'v1\DiscussController@update_is_index');

    /***********************************图库管理（图解列表）***************************************/
    Route::get('picture/diagrams_list', 'v1\PictureController@diagrams_list');
    Route::put('picture/diagrams_update', 'v1\PictureController@diagrams_update');
    Route::delete('picture/diagrams_delete', 'v1\PictureController@diagrams_delete');
    /***********************************图库管理（竞猜列表）***************************************/
    Route::get('picture/forecasts_list', 'v1\PictureController@forecasts_list');
    Route::put('picture/forecasts_update', 'v1\PictureController@forecasts_update');
    Route::delete('picture/forecasts_delete', 'v1\PictureController@forecasts_delete');
    /***********************************图库管理（系列列表）***************************************/
    Route::get('picture/series_list', 'v1\PictureController@series_list');
    Route::post('picture/series_store', 'v1\PictureController@series_store');
    Route::put('picture/series_update', 'v1\PictureController@series_update');
    Route::delete('picture/series_delete', 'v1\PictureController@series_delete');
    /***********************************图库管理（首页图片列表）***************************************/
    Route::get('picture/list', 'v1\PictureController@list');
    Route::put('picture/update', 'v1\PictureController@update');
    Route::post('picture/create', 'v1\PictureController@store');
    Route::delete('picture/delete', 'v1\PictureController@delete');
    Route::post('picture/create_diagram', 'v1\PictureController@store_diagram');
    /***********************************图库管理（视频解析列表）***************************************/
    Route::get('picture/video_list', 'v1\PictureController@video_list');
    Route::put('picture/video_update', 'v1\PictureController@video_update');
    Route::post('picture/video_store', 'v1\PictureController@video_store');
    Route::delete('picture/video_delete', 'v1\PictureController@video_delete');
    Route::post('picture/update_is_video', 'v1\PictureController@update_is_video');

    /***********************************资金管理（平台管理）***************************************/
    Route::get('funds/platforms_list', 'v1\FundsController@platforms_list');
    Route::put('funds/platforms_update', 'v1\FundsController@platforms_update');
    Route::post('funds/platforms_store', 'v1\FundsController@platforms_store');
    Route::delete('funds/platforms_delete', 'v1\FundsController@platforms_delete');
    /***********************************资金管理（会员绑定）***************************************/
    Route::get('funds/user_platform_list', 'v1\FundsController@user_platform_list');
    Route::put('funds/user_platform_update', 'v1\FundsController@user_platform_update');
    Route::delete('funds/user_platform_delete', 'v1\FundsController@user_platform_delete');
    /***********************************资金管理（会员充值）***************************************/
    Route::get('funds/user_recharge_list', 'v1\FundsController@user_recharge_list');
    Route::put('funds/user_recharge_update', 'v1\FundsController@user_recharge_update');
    Route::put('funds/user_recharge_update_status', 'v1\FundsController@user_recharge_update_status');
    Route::delete('funds/user_recharge_delete', 'v1\FundsController@user_recharge_delete');
    /***********************************资金管理（会员提现）***************************************/
    Route::get('funds/user_withdraw_list', 'v1\FundsController@user_withdraw_list');
    Route::put('funds/user_withdraw_update', 'v1\FundsController@user_withdraw_update');
    Route::put('funds/user_withdraw_update_status', 'v1\FundsController@user_withdraw_update_status');
    Route::put('funds/user_withdraw_update_revoke', 'v1\FundsController@user_withdraw_update_revoke');
    Route::delete('funds/user_withdraw_delete', 'v1\FundsController@user_withdraw_delete');
    /***********************************资金管理（额度配置）***************************************/
    Route::get('funds/quota_list', 'v1\FundsController@quota_list');
    Route::put('funds/quota_update', 'v1\FundsController@quota_update');
    /***********************************资金管理（会员投注）***************************************/
    Route::get('funds/bet_list', 'v1\FundsController@bet_list');
    Route::put('funds/bet_account_update', 'v1\FundsController@bet_account_update');
    Route::put('funds/bet_once_account_update', 'v1\FundsController@bet_once_account_update'); // 一键入账
    Route::put('funds/bet_reopen_account_update', 'v1\FundsController@bet_reopen_account_update'); // 奖项重开
    Route::put('funds/bet_type_update', 'v1\FundsController@bet_type_update');
    /***********************************资金管理（会员收益）***************************************/
    Route::get('funds/user_income_apply_list', 'v1\FundsController@user_income_apply_list');
    Route::put('funds/income_apply_update_status', 'v1\FundsController@income_apply_update_status');
    Route::delete('funds/income_apply_delete', 'v1\FundsController@income_apply_delete');
    /***********************************资金管理（额度列表）***************************************/
    Route::get('funds/user_quota_list', 'v1\FundsController@user_quota_list');

    /***********************************发现管理（图片列表）***************************************/
    Route::get('discover/discover_list', 'v1\DiscoverController@discover_list');
    Route::put('discover/discover_update_status', 'v1\DiscoverController@discover_update_status');
    Route::put('discover/discover_update', 'v1\DiscoverController@discover_update');
    Route::delete('discover/discover_delete', 'v1\DiscoverController@discover_delete');
    Route::post('discover/discover_create', 'v1\DiscoverController@discover_create');

    /***********************************活动管理（活动配置）***************************************/
    Route::get('activity/config', 'v1\ActivityController@config');
    Route::put('activity/config_update', 'v1\ActivityController@config_update');
    Route::get('activity/five_index', 'v1\ActivityController@five_index');

    /***********************************幽默竞猜管理***************************************/
    Route::get('humorous/index', 'v1\HumorousController@index');
    Route::put('humorous/update', 'v1\HumorousController@update');
    Route::delete('humorous/delete', 'v1\HumorousController@delete');
    Route::post('humorous/store', 'v1\HumorousController@store');

    /***********************************玄机锦囊管理***************************************/
    Route::get('mystery/index', 'v1\MysteryController@index');
    Route::put('mystery/update', 'v1\MysteryController@update');
    Route::post('mystery/store', 'v1\MysteryController@store');
    Route::delete('mystery/delete', 'v1\MysteryController@delete');

    /***********************************游戏管理***************************************/
    Route::get('game/index', 'v1\GameController@index');
    Route::post('game/store', 'v1\GameController@store');
    Route::put('game/update', 'v1\GameController@update');
    Route::put('game/update_status', 'v1\GameController@update_status');
    // 游戏配置
    Route::get('game_config/index', 'v1\GameController@game_config_index');
    Route::post('game_config/store', 'v1\GameController@game_config_store');
    Route::put('game_config/update', 'v1\GameController@game_config_update');
    Route::delete('game_config/delete', 'v1\GameController@game_config_delete');

    /***********************************地区列表***************************************/
    //地区列表
    Route::get('area/index', 'v1\AreaController@index');
    //添加
    Route::post('area/store', 'v1\AreaController@store');
    //编辑页面
    Route::get('area/edit', 'v1\AreaController@edit');
    //编辑提交
    Route::put('area/update', 'v1\AreaController@update');
    //状态
    Route::put('area/status', 'v1\AreaController@status');
    //排序
    Route::put('area/sorts', 'v1\AreaController@sorts');
    //删除
    Route::delete('area/cDestroy', 'v1\AreaController@cDestroy');
    //导入服务器数据
    Route::get('area/importData', 'v1\AreaController@importData');
    // 写入地区缓存
    Route::post('area/setAreaData', 'v1\AreaController@setAreaData');
    /***********************************会员管理***************************************/
    //会员管理
    Route::get('user/index', 'v1\UserController@index');
    //添加
    Route::post('user/store', 'v1\UserController@store');
    //编辑页面
    Route::get('user/edit', 'v1\UserController@edit');
    //编辑提交
    Route::put('user/update', 'v1\UserController@update');
    //修改额度
    Route::put('user/user_quotas', 'v1\UserController@user_quotas');
    //调整状态
    Route::put('user/status', 'v1\UserController@status');
    //初始化密码
    Route::put('user/updatePwd', 'v1\UserController@updatePwd');
    // 根据account_name获取id
    Route::get('user/user_id_name', 'v1\UserController@user_id_name');
    // 根据account_name获取id | 昵称
    Route::get('user/user_id_full_name', 'v1\UserController@user_id_full_name');
    // 根据nickname获取id
    Route::get('user/id_by_nickname', 'v1\UserController@id_by_nickname');
    // 根据nickname获取id
    Route::put('user/login', 'v1\UserController@login');

    /***********************************会员分组***************************************/
    Route::get('user_group/index', 'v1\UserGroupController@index');

    /***********************************会员小黑屋***************************************/
    Route::get('user_mushin/index', 'v1\UserMushinController@index');
    Route::put('user_mushin/update', 'v1\UserMushinController@update')->name('user_mushin_update');
    Route::delete('user_mushin/delete', 'v1\UserMushinController@delete');
    Route::post('user_mushin/create', 'v1\UserMushinController@store')->name('user_mushin_store');

    /***********************************会员金币***************************************/
    Route::get('user_gold/index', 'v1\UserGoldController@index');

    /***********************************高手榜***************************************/
    Route::get('master_ranking/index', 'v1\MasterRankingController@index');
    Route::put('master_ranking/update', 'v1\MasterRankingController@update');

    /***********************************会员意见***************************************/
    Route::get('user_advice/index', 'v1\UserAdviceController@index');
    Route::put('user_advice/update', 'v1\UserAdviceController@update');
    Route::delete('user_advice/delete', 'v1\UserAdviceController@delete');

    /***********************************会员评论***************************************/
    Route::get('user_comment/index', 'v1\UserCommentController@index');
    Route::put('user_comment/update', 'v1\UserCommentController@update');
    Route::delete('user_comment/delete', 'v1\UserCommentController@delete');
    Route::post('user_comment/create', 'v1\UserCommentController@store');
    Route::post('user_comment/reply', 'v1\UserCommentController@reply');

    /***********************************会员评论【第三方】***************************************/
    Route::get('user_comment3/index', 'v1\UserCommentController@index3');
    Route::put('user_comment3/update', 'v1\UserCommentController@update3');
    Route::put('comments3/status', 'v1\UserCommentController@status');
    Route::delete('user_comment3/delete', 'v1\UserCommentController@delete3');

    /***********************************会员图像审核***************************************/
    Route::get('user_check/avatar', 'v1\UserCheckController@avatar');
    Route::get('user_check/update', 'v1\UserCheckController@update');

    /***********************************会员福利***************************************/
    Route::get('user_welfare/index', 'v1\UserWelfareController@index');
    Route::post('user_welfare/create', 'v1\UserWelfareController@store');
    Route::put('user_welfare/update', 'v1\UserWelfareController@update');

    /***********************************会员手机号黑名单***************************************/
    Route::get('user_mobile_blacklist/index', 'v1\UserBlackListController@index');
    Route::delete('user_mobile_blacklist/delete', 'v1\UserBlackListController@delete');

    /***********************************会员举报***************************************/
    Route::get('report/index', 'v1\ReportController@index');
    Route::put('report/update', 'v1\ReportController@update');
    Route::delete('report/delete', 'v1\ReportController@delete');
    Route::get('report/detail', 'v1\ReportController@detail');

    /***********************************站内公告（信息）***************************************/
    Route::get('announce/index', 'v1\AnnounceController@index');
    Route::post('announce/store', 'v1\AnnounceController@store');
    Route::put('announce/update', 'v1\AnnounceController@update');
    Route::delete('announce/delete', 'v1\AnnounceController@delete');

    /***********************************六合管理（号码属性）***************************************/
    Route::get('liuhe_number/index', 'v1\LiuheNumberController@index');
    Route::put('liuhe_number/update', 'v1\LiuheNumberController@update');
    Route::post('liuhe_number/create', 'v1\LiuheNumberController@create');
    /***********************************六合管理（年份管理）***************************************/
    Route::get('liuhe_year/index', 'v1\LiuheYearController@index');
    Route::put('liuhe_year/update', 'v1\LiuheYearController@update');
    Route::post('liuhe_year/store', 'v1\LiuheYearController@store');
    Route::delete('liuhe_year/delete', 'v1\LiuheYearController@delete');
    /***********************************六合管理（配置管理）***************************************/
    Route::get('liuhe_config/index', 'v1\LiuheConfigController@index');
    Route::put('liuhe_config/update', 'v1\LiuheConfigController@update');
    Route::post('liuhe_config/store', 'v1\LiuheConfigController@store');
    /***********************************六合管理（彩种管理）***************************************/
    Route::get('liuhe_lottery/index', 'v1\LiuheConfigController@liuhe_lottery_index');
    Route::post('liuhe_lottery/create', 'v1\LiuheConfigController@liuhe_lottery_store');
    Route::put('liuhe_lottery/update', 'v1\LiuheConfigController@liuhe_lottery_update');
    Route::delete('liuhe_lottery/destroy', 'v1\LiuheConfigController@liuhe_lottery_delete');
    /***********************************六合管理（竞猜管理）***************************************/
    Route::get('liuhe_forecasts/index', 'v1\LiuheForecastsController@index');
    Route::put('liuhe_forecasts/update', 'v1\LiuheForecastsController@update');
    /***********************************六合管理（投注管理）***************************************/
    Route::get('liuhe_forecast_bets/index', 'v1\LiuheForecastBetsController@index');
    Route::post('liuhe_forecast_bets/create', 'v1\LiuheForecastBetsController@store');
    Route::put('liuhe_forecast_bets/update', 'v1\LiuheForecastsController@update');
    /***********************************聊天管理（聊天室）***************************************/
    Route::get('room/room', 'v1\RoomController@room');
    Route::put('room/update', 'v1\RoomController@update');
    Route::post('room/store', 'v1\RoomController@store');
    Route::put('room/check', 'v1\RoomController@check');
    Route::get('chat/list', 'v1\ChatController@list');
    Route::delete('chat/delete', 'v1\ChatController@delete');
    Route::put('chat/check', 'v1\ChatController@check');
    Route::get('chat_robot/list', 'v1\ChatController@chat_robot_list');
    Route::post('chat_robot/create', 'v1\ChatController@chat_robot_store');
    Route::get('chat_smart/list', 'v1\ChatController@chat_smart_list');
    Route::post('chat_smart/create', 'v1\ChatController@chat_smart_store');
    /***********************************聊天管理（红包）***************************************/
    Route::get('red_packet/list', 'v1\RedPacketController@list'); // 聊天室红包列表
    Route::get('red_packet/round_num', 'v1\RedPacketController@round_num'); // 聊天室红包随机金额
    Route::post('red_packet/store', 'v1\RedPacketController@store'); // 聊天室新增红包
    Route::put('red_packet/update', 'v1\RedPacketController@update'); // 聊天室更新红包
    /***********************************操作日志***************************************/
    //操作日志
    Route::get('operationLog/index', 'v1\OperationLogController@index');
    //删除
    Route::delete('operationLog/cDestroy', 'v1\OperationLogController@cDestroy');
    //批量删除
    Route::delete('operationLog/cDestroyAll', 'v1\OperationLogController@cDestroyAll');
    /***********************************数据库管理***************************************/
    //数据表管理
    Route::get('dataBase/index','v1\DataBaseController@index');
    // 表详情
    Route::get('dataBase/tableData', 'v1\DataBaseController@tableData');
    // 备份表
    Route::post('dataBase/backUp', 'v1\DataBaseController@backUp');
    // 备份列表
    Route::get('dataBase/restoreData', 'v1\DataBaseController@restoreData');
    // 查询文件详情
    Route::get('dataBase/getFiles', 'v1\DataBaseController@getFiles');
    // 删除
    Route::delete('dataBase/delSqlFiles', 'v1\DataBaseController@delSqlFiles');
    /***********************************数据看板***************************************/
    //数据看板
    Route::get('index/dashboard','v1\IndexController@dashboard');
    // 接口请求图表数据
    Route::get('index/getLogCountList','v1\IndexController@getLogCountList');
    // 接口请求图表数据
    Route::get('index/getViewCountList','v1\IndexController@getViewCountList');
    // 接口请求图表数据[ios android]今日下载量
    Route::get('index/getDownloads','v1\IndexController@getDownloads');
    /***********************************项目管理***************************************/
    //项目管理
    Route::get('project/index', 'v1\ProjectController@index');
    //添加
    Route::post('project/store', 'v1\ProjectController@store');
    //编辑页面
    Route::get('project/edit', 'v1\ProjectController@edit');
    //编辑提交
    Route::put('project/update', 'v1\ProjectController@update');
    //调整状态
    Route::put('project/status', 'v1\ProjectController@status');

    // laravel 高级功能应用
    Route::get('laravel/event', 'v1\LaravelController@event');

});

