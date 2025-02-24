<?php

/**
 * @Name 文章分类服务
 * @Description
 */

namespace Modules\BlogApi\Services\articleType;

use Modules\BlogApi\Models\BlogArticleType;
use Modules\BlogApi\Services\BaseApiService;

class ArticleTypeService extends BaseApiService
{
    public function typeList(){
        $model = BlogArticleType::query();
        $list = $model->select('id','name','pid')
            ->where(['status'=>1,'project_id'=>$this->projectId])
            ->orderBy('sort','asc')->orderBy('id','desc')
            ->get()->toArray();
        return $this->apiSuccess('',$this->tree($list));
    }
}
