<?php

namespace Modules\Api\Models;

class UserChat extends BaseApiModel
{
    protected $casts = [
        'from'  => 'array',
        'to'    => 'array',
    ];
}
