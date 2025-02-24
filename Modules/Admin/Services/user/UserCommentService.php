<?php
/**
 * 会员评论管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\Admin\Models\Check;
use Modules\Admin\Models\User;
use Modules\Admin\Services\upload\ImageService;
use Modules\Api\Models\UserComment;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\UserCommentThree;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\ApiException;

class UserCommentService extends BaseApiService
{
    /**
     * 评论列表
     * @param array $data
     * @return JsonResponse
     */
    public function comment_list(array $data): JsonResponse
    {
        $userId = [];
        if (!empty($data['account_name'])) {
            $userId = DB::table('users')->where('account_name', 'like', '%' . $data['account_name'] . '%')->pluck('id')->toArray();
        }
        if (!empty($data['nickname'])) {
            $userId1 = DB::table('users')->where('nickname', 'like', '%' . $data['nickname'] . '%')->pluck('id')->toArray();
            $userId = array_merge($userId, $userId1);
        }

        // 获取所有组别
        $list = UserComment::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['is_all'] != 1, function ($query) use ($data, $userId) {
                $ids = DB::table('users')->where('is_chat', 1)->pluck('id')->toArray();
                if ($data['is_all'] == 0) {
                    // 排除内部用户
                    if ($ids) {
                        if ($userId) {
                            $ids = array_diff($ids, $userId);
                        }
                        $query->whereNotIn('user_id', $ids);
                    }
                } else {
                    // 内部评论
                    if ($ids) {
                        if ($userId) {
                            $ids = array_merge($ids, $userId);
                        }
                        $query->whereIn('user_id', $ids);
                    }
                }

            })
            ->when($data['is_hot'] != -1, function ($query) use ($data) {
                $query->where('is_hot', $data['is_hot']);
            })
            ->when($data['keyword'], function ($query) use ($data) {
                $query->where('content', 'like', '%' . $data['keyword'] . '%');
            })
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'nickname', 'web_sign', 'account_name'])->with([
                        'group' => function ($query) {
                            $query->select(['id', 'user_id', 'group_id']);
                        }
                    ]);
                }, 'images', 'picDetail' => function ($query) {
                    $query->select(['id', 'lotteryType', 'pictureName']);
                }
            ])
            ->when($userId, function ($query) use ($userId) {
                $query->whereIn('user_id', $userId);
            })
            ->latest()
            ->paginate($data['limit'])->toArray();

        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                if (!empty($v['pic_detail'])) {
                    if ($v['pic_detail']['lotteryType'] == 1) {
                        $list['data'][$k]['pic_detail']['name'] = '香港';
                    } else if ($v['pic_detail']['lotteryType'] == 2) {
                        $list['data'][$k]['pic_detail']['name'] = '新澳';
                    } else if ($v['pic_detail']['lotteryType'] == 3) {
                        $list['data'][$k]['pic_detail']['name'] = '台彩';
                    } else if ($v['pic_detail']['lotteryType'] == 4) {
                        $list['data'][$k]['pic_detail']['name'] = '新彩';
                    } else if ($v['pic_detail']['lotteryType'] == 5) {
                        $list['data'][$k]['pic_detail']['name'] = '天澳';
                    } else if ($v['pic_detail']['lotteryType'] == 6) {
                        $list['data'][$k]['pic_detail']['name'] = '快乐八';
                    } else if ($v['pic_detail']['lotteryType'] == 7) {
                        $list['data'][$k]['pic_detail']['name'] = '老澳';
                    } else {
                        $list['data'][$k]['pic_detail']['name'] = '新澳';
                    }
                } else {
                    $list['data'][$k]['pic_detail']['name'] = '新澳';
                    $list['data'][$k]['pic_detail']['pictureName'] = '四不像';
                }
            }
        }

        return $this->apiSuccess('', [
            'list'  => $list['data'],
            'total' => $list['total']
        ]);
    }


    /**
     * 修改
     * @param $id
     * @param array $params
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function update($id, array $params): ?JsonResponse
    {
        if (count($params) == 1) {
            return $this->commonUpdate(UserComment::query(), $id, $params);
        }
        try {
            $userComment = UserComment::query()
                ->where('id', $id)
                ->update([
                    'user_id'       => $params['user_id'],
                    'content'       => $params['content'],
                    'is_hot'        => $params['is_hot'],
                    'thumbUpCount'  => $params['thumbUpCount'],
                    'status'        => $params['status'],
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            if (isset($params['images'])) {
                $userComment = UserComment::query()
                    ->where('id', $id)->first();
                $userComment->images()->delete();
                $images = [];
                if (!is_array($params['images'])) {
                    $params['images'] = [$params['images']];
                }
                foreach($params['images'] as $k => $v) {
                    $data = json_decode($v, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $params['images'][$k] = $data['img_url'];
                    } else {
                        $params['images'][$k] = $v;
                    }
                }
                foreach ($params['images'] as $k => $v) {
                    $url = ltrim(Str::replace($this->getHttp(), '', $v), '/');
                    $imageInfo = (new PictureService())->getImageInfoWithOutHttp($url);
                    $images[$k]['img_url'] = $url;
                    $images[$k]['width'] = $imageInfo['width'];
                    $images[$k]['height'] = $imageInfo['height'];
                    $images[$k]['mime'] = $imageInfo['mime'];
                }
                $userComment->images()->createMany($images);
            }
        } catch (\Exception $exception) {
//            dd($exception->getMessage(), $exception->getLine());
            return $this->apiError('添加失败，请重试');
        }
        return $this->apiSuccess();
    }

    /**
     * 删除
     * @param $id
     * @return JsonResponse|null
     */
    public function delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        foreach($id as $item) {
            UserComment::query()->find($item)->children()->delete();
        }
        return $this->commonDestroy(UserComment::query(), $id);
    }

    /**
     * 第三方评论列表
     * @param array $data
     * @return JsonResponse
     */
    public function comment_list3(array $data): JsonResponse
    {
        // 获取所有组别
        $list = UserCommentThree::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['is_hot'] != -1, function ($query) use ($data) {
                $query->where('is_hot', $data['is_hot']);
            })
            ->with([
                'picDetail' => function ($query) {
                    $query->select(['id', 'lotteryType', 'pictureName']);
                }
            ])
            ->latest()
            ->paginate($data['limit'])->toArray();

        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                if (!empty($v['pic_detail'])) {
                    if ($v['pic_detail']['lotteryType'] == 1) {
                        $list['data'][$k]['pic_detail']['name'] = '香港';
                    } else if ($v['pic_detail']['lotteryType'] == 2) {
                        $list['data'][$k]['pic_detail']['name'] = '新澳';
                    } else if ($v['pic_detail']['lotteryType'] == 3) {
                        $list['data'][$k]['pic_detail']['name'] = '台彩';
                    } else if ($v['pic_detail']['lotteryType'] == 4) {
                        $list['data'][$k]['pic_detail']['name'] = '新彩';
                    } else if ($v['pic_detail']['lotteryType'] == 5) {
                        $list['data'][$k]['pic_detail']['name'] = '老澳';
                    }
                }
            }
        }
        return $this->apiSuccess('', [
            'list'  => $list['data'],
            'total' => $list['total']
        ]);
    }

    public function update3($id, array $data)
    {
        return $this->commonUpdate(UserCommentThree::query(), $id, $data);
    }

    public function delete3($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(UserCommentThree::query(), $id);
    }

    public function status($status): ?JsonResponse
    {
        return $this->commonUpdate(Check::query(), 17, ['status' => $status == 'true' ? 1 : 2]);
    }

    /**
     * 管理员添加评论
     * @param $params
     * @return JsonResponse
     * @throws ApiException
     */
    public function store($params): JsonResponse
    {
        try {
            $userComment = UserComment::query()
                ->create([
                    'user_id'      => $params['user']['id'],
                    'content'      => $params['content'],
                    'thumbUpCount' => $params['thumbUpCount'] ?? 0,
                    'is_hot'       => 1,
                    'status'       => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            $images = [];
            if (!is_array($params['images'])) {
                $params['images'] = [$params['images']];
            }
            foreach ($params['images'] as $k => $v) {
                $url = ltrim(Str::replace($this->getHttp(), '', $v), '/');
                $imageInfo = (new PictureService())->getImageInfoWithOutHttp($url);
                $images[$k]['img_url'] = $url;
                $images[$k]['width'] = $imageInfo['width'];
                $images[$k]['height'] = $imageInfo['height'];
                $images[$k]['mime'] = $imageInfo['mime'];
            }
            $userComment->images()->createMany($images);
        } catch (\Exception $exception) {
            return $this->apiError('添加失败，请重试');
        }
        return $this->apiSuccess();
    }

    /**
     * 管理员回复评论
     * @param $params
     * @return JsonResponse
     * @throws ApiException
     */
    public function reply($params): JsonResponse
    {
        try {
            $userComment = UserComment::query()
                ->create([
                    'user_id'          => $params['user']['id'],
                    'content'          => $params['content'],
                    'commentable_id'   => $params['commentable_id'],
                    'commentable_type' => $params['commentable_type'],
                    'thumbUpCount'     => $params['thumbUpCount'] ?? 0,
                    'up_id'            => $params['id'],
                    'top_id'           => $params['top_id'] != 0 ? $params['top_id'] : $params['id'],
                    'up_user_id'       => $params['user_id'],
                    'is_hot'           => 0,
                    'status'           => 1,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);

            if (isset($params['images'])) {
                $images = [];
                if (!is_array($params['images'])) {
                    $params['images'] = [$params['images']];
                }
                foreach ($params['images'] as $k => $v) {
                    $url = ltrim(Str::replace($this->getHttp(), '', $v), '/');
                    $imageInfo = (new PictureService())->getImageInfoWithOutHttp($url);
                    $images[$k]['img_url'] = $url;
                    $images[$k]['width'] = $imageInfo['width'];
                    $images[$k]['height'] = $imageInfo['height'];
                    $images[$k]['mime'] = $imageInfo['mime'];
                }
                $userComment->images()->createMany($images);
            }
        } catch (\Exception $exception) {
            return $this->apiError('添加失败，请重试');
        }
        return $this->apiSuccess();
    }

}
