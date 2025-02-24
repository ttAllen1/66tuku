<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Api\Events\PicDetailCreatedEvent;

class Vote extends BaseApiModel
{
    protected $casts = [
        'sx_shu'    =>  'array',
        'sx_niu'    =>  'array',
        'sx_hu'     =>  'array',
        'sx_tu'     =>  'array',
        'sx_long'   =>  'array',
        'sx_she'    =>  'array',
        'sx_ma'     =>  'array',
        'sx_yang'   =>  'array',
        'sx_hou'    =>  'array',
        'sx_ji'     =>  'array',
        'sx_gou'    =>  'array',
        'sx_zhu'    =>  'array',
    ];

    public static $_sx = [
        1 => 'sx_shu',
        2 => 'sx_niu',
        3 => 'sx_hu',
        4 => 'sx_tu',
        5 => 'sx_long',
        6 => 'sx_she',
        7 => 'sx_ma',
        8 => 'sx_yang',
        9 => 'sx_hou',
        10 => 'sx_ji',
        11 => 'sx_gou',
        12 => 'sx_zhu',
    ];

    public static $_vote_zodiac = [
        '鼠' => 'sx_shu',
        '牛' => 'sx_niu',
        '虎' => 'sx_hu',
        '兔' => 'sx_tu',
        '龙' => 'sx_long',
        '蛇' => 'sx_she',
        '马' => 'sx_ma',
        '羊' => 'sx_yang',
        '猴' => 'sx_hou',
        '鸡' => 'sx_ji',
        '狗' => 'sx_gou',
        '猪' => 'sx_zhu',
    ];

    public static $_sx_init_obj = [
        'sx_shu' => [
            'sx'            => 'sx_shu',
            'name'          => '鼠',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_niu' => [
            'sx'            => 'sx_niu',
            'name'          => '牛',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_hu' => [
            'sx'            => 'sx_hu',
            'name'          => '虎',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_tu' => [
            'sx'            => 'sx_tu',
            'name'          => '兔',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_long' => [
            'sx'            => 'sx_long',
            'name'          => '龙',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_she' => [
            'sx'            => 'sx_she',
            'name'          => '蛇',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_ma' => [
            'sx'            => 'sx_ma',
            'name'          => '马',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_yang' => [
            'sx'            => 'sx_yang',
            'name'          => '羊',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_hou' => [
            'sx'            => 'sx_hou',
            'name'          => '猴',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_ji' => [
            'sx'            => 'sx_ji',
            'name'          => '鸡',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_gou' => [
            'sx'            => 'sx_gou',
            'name'          => '狗',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'sx_zhu' => [
            'sx'            => 'sx_zhu',
            'name'          => '猪',
            'vote_num'      => 0,
            'percentage'    => 0,
        ],
        'total_num' => 0
    ];

    /**
     * 初始化投票初始数据
     * @param $vote_zodiac
     * @return array
     */
    public static function userVoteInitData($vote_zodiac): array
    {
        $initData = self::$_sx_init_obj;
        foreach ( $initData as $k => $v ) {
            if ($k == $vote_zodiac) {
                $initData[$k]['vote_num']   = 1;
                $initData[$k]['percentage'] = 100;
            }
        }
        $initData['total_num'] = 1;
        return $initData;
    }

    public static function userVoteData()
    {

    }

    /**
     * 获取拥有此投票的模型
     * @return MorphTo
     */
    public function voteable(): MorphTo
    {
        return $this->morphTo();
    }
}
