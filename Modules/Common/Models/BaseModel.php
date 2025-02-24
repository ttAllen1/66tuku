<?php
/**
 * @Name  模型基类
 * @Description  用于所有的数据库定义基类
 */

namespace Modules\Common\Models;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class BaseModel extends EloquentModel
{
    /**
     * @name
     * @description
     * @method  GET
     * @param
     * @return JSON
     **/
    protected $primaryKey = 'id';
    /**
     * @name id是否自增
     * @description
     * @return Bool
     **/
    public $incrementing = true;
    /**
     * @name   表id是否为自增
     * @description
     * @return String
     **/
    protected $keyType = 'int';
    /**
     * @name 指示是否自动维护时间戳
     * @description
     * @return Bool
     **/
    public $timestamps = true;
    /**
     * @name 该字段可被批量赋值
     * @description
     * @return Array
     **/
    protected $fillable = [];
    /**
     * @name 该字段不可被批量赋值
     * @description
     * @return Array
     **/
    protected $guarded = [];

    /**
     * @name 时间格式传唤
     * @description
     **/
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
