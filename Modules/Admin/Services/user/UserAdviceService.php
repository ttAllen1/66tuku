<?php
/**
 * @Name 用户意见管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Modules\Admin\Models\UserAdvice;
use Modules\Admin\Services\BaseApiService;

class UserAdviceService extends BaseApiService
{
    /**
     * @name 用户建议列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = UserAdvice::query()
            ->when($data['is_reply'], function($query) use ($data) {
                $query->where('is_reply', $data['is_reply']);
            })
            ->with(['user'=>function($query) {
                $query->select('id', 'account_name');
            },'images'=>function($query) {
                $query->select('id', 'img_url', 'imageable_id', 'open');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        $data['is_reply'] = 1;
        return $this->commonUpdate(UserAdvice::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(UserAdvice::query(),$id);
    }
}
