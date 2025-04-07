<?php

namespace Modules\Api\Services\picture;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Intervention\Image\Exception\ImageException;
use Intervention\Image\Facades\Image;
use Modules\Admin\Models\LotteryTypeId;
use Modules\Admin\Models\PicSeries;
use Modules\Admin\Models\PicVideo;
use Modules\Admin\Services\lottery\HistoryService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Models\IndexPic;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\UserCollect;
use Modules\Api\Models\Vote;
use Modules\Api\Models\YearPic;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\vote\VoteService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class PictureService extends BaseApiService
{

    private $_xg_pictureIds = [];
    private $_all_pictureIds = [
        29908, 60001, 60002, 60003, 60004, 60005, 60006, 60007, 60008, 60009, 60011, 60012, 60013, 20001, 20002, 20003, 20004, 20005, 20006, 20007, 20008, 20009, 20010, 20011, 20012, 20013, 20014, 20015, 20016, 20017, 20018, 20019, 20020, 20021, 60014, 60015, 60016, 60017, 60018, 60020, 60021, 60022, 60023, 60024, 60025, 60026, 60027, 60028, 60029, 60030, 60031, 60032, 60033
    ];

    private $_am_pictureIds = [
        29908, 60014, 60015, 20001, 20002, 20003, 20004, 20005, 20006, 20007, 20008, 20009, 20010, 20011, 20012, 20013, 20014, 20015, 20016, 20017, 20018, 20019, 20020, 20021
    ];
    private $_kl8_pictureIds = [
        60001, 60002, 60003, 60004, 60005, 60006, 60007, 60008, 60009, 60011, 60012, 60013, 60016, 60017, 60018, 60020, 60021, 60022, 60023, 60024, 60025, 60026, 60027, 60028, 60029, 60030, 60031, 60032, 60033
    ];

    private $_oldam_pictureIds = [];
    private $_tw_pictureIds = [];
    private $_xjp_pictureIds = [];

    private $_current_year = NULL;

    private $_sync_issue_url = 'https://api.xyhzbw.com/unite49/h5/picture/detail?pictureId=%d';

    public function __construct()
    {
        $this->_current_year = date("Y");
        parent::__construct();
        $res = LotteryTypeId::query()->where('year', $this->_current_year)->pluck('typeIds', 'lotteryType')->toArray();
        if ($res) {
            $this->_xg_pictureIds = json_decode($res[1], true);
            $this->_am_pictureIds = json_decode($res[2], true);
            $this->_kl8_pictureIds = json_decode($res[6], true);

//        $this->_oldam_pictureIds = json_decode($res[7], true);
//        $this->_tw_pictureIds = json_decode($res[3], true);
//        $this->_xjp_pictureIds = json_decode($res[4], true);
            $this->_all_pictureIds = array_merge($this->_am_pictureIds, $this->_kl8_pictureIds, $this->_xg_pictureIds, $this->_oldam_pictureIds, $this->_tw_pictureIds, $this->_xjp_pictureIds);
        }
    }

    /**
     * 首页图片列表
     * @param $params
     * @return array|JsonResponse
     */
    public function get_page_list($params)
    {
        if ($params['page'] <= 20 && !isset($params['cache'])) {
            try {
                $res = [];
//                $res = Redis::zrangebyscore('cache_index_pic_'.$params['lotteryType'], $params['page'], $params['page']);
                if ($res && $res[0]) {
                    return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, json_decode($res[0], true));
                }
            } catch (\Exception $exception) {
                return $this->get_index_pic($params);
            }
        }
        return $this->get_index_pic($params);
    }

    /**
     * @param $params
     * @param bool $hasAd
     * @param bool $search
     * @param $searchName
     * @return array|JsonResponse
     */
    public function get_index_pic($params, bool $hasAd = true, bool $search = false, $searchName = '')
    {
        $indexImagesRdx = Redis::get('index_images_list_lottery_type_'. $params['lotteryType'] . '_page_'. $params['page']);
        if ($indexImagesRdx && !$search && date('H') == 21) {
            $indexImages = json_decode($indexImagesRdx, true);
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $indexImages);
        }
        $color = $params['color'] ?? 0;
        $pic_lists = IndexPic::query()
            ->where('is_delete', 0)
            ->where('lotteryType', $params['lotteryType'])
            ->when($color != 0, function ($query) use ($color) {
                $query->where('color', $color);
            })
            ->when($search && $searchName, function ($query) use ($searchName) {
                $query->where('pictureName', 'like', '%' . $searchName . '%');
            })
            ->orderBy('sort')
            ->with([
                'picOther' => function ($query) use ($params) {
                    $query->where('year', 2025)->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                if ($params['lotteryType'] == 3 || $params['lotteryType'] == 1) {
//                    $query->where('year', 2024)->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                } else {
//                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                }
                }
            ])
            ->simplePaginate($hasAd ? 10 : 15, [
                'lotteryType', 'pictureTypeId', 'pictureName', 'color', 'width', 'height', 'sort', 'id'
            ], 'page', isset($params['cache']) ? $params['page'] : null);

        $pic_lists = $pic_lists->toArray();

        foreach ($pic_lists['data'] as $k => $list) {
            if (empty($list['pic_other'])) {
                unset($pic_lists['data'][$k]);
            }
        }
        ksort($pic_lists['data']);
        $this->sortArrayByField($pic_lists['data'], 'sort');


        // 上一期开奖期数
//        $previous = HistoryNumber::query()->where('lotteryType', $params['lotteryType'])->orderBy('id', 'desc')->first(['issue']);
//        $previousIssue = ltrim($previous['issue'], 0);

        $previous = $this->getNextIssue($params['lotteryType']);
        $previousIssue = ltrim($previous, 0);
//        dd($previous, $previousIssue);
        foreach ($pic_lists['data'] as $k => $list) {

            $pic_lists['data'][$k]['is_ad'] = false;
            $pic_lists['data'][$k]['year'] = $list['pic_other']['year'];
            $pic_lists['data'][$k]['pictureId'] = $list['pic_other']['year'] . str_pad($list['pic_other']['max_issue'], 3, 0, STR_PAD_LEFT) . $list['pictureTypeId'];
            $pic_lists['data'][$k]['pictureUrl'] = str_replace('big-pic', 'm', $this->getPicUrl($list['color'], $list['pic_other']['max_issue'], $list['pic_other']['keyword'], $list['lotteryType'])); // , 'jpg', $params['lotteryType']==3?2024:2023
//            if ($params['lotteryType'] == 1) {
//                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace('2024/', '', str_replace('big-pic', 'm', $this->getPicUrl($list['color'], 140, $list['pic_other']['keyword'], $list['lotteryType'], 'jpg'))); // , 'jpg', $params['lotteryType']==3?2024:2023
//            } else if ($params['lotteryType'] == 2) {
//                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace('2024/', '', str_replace('big-pic', 'm', $this->getPicUrl($list['color'], 366, $list['pic_other']['keyword'], $list['lotteryType'], 'jpg', 2024))); // , 'jpg', $params['lotteryType']==3?2024:2023
//            }  else if ($params['lotteryType'] == 6) {
//                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace('2024/', '', str_replace('big-pic', 'm', $this->getPicUrl($list['color'], 352, $list['pic_other']['keyword'], $list['lotteryType'], 'jpg', 2024))); // , 'jpg', $params['lotteryType']==3?2024:2023
//            }   else if ($params['lotteryType'] == 7) {
//                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace('2024/', '', str_replace('big-pic', 'm', $this->getPicUrl($list['color'], 366, $list['pic_other']['keyword'], $list['lotteryType'], 'jpg', 2024))); // , 'jpg', $params['lotteryType']==3?2024:2023
//            } else {
//                $pic_lists['data'][$k]['previousPictureUrl'] = '';
//            }
            $pic_lists['data'][$k]['previousPictureUrl'] = $this->getPicUrl($list['color'], $previousIssue - 1, $list['pic_other']['keyword'], $list['lotteryType']); // , 'jpg', $params['lotteryType']==3?2024:2023
            $pic_lists['data'][$k]['pictureUrlOther'] = '';
            $pic_lists['data'][$k]['previousPictureUrlOther'] = '';

            if ($list['lotteryType'] == 2 || $list['lotteryType'] == 6 || $list['lotteryType'] == 1) {
                $urlPrefixArr = $this->getImgPrefix()[$list['pic_other']['year']][$list['lotteryType']];

                if ($list['pictureTypeId'] == "33344") {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $v) {
                            $pic_lists['data'][$k]['pictureUrl'] = str_replace($v . 'm/', 'https://tu.tuku.fit/aomen/' . $list['pic_other']['year'] . '/', $pic_lists['data'][$k]['pictureUrl']);
                            $pic_lists['data'][$k]['previousPictureUrl'] = str_replace($v . 'm/', 'https://tu.tuku.fit/aomen/' . $list['pic_other']['year'] . '/', $pic_lists['data'][$k]['previousPictureUrl']);
                        }
                    }
                } else if ($list['pictureTypeId'] ==69310) {
                    $pic_lists['data'][$k]['pictureUrl'] = str_replace('/m/', '/big-pic/', $pic_lists['data'][$k]['pictureUrl']);
                    $pic_lists['data'][$k]['previousPictureUrl'] = str_replace('/m/', '/big-pic/', $pic_lists['data'][$k]['previousPictureUrl']);
                } else if (in_array($list['pictureTypeId'], $this->_all_pictureIds)) {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $v) {
                            if (in_array($list['pictureTypeId'], $this->_am_pictureIds)) { // https://am.tuku.fit/galleryfiles/system/m/col/2024/128/ammh.jpg
                                $pic_lists['data'][$k]['pictureUrl'] = str_replace($v.'m/col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/'.$pic_lists['data'][$k]['year'].'/', $pic_lists['data'][$k]['pictureUrl']);
                                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace($v.'m/col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/'.$pic_lists['data'][$k]['year'].'/', $pic_lists['data'][$k]['previousPictureUrl']);
                            } else if (in_array($list['pictureTypeId'], $this->_kl8_pictureIds)) {
                                $pic_lists['data'][$k]['pictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/m/col/', $pic_lists['data'][$k]['pictureUrl']);
                                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/m/col/', $pic_lists['data'][$k]['previousPictureUrl']);
                            } else if (in_array($list['pictureTypeId'], $this->_xg_pictureIds)) {
                                $pic_lists['data'][$k]['pictureUrl'] = str_replace([$v . 'm/col/', $v . '/m/col/'], 'https://xg.tuku.fit/galleryfiles/system/big-pic/col/'.$pic_lists['data'][$k]['year'].'/', $pic_lists['data'][$k]['pictureUrl']);
                                $pic_lists['data'][$k]['previousPictureUrl'] = str_replace([$v . 'm/col/', $v . '/m/col/'], 'https://xg.tuku.fit/galleryfiles/system/big-pic/col/'.$pic_lists['data'][$k]['year'].'/', $pic_lists['data'][$k]['previousPictureUrl']);
                            }
                        }
                    }
                }
            }

//            if ($list['lotteryType'] == 5) {
//                if ($list['pictureTypeId'] != "00403") {
//                    $pic_lists['data'][$k]['pictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $pic_lists['data'][$k]['pictureUrl']);
//                    $pic_lists['data'][$k]['previousPictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $pic_lists['data'][$k]['previousPictureUrl']);
//                } else {
//                    $pic_lists['data'][$k]['pictureUrlOther'] = $pic_lists['data'][$k]['pictureUrl'];
//                    $pic_lists['data'][$k]['previousPictureUrlOther'] = $pic_lists['data'][$k]['previousPictureUrl'];
//                }
//            } https://tk2.zaojiao365.net:4949/ https://tk2.zaojiao365.net:4949/m/col/97/jpbb.jpg
            if ($list['lotteryType'] == 5) {
                $pic_lists['data'][$k]['pictureUrlOther'] = $pic_lists['data'][$k]['pictureUrl'];
                $pic_lists['data'][$k]['previousPictureUrlOther'] = $pic_lists['data'][$k]['previousPictureUrl'];
            }
            if($list['lotteryType'] == 2 && $pic_lists['data'][$k]['pictureId'] ==202419028089) {
                $pic_lists['data'][$k]['pictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240708/ampgt2.jpg';
            }
            if($list['lotteryType'] == 2 && $pic_lists['data'][$k]['pictureId'] ==202420028021) {
                $pic_lists['data'][$k]['pictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240718/ampgt2.jpg';
            }
            if($list['lotteryType'] == 2 && $pic_lists['data'][$k]['pictureId'] ==2024229417013) {
                $pic_lists['data'][$k]['pictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240815/lmkz.jpg';
            }
            if($list['lotteryType'] == 2 && $pic_lists['data'][$k]['pictureId'] ==202426128021) {
                $pic_lists['data'][$k]['pictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240917/amgp.jpg';
            }
            if($list['lotteryType'] == 1 && $pic_lists['data'][$k]['pictureId'] ==202412110869) {
                $pic_lists['data'][$k]['pictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20241112/mj07.jpg';
            }
            unset($pic_lists['data'][$k]['pic_other']);

            if ($list['lotteryType'] == 1) {
                // 新增s3地址  https://amtk.tuku.fit
                if (Str::startsWith($pic_lists['data'][$k]['pictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://xg.tuku.fit', 'https://kk.tuku.fit', 'https://mm.tuku.fit', 'https://49tk.tuku.fit'])) {
                    $pic_lists['data'][$k]['s3_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$previousIssue}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                    $previousIssue1 = $previousIssue - 1;
                    $pic_lists['data'][$k]['s3_prefix_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$previousIssue1}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                } else {
                    $pic_lists['data'][$k]['pictureUrl'] = $pic_lists['data'][$k]['s3_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$previousIssue}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                    $previousIssue1 = $previousIssue - 1;
                    $pic_lists['data'][$k]['previousPictureUrl'] = $pic_lists['data'][$k]['s3_prefix_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$previousIssue1}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                }
            }

            if ($list['lotteryType'] == 2) {
                // 新增s3地址  https://amtk.tuku.fit
                if (Str::startsWith($pic_lists['data'][$k]['pictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://49tk.tuku.fit'])) {
                    $pic_lists['data'][$k]['s3_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$previousIssue}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                    $previousIssue1 = $previousIssue - 1;
                    $pic_lists['data'][$k]['s3_prefix_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$previousIssue1}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                } else {
                    $pic_lists['data'][$k]['pictureUrl'] = $pic_lists['data'][$k]['s3_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$previousIssue}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                    $previousIssue1 = $previousIssue - 1;
                    $pic_lists['data'][$k]['previousPictureUrl'] = $pic_lists['data'][$k]['s3_prefix_pictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$previousIssue1}/" . basename($pic_lists['data'][$k]['pictureUrl']);
                }
            }
        }
//        if (auth('user')->id() == 75454) {
//            dd($pic_lists['data'], $this->getImgPrefix()[2024][2]);
//        }
        // 首页插入广告
        if ($hasAd && count($pic_lists['data'])) {
            $ad = (new AdService())->getIndexAds();
            if ($ad) {
                $ad['ad_image'] = str_replace([
                    'api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'
                ], ConfigService::getAdImgUrl(), $ad['ad_image']);
                $randomRowIndex = mt_rand(0, count($pic_lists['data']) - 1);
                array_splice($pic_lists['data'], $randomRowIndex, 0, [$ad]);
            }
        }

        if (isset($params['cache'])) {
            return $pic_lists;
        }

        if ($params['page'] <= 20) {
            Redis::setex('index_images_list_lottery_type_'. $params['lotteryType'] . '_page_'. $params['page'], 2400, json_encode($pic_lists));
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $pic_lists);
    }

    /**
     * 图片分类数据
     * @param $params
     * @return JsonResponse
     * TODO:: 获取考虑将这块数据加入到redis
     */
    public function get_page_cate($params): JsonResponse
    {
        $list = YearPic::query()
            ->where('is_delete', 0)
            ->select([
                'id', 'pictureTypeId', 'lotteryType', 'year', 'color', 'pictureName', 'max_issue', 'keyword', 'letter'
            ])
            ->where('year', $params['year'])
            ->where('color', $params['color'])
            ->where('lotteryType', $params['lotteryType'])
            ->when(!empty($params['keyword']), function ($query) use ($params) {
                $query->where('pictureName', 'like', '%' . $params['keyword'] . '%');
            })
            ->get()->toArray();
        $maxIssue = $list[0]['max_issue'];
        if ($maxIssue != Redis::get('lottery_real_open_issue_'.$params['lotteryType'])) {
            try {
                (new HistoryService())->update_year_issue($params['lotteryType']);
            } catch (CustomException $e) {
                return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list);
            }
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list);
    }

    /**
     * 图片详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function get_page_detail($params): JsonResponse
    {
        try{
            DB::beginTransaction();
            if (!empty($params['pictureId'])) {
                $params['year'] = mb_substr($params['pictureId'], 0, 4);
                if (!Str::startsWith($params['pictureId'], $params['year'])) {
                    return response()->json(['message' => '年份不允许', 'status' => 40000]);
                }
            }
            $pictureTypeId = $params['pictureTypeId'];
            $lotteryType = $params['lotteryType'];
            $pictureId = (!empty($params['pictureId'])) ? $params['pictureId'] : null;
            $detailData = null;
            $current_issue = null;
            $flag = false;
            $readNum = 0;
            $commentCount = 0;
            $thumbUpCount = 0;
            $isDelete = false;
            $isDeleteId = 0;
            if ($pictureId) {
                $obj = PicDetail::query()->where('pictureId', $pictureId)->select(['id', 'year', 'clickCount', 'commentCount', 'thumbUpCount'])->first();
                if ($obj) {
                    $obj = $obj->toArray();
                    if ($obj['year'] != $params['year']) {
                        $readNum = $obj['clickCount'];
                        $commentCount = $obj['commentCount'];
                        $thumbUpCount = $obj['thumbUpCount'];
                        PicDetail::query()->where('pictureId', $pictureId)->delete();
                        $isDelete = true;
                        $isDeleteId = $obj['id'];
                    }
                }
                $flag = true;
                if ($lotteryType == 4) { // 新彩
                    $current_issue = substr($pictureId, 4, 4);
                    $pictureTypeId = substr($pictureId, 8);
                } else {
                    $current_issue = substr($pictureId, 4, 3);
                    $pictureTypeId = substr($pictureId, 7);
                }
            }

            if (!$pictureId) {
                $detailData = $this->createDetailDataByPictureTypeId($pictureTypeId, $params['year']);
                $pictureId = $detailData['pictureId'];
            }
            // 根据 $pictureId 查出 详情数据
            $PicDetailModel = PicDetail::query()->where('year', $params['year'])->where('pictureId', $pictureId);
            $obj = [];
            $obj['PicDetailData'] = $PicDetailModel->first();   // 详情表数据

            if (!$obj['PicDetailData']) {
                if (!$detailData) {
                    // 根据 $pictureTypeId 查出 详情需要的数据
                    $detailData = $this->createDetailDataByPictureTypeId($pictureTypeId, $params['year']);
                }
                $PicDetailModel = $this->toPicDetailDB($PicDetailModel, $pictureTypeId, $flag ? $pictureId : $detailData['pictureId'], $detailData['pictureName'], $current_issue ?? $detailData['max_issue'], $params['year'], $detailData['color'], $detailData['keyword'], $detailData['lotteryType'], $readNum, $commentCount, $thumbUpCount);
                $obj['PicDetailData'] = $PicDetailModel->find($PicDetailModel->id); // 详情表数据
            }
            if ($isDelete && $isDeleteId>0) {
                DB::table('user_comments')
                    ->where('commentable_id', $isDeleteId)
                    ->where('commentable_type', 'Modules\Api\Models\PicDetail')
                    ->update(['commentable_id'=>$obj['PicDetailData']['id']]);
            }
            if ($obj['PicDetailData']['clickCount'] == 0) {
                $PicDetailModel->increment('clickCount', $this->getFirstViews());
            } else {
                $PicDetailModel->increment('clickCount', $this->getSecondViews());
            }
            // todo 随机增加点赞、收藏数 投票
            $f = AuthActivityConfig::val([
                'zan_random_open', 'fav_random_open', 'random_min_zan_fav', 'random_max_zan_fav'
            ]);
            if ($f['zan_random_open'] == 1) {
                $PicDetailModel->increment('thumbUpCount', rand($f['random_min_zan_fav'], $f['random_max_zan_fav']));
            }
            if ($f['fav_random_open'] == 1) {
                // 随机加
                if (array_rand(range(1, 10)) == 6) {
                    $PicDetailModel->increment('collectCount', rand($f['random_min_zan_fav'], $f['random_max_zan_fav']));
                }
            }
//        if(date('H') != 20 && date('i')<40) {
//            event(new CreateVoteByPic($obj['PicDetailData']));
//        } else if (date('H' != 21 && (date('i')<=20) && date('i')>=40)) {
//            event(new CreateVoteByPic($obj['PicDetailData']));
//        } else if (date('H' != 22 && (date('i')<=20) && date('i')>=40)) {
//            event(new CreateVoteByPic($obj['PicDetailData']));
//        }

//        $zodiac = $this->jiaqin + $this->yeshou;
//        (new VoteService())->insertUserVote($obj['PicDetailData'], Vote::$_vote_zodiac[$zodiac[array_rand($zodiac)]], 54377);

            // 获取投票信息
            $obj['voteList'] = VoteService::getVoteData($obj['PicDetailData']);
            unset($obj['PicDetailData']['votes']);

            $obj['PicDetailData']['follow'] = false;
            $obj['PicDetailData']['collect'] = false;
            $userId = auth('user')->id();
            if ($userId) {
                $obj['PicDetailData']['follow'] = (bool)$obj['PicDetailData']->follow()->where('user_id', $userId)->value('id');
//            $obj['PicDetailData']['collect'] = (bool)$obj['PicDetailData']->collect()->where('user_id', $userId)->value('id');
                $collectIds = UserCollect::query()
                    ->where('user_id', $userId)
                    ->where('collectable_type', 'Modules\Api\Models\PicDetail')
                    ->pluck('collectable_id')->toArray();
                if ($collectIds) {
                    $pictureTypeIds = PicDetail::query()->whereIn('id', $collectIds)->pluck('pictureTypeId')->toArray();
                    if ($pictureTypeIds && in_array($obj['PicDetailData']['pictureTypeId'], $pictureTypeIds)) {
                        $obj['PicDetailData']['collect'] = true;
                    }
                }
            }

            $obj['PicDetailData'] = $obj['PicDetailData']->toArray();
//        dd($obj['PicDetailData']);
            $obj['PicDetailData']['largePictureUrl'] = $this->getPicUrl($obj['PicDetailData']['color'], $obj['PicDetailData']['issue'], $obj['PicDetailData']['keyword'], $obj['PicDetailData']['lotteryType'], 'jpg', $obj['PicDetailData']['year'], true);
            $obj['PicDetailData']['largePictureUrlOther'] = '';
            if ($obj['PicDetailData']['lotteryType'] == 2 || $obj['PicDetailData']['lotteryType'] == 6 || $obj['PicDetailData']['lotteryType'] == 1) {
                $urlPrefixArr = $this->getImgPrefix()[$obj['PicDetailData']['year']][$obj['PicDetailData']['lotteryType']];
                if ($obj['PicDetailData']['pictureTypeId'] == "33344") {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $v) {
                            if ($obj['PicDetailData']['year'] == date('Y')) {
                                $obj['PicDetailData']['largePictureUrl'] = str_replace($v, 'https://tu.tuku.fit/aomen/' . $obj['PicDetailData']['year'] . '/', $obj['PicDetailData']['largePictureUrl']);
                            } else {
                                $obj['PicDetailData']['largePictureUrl'] = str_replace($v, 'https://tu.tuku.fit/aomen/', $obj['PicDetailData']['largePictureUrl']);
                            }
                        }
                    }
                } else if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_all_pictureIds)) {
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $v) {
                            if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_am_pictureIds)) {
                                $obj['PicDetailData']['largePictureUrl'] = str_replace($v.'col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/'.$obj['PicDetailData']['year'].'/', $obj['PicDetailData']['largePictureUrl']);
                            } else if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_kl8_pictureIds)) {
                                $obj['PicDetailData']['largePictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $obj['PicDetailData']['largePictureUrl']);
                            } else if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_xg_pictureIds)){
                                $obj['PicDetailData']['largePictureUrl'] = str_replace($v.'col/', 'https://xg.tuku.fit/galleryfiles/system/big-pic/col/'.$obj['PicDetailData']['year'].'/', $obj['PicDetailData']['largePictureUrl']);
                            }
                        }
                    }
                }
            }
            if ($obj['PicDetailData']['lotteryType'] == 5) {
//            if ($obj['PicDetailData']['pictureTypeId'] != "00403") {
//                $obj['PicDetailData']['largePictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $obj['PicDetailData']['largePictureUrl']);
//            } else {
//                $obj['PicDetailData']['largePictureUrlOther'] = $obj['PicDetailData']['largePictureUrl'];
//            }
                $obj['PicDetailData']['largePictureUrlOther'] = $obj['PicDetailData']['largePictureUrl'];
            }
            if ($obj['PicDetailData']['lotteryType'] == 2 && $obj['PicDetailData']['pictureId'] == 202419028089) {
                $obj['PicDetailData']['largePictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240708/ampgt2.jpg';
            }
            if($obj['PicDetailData']['lotteryType'] == 2 && $obj['PicDetailData']['pictureId'] ==202420028021) {
                $obj['PicDetailData']['largePictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240718/ampgt2.jpg';
            }
            if($obj['PicDetailData']['lotteryType'] == 2 && $obj['PicDetailData']['pictureId'] ==2024229417013) {
                $obj['PicDetailData']['largePictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240815/lmkz.jpg';
            }
            if($obj['PicDetailData']['lotteryType'] == 2 && $obj['PicDetailData']['pictureId'] ==202426128021) {
                $obj['PicDetailData']['largePictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20240917/amgp.jpg';
            }
            if($obj['PicDetailData']['lotteryType'] == 1 && $obj['PicDetailData']['pictureId'] ==202412110869) {
                $obj['PicDetailData']['largePictureUrl'] = 'https://api1.49tkapi8.com/upload/images/20241112/mj07.jpg';
            }
            if ($obj['PicDetailData']['lotteryType'] == 1) {
                if ($obj['PicDetailData']['year'] == 2024) {
                    $obj['PicDetailData']['largePictureUrl'] = str_replace('https://tk.zaojiao365.net:4949', 'https://tk.tuku.fit/xianggang/2024', $obj['PicDetailData']['largePictureUrl']);
                } else if ($obj['PicDetailData']['year'] == 2023) {
                    $obj['PicDetailData']['largePictureUrl'] = str_replace('https://tk.zaojiao365.net:4949', 'https://tk.tuku.fit/xianggang/' . $obj['PicDetailData']['year'], $obj['PicDetailData']['largePictureUrl']);
                } else {
                    $obj['PicDetailData']['largePictureUrl'] = str_replace('https://tk.zaojiao365.net:4949', 'https://tk.tuku.fit/xianggang/', $obj['PicDetailData']['largePictureUrl']);
                }
            }
            // 期数列表
            $issues = YearPic::query()->where('pictureTypeId', $pictureTypeId)->where('year', $params['year'])->select([
                'id', 'max_issue', 'issues'
            ])->first();

            $arr = [];
            if ($issues) {
                // 更新年份期数表
                if ($issues['max_issue'] != Redis::get('lottery_real_open_issue_'.$obj['PicDetailData']['lotteryType'])) {
                    try {
                        (new HistoryService())->update_year_issue($obj['PicDetailData']['lotteryType']);
                    } catch (CustomException $e) {
//                        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS);
                    }
                }
                foreach ($issues['issues'] as $k => $issue) {
                    if ($params['lotteryType'] == 3 && $issue == "第117期") {
                        continue;
                    }
                    if ($params['lotteryType'] == 4 && $params['year'] == 2023 && $issue == "第3935期") {
                        continue;
                    }
//                if (count($arr)<50) {
                    if ($params['lotteryType'] == 2 && in_array($pictureTypeId, $this->_am_pictureIds) && date('Y') == 2024) {
                        $iss = str_pad(rtrim(ltrim($issue, '第'), '期'), 3, 0, STR_PAD_LEFT);
                        if ((int)$iss < 95) {
                            continue;
                        }
                        $arr[$k]['issue'] = $iss;
                    } else if ($params['lotteryType'] == 6 && in_array($pictureTypeId, $this->_kl8_pictureIds) && date('Y') == 2024) {
//                        $iss = str_pad(rtrim(ltrim($issue, '第'), '期'), 3, 0, STR_PAD_LEFT);
//                        if ((int)$iss < 85) {
//                            continue;
//                        }
//                        $arr[$k]['issue'] = $iss;
                    } else {
                        $arr[$k]['issue'] = str_pad(rtrim(ltrim($issue, '第'), '期'), 3, 0, STR_PAD_LEFT);
                    }
                    $arr[$k]['name'] = $issue;

                    $arr[$k]['pictureId'] = $obj['PicDetailData']['year'] . $arr[$k]['issue'] . $obj['PicDetailData']['pictureTypeId'];
//                }
                }
                rsort($arr);
            }
            // 新澳 S3 图片替换 最新一期
            if ( $obj['PicDetailData']['lotteryType'] == 1 && !Str::startsWith($obj['PicDetailData']['largePictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://xg.tuku.fit', 'https://kk.tuku.fit', 'https://mm.tuku.fit', 'https://49tk.tuku.fit'])) {
                if ($obj['PicDetailData']['issue'] == $obj['PicDetailData']['issues'][0]['issue']) {
                    $obj['PicDetailData']['largePictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$obj['PicDetailData']['issue']}/" . $obj['PicDetailData']['keyword'] . ".jpg";
                }
            }
            if ( $obj['PicDetailData']['lotteryType'] == 2 && !Str::startsWith($obj['PicDetailData']['largePictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://49tk.tuku.fit'])) {
                if ($obj['PicDetailData']['issue'] == $obj['PicDetailData']['issues'][0]['issue']) {
                    $obj['PicDetailData']['largePictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$obj['PicDetailData']['issue']}/" . $obj['PicDetailData']['keyword'] . ".jpg";
                }
            }
            $obj['PicDetailData']['issues'] = $arr;
            if (isset($params['ts'])) {
                dd($obj);
            }
//        $obj['PicDetailData']['collectCount'] += rand(10000, 30000);
//        $obj['PicDetailData']['thumbUpCount'] += rand(10000, 30000);

            // 详情广告
//        $adList = (new AdService())->getAdListByPoi([2]);
//        $obj['adList'] = $adList;
            $obj['PicDetailData']['ai_analyzes'] = [];
            DB::commit();
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $obj);
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('图片详情出错:', ['message'=>$exception->getMessage(), 'data'=>json_encode($params)]);
            return response()->json(['message' => '网络异常，请重试', 'status' => 40000]);
        }

    }

    /**
     * 通过`$pictureTypeId`生成图片详情对象
     * @param $pictureTypeId
     * @param $year
     * @return array
     * @throws CustomException
     */
    private function createDetailDataByPictureTypeId($pictureTypeId, $year = null): array
    {
        if (!$year) {
            $year = $this->_current_year;
        }
        try {
            $yearPicData = YearPic::query()->where('pictureTypeId', $pictureTypeId)
                ->where('year', $year)
                ->select(['pictureName', 'max_issue', 'year', 'color', 'keyword', 'lotteryType', 'pictureTypeId'])
                ->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => 'pictureTypeId不存在']);
        }

        $data = [];
        $data['pictureId'] = $yearPicData['year'] . str_pad($yearPicData['max_issue'], 3, '0', STR_PAD_LEFT) . $pictureTypeId;
        $data['pictureName'] = $yearPicData['pictureName'];
        $data['max_issue'] = $yearPicData['max_issue'];
        $data['color'] = $yearPicData['color'];
        $data['keyword'] = $yearPicData['keyword'];
        $data['lotteryType'] = $yearPicData['lotteryType'];
        $data['pictureTypeId'] = $yearPicData['pictureTypeId'];

        return $data;
    }

    /**
     * 首页访问某期图片时  不存在则入库处理
     * @param $PicDetailModel
     * @param $pictureTypeId
     * @param $pictureId
     * @param $pictureName
     * @param $current_issue
     * @param $year
     * @param $color
     * @param $keyword
     * @param $lotteryType
     * @param int $readNum
     * @return mixed
     */
    private function toPicDetailDB($PicDetailModel, $pictureTypeId, $pictureId, $pictureName, $current_issue, $year, $color, $keyword, $lotteryType, $readNum=0, $commentCount=0, $thumbUpCount=0)
    {
        // 先把（伪）最新的一期图片的点赞和收藏量拿出来
        $res = DB::table('pic_details')
            ->where('pictureTypeId', $pictureTypeId)
            ->where('year', $year)
            ->where('issue', $current_issue - 1)
            ->select(['collectCount', 'thumbUpCount'])
            ->first();
        if (!$res) {
            $res = DB::table('pic_details')
                ->where('pictureTypeId', $pictureTypeId)
                ->where('year', $year - 1)
                ->orderByDesc('issue')
                ->select(['collectCount', 'thumbUpCount'])
                ->first();
        }
        $collectCount = 0;
        if ($res) {
            $collectCount = $res->collectCount ?? $collectCount;
            $thumbUpCount = $thumbUpCount ?: ($res->thumbUpCount ?? 0);
        }
        return $PicDetailModel->create([
            'pictureTypeId' => $pictureTypeId,
            'pictureId'     => $pictureId,
            'pictureName'   => $pictureName,
            'issue'         => $current_issue,
            'year'          => $year,
            'color'         => $color,
            'keyword'       => $keyword,
            'lotteryType'   => $lotteryType,
            'collectCount'  => $collectCount,
            'thumbUpCount'  => $thumbUpCount,
            'clickCount'    => $readNum,
            'commentCount'    => $commentCount,
        ]);
    }

    /**
     * 图片点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        try {
            $picDetailModel = PicDetail::query()->where('pictureId', $params['pictureId']);

            return (new FollowService())->follow($picDetailModel);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => 'pictureId不存在']);
        }
    }

    /**
     * 图片详情[投注站]
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function get_page_detail_bet($params): JsonResponse
    {
        $params['year'] = $params['year'] ?? date('Y');
        if (!empty($params['pictureId'])) {
            if (!Str::startsWith($params['pictureId'], $params['year'])) {
                return response()->json(['message' => '年份不允许', 'status' => 40000]);
            }
        }
        try {
            if (!is_numeric($params['year']) || !checkdate(1, 1, $params['year']) || ($params['year'] < 2020)) {
                return response()->json(['message' => '年份不合法', 'status' => 40000]);
            }
        } catch (\Exception $exception) {
            return response()->json(['message' => '年份不合法', 'status' => 40000]);
        }

        $pictureTypeId = $params['pictureTypeId'];
        $lotteryType = $params['lotteryType'];
        $pictureId = (isset($params['pictureId']) && !empty(isset($params['pictureId']))) ? $params['pictureId'] : null;
        $detailData = null;
        $current_issue = null;
        $flag = false;
        if ($pictureId) {
            $flag = true;
            if ($lotteryType == 4) {
                $current_issue = substr($pictureId, 4, 4);
                $pictureTypeId = substr($pictureId, 8);
            } else {
                $current_issue = substr($pictureId, 4, 3);
                $pictureTypeId = substr($pictureId, 7);
            }
        }

        if (!$pictureId) {
            $detailData = $this->createDetailDataByPictureTypeId($pictureTypeId, $params['year']);
            $pictureId = $detailData['pictureId'];
        }
        // 根据 $pictureId 查出 详情数据
        $PicDetailModel = PicDetail::query()->where('pictureId', $pictureId)->where('year', $params['year']);
        $obj = [];
        $obj['PicDetailData'] = $PicDetailModel->first();   // 详情表数据
        if (!$obj['PicDetailData']) {
            if (!$detailData) {
                // 根据 $pictureTypeId 查出 详情需要的数据
                $detailData = $this->createDetailDataByPictureTypeId($pictureTypeId, $params['year']);
            }
            $PicDetailModel = $this->toPicDetailDB($PicDetailModel, $pictureTypeId, $flag ? $pictureId : $detailData['pictureId'], $detailData['pictureName'], $current_issue ?? $detailData['max_issue'], $params['year'], $detailData['color'], $detailData['keyword'], $detailData['lotteryType']);
            $obj['PicDetailData'] = $PicDetailModel->find($PicDetailModel->id); // 详情表数据
        }
        if ($obj['PicDetailData']['clickCount'] == 0) {
            $PicDetailModel->increment('clickCount', $this->getFirstViews());
        } else {
            $PicDetailModel->increment('clickCount', $this->getSecondViews());
        }

        $obj['PicDetailData']['follow'] = false;
        $obj['PicDetailData']['collect'] = false;
//        $userId = auth('user')->id();
//        if ($userId) {
//            $obj['PicDetailData']['follow']  = (bool)$obj['PicDetailData']->follow()->where('user_id', $userId)->value('id');
//            $obj['PicDetailData']['collect'] = (bool)$obj['PicDetailData']->collect()->where('user_id', $userId)->value('id');
//        }

        $obj['PicDetailData'] = $obj['PicDetailData']->toArray();
        $obj['PicDetailData']['largePictureUrl'] = $this->getPicUrl($obj['PicDetailData']['color'], $obj['PicDetailData']['issue'], $obj['PicDetailData']['keyword'], $obj['PicDetailData']['lotteryType'], 'jpg', $obj['PicDetailData']['year'], true);
        $obj['PicDetailData']['largePictureUrlOther'] = '';
        if ($obj['PicDetailData']['lotteryType'] == 5) {
            $obj['PicDetailData']['largePictureUrlOther'] = $obj['PicDetailData']['largePictureUrl'];
        }
        if ($obj['PicDetailData']['lotteryType'] == 2 || $obj['PicDetailData']['lotteryType'] == 6) {
            $urlPrefixArr = $this->getImgPrefix()[$params['year']][$obj['PicDetailData']['lotteryType']];
            if ($obj['PicDetailData']['pictureTypeId'] == "33344") {
                if (is_array($urlPrefixArr)) {
                    foreach ($urlPrefixArr as $v) {
                        $obj['PicDetailData']['largePictureUrl'] = str_replace($v, 'https://tu.tuku.fit/aomen/' . $params['year'] . '/', $obj['PicDetailData']['largePictureUrl']);
                    }
                }
            } else if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_am_pictureIds)) {
                if (is_array($urlPrefixArr)) {
                    foreach ($urlPrefixArr as $v) {
                        if (in_array($obj['PicDetailData']['pictureTypeId'], $this->_kl8_pictureIds)) {
                            $obj['PicDetailData']['largePictureUrl'] = str_replace($v.'col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/', $obj['PicDetailData']['largePictureUrl']);
                        } else {
                            $obj['PicDetailData']['largePictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $obj['PicDetailData']['largePictureUrl']);
                        }
                    }
                }
            }
        }
        unset($obj['PicDetailData']['issue']);
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $obj);
    }

    /**
     * 图片推荐
     * @param $year
     * @param $pictureTypeId
     * @param $lotteryType
     * @return JsonResponse
     */
    public function recommends($year, $pictureTypeId, $lotteryType): JsonResponse
    {
        $letter = YearPic::query()->where('pictureTypeId', $pictureTypeId)->where('year', $year)->value('letter');

        $recommends = YearPic::query()->where('year', $year)->where('lotteryType', $lotteryType)->where('letter', $letter)->select([
            'pictureTypeId', 'year', 'max_issue', 'color', 'keyword', 'lotteryType', 'pictureName'
        ])->simplePaginate(10);
        $picRecommend = [];
        // 上一期开奖期数
        $previous = HistoryNumber::query()->where('lotteryType', $lotteryType)->orderBy('id', 'desc')->first(['issue']);
        $previousIssue = ltrim($previous['issue'], 0);
        if (!$recommends->isEmpty()) {
            foreach ($recommends as $k => $recommend) {
                $recommends[$k]['pictureId'] = $recommend['year'] . str_pad($recommend['max_issue'], 3, 0, STR_PAD_LEFT) . $recommend['pictureTypeId'];
                $recommends[$k]['largePictureUrl'] = $this->getPicUrl($recommend['color'], $recommend['max_issue'], $recommend['keyword'], $recommend['lotteryType'], 'jpg', $year);
                $recommends[$k]['previousPictureUrl'] = $this->getPicUrl($recommend['color'], $previousIssue, $recommend['keyword'], $recommend['lotteryType']);
                $recommends[$k]['largePictureUrlOther'] = '';
                $recommends[$k]['previousPictureUrlOther'] = '';
                if ($recommend['lotteryType'] == 5) {
//                    if ($recommend['pictureTypeId'] != "00403") {
//                        $recommends[$k]['largePictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $recommends[$k]['largePictureUrl']);
//                        $recommends[$k]['previousPictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $recommends[$k]['previousPictureUrl']);
//                    } else {
//                        $recommends[$k]['largePictureUrlOther'] = $recommends[$k]['largePictureUrl'];
//                        $recommends[$k]['previousPictureUrlOther'] = $recommends[$k]['previousPictureUrl'];
//                    }
                    $recommends[$k]['largePictureUrlOther'] = $recommends[$k]['largePictureUrl'];
                    $recommends[$k]['previousPictureUrlOther'] = $recommends[$k]['previousPictureUrl'];
                }
                if ($recommend['lotteryType'] == 2 || $recommend['lotteryType'] == 6) {
                    $urlPrefixArr = $this->getImgPrefix()[$year][$recommend['lotteryType']];
                    if ($recommend['pictureTypeId'] == "33344") {
                        $year = date('Y');
                        if (is_array($urlPrefixArr)) {
                            foreach ($urlPrefixArr as $v) {
                                $recommends[$k]['largePictureUrl'] = str_replace($v, 'https://tu.tuku.fit/aomen/' . $year . '/', $recommends[$k]['largePictureUrl']);
                                $recommends[$k]['previousPictureUrl'] = str_replace($v, 'https://tu.tuku.fit/aomen/' . $year . '/', $recommends[$k]['previousPictureUrl']);
                            }
                        }
                    } else if (in_array($recommend['pictureTypeId'], $this->_all_pictureIds)) {
                        if (is_array($urlPrefixArr)) {
                            foreach ($urlPrefixArr as $v) {
                                if (in_array($recommend['pictureTypeId'], $this->_am_pictureIds)) {
                                    $recommends['data'][$k]['pictureUrl'] = str_replace($v.'m/col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/'.$year.'/', $recommends[$k]['pictureUrl']);
                                    $recommends['data'][$k]['previousPictureUrl'] = str_replace($v.'m/col/', 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/'.$year.'/', $recommends[$k]['previousPictureUrl']);
                                } else {
                                    $recommends['data'][$k]['pictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $recommends[$k]['pictureUrl']);
                                    $recommends['data'][$k]['previousPictureUrl'] = str_replace($v.'col/', 'https://am.tuku.fit/galleryfiles/system/big-pic/col/', $recommends[$k]['previousPictureUrl']);
                                }
                            }
                        }
                    }
                }
                // 新澳 S3 图片替换 最新一期 largePictureUrl
                if ( $recommends[$k]['lotteryType'] == 1 && !Str::startsWith($recommends[$k]['largePictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://xg.tuku.fit', 'https://kk.tuku.fit', 'https://mm.tuku.fit', 'https://49tk.tuku.fit'])) {
//                    if ($recommends['data'][$k]['max_issue'] == 98) {
                    $recommends[$k]['largePictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$recommends[$k]['max_issue']}/" . $recommends[$k]['keyword'] . ".jpg";
                    $previousIssue = $recommends[$k]['max_issue'] - 1;
                    $recommends[$k]['previousPictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/xg/{$previousIssue}/" . $recommends[$k]['keyword'] . ".jpg";
//                    }
                }
                if ( $recommends[$k]['lotteryType'] == 2 && !Str::startsWith($recommends[$k]['largePictureUrl'], ['https://amtk.tuku.fit', 'https://tu.tuku.fit', 'https://49tk.tuku.fit'])) {
//                    if ($recommends['data'][$k]['max_issue'] == 98) {
                    $recommends[$k]['largePictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$recommends[$k]['max_issue']}/" . $recommends[$k]['keyword'] . ".jpg";
                    $previousIssue = $recommends[$k]['max_issue'] - 1;
                    $recommends[$k]['previousPictureUrl'] = "https://lty-s1.s3.ap-east-1.amazonaws.com/49_tk/images/{$previousIssue}/" . $recommends[$k]['keyword'] . ".jpg";
//                    }
                }
            }
            $recommends = $recommends->toArray();
            $picRecommend = $recommends['data'];
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $picRecommend);
    }

    /**
     * 期数
     * @param $params
     * @return JsonResponse
     */
    public function issues($params): JsonResponse
    {
//        $page = $params['page'] ?? 1;
//        $limit = 50;
//        $start = ($page-1) * $limit; // 0 50 100
//        $end = $page * $limit;  // 50  100 150
        // 期数列表
        $issues = YearPic::query()
            ->where('pictureTypeId', $params['pictureTypeId'])
            ->where('year', $params['year'])
            ->value('issues');
//        dd($issues);
        $arr = [];
        foreach ($issues as $k => $issue) {
            $arr[$k]['issue'] = str_pad(rtrim(ltrim($issue, '第'), '期'), 3, 0, STR_PAD_LEFT);
            $arr[$k]['name'] = $issue;
            $arr[$k]['pictureId'] = $params['year'] . $arr[$k]['issue'] . $params['pictureTypeId'];
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $arr);
    }

    /**
     * 图片投票
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function votes($params): JsonResponse
    {
        try {
            $picDetailModel = PicDetail::query()->where('pictureId', $params['pictureId'])->firstOrFail(['id']);
            (new VoteService())->insertUserVote($picDetailModel, Vote::$_vote_zodiac[$params['vote_zodiac']], $params['isAdminUserId'] ?? 0);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => 'pictureId不存在']);
        }
        $res = VoteService::getVoteData(PicDetail::query()->where('pictureId', $params['pictureId'])->firstOrFail(['id']));

        return $this->apiSuccess(ApiMsgData::VOTE_API_SUCCESS, ['voteList' => $res]);
    }

    /**
     * 图片点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect($params): JsonResponse
    {
        try {
            $picDetailIds = DB::table('pic_details')
                ->select('id')
                ->where('pictureTypeId', function ($query) use ($params) {
                    $query
                        ->select('pictureTypeId')
                        ->where('pictureId', $params['pictureId'])
                        ->from('pic_details');
                })
                ->get('id')
                ->map(function ($item) {
                    return (array)$item;
                })
                ->toArray();
            $picDetailIds = array_map(function ($item) {
                return $item['id'];
            }, $picDetailIds);
            $exists = DB::table('user_collects')
                ->where('user_id', auth('user')->id())
                ->whereIn('collectable_id', $picDetailIds)
                ->where('collectable_type', 'Modules\Api\Models\PicDetail')
                ->exists();
            if ($exists) {
                // 取消收藏
                $res = DB::table('user_collects')
                    ->where('user_id', auth('user')->id())
                    ->whereIn('collectable_id', $picDetailIds)
                    ->where('collectable_type', 'Modules\Api\Models\PicDetail')
                    ->delete();
                if ($res) {
                    DB::table('pic_details')->where('pictureId', $params['pictureId'])->decrement('collectCount');
                    return $this->apiSuccess(ApiMsgData::COLLECT_API_CANCEL);
                }
            } else {
                $res = DB::table('user_collects')
                    ->insert([
                        'user_id'          => auth('user')->id(),
                        'collectable_id'   => $picDetailIds[0],
                        'collectable_type' => 'Modules\Api\Models\PicDetail',
                        'created_at'       => date('Y-m-d H:i:s')
                    ]);
                if ($res) {
                    DB::table('pic_details')->where('pictureId', $params['pictureId'])->increment('collectCount');
                    return $this->apiSuccess(ApiMsgData::COLLECT_API_SUCCESS);
                }
            }

            return $this->apiSuccess('请刷新重试');
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => 'pictureId不存在']);
        }
    }

    /**
     * 图片系列
     * @return JsonResponse
     */
    public function series_list(): JsonResponse
    {
        $list = PicSeries::query()->orderBy('sort')->select(['id', 'name'])->get();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list->toArray());
    }

    /**
     * 图片系列图片详情
     * @param $params
     * @return JsonResponse
     */
    public function series_detail($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'] ?? 0;
        $keyword = $params['keyword'] ?? '';
        if (!in_array($lotteryType, [0, 1, 2, 3, 4, 5, 6, 7])) {
            return response()->json(['message' => $lotteryType . '不合法', 'status' => 40000], 400);
        }
        $pic_lists = IndexPic::query()
            ->where('series_id', $params['id'])
            ->when($lotteryType, function ($query) use ($lotteryType) {
                $query->where('lotteryType', $lotteryType);
            })
            ->when($keyword, function ($query) use ($params) {
                $query->where('pictureName', 'like', '%' . $params['keyword'] . '%');
            })
            ->orderBy('sort')
            ->with([
                'picOther' => function ($query) {
                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
                }
            ])
            ->simplePaginate(10, ['lotteryType', 'pictureTypeId', 'pictureName', 'color', 'width', 'height']);
        $pic_lists = $pic_lists->toArray();
        foreach ($pic_lists['data'] as $k => $list) {
            if (empty($list['pic_other'])) {
                unset($pic_lists['data'][$k]);
            }
        }
        ksort($pic_lists['data']);
        for ($i = 1; $i <= 6; $i++) {
            $previous[$i] = ltrim($this->getNextIssue($i), 0);
        }
        foreach ($pic_lists['data'] as $k => $list) {
            $pic_lists['data'][$k]['is_ad'] = false;
            $pic_lists['data'][$k]['year'] = $list['pic_other']['year'];
            $pic_lists['data'][$k]['pictureId'] = $list['pic_other']['year'] . str_pad($list['pic_other']['max_issue'], 3, 0, STR_PAD_LEFT) . $list['pictureTypeId'];
            $pic_lists['data'][$k]['pictureUrl'] = $this->getPicUrl($list['color'], $list['pic_other']['max_issue'], $list['pic_other']['keyword'], $list['lotteryType']);
            $pic_lists['data'][$k]['previousPictureUrl'] = $this->getPicUrl($list['color'], $previous[$list['lotteryType']] - 1, $list['pic_other']['keyword'], $list['lotteryType']);
            $pic_lists['data'][$k]['pictureUrlOther'] = '';
            $pic_lists['data'][$k]['previousPictureUrlOther'] = '';
            if ($list['lotteryType'] == 2) {
                if ($list['pictureTypeId'] == "33344") {
                    $urlPrefixArr = $this->getImgPrefix()[$list['pic_other']['year']][$list['lotteryType']];
                    if (is_array($urlPrefixArr)) {
                        foreach ($urlPrefixArr as $v) {
                            $pic_lists['data'][$k]['pictureUrl'] = str_replace($v . 'm/', 'https://tu.tuku.fit/aomen/' . $list['pic_other']['year'] . '/', $pic_lists['data'][$k]['pictureUrl']);
                            $pic_lists['data'][$k]['previousPictureUrl'] = str_replace($v . 'm/', 'https://tu.tuku.fit/aomen/' . $list['pic_other']['year'] . '/', $pic_lists['data'][$k]['previousPictureUrl']);
                        }
                    }
                }
            }
            if ($list['lotteryType'] == 5) {
                $pic_lists['data'][$k]['pictureUrlOther'] = $pic_lists['data'][$k]['pictureUrl'];
                $pic_lists['data'][$k]['previousPictureUrlOther'] = $pic_lists['data'][$k]['previousPictureUrl'];
            }
            unset($pic_lists['data'][$k]['pic_other']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $pic_lists);
    }

    /**
     * 根据服务器上图片的地址获取图片信息
     * 图片路径不能以http https 或者 // 开头
     * @param string $img_url
     * @param bool $water
     * @param bool $isCheck
     * @return array
     * @throws CustomException
     */
    public function getImageInfoWithOutHttp(string $img_url = '', bool $water = false, bool $isCheck=true): array
    {
        if (!$img_url) {
            throw new CustomException(['message' => '图片地址为空']);
        }
        if ($isCheck) {
            if (Str::startsWith($img_url, 'http') || Str::startsWith($img_url, '\\\\')) {
                throw new CustomException(['message' => '非法上传图片']);
            }
            if (!Str::startsWith($img_url, 'upload')) {
                throw new CustomException(['message' => '图片路径不正确']);
            }
        }
        try {
            $image = [];
            $imageInfo = Image::make($img_url);

            $image['width'] = $imageInfo->width();
            $image['height'] = $imageInfo->height();
            $image['mime'] = $imageInfo->mime();
            if ($water) {
                $fontPath = public_path('font/PingFang.ttc');
//                $fontPath = public_path('font/font.ttf');
                // 在图片上添加居中文本
//                $text = '49图库';
                // 文本颜色，可以使用十六进制或 RGB 值

//                $imageInfo->text($text, $image['width'] / 2, $image['height'] / 2 - 100, function ($font) use ($fontPath) {
////                    $fontColor = '#000';
//                    $fontColor = [
//                        'red'   => 0,
//                        'green' => 0,
//                        'blue'  => 0,
//                        'alpha' => .7, // 设置透明度
//                    ];
//                    $fontSize = 170;
//                    $font->file($fontPath);
//                    $font->size($fontSize);
//                    $font->color($fontColor);
//                    $font->align('center');
//                    $font->valign('middle');
//                });
                $text = '949tk.com';
                // 获取随机角度
                $angle = random_int(0, 90);
                // 生成随机颜色
                // 随机透明度
                $imageInfo->text($text, $image['width'] / 2, $image['height'] / 2, function ($font) use ($fontPath, $angle) {
                    $fontColor = [
                        'red'   => random_int(28, 125), // 红色范围在 128 到 255 之间
                        'green' => random_int(28, 125), // 绿色范围在 128 到 255 之间
                        'blue'  => random_int(28, 125), // 蓝色范围在 128 到 255 之间
//                        'alpha' => .9, // 设置透明度
                    ];
                    $fontSize = 32;
                    $font->file($fontPath);
                    $font->size($fontSize);
                    $font->color($fontColor);
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle($angle);
                });

                // 保存处理后的图片
                $imageInfo->save($img_url);
            }
            return $image;
        } catch (ImageException|\Exception $exception) {
            Log::info('图片失败', ['message' => $exception->getMessage()]);
            throw new CustomException(['message' => $exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

    public function getImageInfo(string $img_url = ''): array
    {
        if (!$img_url) {
            throw new CustomException(['message' => '图片地址为空']);
        }
        try {
            $image = [];
            $imageInfo = Image::make($img_url);
            $image['width'] = $imageInfo->width();
            $image['height'] = $imageInfo->height();
            $image['mime'] = $imageInfo->mime();
            return $image;
        } catch (ImageException $exception) {
            Log::info('图片失败', ['message' => $exception->getMessage()]);
            throw new CustomException(['message' => $exception->getMessage()]);
        }
    }

    /**
     * 判断指定id是否存在
     * @param $id
     * @return bool
     */
    public function existsById($id): bool
    {
        return (bool)PicDetail::query()->find($id, ['id']);
    }

    /**
     *
     * @param $lotteryType
     * @param $pictureId
     * @return array
     */
    private function SyncIssueList($lotteryType, $pictureId): array
    {
        $response = Http::withOptions([
            'verify' => false
        ])->withHeaders([
            'Lotterytype' => $lotteryType
        ])->get(sprintf($this->_sync_issue_url, $pictureId));
        if ($response->status() != 200) {
            Log::error('命令【module:pic-info-other】，出现非200状态码，请立即排查。当前状态码：' . $response->status());
            return [];
        }
        $res = json_decode($response->body(), true);
        $issues = [];
        if ($res['data']['periodList']) {
            foreach ($res['data']['periodList'] as $k => $v) {
                $issues[] = $v['name'];
            }
        }

        return $issues;
    }

    public function get_video_list($params)
    {
        $subQuery = DB::table('pic_videos')
            ->where('lotteryType', '=', $params['lotteryType'])
            ->select('lotteryType', 'pic_name', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('lotteryType', 'pic_name');

        $results = PicVideo::with(['images'])
            ->joinSub($subQuery, 't2', function ($join) {
                $join->on('pic_videos.lotteryType', '=', 't2.lotteryType')
                    ->on('pic_videos.pic_name', '=', 't2.pic_name')
                    ->on('pic_videos.created_at', '=', 't2.max_created_at');
            })
            ->get()->toArray();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $results);
    }

    /**
     * 获取ai分析数据
     * @param $params
     * @return JsonResponse
     */
    public function ai_analyze($params): JsonResponse
    {
        if ($rdsData = Redis::get('ai_analyze_'. $params['lotteryType']. '_'. $params['pictureTypeId']. '_'. $params['year'])) {
            return response()->json(json_decode($rdsData, true));
        }
        $data = Http::get(config('config.49_server_url').'/api/v1/picture/ai_analyze', [
            'lotteryType' => $params['lotteryType'],
            'pictureTypeId' => $params['pictureTypeId'],
            'year' => $params['year'],
        ]);
        if ($data->status() != 200) {
            return $this->apiSuccess();
        }
        $data = json_decode($data->body(), true);

        if (!$data) {
            return $this->apiSuccess();
        }
        Redis::setex('ai_analyze_'. $params['lotteryType']. '_'. $params['pictureTypeId']. '_'. $params['year'], 3600, json_encode($data));
        return response()->json($data);
    }

    public function get_flow_list($params)
    {
        try {
            $userId = auth('user')->id() ?? 0;
//        if ($userId) {
            $res = IndexPic::query()
                ->where('color', 1)
                ->where('lotteryType', $params['lotteryType'])
                ->orderBy('sort')
                ->with([
                    'picDetail' => function ($query) {
                        $query->select([
                            'id', 'pictureTypeId', 'issue', 'pictureName', 'clickCount', 'collectCount', 'commentCount',
                            'thumbUpCount', 'keyword', 'color', 'lotteryType', 'year'
                        ]);
                    }, 'picOther' => function ($query) use ($params) {
                        $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                if ($params['lotteryType'] == 3 || $params['lotteryType'] == 1) {
//                    $query->where('year', 2024)->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                } else {
//                    $query->where('year', date('Y'))->select(['pictureTypeId', 'keyword', 'max_issue', 'year']);
//                }
                    }
                ])
                ->simplePaginate(10, [
                    'id', 'lotteryType', 'pictureTypeId', 'pictureName', 'color', 'width', 'height'
                ])->toArray();
//        }

            if (!$res['data']) {
                return $this->apiSuccess();
            }
            $list = $res['data'];
            $pidDetailIds = [];
            foreach ($list as $k => $v) {
                $list[$k]['is_thumbUp'] = false;
                $list[$k]['is_collect'] = false;
                if (!empty($v['pic_detail'])) {
                    $pidDetailIds[] = $v['pic_detail']['id'];
                }
                // 图片
                $list[$k]['largePictureUrl'] = $this->getPicUrl($v['color'], $v['pic_other']['max_issue'], $v['pic_other']['keyword'], $params['lotteryType'], 'jpg', $v['pic_other']['year'], true);
            }

//        dd($pidDetailIds);
            if ($userId && $pidDetailIds) {
                $thumbUpIds = DB::table('user_follows')
                    ->where('followable_type', 'Modules\\Api\\Models\\PicDetail')
                    ->where('user_id', $userId)
                    ->whereIn('followable_id', $pidDetailIds)
                    ->pluck('followable_id')->toArray();
//            dd($thumbUpIds);

                $collectIds = DB::table('user_collects')
                    ->where('collectable_type', 'Modules\\Api\\Models\\PicDetail')
                    ->where('user_id', $userId)
                    ->whereIn('collectable_id', $pidDetailIds)
                    ->pluck('collectable_id')->toArray();
//            dd($collectIds);
                foreach ($list as $k => $v) {
                    if (isset($v['pic_detail']['id']) && in_array($v['pic_detail']['id'], $thumbUpIds)) {
                        $list[$k]['is_thumbUp'] = true;
                    }
                    if (isset($v['pic_detail']['id']) && in_array($v['pic_detail']['id'], $collectIds)) {
                        $list[$k]['is_collect'] = true;
                    }

                }
            }

            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list);
        } catch (\Exception $exception) {
            dd($exception->getMessage(), $exception->getLine(), $exception->getFile());
        }
    }
}
