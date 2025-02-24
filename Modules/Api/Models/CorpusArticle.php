<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Common\Exceptions\CustomException;

class CorpusArticle extends BaseApiModel
{
    /**
     * 设置表名
     * @param $data
     * @return CorpusArticle
     * @throws CustomException
     */
    public static function setTables($data)
    {
        $tableIdx = self::getTableIdx($data);
        $that = new CorpusArticle();
        $that->table = 'corpus_articles' . $tableIdx;
        $that->tableIdx = $tableIdx;
        return $that;
    }

    public static function getTableIdx($data)
    {
        if (!isset($data['corpusTypeId']))
        {
            throw new CustomException(['message'=>'类型错误']);
        }
        $corpusTypeId = intval($data['corpusTypeId']);
        $tableIdx = CorpusType::where('id', $corpusTypeId)->value('table_idx');
        if (!$tableIdx)
        {
            throw new CustomException(['message'=>'类型错误']);
        }
        return $tableIdx;
    }

    /**
     * 关联点赞
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function follow()
    {
        $this->alias();
        return $this->morphOne(UserFollow::class, 'followable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function corpusType()
    {
        return $this->belongsTo(CorpusType::class, 'corpusTypeId', 'id');
    }

    /**
     * 获取此图片得所有评论
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        $this->alias();
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 关联举报
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function complaint()
    {
        $this->alias();
        return $this->morphMany(Complaint::class, 'complaintable');
    }

    /**
     * 多态类型别名
     * @return void
     */
    protected function alias()
    {
        $tableIdx = str_replace('corpus_articles', '', $this->table);
        Relation::morphMap([
            'Modules\Api\Models\CorpusArticle' . $tableIdx => 'Modules\Api\Models\CorpusArticle'
        ]);
    }

}
