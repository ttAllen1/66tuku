<?php
/**
 * @Name 聊天室配置
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Services\chat\RoomService;

class RoomController extends BaseApiController
{
    public function room(Request $request)
    {

        return (new RoomService())->room($request->all());
    }

    public function store(Request $request)
    {
        return (new RoomService())->store($request->all());
    }

    public function update(Request $request)
    {
        return (new RoomService())->update($request->input('id', 0), $request->except(['scene', 'onlines']));
    }

    public function check(Request $request)
    {
        return (new RoomService())->check($request->input('id', 0), $request->all());
    }
}
