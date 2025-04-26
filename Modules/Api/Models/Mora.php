<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mora extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function joinUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'join_user_id', 'id');
    }
}
