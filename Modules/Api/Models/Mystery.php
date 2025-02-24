<?php

namespace Modules\Api\Models;

class Mystery extends BaseApiModel
{
    protected $fillable = [];

    protected $casts = [
        'content'   => 'array'
    ];
}
