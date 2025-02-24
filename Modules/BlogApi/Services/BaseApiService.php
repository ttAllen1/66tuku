<?php
/**
 * @Name 当前模块服务基类
 * @Description
 */

namespace Modules\BlogApi\Services;


use Modules\BlogApi\Models\AuthProject;
use Modules\Common\Services\BaseService;
class BaseApiService extends BaseService
{
    protected $projectId = '';
    public function __construct()
    {
        $baseHttp = request()->header('basehttp');
        $id = AuthProject::query()->where(['url'=>$baseHttp])->value('id');
        if(!$id){
          $this->apiError('项目不存在！');
        }
        $this->projectId = $id;
        parent::__construct();
    }
}
