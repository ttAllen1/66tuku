<?php
namespace Modules\Admin\Models;

use Modules\Common\Services\BaseService;

class Ad extends BaseApiModel
{
    protected $appends = ['position_type'];

    public static $_position_type = [
        ['value'=>0, 'label'=>'请选择广告位置'],
        ['value'=>1, 'label'=>'首页轮播图'],
        ['value'=>2, 'label'=>'详情广告'],
        ['value'=>3, 'label'=>'列表广告'],
        ['value'=>4, 'label'=>'网址大全'],
        ['value'=>5, 'label'=>'担保平台'],
        ['value'=>6, 'label'=>'首页图片列表'],
        ['value'=>7, 'label'=>'首页启动图'],
        ['value'=>8, 'label'=>'首页推荐网址'],
        ['value'=>9, 'label'=>'详情广告（文字版）'],
    ];

    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }

    public function getAdImageAttribute($value)
    {
        return $value ? (strpos($value, 'http') === 0 ? $value : (new BaseService())->getHttp().$value) : '';
    }

    public function getPositionTypeAttribute()
    {
        return [
            1   => '首页轮播图',
            2   => '详情广告',
            3   => '列表广告',
            4   => '网址大全',
            5   => '担保平台',
            6   => '首页图片列表',
        ];
    }

}
