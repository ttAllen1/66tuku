<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class LiuheBetsRequest extends FormRequest
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
            'id'         => 'required|is_positive_integer',
            'name'       => 'required|unique:forecast_bets,name,' . $this->id,
            'contents'   => 'required',
//            'contents.*' => 'required|json',
            'sort'       => 'nullable|numeric',
            'status'     => 'required|' . Rule::in([0, 1])
        ];
    }

    public function messages()
    {
        return [
            'id.required'            => '参数错误，请重试',
            'id.is_positive_integer' => '参数错误，请重试',
            'name.required'          => '玩法名称必填',
            'name.unique'            => '玩法名称已存在',
            'contents.required'      => '赔率内容不能为空',
//            'contents.array'         => '赔率内容应为数组',
//            'contents.*.required'    => '赔率内容不能为空',
//            'contents.*.json'        => '赔率内容应为JSON',
            'sort.numeric'           => '排序字段应为数字',
            'status.required'        => '状态必填',
            'status.in'              => '状态只能填写0或1',
        ];
    }

    public function scene()
    {
        return [
            //add 场景
            'store' => ['name', 'contents', 'contents.*', 'sort', 'status'],
            'update' => [
                'id', 'name', 'contents', 'contents.*', 'sort', 'status'
            ]
        ];
    }
}









