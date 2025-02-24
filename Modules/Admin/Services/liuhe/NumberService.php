<?php
namespace Modules\Admin\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\LiuheNumber;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;

class NumberService extends BaseApiService
{
    public $first_year = 2020;
    public $first_attr = '鼠';
    public $chinese_zodiac = [
        '鼠','牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪',
    ];
    public $zodiac_numbers = [];
    public function init_zodiac_data_by_year($year = null)
    {
        $current_year = $year ?? date('Y');
        if ($current_year-$this->first_year<0) {
            exit('不支持');
        }
        $year_diff = $current_year - $this->first_year;
        if ($current_year-$this->first_year<=11) {
            $current_attr = $this->chinese_zodiac[$year_diff];
        } else {
            $current_attr = $this->chinese_zodiac[$year_diff % 12];
        }
        // 找到今年生肖对应的索引
        $index = array_search($current_attr, $this->chinese_zodiac);
        // 重排数组
        $first_arr = [];
        $second_arr = [];
        foreach ($this->chinese_zodiac as $k => $v) {
            if ($k < $index) {
                $first_arr[] = $v;
            } else if ($k > $index) {
                $second_arr[] = $v;
            }
        }
        $sort_current_year_zodiac_arr = array_merge(array_reverse($first_arr), array_reverse($second_arr));
        array_unshift($sort_current_year_zodiac_arr, $current_attr);
        // 将49个数字分配给十二生肖
        $num = 0;

        for ($i=1; $i<=49; $i++) {
            $this->zodiac_numbers[$num]['attr'] = $sort_current_year_zodiac_arr[$num];
            $this->zodiac_numbers[$num]['numbers'][] = $i;
            $num++;
            if ($num>11) {
                $num = $num % 12;
            }
        }
        // 以数字为导向
        $arr = [];
        foreach ($this->zodiac_numbers as $k => $v) {
            foreach ($v['numbers'] as $kk => $vv) {
                $arr[$vv]['number'] = $vv > 10 ? $vv : '0'.$vv;
                $arr[$vv]['zodiac'] = $v['attr'];
                $arr[$vv]['year'] = $current_year;
                if ($current_year==2020) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['06', '07', '20', '21', '28', '29', '36', '37']) ? '金' : (in_array($arr[$vv]['number'], ['02', '03', '10', '11', '18', '19', '32', '33', '40', '41', '48', '49']) ? '木' : (in_array($arr[$vv]['number'], ['08', '09', '16', '17', '24', '25', '38', '39', '46', '47']) ? '水' : (in_array($arr[$vv]['number'], ['04', '05', '12', '13', '26', '27', '34', '35', '42', '43']) ? '火' : (in_array($arr[$vv]['number'], ['01', '14', '15', '22', '23', '30', '31', '44', '45']) ? '土' : '-'))));
                } else if ($current_year==2021) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['07', '08', '21', '22', '29', '30', '37', '38']) ? '金' : (in_array($arr[$vv]['number'], ['03', '04', '11', '12', '19', '20', '33', '34', '41', '42', '49']) ? '木' : (in_array($arr[$vv]['number'], ['09', '10', '17', '18', '25', '26', '39', '40', '47', '48']) ? '水' : (in_array($arr[$vv]['number'], ['05', '06', '13', '14', '27', '28', '35', '36', '43', '44']) ? '火' : (in_array($arr[$vv]['number'], ['01', '02', '15', '16', '23', '24', '31', '32 ', '45', '46']) ? '土' : '-'))));
                } else if ($current_year==2022) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['01', '08', '09', '22', '23', '30', '31', '38', '39']) ? '金' : (in_array($arr[$vv]['number'], ['04', '05', '12', '13', '20', '21', '34', '35', '42', '43']) ? '木' : (in_array($arr[$vv]['number'], ['10', '11', '18', '19', '26', '27', '40', '41', '48', '49']) ? '水' : (in_array($arr[$vv]['number'], ['06', '07', '14', '15', '28', '29', '36', '37', '44', '45']) ? '火' : (in_array($arr[$vv]['number'], ['02', '03', '16', '17', '24', '25', '32', '33', '46', '47']) ? '土' : '-'))));
                } else if ($current_year==2023) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['01', '02', '09', '10', '23', '24', '31', '32', '39', '40']) ? '金' : (in_array($arr[$vv]['number'], ['05', '06', '13', '14', '21', '22', '35', '36', '43', '44']) ? '木' : (in_array($arr[$vv]['number'], ['11', '12', '19', '20', '27', '28', '41', '42', '49']) ? '水' : (in_array($arr[$vv]['number'], ['07', '08', '15', '16', '29', '30', '37', '38', '45', '46']) ? '火' : (in_array($arr[$vv]['number'], ['03', '04', '17', '18', '25', '26', '33', '34', '47', '48']) ? '土' : '-'))));
                } else if ($current_year==2024) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['02', '03', '10', '11', '24', '25', '32', '33', '40', '41']) ? '金' : (in_array($arr[$vv]['number'], ['06', '07', '14', '15', '22', '23', '36', '37', '44', '45']) ? '木' : (in_array($arr[$vv]['number'], ['12', '13', '20', '21', '28', '29', '42', '43']) ? '水' : (in_array($arr[$vv]['number'], ['01', '08', '09', '16', '17', '30', '31', '38', '39', '46', '47']) ? '火' : (in_array($arr[$vv]['number'], ['04', '05', '18', '19', '26', '27', '34', '35', '48', '49']) ? '土' : '-'))));
                } else if ($current_year==2025) {
                    $arr[$vv]['five_elements'] = in_array($arr[$vv]['number'], ['03', '04', '11', '12', '25', '26', '33', '34', '41', '42']) ? '金' : (in_array($arr[$vv]['number'], ['07', '08', '15', '16', '23', '24', '37', '38', '45', '46']) ? '木' : (in_array($arr[$vv]['number'], ['13', '14', '21', '22', '29', '30', '43', '44']) ? '水' : (in_array($arr[$vv]['number'], ['01', '02', '09', '10', '17', '18', '31', '32', '39', '40', '47', '48']) ? '火' : (in_array($arr[$vv]['number'], ['05', '06', '19', '20', '27', '28', '35', '36', '49']) ? '土' : '-'))));
                }

                $arr[$vv]['bose'] = $this->get_bose_num($vv);
                $arr[$vv]['size'] = $vv >= 25 ? '大' : '小';
                $arr[$vv]['fowls_beast'] = in_array($v['attr'], ['牛', '马', '羊', '鸡', '狗', '猪']) ? '家禽' : '野兽';
                $arr[$vv]['mantissa'] = $vv < 10 ? $vv : ($vv % 10);
                $arr[$vv]['mantissa_size'] = $arr[$vv]['mantissa'] >= 25 ? '大' : '小';
                $arr[$vv]['beauty_ugly'] = in_array($v['attr'], ['兔', '龙', '蛇', '马', '羊', '鸡']) ? '吉美' : '凶丑';
                $arr[$vv]['sky_land'] = in_array($v['attr'], ['兔', '马', '猴', '猪', '牛', '龙']) ? '天肖' : '地肖';
                $arr[$vv]['four_arts'] = in_array($v['attr'], ['兔', '蛇', '鸡']) ? '琴' : (in_array($v['attr'], ['鼠', '牛', '狗']) ? '棋' : (in_array($v['attr'], ['虎', '龙', '马']) ? '书' : (in_array($v['attr'], ['羊', '猴', '猪']) ? '画' : '-')));
            }
        }
        sort($arr);
        return $arr;
    }


    /**
     * @name 号码列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
//        for ($i=2024; $i<=2024; $i++) {
//            $res = $this->init_zodiac_data_by_year($i);
//            dd($res);
//            LiuheNumber::query()->insert($res);
//        }
        $data['year'] = $data['year'] ?? date('Y');
        $data['year'] = date('Y', strtotime($data['year']));
        $list = LiuheNumber::query()
            ->where('year', $data['year'])
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        return $this->commonUpdate(LiuheNumber::query(),$id,$data);
    }

    /**
     * 生成新的一年数据
     *
     * @param $params
     * @return JsonResponse
     * @throws ApiException
     */
    public function create($params): JsonResponse
    {
        $res = $this->init_zodiac_data_by_year($params['year']);
        if ($params['year']) {
            LiuheNumber::query()->where('year', $params['year'])->delete();
            LiuheNumber::query()->insert($res);

            return $this->apiSuccess();
        }

        return $this->apiError('操作失败');
    }
}
