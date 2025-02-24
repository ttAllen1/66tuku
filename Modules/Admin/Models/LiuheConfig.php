<?php
namespace Modules\Admin\Models;

class LiuheConfig extends BaseApiModel
{
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }
}
