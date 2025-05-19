<?php
namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRanking extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
