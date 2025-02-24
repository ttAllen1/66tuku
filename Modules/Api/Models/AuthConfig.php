<?php

namespace Modules\Api\Models;

class AuthConfig extends BaseApiModel
{
    protected $casts = [
        'quota_list'    => 'array'
    ];
    /**
     * 查询配置数据
     * @return mixed
     */
    static function getinfo()
    {
         $config = self::select('name', 'logo_id', 'wide_logo_id', 'not_find_img_id', 'avatar_id',  'about_us', 'video_url', 'ws_url', 'server_url', 'h5_url', 'download_url', 'main_domain', 'xg_live', 'am_live', 'xam_live', 'oldam_live', 'ios_version', 'android_version', 'ios_must_update', 'android_must_update', 'register_send_sms', 'login_send_sms', 'found_send_sms', 'wydun', 'wydun_captcha_id', 'register_gift', 'ios_download_url', 'android_download_url', 'old_ws_url', 'cloud_url', 'ios_resource_download_url', 'ad_img_url', 'vpn_url', 'bet_url', 'aws_video_url')
             ->with([
                 'logo_one' => function($query) {
                    $query->where(['status' => 1])->select('id', 'url')->first();
                 },
                 'wideLogo_one' => function($query) {
                     $query->where(['status' => 1])->select('id', 'url')->first();
                 },
                 'notFindImg_one' => function($query) {
                     $query->where(['status' => 1])->select('id', 'url')->first();
                 },
            ])
             ->first()
             ->toArray();
        $config['ios_must_update'] = (bool)$config['ios_must_update'];
        $config['android_must_update'] = (bool)$config['android_must_update'];
        $config['register_send_sms'] = (bool)$config['register_send_sms'];
        $config['login_send_sms'] = (bool)$config['login_send_sms'];
        $config['found_send_sms'] = (bool)$config['found_send_sms'];
        $config['register_gift'] = (bool)$config['register_gift'];
//         $swiperIds = explode(',', $config['swiper_ids']);
//         if (count($swiperIds) > 0 && !empty($swiperIds[0]))
//         {
//             $images = AuthImage::select(['id', 'url'])->find($swiperIds)->pluck('url')->toArray();
//             $config['swiper_images'] = $images;
//         }
         if ($config['logo_one'])
         {
             $config['logo'] = $config['logo_one']['url'];
         }
         if ($config['wide_logo_one']) {
             $config['wide_logo'] = $config['wide_logo_one']['url'];
         }
         if ($config['not_find_img_one'])
         {
             $config['notfind_image'] = $config['not_find_img_one']['url'];
         }
         unset($config['logo_id']);
         unset($config['wide_logo_id']);
         unset($config['not_find_img_id']);
         unset($config['logo_one']);
         unset($config['wide_logo_one']);
         unset($config['not_find_img_one']);
         unset($config['avatar_id']);
//         unset($config['swiper_ids']);
         return $config;
    }

    /**
     * 关联LOGO图片资源表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function logo_one()
    {
        return $this->hasOne(AuthImage::class,'id','logo_id');
    }

    /**
     * 关联宽LOGO图片资源表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wideLogo_one()
    {
        return $this->hasOne(AuthImage::class,'id','wide_logo_id');
    }

    /**
     * 关联印刷中图片资源表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function notFindImg_one()
    {
        return $this->hasOne(AuthImage::class,'id','not_find_img_id');
    }

    /**
     * 默认头像
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function avatar()
    {
        return $this->hasOne(AuthImage::class,'id','avatar_id');
    }

    public static function image_status()
    {
        return self::where('id',1)->value('image_status');
    }

}
