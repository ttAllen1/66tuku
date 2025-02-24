<?php
namespace Modules\Admin\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\Forecast;
use Modules\Admin\Services\BaseApiService;

class LiuHeForecastsService extends BaseApiService
{
    /**
     * 竞猜列表数据
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
//        $odds = [
//            [
//                "name"  => "特肖1",
//                "odd"   => 1.49
//            ],
//            [
//                "name"  => "特肖2",
//                "odd"   => 2.49
//            ]
//        ];
//        dd(json_encode($odds));
        $list = Forecast::query()
            ->where('pid', 0)
            ->when($data['name'], function($query) use ($data) {
                $query->where('name', $data['name']);
            })
//            ->with(['pName'=>function($query) {
//                $query->select('id', 'pid', 'name');
//            }])
            ->get();
        if ($list->isEmpty()) {
            return $this->apiSuccess('',[
                'list'          => [],
            ]);
        }
        $list = $list->toArray();
        foreach ($list as $k => $v) {
            if (!$v['odds']) {
                $list[$k]['odds'] = [
                    [
                        "name"  => $v['name']."1",
                        "odd"   => $v['min_bet_money']
                    ]
                ];
            }
        }
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
//        $data['odds'] = array_map(function ($item) {
//            return json_decode($item, true);
//        }, $data['odds']);
//        if ($data['is_bet']==1) {
//
//        }

        return $this->commonUpdate(Forecast::query(), $data['id'], $data);
    }

}
