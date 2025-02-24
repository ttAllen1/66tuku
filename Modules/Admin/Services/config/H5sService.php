<?php

/**
 * H5地址服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\Ip;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Common\Exceptions\ApiException;

class H5sService extends BaseApiService
{
    /**
     * 列表
     * @description
     **/
    public function index(){
        $configs = AuthActivityConfig::query()
            ->where('k', 'h5_urls')
            ->first()
            ->toArray();

        $list = [];
        $list[$configs['k']] = json_decode($configs['v'], true);
        $list = array_map(function($item) {
            return ['url'=>$item];
        }, $list[$configs['k']]);
//        dd($list);
        return $this->apiSuccess('',[
            'list'  =>$list,
        ]);
    }

    /**
     * 添加
     * @param array $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function store(array $data): JsonResponse
    {
        try{
            $res = AuthActivityConfig::query()
                ->where('k', 'h5_urls')
                ->first()
                ->toArray();

            $list = json_decode($res['v'], true);

            if ($list && in_array($data['url'], $list)) {
                return $this->apiError('该链接地址已存在');
            }
            $list[] = $data['url'];
            $data['h5_urls'] = json_encode($list);
            AuthActivityConfig::query()->updateOrInsert(
                ['k' => 'h5_urls'],
                ['v' => $data['h5_urls']]
            );

            return $this->apiSuccess();
        }catch(\Exception $exception) {
            return $this->apiError();
        }
    }

    /**
     * 修改提交
     * @param array $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function update(array $data): JsonResponse
    {
        try{
            $res = AuthActivityConfig::query()
                ->where('k', 'h5_urls')
                ->first()
                ->toArray();
            $list = json_decode($res['v'], true);

            foreach($list as $k => $v) {
                if ($v == $data['old']) {
                    $list[$k] = $data['url'];
                }
            }
            $data['h5_urls'] = json_encode($list);
            AuthActivityConfig::query()->updateOrInsert(
                ['k' => 'h5_urls'],
                ['v' => $data['h5_urls']]
            );

            return $this->apiSuccess();
        }catch (\Exception $exception) {
            return $this->apiError();
        }
    }

    /**
     * 删除
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function delete($data): JsonResponse
    {
        try{
            $res = AuthActivityConfig::query()
                ->where('k', 'h5_urls')
                ->first()
                ->toArray();
            $list = json_decode($res['v'], true);

            foreach($list as $k => $v) {
                if ($v == $data['url']) {
                    unset($list[$k]);
                }
            }
            sort($list);
            $data['h5_urls'] = json_encode($list);
            AuthActivityConfig::query()->updateOrInsert(
                ['k' => 'h5_urls'],
                ['v' => $data['h5_urls']]
            );

            return $this->apiSuccess();
        }catch (\Exception $exception) {
            return $this->apiError();
        }
    }
}
