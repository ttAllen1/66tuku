<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class Complaint extends BaseApiModel
{
    public function images()
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complaintable(): MorphTo
    {
        return $this->morphTo();
    }
}
