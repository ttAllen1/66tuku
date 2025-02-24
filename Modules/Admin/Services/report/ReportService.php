<?php
/**
 * @Name 活动管理服务
 * @Description
 */

namespace Modules\Admin\Services\report;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\Complaint;
use Modules\Api\Models\Humorous;
use Modules\Common\Exceptions\ApiMsgData;

class ReportService extends BaseApiService
{
    /**
     * @name 举报列表
     * @description
     **/
    public function index(array $data)
    {
        $complaint = Complaint::query()
            ->when($data['audit'] != -2, function ($query) use ($data) {
                $query->where('audit', $data['audit']);
            })
            ->with(['images:img_url', 'user'=>function($query){
                $query->select(['id', 'nickname']);
            }])
            ->orderByDesc('created_at')
            ->paginate()
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
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'total' => $complaint['total'],
            'list' => $complaint['data']
        ]);
    }

    public function update($id,array $data)
    {
        if ($data['audit'] ==1) {
            $data = $this->getTables($id);
            foreach ($data as $k => $v) {
                if (in_array($v['table'], ['user_discoveries', 'discusses', 'pic_diagrams', 'pic_forecasts'])) {
                    DB::table($v['table'])->where('id', $v['complaintable_id'])->update(['status'=>-1]);
                }
            }
        }
        return $this->commonUpdate(Complaint::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(Complaint::query(),$id);
    }

    public function detail($id)
    {
        $data = $this->getTables([$id]);
        if ($data[0]['table']) {
            $complaint = DB::table($data[0]['table'])
                ->leftJoin('users', 'users.id', '=', $data[0]['table'].'.user_id')
                ->select($data[0]['table'].'.*', 'users.id', 'users.nickname')
                ->where($data[0]['table'].'.id', $data[0]['complaintable_id'])->first();

            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
                'detail' => $complaint,
            ]);
        }

        return $this->apiError();
    }

    private function getTables(array $ids): array
    {
        $complaint = Complaint::query()->whereIn('id', $ids)->select(['complaintable_id', 'complaintable_type'])->get();
        $data = [];
        foreach ($complaint as $k => &$item)
        {
            $table = '';
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
            }
            $data[$k]['table'] = $table;
            $data[$k]['complaintable_id'] = $item['complaintable_id'];

        }
        return $data;
    }
}
