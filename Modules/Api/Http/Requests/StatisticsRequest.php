<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatisticsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'device' => 'required|'.Rule::in([1, 2]),
            'device_code' => 'required',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'device.required' => '设备类型必填！',
            'device.in' => '设备类型只能是1或2！',
            'device_code.required' => '唯一设备码必填',
        ];
    }

}
