<?php
namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Common\Services\BaseService;

class UserCommentThree extends BaseApiModel
{

    protected $appends = ['time_str'];

    public function getTimeStrAttribute()
    {

        return (new BaseService())->format_time(strtotime($this->attributes['created_at']));
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

    public function children()
    {
        return $this->hasMany(self::class, 'top_id', 'id');
    }

    /**
     * 当前评论用户信息
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 上一级评论用户信息
     * @return HasOne
     */
    public function upUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'up_user_id');
    }

    /**
     * 获取拥有此评论得模型【图片详情...】
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取此评论得所有图片
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

    /**
     * 获取此评论得所有回复
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserCommentThree::class, 'commentable');
    }

    /**
     * 获取此评论的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

    public function corpusArticle(): HasOne
    {
        return $this->hasOne(CorpusArticle::class, 'id', 'commentable_id');
    }

    public function picDetail(): HasOne
    {
        return $this->hasOne(PicDetail::class, 'id', 'commentable_id');
    }

    public function discuss(): HasOne
    {
        return $this->hasOne(Discuss::class, 'id', 'followable_id');
    }

    public function humorou(): HasOne
    {
        return $this->hasOne(Humorous::class, 'id', 'followable_id');
    }

    public function userDiscovery(): HasOne
    {
        return $this->hasOne(UserDiscovery::class, 'id', 'followable_id');
    }

}
