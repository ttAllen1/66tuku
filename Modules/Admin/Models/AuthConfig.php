<?php
namespace Modules\Admin\Models;

class AuthConfig extends BaseApiModel
{
    protected $casts = [
        'quota_list'    => 'array'
    ];
    /**
	 * @name 关联后台站点logo图片
	 * @description
	 **/
    public function logo_one()
    {
        return $this->hasOne(AuthImage::class,'id','logo_id');
    }

    /**
     * @name 关联会员默认avatar图片
     * @description
     **/
    public function user_avatar()
    {
        return $this->hasOne(AuthImage::class,'id','avatar_id');
    }

    /**
     * @name 关联横版Logo图片
     * @description
     **/
    public function wide_logo()
    {
        return $this->hasOne(AuthImage::class,'id','wide_logo_id');
    }

    /**
     * @name 关联图片还在刷新中
     * @description
     **/
    public function not_find_img()
    {
        return $this->hasOne(AuthImage::class,'id','not_find_img_id');
    }
}
