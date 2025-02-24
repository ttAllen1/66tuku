<?php
/**
 * @Name 操作日志模型
 * @Description
 */

namespace Modules\Admin\Models;

class AuthOperationLog extends BaseApiModel
{
    /**
     * @name 关联管理员
     * @description 多对一关系
     **/
    public function admin_one()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthAdmin','admin_id','id');
    }
}
