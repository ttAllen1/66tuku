<?php
/**
 * 平台公共相关接口
 * @Description
 */
namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\PwdRequest;
use Modules\Admin\Services\admin\UpdatePasswordService;
use Modules\Admin\Services\auth\ConfigService;
use Modules\Admin\Services\auth\MenuService;
use Modules\Admin\Services\auth\TokenService;

class IndexController extends BaseApiController
{
    /**
     * @name 刷新token
     * @description
     * @method  PUT
     * @return JSON
     **/
    public function refreshToken()
    {
        return (new TokenService())->refreshToken();
    }
    /**
     * @name 退出登录
     * @description
     * @method  DELETE
     * @return JSON
     **/
    public function logout()
    {
        return (new TokenService())->logout();
    }
    /**
     * @name 清除缓存
     * @description
     * @method  DELETE
     * @return JSON
     **/
    public function outCache()
    {
        return (new ConfigService())->outCache();
    }
    /**
     * @name 修改密码
     * @description
     * @method  PUT
     * @param  y_password String 原密码
     * @param  password String 密码
     * @param  password_confirmation String 确认密码
     * @return JSON
     **/
    public function upadtePwdView(PwdRequest $request)
    {
        return (new UpdatePasswordService())->upadtePwdView($request->only(['y_password','password']));
    }
    /**
     * @name 获取平台信息
     * @description
     * @method  GET
     **/
    public function getMain()
    {
        return (new ConfigService())->getMain();
    }
    /**
     * @name 获取左侧栏
     * @description
     * @method  GET
     * @param  id   Int    模块id
     **/
    public function getMenu(CommonIdRequest $request)
    {
        return (new MenuService())->getMenu($request->get('id'));
    }
    /**
     * @name 获取模块
     * @description
     * @method  GET
     * @return JSON
     **/
    public function getModel()
    {
        return (new MenuService())->getModel();
    }
    /**
     * @name 获取管理员信息
     * @description
     * @method  GET
     * @return JSON
     **/
    public function info(){
        return (new TokenService())->info();
    }

    /**
     * @name 数据看板
     * @description
     * @method  GET
     * @return JSON
     **/
    public function dashboard(){
        return (new ConfigService())->dashboard();
    }
    /**
     * @name 接口请求图表数据
     * @description
     * @method  GET
     * @return JSON
     **/
    public function getLogCountList(){
        return (new ConfigService())->getLogCountList();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getViewCountList(Request $request) :JsonResponse
    {
        return (new ConfigService())->getViewCountList($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDownloads(Request $request):JsonResponse
    {
        return (new ConfigService())->getDownloads($request->all());
    }

    /**
     * @name 获取地区数据
     * @description
     * @method  GET
     * @return JSON
     **/
    public function getAreaData(){
        return (new ConfigService())->getAreaData();
    }
    /**
     * @name 转换编辑器内容
     * @description
     * @method  POST
     * @param  content String  编辑器内容
     * @return JSON
     **/
    public function setContent(Request $request){
        return (new ConfigService())->setContentU($request->get('content'));
    }
}
