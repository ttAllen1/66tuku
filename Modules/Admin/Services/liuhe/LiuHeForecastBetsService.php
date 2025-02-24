<?php
namespace Modules\Admin\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\ForecastBet;
use Modules\Admin\Services\BaseApiService;

class LiuHeForecastBetsService extends BaseApiService
{
    /**
     * 创建 ｜ 修改
     * @param array $params
     * @return JsonResponse|null
     */
    public function store(array $params): ?JsonResponse
    {
        $contents = $params['contents'];
        if (!is_array(json_decode($contents, true))) {
            return $this->apiError('contents数据格式不正确');
        }
//        $params['contents'] = array_map(function ($item) use ($params){
//            return json_decode($item, true);
//        }, $params['contents']);
//        $params['contents'] = json_encode($params['contents']);

        if(!empty($params['id'])) {
            return $this->commonUpdate(ForecastBet::query(), $params['id'], $params);
        } else {
            return $this->commonCreate(ForecastBet::query(), $params);
        }
    }

    /**
     * 竞猜列表数据
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $list = ForecastBet::query()
            ->where('pid', 0)
            ->when($data['name'], function($query) use ($data) {
                $query->where('name', $data['name']);
            })
            ->get();
        if ($list->isEmpty()) {
            return $this->apiSuccess('',[
                'list'          => [],
            ]);
        }
        $list = $list->toArray();

        return $this->apiSuccess('',[
            'list'          => $list,
        ]);
    }

    /**
     * 更新
     * @param $data
     * @return JsonResponse|null
     */
    public function update($data): ?JsonResponse
    {
        unset($data['p_name']);
        unset($data['odds']);

        return $this->commonUpdate(ForecastBet::query(), $data['id'], $data);
    }

}
