<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class MasterRankingRequest extends FormRequest
{
    use SceneValidator;

    /**
     * php artisan module:make-request AdminRequest Admin
     */

    public function authorize()
    {
        return true;
    }

    /**
     * 设置是否自动验证
     * @return bool
     */
    public function autoValidate()
    {
        return false;  //关闭
    }

    public function rules()
    {
        return [
            'id'           => 'required|is_positive_integer',
            'page'         => 'required|is_positive_integer',
            'config_id'    => 'required|int|min:1',
            'mark'         => 'required|string',
            'content'      => 'required|string',
            'type'         => 'sometimes|nullable|' . Rule::in([0, 1, 2]),
            'issue'        => 'sometimes|nullable|' . Rule::in([0, 1, 2, 5, 10, 20]),
            'sort'         => 'sometimes|nullable|' . Rule::in([0, 1, 2, 3, 4]),
            'is_fee'       => 'sometimes|nullable|' . Rule::in([-1, 0, 1]),
            'is_master'    => 'sometimes|nullable|' . Rule::in([0, 1]),
            'filter'       => 'sometimes|nullable|' . Rule::in([0, 1]),
            'min_accuracy' => 'sometimes|nullable|is_positive_integer',
            'min_issue'    => 'sometimes|nullable|is_positive_integer',
            'fee'          => 'nullable|required_if:type,2|numeric|min:0',
            'lotteryType'  => 'required|' . Rule::in([1, 2, 3, 4, 5, 6, 7]),
        ];
    }

    public function messages()
    {
        return [
            'id.required'              => '参数错误，请重试',
            'id.is_positive_integer'   => '参数错误，请重试',
            'page.is_positive_integer' => '参数错误，请重试',
            'config_id.required'       => '配置ID必填',
            'config_id.int'            => '配置ID不正确',
            'config_id.min'            => '配置ID不正确',

            'mark.required'        => '标识必填！',
            'content.required'     => '内容必填！',
            'lotteryType.required' => '彩种必填！',
            'lotteryType.in'       => '彩种不正确！',
            'fee.required_if'      => '金额必填！',
        ];
    }

    public function scene()
    {
        return [
            'create' => ['config_id', 'mark', 'content', 'lotteryType', 'type', 'fee'],
            'list'   => ['lotteryType', 'page', 'config_id', 'issue',  'sort', 'is_fee', 'is_master', 'filter', 'min_accuracy', 'min_issue'],
        ];
    }
}









