<?php
namespace Modules\Admin\Models;

class UserMushin extends BaseApiModel
{
    protected $fillable = ['user_id', 'mushin_id', 'mushin_start_date', 'mushin_end_date', 'mushin_days', 'is_forever'];

    public function setIsForeverAttribute($value)
    {
        return $value ? 1 : 0;
    }

    public function getIsForeverAttribute($value)
    {
        return $value === 1;
    }


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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function mushin() {
        return $this->belongsTo(Mushin::class, 'mushin_id', 'id');
    }

}
