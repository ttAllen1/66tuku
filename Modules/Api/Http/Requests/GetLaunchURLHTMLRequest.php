<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLaunchURLHTMLRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'gameId' => ['required', 'integer'],
            'referer' => ['required', 'url'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'gameId.required' => '游戏ID错误！',
            'gameId.integer' => '游戏ID错误！',
            'referer.required' => '来源错误！',
        ];
    }

}
