<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFocuson extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_userid');
    }

    /**
     * @return BelongsTo
     */
    public function fromuser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
