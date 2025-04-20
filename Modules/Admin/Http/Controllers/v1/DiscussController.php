<?php
/**
 * 论坛控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\discuss\DiscussService;
use Modules\Common\Exceptions\CustomException;

class DiscussController extends BaseApiController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @description
     * @method  GET
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new DiscussService())->index($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function update(Request $request): ?JsonResponse
    {
        return (new DiscussService())->update($request->input('id', 0), $request->except(['id', 'user']));
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function status(Request $request): ?JsonResponse
    {
        return (new DiscussService())->status($request->input('id', 0), $request->except(['id', 'user', 'images']));
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function delete(Request $request): ?JsonResponse
    {
        return (new DiscussService())->delete($request->input('id', 0));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return (new DiscussService())->store($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous(Request $request): JsonResponse
    {
        return (new DiscussService())->previous($request->all());
    }

    /**
     * 资料设置列表
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return (new DiscussService())->list($request->all());
    }

    public function update_is_index(Request $request): ?JsonResponse
    {
        return (new DiscussService())->update_is_index($request->input('id'), $request->all());
    }

    public function configs()
    {
//          【文官】鼠、兔、龙、羊、鸡、猪
//          【武将】牛、马、虎、蛇、猴、狗
//          男肖：鼠牛虎龙马猴狗
//          女肖：兔蛇羊鸡猪
        $type = [
            'dan-shuang'     => [
                [
                    'title' => '单双中特',
                    'num'   => 12,
                    'color' => '#FF0000'
                ],
                [
                    'title' => '单双24码',
                    'num'   => 12,
                    'color' => '#00FF00'
                ]
            ],
            'da-xiao'        => [
                [
                    'title' => '特码大小',
                ]
            ],
            'bo-se'          => [
                [
                    'title' => '一波中特',
                    'num'   => 1,
                    'color' => '#800080'
                ],
                [
                    'title' => '双波中特',
                    'num'   => 2,
                    'color' => '#808080'
                ],
            ],
            'wen-wu'          => [
                [
                    'title' => '文武中特',
                    'sub_title' => '文官',
                    'num'   => 1,
                    'color' => '#800080'
                ],
                [
                    'title' => '文武中特',
                    'sub_title' => '武官',
                    'num'   => 1,
                    'color' => '#808080'
                ],
            ],
            'tian-di'       => [
                [
                    'title' => '天地肖',
                    'sub_title' => '天肖',
                    'num'   => 1,
                    'color' => '#FFA500'
                ],
                [
                    'title' => '天地肖',
                    'sub_title' => '地肖',
                    'num'   => 1,
                    'color' => '#0000FF'
                ]
            ],
            'nan-nv'        => [
                [
                    'title' => '男女肖',
                    'sub_title' => '男肖',
                    'num'   => 1,
                    'color' => '#FFA500'
                ],
                [
                    'title' => '男女肖',
                    'sub_title' => '女肖',
                    'num'   => 1,
                    'color' => '#0000FF'
                ]
            ],
            'yin-yang'        => [
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阴肖',
                    'num'   => 1,
                    'color' => '#FFG500'
                ],
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阳肖',
                    'num'   => 1,
                    'color' => '#FFG500'
                ]
            ],
            'qin-qi-shu-hua' => [
                [
                    'title' => '琴棋书画',
                    'sub_title' => '琴棋',
                    'num'   => 1,
                    'color' => '#FFA500'
                ],
                [
                    'title' => '琴棋书画',
                    'sub_title' => '书画',
                    'num'   => 1,
                    'color' => '#0000FF'
                ]
            ],
            'jia-qin-ye-shou' => [
                [
                    'title' => '家禽野兽',
                    'sub_title' => '家禽',
                    'num'   => 1,
                    'color' => '#FFA500'
                ],
                [
                    'title' => '家禽野兽',
                    'sub_title' => '野兽',
                    'num'   => 1,
                    'color' => '#0000FF'
                ]
            ],
        ];
    }
}
