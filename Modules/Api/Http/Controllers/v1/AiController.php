<?php

namespace Modules\Api\Http\Controllers\v1;



use Illuminate\Http\Request;
use Modules\Api\Services\ai\AiService;

class AiController extends BaseApiController
{

    public function config()
    {
        return (new AiService())->config();
    }

    public function list()
    {
        return (new AiService())->list();
    }

    public function detail(Request $request)
    {
        return (new AiService())->detail($request->input('id'));
    }
}
