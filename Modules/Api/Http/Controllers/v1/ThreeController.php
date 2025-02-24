<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Api\Services\three\ThreeService;
use Telegram\Bot\Laravel\Facades\Telegram;

class ThreeController extends BaseApiController
{
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN'); // 从配置中获取 token
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function queryIDByPlatAccount(Request $request): ?JsonResponse
    {
        return (new ThreeService())->queryIDByPlatAccount($request->all());
    }

    public function send()
    {
//        $telegram = Telegram::bot('mybot')->getMe();
//        $response = $telegram->sendMessage([
//            'chat_id' => -1002453327598,
//            'text' => 'Hello World'
//        ]);
//        dd($response);
        // 多图
//        $response = Http::post("{$this->apiUrl}/sendMediaGroup", [
//            'chat_id' => -1002453327598, // 替换为你的 chat_id
//            'media' => json_encode([
//                [
//                    'type' => 'photo',
//                    'media' => 'https://amo.wyvogue.com:4949/m/col/293/ampgt.jpg',
//                    'caption' => '澳门跑狗图' // 可选的图片说明
//                ]
//            ]),
//        ]);

        $response = Http::post("{$this->apiUrl}/sendPhoto", [
            'chat_id' => -1002453327598, // 替换为你的 chat_id
            'photo' => 'https://lam.tutu.finance/galleryfiles/system/big-pic/col/2024/293/ampgt.jpg',
            'caption' => " 👉 [自己的快乐8图片](https://h5.49tkaapi.com/materials/list?id=3667&title=外围博彩&type=2) 👈"
        ]);



        // 资料 链接
//        $response = Http::post("{$this->apiUrl}/sendMessage", [
//            'chat_id' => -1002453327598,
//            'text' => "好料尽在这里： 👉 [外围博彩](https://h5.49tkaapi.com/materials/list?id=3667&title=外围博彩&type=2) 👈",
//            'parse_mode' => 'MarkdownV2',
//        ]);

        return $response->json();
    }
}
