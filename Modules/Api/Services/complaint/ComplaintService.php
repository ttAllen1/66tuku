<?php

namespace Modules\Api\Services\complaint;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\Complaint;
use Modules\Api\Models\CorpusArticle;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\Humorous;
use Modules\Api\Models\PicDiagram;
use Modules\Api\Models\UserDiscovery;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class ComplaintService extends BaseApiService
{
    /**
     * 添加举报
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function add(array $data): JsonResponse
    {
        $object = false;
        switch ($data['type'])
        {
            // 图解
            case 2:
                $object = PicDiagram::find($data['id']);
                break;

            // 竞猜
            case 3:
                $object = Humorous::find($data['id']);
                break;

            // 资料大全
            case 4:
                $object = CorpusArticle::setTables(request()->input())->where('id', $data['id'])->first();
                break;

            // 发现
            case 6:
                $object = UserDiscovery::find($data['id']);
                break;

            // 论坛
            case 9:
                $object = Discuss::find($data['id']);
                break;
        }

        if (!$object)
        {
            throw new CustomException(['message'=>'数据未找到']);
        }

        $complaintData['user_id'] = request()->userinfo->id;
        $complaintData['content'] = $data['content'];
        $complaint = $object->complaint()->create($complaintData);
        if ($images = request()->input('images'))
        {
            foreach ($images as $val)
            {
                if (empty($val))
                {
                    throw new CustomException(['message'=>'请选择图片']);
                }
                $addImages = ['img_url' => $val];
                $imageInfo = (new PictureService)->getImageInfoWithOutHttp($val);
                $addImages['height'] = $imageInfo['height'];
                $addImages['width'] = $imageInfo['width'];
                $addImages['mime'] = $imageInfo['mime'];
                $complaint->images()->create($addImages);
            }
        }
        return $this->apiSuccess('举报成功');
    }

    /**
     * 举报列表
     * @return JsonResponse
     */
    public function list()
    {
        $complaint = Complaint::where('user_id', request()->userinfo->id)
            ->with('images:img_url')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->toArray();
        foreach ($complaint['data'] as &$item)
        {
            if (preg_match("/CorpusArticle/i", $item['complaintable_type']))
            {
                $tableIdx = str_replace('Modules\Api\Models\CorpusArticle', '', $item['complaintable_type']);
                if ($tableIdx < 1)
                {
                    continue;
                }
                $table = 'corpus_articles' . $tableIdx;
            } elseif ($item['complaintable_type'] == 'Modules\Api\Models\PicDiagram') {
                $table = 'pic_diagrams';
            } elseif ($item['complaintable_type'] == 'Modules\Api\Models\Discuss') {
                $table = 'discusses';
            } elseif ($item['complaintable_type'] == 'Modules\Api\Models\UserDiscovery') {
                $table = 'user_discoveries';
            } elseif ($item['complaintable_type'] == 'Modules\Api\Models\PicForecast') {
                $table = 'pic_forecasts';
            } else {
                continue;
            }
            $title = DB::table($table)->where('id', $item['complaintable_id'])->value('title');
            if ($title)
            {
                $item['title'] = $title;
            }
            unset($item['complaintable_id']);
            unset($item['complaintable_type']);
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page' => $complaint['last_page'],
            'current_page' => $complaint['current_page'],
            'total' => $complaint['total'],
            'list' => $complaint['data']
        ]);
    }
}
