<?php
namespace Modules\Admin\Models;

class ImgPrefix extends BaseApiModel
{
    protected $casts = [
        'xg_img_prefix' => 'array',
        'xam_img_prefix' => 'array',
        'tw_img_prefix' => 'array',
        'xjp_img_prefix' => 'array',
        'am_img_prefix' => 'array',
        'kl8_img_prefix' => 'array',
        'oldam_img_prefix' => 'array',
    ];
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }
}
