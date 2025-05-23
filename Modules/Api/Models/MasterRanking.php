<?php
namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRanking extends BaseApiModel
{

    public function config(): BelongsTo
    {
        return $this->belongsTo(MasterRankingConfig::class, 'config_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
