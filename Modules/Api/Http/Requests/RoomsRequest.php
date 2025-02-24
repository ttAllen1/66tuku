<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class RoomsRequest extends FormRequest
{
    use SceneValidator;

    public function autoValidate(){
        return false;  //关闭
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'room_id'       => 'required|'.Rule::in([1, 2, 3, 4, 5, 10]),
            'client_id'     => 'required',
            'message'       => 'required',
            'style'         => 'required|'.Rule::in(['string', 'image', 'link', 'red_receive_ok']),
            'type'          => 'required|'.Rule::in(['all', 'at']),
//            'from'          => 'required|is_positive_integer',
            'to'            => 'required_if:type,at|array',  // 当type=at时 必传 此时message就是@用户 data data可为空
            'cate'          => 'required_if:style,link|'.Rule::in([0, 1, 2, 3]),  // 当style=link时 必传 1高手论坛全部主题 2图片图解详情 3资料大全详情
            'detail_id'     => 'required_if:style,link|integer',  // style=link时 必传 detail_id 为超链接跳转的对象id
            'corpusTypeId'  => 'required_if:cate,3|integer',  // cate=3时 必传 corpusTypeId 为帖子分类id
            'page'          => 'required|is_positive_integer',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'room_id.required'                          => '房间号不能为空！',
            'room_id.in'                                => '房间号不允许！',
            'client_id.required'                        => 'client_id必传！',
            'message.required'                          => '聊天内容必传！',
            'style.required'                            => 'style格式必传！',
            'style.in'                                  => 'style格式不允许！',
            'type.required'                             => '聊天类型type必传！',
            'type.in'                                   => '聊天类型type不允许！',
//            'from.required'                             => '发送者必传！',
//            'from.is_positive_integer'                  => '发送者格式不正确！',
            'to.required_if'                            => '接收者必传！',
            'to.array'                                  => '接收者格式不正确！',
            'cate.required_if'                          => '类型分类必传！',
            'cate.in'                                   => '类型分类应在0～3之间！',
            'detail_id.required_if'                     => 'detail_id必传！',
            'detail_id.integer'                         => 'detail_id格式不正确！',
            'corpusTypeId.required_if'                  => 'corpusTypeId必传！',
            'corpusTypeId.integer'                      => 'corpusTypeId格式不正确！',
            'page.required'                             => '页码不能为空！',
            'page.is_positive_integer'                  => '页码必须为整数！',
        ];
    }

    public function scene()
    {
        return [
            'bind'          => ['client_id'],
            'join'          => ['room_id', 'client_id'],
            'switch'        => ['room_id', 'client_id'],
            'chat'          => ['room_id', 'message', 'style', 'type', 'to', 'cate', 'detail_id', 'corpusTypeId'],
            'record'        => ['room_id', 'page'],
            'delete'        => ['room_id', 'page'],
        ];
    }

}
