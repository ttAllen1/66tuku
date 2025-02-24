<?php
/**
 * @Name 数据库管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\CommonNameRequest;
use Modules\Admin\Http\Requests\TableRequest;
use Modules\Admin\Services\dataBase\DataBaseService;
use Illuminate\Http\Request;
class DataBaseController extends BaseApiController
{
    /**
     * @name 列表
     * @description
     * @method  GET
     * @param  name String 表名
     * @return JSON
     **/
    public function index(Request $request)
    {
        return (new DataBaseService())->index($request->get('name'));
    }
    /**
     * @name 表详情
     * @description
     * @method  GET
     * @param table  String 表名
     * @return JSON
     **/
    public function tableData(TableRequest $request)
    {
        return (new DataBaseService())->tableData($request->get('table'));
    }
    /**
     * @name 备份表
     * @description
     * @method  GET
     * @param tables  Array 表名多个
     * @return JSON
     **/
    public function backUp(Request $request){
        return (new DataBaseService())->backUp($request->get('tables'));
    }
    /**
     * @name 备份文件列表
     * @description
     * @method  GET
     * @return JSON
     **/
    public function restoreData(){
        return (new DataBaseService())->restoreData();
    }
    /**
     * @name 读取文件内容
     * @description
     * @method  GET
     * @param name String   文件名称
     * @return JSON
     **/
    public function getFiles(CommonNameRequest $request) {
        return (new DataBaseService())->getFiles($request->get('name'));
    }
    /**
     * @name 删除sql文件
     * @description
     * @method  DELETE
     * @param name String   文件名称
     * @return JSON
     **/
    public function delSqlFiles(CommonNameRequest $request) {
        return (new DataBaseService())->delSqlFiles($request->get('name'));
    }
}
