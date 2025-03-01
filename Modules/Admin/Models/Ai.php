<?php
namespace Modules\Admin\Models;


use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Api\Models\UserComment;

class Ai extends BaseApiModel
{

    /**
     * 获取此幽默竞猜的所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }
}
