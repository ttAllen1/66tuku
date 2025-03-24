<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Common\Services\BaseService;

class UserImage extends BaseApiModel
{
//    protected $fillable = ['img_url'];

    /**
     * @param $value
     * @return string
     */
    public function getImgUrlAttribute($value): string
    {
//        return $value ? (Str::startsWith($value, 'http') ? $value : (new BaseService())->getHttp().'/'.$value) : '';
//        return $value ? (Str::startsWith($value, 'http') ? $value : 'https://api.48tkapi.com/'.$value) : '';
        return $value ? (Str::startsWith($value, 'http') ? $value : 'https://api.49api66.com:8443/'.$value) : '';
    }

    // 监听删除事件
    protected static function booted()
    {
        parent::booted();
        static::deleting(function ($image) {
            // 删除服务器上的图片资源
            try{
                Storage::disk('api_delete')->delete($image->original['img_url']);
            }catch (\Exception $exception) {
                return ;
            }
        });
    }

    /**
     * 获取拥有此图片得模型【用户意见...】
     * @return MorphTo
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
