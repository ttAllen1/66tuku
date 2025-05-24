<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class CommentRequest extends FormRequest
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
            'page'              => 'required|is_positive_integer',
            'commentId'         => "required|is_positive_integer",
            'user_id'           => "required|is_positive_integer",
            'nick_name'         => "required",
            'avatar'            => "required|url",
            'content'           => 'required',
            'cate'              => 'required|'.Rule::in([1, 2]),
            'location'          => 'required|'.Rule::in([1, 2]),
            'three_cate'        => 'required|'.Rule::in([1, 5]),
            'type'              => 'required|'.Rule::in(1, 2, 3, 4, 5, 6, 9, 11, 12, 13),
            'target_id'         => 'required|is_positive_integer',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'commentId.required'                => '评论ID不能为空！',
            'commentId.is_positive_integer'     => '评论ID必须为整数！',
            'user_id.required'                  => '用户ID不能为空！',
            'user_id.is_positive_integer'       => '用户ID必须为整数！',
            'nick_name.required'                => '用户名不能为空！',
            'avatar.required'                   => '用户图像不能为空！',
            'avatar.url'                        => '用户图像url格式不对！',
            'content.required'                  => '评论内容不能为空！',
            'type.required'                     => '评论类型不能为空！',
            'type.in'                           => '没有与之对应的评论类型！',
            'cate.required'                     => '点赞类型不能为空！',
            'cate.in'                           => '没有与之对应的点赞类型！',
            'location.required'                 => '定位类型不能为空！',
            'location.in'                       => '没有与之对应的定位类型！',
            'target_id.required'                => '评论对象ID不能为空！',
            'target_id.is_positive_integer'     => '评论对象ID必须为整数！',
        ];
    }

    public function scene()
    {
        return [
            'create'        => ['type', 'target_id', 'content'],
            'children'      => ['commentId', 'page'],
            'children_3'    => ['location', 'commentId', 'page'],
            'follow'        => ['commentId'],
            'follow_3'      => ['cate','target_id'],
            'list_3'        => ['page', 'three_cate','target_id'],
            'create_3'      => ['three_cate', 'target_id', 'content', 'user_id', 'nick_name', 'avatar'],
            'comment'       => ['type', 'target_id', 'page'],
        ];
    }
}
