<?php
/**
 * @Name 会员分组管理服务
 * @Description
 */

namespace Modules\Admin\Services\picture;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Models\AuthImage;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\LotteryTypeId;
use Modules\Admin\Models\PicForecast;
use Modules\Admin\Models\PicSeries;
use Modules\Admin\Models\PicVideo;
use Modules\Admin\Models\YearPic;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\PicDiagram;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class PictureService extends BaseApiService
{
    private $_xg_pictureIds = [];
    private $_all_pictureIds = [
        29908, 60001, 60002, 60003, 60004, 60005, 60006, 60007, 60008, 60009, 60011, 60012, 60013, 20001, 20002, 20003,
        20004, 20005, 20006, 20007, 20008, 20009, 20010, 20011, 20012, 20013, 20014, 20015, 20016, 20017, 20018, 20019,
        20020, 20021, 60014, 60015, 60016, 60017, 60018, 60020, 60021, 60022, 60023, 60024, 60025, 60026, 60027, 60028,
        60029, 60030, 60031, 60032, 60033
    ];

    private $_am_pictureIds = [
        29908, 60014, 60015, 20001, 20002, 20003, 20004, 20005, 20006, 20007, 20008, 20009, 20010, 20011, 20012, 20013,
        20014, 20015, 20016, 20017, 20018, 20019, 20020, 20021
    ];
    private $_kl8_pictureIds = [
        60001, 60002, 60003, 60004, 60005, 60006, 60007, 60008, 60009, 60011, 60012, 60013, 60016, 60017, 60018, 60020,
        60021, 60022, 60023, 60024, 60025, 60026, 60027, 60028, 60029, 60030, 60031, 60032, 60033
    ];

    private $_current_year = NULL;

    public function __construct()
    {
        $this->_current_year = date("Y");
        parent::__construct();
        $res = LotteryTypeId::query()->where('year', $this->_current_year)->pluck('typeIds', 'lotteryType')->toArray();
        $this->_xg_pictureIds = json_decode($res[1], true);
        $this->_am_pictureIds = json_decode($res[2], true);
        $this->_kl8_pictureIds = json_decode($res[6], true);
//        $this->_all_pictureIds = $this->_am_pictureIds + $this->_kl8_pictureIds + $this->_am_pictureIds;
        $this->_all_pictureIds = array_merge($this->_am_pictureIds, $this->_kl8_pictureIds, $this->_xg_pictureIds);

    }

    /**
     * @name 图解列表
     * @description
     * @param data Array 查询相关参数
     * @param data.page Int 页码
     * @param data.limit Int 每页显示条数
     **/
    public function diagrams_list(array $data)
    {
        $list = PicDiagram::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['is_essence'] != -1, function ($query) use ($data) {
                $query->where('is_essence', $data['is_essence']);
            })
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'nickname']);
                }
            ])
            ->latest()
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('', [
            'list'  => $list['data'],
            'total' => $list['total']
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param data Array 修改数据
     **/
    public function diagrams_update($id, array $data)
    {
        return $this->commonUpdate(PicDiagram::query(), $id, $data);
    }

    /**
     * @name 调整状态
     * @description
     * @param data Array 调整数据
     **/
    public function diagrams_delete($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(PicDiagram::query(), $id);
    }

    public function forecasts_list($data)
    {
        $list = PicForecast::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'nickname']);
                }
            ])
            ->latest()
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('', [
            'list'  => $list['data'],
            'total' => $list['total']
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param data Array 修改数据
     **/
    public function forecasts_update($id, array $data)
    {
        return $this->commonUpdate(PicForecast::query(), $id, $data);
    }

    /**
     * @name 调整状态
     * @description
     * @param data Array 调整数据
     **/
    public function forecasts_delete($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(PicForecast::query(), $id);
    }

    public function list($params)
    {
        $indexPic = IndexPic::query()
            ->when($params['lotteryType'], function ($query) use ($params) {
                $query->where('lotteryType', $params['lotteryType']);
            })
            ->when($params['color'], function ($query) use ($params) {
                $query->where('color', $params['color']);
            })
            ->when($params['pictureName'], function ($query) use ($params) {
                $query->where('pictureName', 'like', '%' . $params['pictureName'] . '%');
            })
            ->with([
                'picOther' => function ($query) {
                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
                }
            ])
            ->orderBy('lotteryType')
            ->orderBy('sort')
            ->paginate($params['limit'])->toArray();
//        dd($this->_all_pictureIds);
        foreach ($indexPic['data'] as $k => $v) {
            $indexPic['data'][$k]['is_video'] = (bool)$v['is_video']; // 是否是视频
            $indexPic['data'][$k]['image'] = $this->getPicUrl($v['color'], $v['pic_other']['max_issue'], $v['pic_other']['keyword'], $v['lotteryType']);
//            $indexPic['data'][$k]['preImage'] = $this->getPicUrl($v['color'], $v['pic_other']['max_issue'] - 1, $v['pic_other']['keyword'], $v['lotteryType']);

            if ( ($v['lotteryType'] == 2 || $v['lotteryType'] == 1) && Str::startsWith($indexPic['data'][$k]['image'], 'https://tk2.tuku.fit')) {
                $indexPic['data'][$k]['image'] = Str::replace('/m/', '/', $indexPic['data'][$k]['image']);
                // 处理黑白
                if (Str::contains($indexPic['data'][$k]['image'], 'black')) {
                    $arr = explode('black', $indexPic['data'][$k]['image']);
                    $indexPic['data'][$k]['image'] = "https://amtk.tuku.fit/galleryfiles/system/big-pic/black/" . date('Y') . $arr[1];
                }
            }

            $indexPic['data'][$k]['preImage'] = $this->getPicUrl($v['color'], $v['pic_other']['max_issue'] - 1, $v['pic_other']['keyword'], $v['lotteryType']);

            if ( ($v['lotteryType'] == 2 || $v['lotteryType'] == 1) && Str::startsWith($indexPic['data'][$k]['preImage'], 'https://tk2.tuku.fit')) {
                $indexPic['data'][$k]['preImage'] = Str::replace('/m/', '/', $indexPic['data'][$k]['preImage']);
                // 处理黑白
                if (Str::contains($indexPic['data'][$k]['preImage'], 'black')) {
                    $arr = explode('black', $indexPic['data'][$k]['preImage']);
                    $indexPic['data'][$k]['preImage'] = "https://amtk.tuku.fit/galleryfiles/system/big-pic/black/" . date('Y') . $arr[1];
                }
            }


            // $pic_lists['data'][$k]['previousPictureUrl'] = $this->getPicUrl($list['color'], $previousIssue-1, $list['pic_other']['keyword'], $list['lotteryType']); // , 'jpg', $params['lotteryType']==3?2024:2023
            if ($v['lotteryType'] == 2 || $v['lotteryType'] == 6 || $v['lotteryType'] == 1) {
                $urlPrefixArr = $this->getImgPrefix()[$v['pic_other']['year']][$v['lotteryType']];

                if ($v['pictureTypeId'] == "33344") {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $vv) {
                            $indexPic['data'][$k]['image'] = str_replace([$vv . 'm/', $vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://tu.tuku.fit/aomen/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['image']);
                            $indexPic['data'][$k]['preImage'] = str_replace([$vv . 'm/', $vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://tu.tuku.fit/aomen/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['preImage']);
                        }
                    }
                } else if (in_array($v['pictureTypeId'], $this->_all_pictureIds)) {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $vv) {
                            if (in_array($v['pictureTypeId'], $this->_am_pictureIds)) {
                                $indexPic['data'][$k]['image'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['image']);
                                $indexPic['data'][$k]['preImage'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['preImage']);
                            } else if (in_array($v['pictureTypeId'], $this->_kl8_pictureIds)) {
                                $indexPic['data'][$k]['image'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $indexPic['data'][$k]['image']);
                                $indexPic['data'][$k]['preImage'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $indexPic['data'][$k]['preImage']);
                            } else if (in_array($v['pictureTypeId'], $this->_xg_pictureIds)) { // https://xg.tuku.fit/galleryfiles/system/big-pic/col/2024/075/ktzsx.jpg
//                                dd($vv, $indexPic['data'][$k]['image']);
                                $indexPic['data'][$k]['image'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://xg.tuku.fit/galleryfiles/system/big-pic/col/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['image']);
                                $indexPic['data'][$k]['preImage'] = str_replace([$vv . 'm/col/', $vv . 'col/', $vv . '/col/'], 'https://xg.tuku.fit/galleryfiles/system/big-pic/col/' . $v['pic_other']['year'] . '/', $indexPic['data'][$k]['preImage']);
                            }
                        }
                    }
                }
            }
        }
        $not_find_img_id = (new ConfigService())->getConfigs(['not_find_img_id']);
        $not_find_img = AuthImage::query()->where('id', $not_find_img_id['not_find_img_id'])->value('url');

        return $this->apiSuccess('', [
            'list'         => $indexPic['data'],
            'total'        => $indexPic['total'],
            'not_find_img' => $this->getHttp() . $not_find_img,
        ]);
    }

    /**
     *  新增图片
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function store($data): JsonResponse
    {
        try {
            $pictureTypeId = $this->getPictureTypeId($data['lotteryType']);
            $path = parse_url($data['link'], PHP_URL_PATH);
            $keyword = pathinfo($path, PATHINFO_FILENAME);
            $model = YearPic::query()->where('lotteryType', $data['lotteryType'])->where('year', date('Y'))->firstOrFail();
            DB::beginTransaction();
            $model->replicate()->fill([
                'pictureTypeId' => $pictureTypeId,
                'color'         => 1,
                'pictureName'   => $data['name'],
                'keyword'       => $keyword,
                'is_add'        => 1,
                'letter'        => strtoupper($data['letter']),
            ])->save();
            IndexPic::query()->insert([
                'lotteryType'   => $data['lotteryType'],
                'pictureTypeId' => $pictureTypeId,
                'pictureName'   => $data['name'],
                'sort'          => $data['sort'],
                'color'         => 1,
                'is_add'        => 1,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $type = LotteryTypeId::query()->where('lotteryType', $data['lotteryType'])->where('year', date('Y'))->first();
            $typeIds = [];
            if ($type) {
                $typeIds = json_decode($type->typeIds, true);
                $typeIds[] = (int)$pictureTypeId;
                $type->update(['typeIds' => json_encode($typeIds)]);
            } else {
                LotteryTypeId::query()
                    ->insert([
                        'lotteryType' => $data['lotteryType'],
                        'year'        => date('Y'),
                        'typeIds'     => json_encode($typeIds),
                        'created_at'  => date('Y-m-d H:i:s')
                    ]);
            }

            DB::commit();
            return $this->apiSuccess();
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception->getMessage(), $exception->getLine());
            return $this->apiError($exception->getMessage());
        }
    }

    /**
     * 创建新的 pictureTypeId
     * @param $lotteryType
     * @return string
     * @throws Exception
     */
    private function getPictureTypeId($lotteryType): string
    {
        $randomNumber = '';
        for ($i = 0; $i < 4; $i++) {
            $randomNumber .= random_int(0, 9);
        }
        if (YearPic::query()->where('pictureTypeId', $lotteryType . $randomNumber)->exists()) {
            return $this->getPictureTypeId($lotteryType);
        }

        return $lotteryType . $randomNumber;
    }

    public function update($id, $data)
    {
        return $this->commonUpdate(IndexPic::query(), $id, $data);
    }

    /**
     * 图库系列相关【新增】
     * @param $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function series_store($data): JsonResponse
    {
        $name = str_replace(['序列', '系列'], '', $data['name']);
        $array = IndexPic::query()
            ->where('pictureName', 'like', '%' . $name . '%')
            ->pluck('pictureName', 'id');
        if ($array->isEmpty()) {
            throw new CustomException(['message' => '该序列下无图片']);
        }
        $array = $array->toArray();
        $ids = array_keys($array);
        $names = array_values($array);
        $data['name'] = $name . '系列';
        $data['index_pic_ids'] = json_encode($ids);
        $data['index_pic_names'] = json_encode($names);
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = PicSeries::query()->insertGetId($data);
        if ($data['status'] == 1) {
            IndexPic::query()->whereIn('id', $ids)->update(['series_id' => $id]);
        }

        return $this->apiSuccess('添加成功');
    }

    /**
     * 图库系列相关【列表】
     * @param $data
     * @return JsonResponse
     */
    public function series_list($data): JsonResponse
    {
        $list = PicSeries::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->latest()
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('', [
            'list'  => $list['data'],
            'total' => $list['total']
        ]);
    }

    /**
     * 图库系列相关【修改】
     * @param $id
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function series_update($id, array $data): JsonResponse
    {
        $name = str_replace(['序列', '系列'], '', $data['name']);
        $array = IndexPic::query()
            ->where('pictureName', 'like', '%' . $name . '%')
            ->pluck('pictureName', 'id');
        if ($array->isEmpty()) {
            throw new CustomException(['message' => '该序列下无图片']);
        }
        $array = $array->toArray();
        $ids = array_keys($array);
        $names = array_values($array);
        $data['name'] = $name;
        $data['index_pic_ids'] = json_encode($ids);
        $data['index_pic_names'] = json_encode($names);
        PicSeries::query()->where('id', $id)->delete();
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = PicSeries::query()->insertGetId($data);
        if ($data['status'] == 1) {
            IndexPic::query()->whereIn('id', $ids)->update(['series_id' => $id]);
        } else {
            IndexPic::query()->whereIn('id', $ids)->update(['series_id' => 0]);
        }

        return $this->apiSuccess('修改成功');
    }

    /**
     * 图库系列相关【删除】
     * @param $id
     * @return JsonResponse
     */
    public function series_delete($id): JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(PicSeries::query(), $id);
    }

    /**
     * 图库图解相关【创建】
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function store_diagram($data): JsonResponse
    {
        $year = date('Y');
        // 随机一个机器人
        $userId = DB::table('users')->where('is_chat', 1)->inRandomOrder()->limit(1)->value('id');
        // 判断详情是否存在
        $issue = $this->getNextIssue($data['lotteryType']);
        $detailId = DB::table('pic_details')->where('lotteryType', $data['lotteryType'])->where('pictureTypeId', $data['pictureTypeId'])->where('issue', $issue)->where('year', $year)->value('id');
        if (!$detailId) {
            // 新增详情
            $detailId = DB::table('pic_details')->insertGetId([
                'lotteryType'   => $data['lotteryType'],
                'pictureTypeId' => $data['pictureTypeId'],
                'issue'         => $issue,
                'year'          => $year,
                'keyword'       => $data['keyword'],
                'pictureName'   => $data['pictureName'],
                'pictureId'     => $year . str_pad($issue, 3, STR_PAD_LEFT) . $data['pictureTypeId'],
                'color'         => 1,
            ]);
        }
        // 创建图解
        $diagramData = [];
        $diagramData['user_id'] = $userId;
        $diagramData['pic_detail_id'] = $detailId;
        $diagramData['issue'] = $issue;
        $diagramData['lotteryType'] = $data['lotteryType'];
        $diagramData['title'] = $data['title'];
        $diagramData['content'] = $data['content'];
        $diagramData['views'] = $data['views'];
        $diagramData['thumbUpCount'] = $data['thumbUpCount'];
        $diagramData['is_top'] = $data['is_top'];
        $diagramData['is_essence'] = $data['is_essence'];
        $diagramData['status'] = 1;
        $diagramData['created_at'] = date('Y-m-d H:i:s');
        if (DB::table('pic_diagrams')->insertGetId($diagramData)) {
            return $this->apiSuccess('修改成功');
        }
        return $this->apiError();
    }

    public function video_list($data)
    {
        $picList = IndexPic::query()
            ->where('is_video', 1)
            ->selectRaw('lotteryType as value, pictureName as label')
            ->orderBy('lotteryType')
            ->get()->toArray();
        if (empty($picList)) {
            return $this->apiSuccess('', [
                'list'    => [],
                'total'   => 0,
                'picList' => []
            ]);
        }
        // 相同的lotteryType合并
        $picListArr = [];
        foreach ($picList as $v) {
            $picListArr[$v['value']][] = ['label' => $v['label'], 'value' => $v['value']];
        }
        sort($picListArr);
        $list = PicVideo::query()
            ->when($data['lotteryType'], function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->latest()
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('', [
            'list'     => $list['data'],
            'total'    => $list['total'],
            'picList'  => $picListArr,
            'video_url' => AuthConfig::query()->where('id', 1)->value('video_url')
        ]);
    }

    public function video_store($params)
    {
        $nextIssue = (int)$this->getNextIssue($params['lotteryType']);
        try {
            $picVideo = PicVideo::query()->create([
                'lotteryType' => $params['lotteryType'],
                'issue'       => $nextIssue,
                'pic_name'    => $params['pic_name'],
            ]);

            if (!empty($params['videos'])) {
                $video_path = $this->attachS3Video($params['videos'], $picVideo);
                $picVideo->update(['video' => $video_path]);
            }
        } catch (\Exception $exception) {
            return $this->apiError(ApiMsgData::ADD_API_ERROR);
        }

        return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
    }

    public function video_update($id, $params)
    {
//        $picVideo = PicVideo::query()->where('id', $params['id'])->first();

//        $picVideo->images->each->delete();
//        $video_path = $this->attachS3Video($params['videoUrl'], $picVideo);
//        $params['video'] = $video_path;

        unset($params['videoUrl']);
        unset($params['image']);
        unset($params['video']);
        return $this->commonUpdate(PicVideo::query(),$id,$params);
    }

    public function video_delete($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(PicVideo::query(), $id);
    }

    public function update_is_video($id,$params)
    {
        $params['is_video'] = $params['is_video'] ? 1 : 0;
        return $this->commonUpdate(IndexPic::query(),$id,$params);
    }
}
