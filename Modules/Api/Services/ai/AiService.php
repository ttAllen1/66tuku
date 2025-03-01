<?php

namespace Modules\Api\Services\ai;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Ai;
use Modules\Api\Services\BaseApiService;

class AiService extends BaseApiService
{
    public function config()
    {
        return $this->apiSuccess('', [
            'childTypeList' => [
                [
                    'key'   => -1,
                    'name'  => '全部',
                ],
                [
                    'key'   => 0,
                    'name'  => '低风险',
                ],
                [
                    'key'   => 1,
                    'name'  => '低投注',
                ]
            ],
            'openOrderList' => [
                [
                    'key'   => -1,
                    'name'  => '全部',
                ],
                [
                    'key'   => 1,
                    'name'  => '落球顺序',
                ],
                [
                    'key'   => 0,
                    'name'  => '大小顺序',
                ]
            ],
            'periodCountList' => [
                [
                    'key'   => 1,
                    'name'  => '1',
                ],
                [
                    'key'   => 2,
                    'name'  => '2',
                ],
                [
                    'key'   => 3,
                    'name'  => '3',
                ],
                [
                    'key'   => 4,
                    'name'  => '4',
                ],
                [
                    'key'   => 5,
                    'name'  => '5',
                ],
                [
                    'key'   => 6,
                    'name'  => '6',
                ],
                [
                    'key'   => 7,
                    'name'  => '7',
                ],
                [
                    'key'   => 8,
                    'name'  => '8',
                ],
                [
                    'key'   => 9,
                    'name'  => '9',
                ],
                [
                    'key'   => 10,
                    'name'  => '10',
                ]
            ],
            'typeList'       => [
                [
                    'key'   => "",
                    'name'  => '全部',
                ],
                [
                    'key'   => '号码',
                    'name'  => '号码',
                ],
                [
                    'key'   => '生肖',
                    'name'  => '生肖',
                ],
                [
                    'key'   => '大小',
                    'name'  => '大小',
                ],
                [
                    'key'   => '单双',
                    'name'  => '单双',
                ],
                [
                    'key'   => '波色',
                    'name'  => '波色',
                ],
                [
                    'key'   => '尾数',
                    'name'  => '尾数',
                ],
                [
                    'key'   => '头数',
                    'name'  => '头数',
                ],
                [
                    'key'   => '合数',
                    'name'  => '合数',
                ],
            ]
        ]);
    }

    public function list()
    {
        $lotteryType = request()->input('lotteryType', 1);
        $nextIssue = date('Y') . $this->getNextIssue($lotteryType);
        $res = Ai::query()
            ->orderBy('id')
            ->where('lotteryType', $lotteryType)
            ->where('period', $nextIssue)
            ->select(['id', 'lotteryType', 'childType', 'period', 'periodCount', 'thumbUrl', 'title', 'type'])
            ->get()->toArray();

        return $this->apiSuccess('', $res);
    }

    public function detail($id)
    {

        $model = Ai::query()
            ->where('id', $id)
            ->firstOrFail();
        $model->increment('viewCount');
        return $this->apiSuccess('', $model->toArray());


    }
}
