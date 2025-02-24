<?php

namespace Modules\Admin\Models;

use Modules\Api\Models\BaseApiModel;

class PicSeries extends BaseApiModel
{
    protected $casts = [
        'index_pic_ids'   => 'array',
        'index_pic_names'   => 'array'
    ];
}
