<?php
namespace Modules\Api\Models;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Admin\Models\UserGroup;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $guard = 'users';
    protected $guarded = [];
    protected $hidden = [
        'password'
    ];
    /**
     * @name jwt标识
     * @description
     **/
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * @name jwt自定义声明
     * @description
     **/
    public function getJWTCustomClaims()
    {
        return [];
    }
    /**
     * @name 更新时间为null时返回
     * @description
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }

    /**
     * @name 时间格式传唤
     * @description
     **/
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    static function getUserInfoByAccountName(string $account_name)
    {
        return self::where(['account_name'=>$account_name])->select('id', 'name', 'chat_user', 'status', 'is_lock')->first();
    }
    static function getUserInfoById(int $id)
    {
        return self::where(['id'=>$id])->select('id', 'name', 'nickname', 'account_name', 'avatar', 'avatar_is_check', 'invite_code', 'mobile', 'account_balance', 'is_balance_freeze', 'level_id', 'is_forbid_speak', 'register_at')->first();
    }
    static function updateUserPassword(array $data)
    {
        return self::where('account_name', $data['account_name'])->update(['password'=>bcrypt($data['password'])]);
    }
    static function getUserId(array $data)
    {
        return self::where($data)->select('id')->first()->id;
    }

    /**
     * 用户单个关注信息
     * @return HasOne
     */
    public function focus(): HasOne
    {
        return $this->hasOne(UserFocuson::class, 'to_userid', 'id');
    }

    /**
     * 用户关联分组
     * @return HasOne
     */
    public function group(): HasOne
    {
        return $this->hasOne(UserGroup::class, 'user_id', 'id');
    }
}
