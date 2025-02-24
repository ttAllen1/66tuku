<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\User;
use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\Platform;

class UserPlatQuota extends BaseApiModel
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
    public function plat(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'plat_id', 'id');
    }
}
