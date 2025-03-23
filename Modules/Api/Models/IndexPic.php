<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

class IndexPic extends BaseApiModel
{
    public function picOther()
    {
//        return $this->hasOne(YearPic::class, 'pictureTypeId', 'pictureTypeId')->where('year', date('Y'));
        return $this->hasOne(YearPic::class, 'pictureTypeId', 'pictureTypeId');
    }

    /**
     * @return HasOne
     */
    public function picDetail()
    {
        return $this->hasOne(PicDetail::class, 'pictureTypeId', 'pictureTypeId')
            ->where('year', date('Y'))
            ->orderBy('issue', 'desc')
            ->limit(1);
    }
}
