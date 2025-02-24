<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserMushin extends BaseApiModel
{
    public function scopeWithMushin($query)
    {
        $today = date('Y-m-d H:i:s');
        return $query
            ->where('user_id', auth('user')->id())
            ->where(function($query) use ($today) {
                $query->where(function($query) use ($today) {
                    $query
                        ->where('mushin_start_date', '<=', $today)
                        ->where('mushin_end_date', '>=', $today);
                })->orwhere('is_forever', 1);
            });
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasOne
     */
    public function mushin(): HasOne
    {
        return $this->hasOne(Mushin::class, 'id', 'mushin_id');
    }
}
