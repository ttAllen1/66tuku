<?php
/**
 * @Name 用户参与图片竞猜
 * @Description
 */

namespace Modules\Api\Services\user;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\PicForecast;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\CustomException;

class UserPicForecastService extends BaseApiService
{

    /**
     * 判断用户是否已经对该期图片进行竞猜
     * @param $user_id
     * @param $voteable_id
     * @return bool
     */
    public function hasUserJoinForecast($pic_detail_id,  $forecastTypeId): bool
    {
        $user_id = auth('user')->id();
        $isExist = DB::table('pic_forecasts')
            ->where('user_id', $user_id)
            ->where('pic_detail_id', $pic_detail_id)
            ->where('forecastTypeId', $forecastTypeId)
            ->value('id');

        return (bool)$isExist;
    }

    /**
     * 创建用户竞猜数据
     * @param $params
     * @return bool
     * @throws CustomException
     */
    public function create($params): bool
    {
        $picDetail = PicDetail::query()->select(['year', 'issue', 'pictureName'])->findOrFail($params['pic_detail_id']);
        $arr = [];
        foreach ($params['content'] as $k => $v) {
            $arr[$k]['success'] = -1; // -1 未开奖 0未中奖 1中奖
            $arr[$k]['value'] = $v;
        }
        $model = PicForecast::query()->create([
            'user_id'           => auth('user')->id(),
            'year'              => $params['year'],
            'lotteryType'       => $params['lotteryType'],
            'pic_detail_id'     => $params['pic_detail_id'],
            'issue'             => $params['issue'],
            'forecastTypeId'    => $params['forecast_type_id'],
            'position'          => $params['position'],
            'title'             => '第'.$picDetail['year'].str_pad($picDetail['issue'], 3, 0, STR_PAD_LEFT).'期'.$picDetail['pictureName'].'竞猜',
            'body'              => $params['body'],
            'content'           => $arr,
            'status'            => $this->getCheckStatus(7) == 1 ? 0 : 1
        ]);

        return (bool)$model;
    }

    /**
     * 根据图片详情id查出该图片下的竞猜信息
     * @param $pic_detail_id
     * @return Paginator
     */
    public function getPicForecastByPicId($pic_detail_id): Paginator
    {
        $picForecast = PicForecast::query()
            ->select(['id', 'user_id', 'title', 'body', 'content', 'created_at'])
            ->where('pic_detail_id', $pic_detail_id)
            ->where('status', 1)
            ->removeBlack()
            ->with(['user'=>function($query) {
                $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
            }])
            ->simplePaginate();

        return $picForecast;
    }

    /**
     * 竞猜详情
     * @param $user_forecast_id
     * @return array|Builder|Builder[]|Collection|Model
     * @throws CustomException
     */
    public function detail($user_forecast_id)
    {
        try {
            $picForecast = PicForecast::query()
                ->where('status', 1)
                ->with(['picture'=>function($query) {
                    $query->select('id', 'lotteryType', 'pictureTypeId', 'issue', 'keyword', 'year', 'color');
                }])
                ->findOrFail($user_forecast_id);
            $picForecast['largePictureUrl'] = $this->getPicUrl($picForecast['picture']['color'], $picForecast['picture']['issue'], $picForecast['picture']['keyword'], $picForecast['picture']['lotteryType'], 'jpg', $picForecast['picture']['year'], true);
            $picForecast['largePictureUrlOther'] = '';
            if ($picForecast['picture']['lotteryType'] == 5) {
//                if ($picForecast['picture']['pictureTypeId'] != "00403") {
//                    $picForecast['largePictureUrlOther'] = str_replace('https://am.tuku.fit', $this->_replace_6c_img_url, $picForecast['largePictureUrl']);
//                } else {
//                    $picForecast['largePictureUrlOther'] = $picForecast['largePictureUrl'];
//                }
                $picForecast['largePictureUrlOther'] = $picForecast['largePictureUrl'];
            }
            $picForecast['follow'] = false;
            $userId = auth('user')->id();
            if ($userId) {
                $picForecast['follow']  = (bool)$picForecast->follow()->where('user_id', $userId)->value('id');
            }
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'竞猜不存在或被删除']);
        }
        if ($picForecast['views']==0) {
            $picForecast->increment('views', $this->getFirstViews());
        } else {
            $picForecast->increment('views', $this->getSecondViews());
        }

        return $picForecast;
    }
}
