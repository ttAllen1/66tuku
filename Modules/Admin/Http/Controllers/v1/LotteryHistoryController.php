<?php
/**
 * @Name 历史开奖管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\LotteryHistoryRequest;
use Modules\Admin\Services\lottery\HistoryService;

class LotteryHistoryController extends BaseApiController
{
    public function latest(Request $request)
    {
        return (new HistoryService())->latest($request->all());
    }
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new HistoryService())->index($request->all());
    }

    public function update(LotteryHistoryRequest $request)
    {
        $request->validate();
        return (new HistoryService())->update($request->input('id', 0), $request->all());
    }

    public function real_open(LotteryHistoryRequest $request)
    {
        $request->validate('real_open');
        return (new HistoryService())->real_open($request->all());
    }

    public function store(LotteryHistoryRequest $request)
    {
        $request->validate($request->input('scene', 'create'));
        return (new HistoryService())->store($request->except(['scene']));
    }

    public function manually(LotteryHistoryRequest $request)
    {
        return (new HistoryService())->manually($request->all());
    }

}
