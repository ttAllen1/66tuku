<?php
namespace Modules\Admin\Models;

use Modules\Common\Services\BaseService;

class UserImages extends BaseApiModel
{
    protected $appends = ['imgUrlBase64'];
    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? strtotime($value) : '';
    }

    public function getImgUrlAttribute($value)
    {
        $value = $value ? (strpos($value, 'http') ===0 ? $value : (new BaseService())->getHttp().$value) : '';
//        if ($this->imageable_type == 'Modules\Admin\Models\Level') {
//
//            return $this->a = 'data:image/png;base64,' . base64_encode(file_get_contents($value));
//        }
        return $value;
    }

    public function getImgUrlBase64Attribute()
    {

//        if ($this->imageable_type == 'Modules\Admin\Models\Level') {
//            try{
//                return 'data:image/png;base64,' . base64_encode(file_get_contents($this->attributes['img_url']));
//            }catch (\Exception $exception) {
//                return '';
//            }
//
//        }
//        return '';
    }

    /**
     * 获取拥有此图片得模型【用户意见...】
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function imageable()
    {
        return $this->morphTo();
    }

}
