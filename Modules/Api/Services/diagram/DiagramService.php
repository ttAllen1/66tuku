<?php

namespace Modules\Api\Services\diagram;

use Illuminate\Http\JsonResponse;
use Modules\Api\Models\PicDiagram;
use Modules\Api\Models\User;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\picture\PictureService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class DiagramService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建图解
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create($params): JsonResponse
    {
        $title                  = strip_tags($params['title']);
        $content                = strip_tags($params['content']);
        $pic_detail_id          = $params['id'];
        if ( !(new PictureService())->existsById($pic_detail_id) ) {
            throw new CustomException(['message'=>'图片详情id不存在']);
        }
        $checkStatus = $this->getCheckStatus(12);
        PicDiagram::query()
            ->create([
                'user_id'       => auth('user')->id(),
                'pic_detail_id' => $pic_detail_id,
                'title'         => $title,
                'content'       => $content,
                'issue'         => $params['issue'],
                'lotteryType'   => $params['lotteryType'],
                'status'        => $checkStatus == 1 ? 0 : 1
            ]);
        // 加成长值
        (new UserGrowthScoreService())->growthScore($this->_grow['create_post']);

        return $this->apiSuccess(ApiMsgData::DIAGRAM_API_SUCCESS);
    }

    /**
     * 获取图解列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function list($params): JsonResponse
    {
        $picDiagram = PicDiagram::query()
            ->where('pic_detail_id', $params['id'])
            ->where('status', 1)
            ->removeBlack()
            ->orderBy('created_at', 'desc')
            ->with(['user'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }])
            ->simplePaginate();
        if ( $picDiagram->isEmpty() ) {
            throw new CustomException(['message'=>'数据不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $picDiagram->toArray());
    }

    /**
     * 图解详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        $picDiagram = PicDiagram::query()
            ->where('status', 1)
            ->with(['picture'=>function($query) {
                $query->select('id', 'lotteryType', 'pictureTypeId', 'issue', 'keyword', 'year', 'color');
            }])
            ->find($params['id']);
        if (!$picDiagram) {
            throw new CustomException(['message'=>'图解不存在或被删除']);
        }
        if (!$picDiagram['picture']) {
            throw new CustomException(['message'=>'图解对应详情不存在或被删除']);
        }
        if ($picDiagram['views']==0) {
            $picDiagram->increment('views', $this->getFirstViews());
        } else {
            $picDiagram->increment('views', $this->getSecondViews());
        }

        $picDiagram['largePictureUrl'] = $this->getPicUrl($picDiagram['picture']['color'], $picDiagram['picture']['issue'], $picDiagram['picture']['keyword'], $picDiagram['picture']['lotteryType'], 'jpg', $picDiagram['picture']['year'], true);
        $picDiagram['largePictureUrlOther'] = '';
        if ($picDiagram['picture']['lotteryType'] == 5) {
//            if ($picDiagram['picture']['pictureTypeId'] != "00403") {
//                $picDiagram['largePictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $picDiagram['largePictureUrl']);
//            } else {
//                $picDiagram['largePictureUrlOther'] = $picDiagram['largePictureUrl'];
//            }
            $picDiagram['largePictureUrlOther'] = $picDiagram['largePictureUrl'];
        }
        $picDiagram['follow'] = false;
        $userId = auth('user')->id();
        if ($userId) {
            $picDiagram['follow']  = (bool)$picDiagram->follow()->where('user_id', $userId)->value('id');
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $picDiagram->toArray());
    }

    /**
     * 图解点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $picDiagramBuilder = PicDiagram::query()->where('id', $params['diagrams_id']);

        return (new FollowService())->follow($picDiagramBuilder);
    }

    /**
     * 高手论坛里的图解小组列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function discuss_list($params): JsonResponse
    {
        $keyword = $params['keyword'] ?? null;
        $picDiagram = PicDiagram::query()
            ->where('status', 1)
            ->when($params['lotteryType'], function($query) use ($params) {
                $query->where('lotteryType', $params['lotteryType']);
            })
            ->removeBlack()
            ->when($keyword, function($query) use ($params){
                $query->where(function ($query) use ($params) {
                    $query->orWhere('title', 'like', '%'.$params['keyword'].'%')
                        ->orWhereHas('user', function($query) use ($params){
                            $query->where('nickname', 'like', '%'.$params['keyword'].'%');
                        });
                });
            })
            ->when($params['sort']==1, function($query) {
                $query->where('is_essence', 1)->orderby('created_at', 'desc');
            })
            ->when($params['sort']==3, function($query) {
                $query->orderby('created_at', 'desc');
            })
            ->when($params['sort']==2, function($query) {
                $query->orderby('thumbUpCount', 'desc')->orderby('created_at', 'desc');
            })
            ->when($params['sort']==4, function($query) {
                $query->orderby('is_top', 'desc')->orderby('is_essence', 'desc')->orderby('created_at', 'desc');
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }, 'user.focus'=>function($query) {
                $query->where('user_id', auth('user')->id());
            }])
            ->simplePaginate();
        if ( $picDiagram->isEmpty() ) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        $picDiagram = $picDiagram->toArray();
        foreach ($picDiagram['data'] as $k => $item) {
            $picDiagram['data'][$k]['content'] = strip_tags($item['content']);
            $picDiagram['data'][$k]['user']['focus'] = (bool)$item['user']['focus'];
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $picDiagram);
    }

}
