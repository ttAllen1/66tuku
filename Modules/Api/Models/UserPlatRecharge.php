<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlatRecharge extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user_plats(): BelongsTo
    {
        return $this->belongsTo(UserPlatform::class, 'plat_id', 'plat_id');
    }

    /**
     * @return BelongsTo
     */
    public function plats(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'plat_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
