<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlatform extends BaseApiModel
{
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
