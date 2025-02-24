<?php

namespace Modules\Api\Models;

class IndexPic extends BaseApiModel
{
    public function picOther()
    {
//        return $this->hasOne(YearPic::class, 'pictureTypeId', 'pictureTypeId')->where('year', date('Y'));
        return $this->hasOne(YearPic::class, 'pictureTypeId', 'pictureTypeId');
    }
}
