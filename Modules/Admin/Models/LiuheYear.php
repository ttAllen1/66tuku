<?php
namespace Modules\Admin\Models;

class LiuheYear extends BaseApiModel
{
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }
}
