<?php
namespace Modules\Admin\Models;

class UserMessage extends BaseApiModel
{
    protected $primaryKey = 'id';

    protected $guarded = [];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
