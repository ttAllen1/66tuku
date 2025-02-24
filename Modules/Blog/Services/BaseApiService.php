<?php
/**
 * @Name 当前模块服务基类
 * @Description
 */

namespace Modules\Blog\Services;


use Modules\Common\Services\BaseService;
use Modules\Admin\Services\auth\TokenService;
class BaseApiService extends BaseService
{
    protected $projectId = '';
    public function __construct()
    {
        $this->projectId = (new TokenService())->my()->project_id;
        parent::__construct();
    }
}
