<?php
/**
 * @Name 用户禁言管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Modules\Admin\Models\Mushin;
use Modules\Admin\Models\UserMushin;
use Modules\Admin\Services\BaseApiService;

class UserMushinService extends BaseApiService
{
    /**
     * @name 用户禁言列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = UserMushin::query()
            ->whereHas('user', function($query) use ($data) {
                $query
                    ->when($data['account_name'], function($query) use ($data) {
                        $query->where('account_name', 'like', '%'.$data['account_name'].'%');
                    })
                    ->when($data['name'], function($query) use ($data) {
                        $query->where('name', 'like', '%'.$data['name'].'%');
                    });
            })
            ->with(['mushin', 'user'=>function($query) {
                $query->select('id', 'account_name', 'name');
            }])
            ->where(function ($query) use ($data) {
                return $query->where('mushin_end_date', '>=', date("Y-m-d H:i:s"))
                    ->when($data['date_range'], function($query) use ($data) {
                        $query->where('mushin_start_date', '>=', $this->getDateFormat($data['date_range'][0]))
                            ->where('mushin_end_date', '<=', $this->getDateFormat($data['date_range'][1]));
                    })
                    ->orWhere('is_forever', 1);
            })

            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();
        $mushins = (new MushinService)->getMushins();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'mushins'       => $mushins,
            'total'         => $list['total']
        ]);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $arr)
    {
        $data = [];
        foreach ($arr['user_id'] as $k => $userId) {
            $data[$k]['user_id'] = $userId;
            $data[$k]['mushin_id'] = $arr['mushin_id'];
            $data[$k]['mushin_start_date'] = date('Y-m-d H:i:s');
            $data[$k]['mushin_end_date'] = date('Y-m-d H:i:s', strtotime('+'.$arr['mushin_days']. ' days'));
            $data[$k]['mushin_days'] = $arr['mushin_days'];
            $data[$k]['is_forever'] = $arr['is_forever'] ? 1 : 0;
            $data[$k]['created_at'] = date('Y-m-d H:i:s');
        }
        if (UserMushin::query()->insert($data)){
            return $this->apiSuccess('添加成功');
        }
        return $this->apiError('添加失败');
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        $data['user_id'] = $data['user_id'][0];
        $data['is_forever'] = $data['is_forever'] == 'true' ? 1 : 0;
        $data['mushin_start_date'] = date('Y-m-d H:i:s');
        $data['mushin_end_date'] = date('Y-m-d H:i:s', strtotime('+'.$data['mushin_days'].' days'));
        $data['updated_at'] = date('Y-m-d H:i:s');
        UserMushin::query()->where('id',$id)->update($data);
        return $this->apiSuccess('修改成功', ['mushin_end_date'=>$data['mushin_end_date']]);

    }

    /**
     * @name 删除
     * @description
     * @param id Int 权限id
     **/
    public function cDestroy($id){
        $id = is_array($id) ? $id : [$id];
        return $this->commonDestroy(UserMushin::query(),$id);
    }
}
