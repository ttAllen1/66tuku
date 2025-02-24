<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFollow extends BaseApiModel
{
    use SoftDeletes;
    /**
     * @return MorphTo
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasOne
     */
    public function corpusArticle(): HasOne
    {
        return $this->hasOne(CorpusArticle::class, 'id', 'followable_id');
    }

    /**
     * @return HasOne
     */
    public function picDetail(): HasOne
    {
        return $this->hasOne(PicDetail::class, 'id', 'followable_id');
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
