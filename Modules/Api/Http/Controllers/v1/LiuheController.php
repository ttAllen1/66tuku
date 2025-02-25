<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Jobs\OpenEdLottery;
use Modules\Api\Http\Requests\LiuheRequest;
use Modules\Api\Models\YearPic;
use Modules\Api\Services\liuhe\LiuheService;
use Modules\Common\Exceptions\CustomException;

class LiuheController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取五行 生肖 等属性
     * @return JsonResponse
     * @throws CustomException
     */
    public function number_attr(): JsonResponse
    {
        return (new LiuheService())->get_number_attr();
    }

    /**
     * 获取五行 生肖 等属性
     * @return JsonResponse
     * @throws CustomException
     */
    public function number_attr2(): JsonResponse
    {
        return (new LiuheService())->get_number_attr2();
    }

    /**
     * 获取某一期开奖号码详情
     * @param LiuheRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function numbers(LiuheRequest $request): JsonResponse
    {
        $request->validate('numbers');

        return (new LiuheService())->get_number($request->only(['year', 'issue', 'lotteryType']));
    }

    /**
     * 历史号码
     * @param LiuheRequest $request
     * @return JsonResponse
     */
    public function history(LiuheRequest $request): JsonResponse
    {
        $request->validate('history');

        return (new LiuheService())->history($request->only(['year', 'sort', 'lotteryType', 'page']));
    }

    /**
     * 号码推荐
     * @param LiuheRequest $request
     * @return JsonResponse
     */
    public function recommend(LiuheRequest $request): JsonResponse
    {
        $request->validate('recommend');

        return (new LiuheService())->recommend($request->only(['year', 'lotteryType']));
    }

    /**
     * 开奖记录
     * @param LiuheRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function record(LiuheRequest $request): JsonResponse
    {
        $request->validate('record');

        return (new LiuheService())->record($request->only(['id']));
    }

    /**
     * 六合统计
     * @param LiuheRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function statistics(LiuheRequest $request): JsonResponse
    {
        $request->validate('statistics');

        return (new LiuheService())->statistics($request->only(['lotteryType', 'issue']));
    }

    /**
     * 开奖日期
     * @param LiuheRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function open_date(LiuheRequest $request): JsonResponse
    {
        $request->validate('open_date');

        return (new LiuheService())->open_date($request->only(['lotteryType']));
    }

    /**
     * 下一期日期
     * @return JsonResponse
     */
    public function next(): JsonResponse
    {
        return (new LiuheService())->next();
    }

    /**
     * 开奖回放
     * @param Request $request
     * @return JsonResponse
     */
    public function video(Request $request): JsonResponse
    {
        return (new LiuheService())->video($request->input());
    }

    public function lottery()
    {
        return (new LiuheService())->lottery();
    }

}
