<?php

/**
 * @Name 审核服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\ImgPrefix;
use Modules\Admin\Services\BaseApiService;

class ImgPrefixService extends BaseApiService
{
    public function index(){
        $years1 = range(2025, 2020);
        $years = [];
        foreach ($years1 as $k => $v) {
            $years[$k]['year'] = $v;
        }
        $list = ImgPrefix::query()
            ->orderBy('year', 'desc')
            ->get()
            ->toArray();
        if (!$list) {
            foreach ($years as $k => $year) {
                $years[$k]['year'] = $year['year'];
                $years[$k]['xg_img_prefix'] = [];
                $years[$k]['xam_img_prefix'] = [];
                $years[$k]['tw_img_prefix'] = [];
                $years[$k]['xjp_img_prefix'] = [];
                $years[$k]['am_img_prefix'] = [];
                $years[$k]['kl8_img_prefix'] = [];
                $years[$k]['oldam_img_prefix'] = [];
//                $years[$k]['am_img_prefix_2'] = '';
            }
        } else {
            foreach ($years as $k => $year) {
                foreach ($list as $vv) {
                    if ($vv['year'] == $year['year']) {
                        $years[$k]['year'] = $year['year'];
                        $years[$k]['xg_img_prefix'] = json_decode($vv['xg_img_prefix'], true);
                        $years[$k]['xam_img_prefix'] = json_decode($vv['xam_img_prefix'], true);
                        $years[$k]['tw_img_prefix'] = json_decode($vv['tw_img_prefix'], true);
                        $years[$k]['xjp_img_prefix'] = json_decode($vv['xjp_img_prefix'], true);
                        $years[$k]['am_img_prefix'] = json_decode($vv['am_img_prefix'], true);
                        $years[$k]['kl8_img_prefix'] = json_decode($vv['kl8_img_prefix'], true);
                        $years[$k]['oldam_img_prefix'] = json_decode($vv['oldam_img_prefix'], true);
//                        $years[$k]['am_img_prefix_2'][] = $vv['am_img_prefix_2'];
                    }
                }
            }
        }
//        foreach ($years as $k => $v) {
//            if (!isset($v['am_img_prefix'])) {
//                $years[$k]['am_img_prefix'] = ["https://8.tuku.fit/galleryfiles/system/big-pic/"];
//            }
//            if (!isset($v['tw_img_prefix'])) {
//                $years[$k]['tw_img_prefix'] = ["https://lqt.smhuyjhb.com/taiwan/"];
//            }
//            if (!isset($v['xam_img_prefix'])) {
//                $years[$k]['xam_img_prefix'] = ["https://tk2.zaojiao365.net:4949/"];
//            }
//            if (!isset($v['xg_img_prefix'])) {
//                $years[$k]['xg_img_prefix'] = ["https://tk.zaojiao365.net:4949/"];
//            }
//            if (!isset($v['xjp_img_prefix'])) {
//                $years[$k]['xjp_img_prefix'] = ["https://tk5.zaojiao365.net:4949/"];
//            }
//        }

        return $this->apiSuccess('',[
            'list'          => $years,
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update( array $data)
    {

        foreach ($data as $k => $v) {
            $data[$k] = json_decode($v, true);
            unset($data[$k]['id']);
            unset($data[$k]['created_at']);
            unset($data[$k]['updated_at']);
            unset($data[$k]['am_img_prefix_2']);
        }
        foreach ($data as $k => $v) {
            $data[$k]['year'] = $v['year'];
            $data[$k]['xg_img_prefix'] = json_encode($v['xg_img_prefix']);
            $data[$k]['xam_img_prefix'] = json_encode($v['xam_img_prefix']);
            $data[$k]['tw_img_prefix'] = json_encode($v['tw_img_prefix']);
            $data[$k]['xjp_img_prefix'] = json_encode($v['xjp_img_prefix']);
            $data[$k]['am_img_prefix'] = json_encode($v['am_img_prefix']);
            $data[$k]['kl8_img_prefix'] = json_encode($v['kl8_img_prefix']);
            $data[$k]['oldam_img_prefix'] = json_encode($v['oldam_img_prefix']);
        }
//        dd($data);
        foreach ($data as $v) {
            ImgPrefix::query()->updateOrCreate(
                ["year"=>$v['year']],
                $v
            );
        }
        foreach ($data as $v) {
            $redisData[$v['year']][1] = $v['xg_img_prefix'];
            $redisData[$v['year']][2] = $v['xam_img_prefix'];
            $redisData[$v['year']][3] = $v['tw_img_prefix'];
            $redisData[$v['year']][4] = $v['xjp_img_prefix'];
            $redisData[$v['year']][5] = $v['am_img_prefix'];
            $redisData[$v['year']][6] = $v['kl8_img_prefix'];
            $redisData[$v['year']][7] = $v['oldam_img_prefix'];
        }

        Redis::set('img_prefix', json_encode($redisData));

        return $this->apiSuccess();
    }
}
