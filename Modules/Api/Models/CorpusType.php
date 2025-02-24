<?php

namespace Modules\Api\Models;

class CorpusType extends BaseApiModel
{
    public function articles()
    {
        return $this->hasOne(CorpusArticle::class, 'corpusTypeId', 'id');
    }
}
