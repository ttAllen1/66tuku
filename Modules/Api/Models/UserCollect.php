<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserCollect extends BaseApiModel
{
    /**
     * @return MorphTo
     */
    public function collectable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasOne
     */
    public function picDetail(): HasOne
    {
        return $this->hasOne(PicDetail::class, 'id', 'collectable_id');
    }

    /**
     * @return HasOne
     */
    public function humorou(): HasOne
    {
        return $this->hasOne(Humorous::class, 'id', 'collectable_id');
    }

    /**
     * @return HasOne
     */
    public function userDiscovery(): HasOne
    {
        return $this->hasOne(UserDiscovery::class, 'id', 'collectable_id');
    }
}
