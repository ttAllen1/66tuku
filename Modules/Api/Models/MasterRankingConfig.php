<?php /** @noinspection SpellCheckingInspection */

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRankingConfig extends BaseApiModel
{
    public const MARK_SANXIAO = 'sanxiao';
    public const MARK_LIUXIAO = 'liuxiao';
    public const MARK_JIUXIAO = 'jiuxiao';
    public const MARK_WUMA = 'wuma';
    public const MARK_SHIMA = 'shima';
    public const MARK_ERSHIMA = 'ershima';
    public const MARK_SANSHIWUMA = 'sanshiwuma';
    public const MARK_PINGTEYIXIAO = 'pingteyixiao';
    public const MARK_PINGTEYIWEI = 'pingteyiwei';
    public const MARK_WUBUZHONG = 'wubuzhong';
    public const MARK_QIBUZHONG = 'qibuzhong';
    public const MARK_SANZHONGYI = 'sanzhongyi';
    public const MARK_ERLIANXIAO = 'erlianxiao';
    public const MARK_ERLIANWEI = 'erlianwei';
    public const MARK_DANSHUANG = 'danshuang';
    public const MARK_DAXIAO = 'daxiao';
    public const MARK_JIAYE = 'jiaye';
    public const MARK_SHUANGBO = 'shuangbo';
    public const MARK_HEDANSHUANG = 'hedanshuang';
    public const MARK_SANTOU = 'santou';
    public const MARK_LIUWEI = 'liuwei';
    public const MARK_SANXING = 'sanxing';
    public const MARK_QIWEI = 'qiwei';
    public const MARK_SHAYIXIAO = 'shayixiao';
    public const MARK_SHAWUMA = 'shawuma';
    public const MARK_SHAYITOU = 'shayitou';
    public const MARK_SHAYIWEI = 'shayiwei';
    public const MARK_SHAYIDUAN = 'shayiduan';
    public const MARK_SHAYIXING = 'shayixing';
    public const MARK_SHAERWEI = 'shaerwei';
    public const MARK_SHAERXIAO = 'shaerxiao';
    public const MARK_SHABANBO = 'shabanbo';

    public const ZODIAC_ARR = [self::MARK_SANXIAO, self::MARK_LIUXIAO, self::MARK_JIUXIAO, self::MARK_PINGTEYIXIAO, self::MARK_ERLIANXIAO, self::MARK_SHAYIXIAO, self::MARK_SHAERXIAO];
    public const NUMBER_ARR = [self::MARK_WUMA, self::MARK_SHIMA, self::MARK_ERSHIMA, self::MARK_SANSHIWUMA, self::MARK_WUBUZHONG, self::MARK_QIBUZHONG, self::MARK_SANZHONGYI, self::MARK_SHAWUMA];
    public const WEI_ARR = [self::MARK_PINGTEYIWEI, self::MARK_ERLIANWEI, self::MARK_LIUWEI, self::MARK_QIWEI, self::MARK_SHAYIWEI, self::MARK_SHAERWEI];
    public const TOU_ARR = [self::MARK_SANTOU, self::MARK_SHAYITOU];
    public const WUXING_ARR = [self::MARK_SANXING, self::MARK_SHAYIXING];
    public const ZODIAC = [
        '鼠',
        '牛',
        '虎',
        '兔',
        '龙',
        '蛇',
        '马',
        '羊',
        '猴',
        '鸡',
        '狗',
        '猪'
    ];

    public const WUXING = [
        '金',
        '木',
        '水',
        '火',
        '土'
    ];

    public const BOSE = [
        '红波',
        '蓝波',
        '绿波',
    ];

    public const DUAN = [
        '1段',
        '2段',
        '3段',
        '4段',
        '5段',
        '6段',
        '7段',
    ];

    public const BANBO = [
        '红单',
        '红双',
        '蓝单',
        '蓝双',
        '绿单',
        '绿双',
    ];

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }

    public static function checkInputData(string $mark, string $date): bool
    {
        if ( in_array($mark, self::ZODIAC_ARR)) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return in_array($word, self::ZODIAC);
            });

            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if (in_array($mark, self::NUMBER_ARR)) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return is_numeric($word) && $word >= 1 && $word <= 49;
            });

            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if (in_array($mark, self::WEI_ARR)) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return in_array($word, [0,1,2,3,4,5,6,7,8,9]);
            });

            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if (in_array($mark, self::TOU_ARR)) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return in_array($word, [0,1,2,3,4]);
            });

            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if (in_array($mark, self::WUXING_ARR)) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return in_array($word, self::WUXING);
            });

            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if ($mark == self::MARK_DANSHUANG) {
            return in_array($date, ['单数', '双数']);
        } else if ($mark == self::MARK_DAXIAO) {
            return in_array($date, ['大数', '小数']);
        } else if ($mark == self::MARK_JIAYE) {
            return in_array($date, ['家禽', '野兽']);
        } else if ($mark == self::MARK_SHUANGBO) {
            $words = collect(explode('-', $date));
            $allValid = $words->every(function ($word) {
                return in_array($word, self::BOSE);
            });
            if ($allValid) {
                return true;
            } else {
                return false;
            }
        } else if ($mark == self::MARK_HEDANSHUANG) {
            return in_array($date, ['合单', '合双']);
        } else if ($mark == self::MARK_SHAYIDUAN) {
            // 1,2,3,4,5,6,7
            // 8,9,10,11,12,13,14
            // 15,16,17,18,19,20,21
            // 22,23,24,25,26,27,28
            // 29,30,31,32,33,34,35
            // 36,37,38,39,40,41,42
            // 43,44,45,46,47,48,49
            return in_array($date, self::DUAN);
        } else if ($mark == self::MARK_SHABANBO) {
            return in_array($date, self::BANBO);
        }
        return false;
    }

}
