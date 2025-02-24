<?php

namespace Modules\Api\Services\lottery;

use Illuminate\Support\Facades\Log;
use Modules\Api\Services\BaseApiService;

class LotteryService extends BaseApiService
{
    private $historyNumber;
    private $content = [];
    public function base($historyNumber, $picForecast)
    {
        $this->historyNumber = $historyNumber;
        $forecastTypeId = $picForecast->forecastTypeId;
        $position = $picForecast->position;
        $this->content = $picForecast->content;
        $map = [
            1 => [
                ['texiao'],
                ['main']
            ],
            11 => [
                ['tema'],
                ['main']
            ],
            48 => [
                ['danshuang'],
                ['zheng1', 'zheng2', 'zheng3', 'zheng4', 'zheng5', 'zheng6', 'tema']
            ],
            49 => [
                ['pingtexiao'],
                ['main']
            ],
            50 => [
                ['daxiao'],
                ['zheng1', 'zheng2', 'zheng3', 'zheng4', 'zheng5', 'zheng6', 'tema']
            ],
            51 => [
                ['bose'],
                ['zheng1', 'zheng2', 'zheng3', 'zheng4', 'zheng5', 'zheng6', 'tema']
            ],
            54 => [
                ['lianxiao'],
                ['main']
            ],
            59 => [
                ['shatexiao'],
                ['main']
            ],
            69 => [
                ['yiwei'],
                ['main']
            ],
            70 => [
                ['shaxiao'],
                ['main']
            ],
            74 => [
                ['shaweite'],
                ['main']
            ],
            78 => [
                ['shawei'],
                ['main']
            ],
            82 => [
                ['buzhong'],
                ['main']
            ],
            87 => [
                ['tiandishengxiao'],
                ['main']
            ],
            88 => [
                ['jiaqinyeshou'],
                ['main']
            ],
            89 => [
                ['zuoyoushengxiao'],
                ['main']
            ],
            90 => [
                ['shayitou'],
                ['main']
            ],
            91 => [
                ['wenjinyiduan'],
                ['main']
            ],
            92 => [
                ['heshudanshuang'],
                ['main']
            ],
            93 => [
                ['shayihe'],
                ['main']
            ],
            94 => [
                ['sanxingzhongte'],
                ['main']
            ]
        ];
        if (empty($map[$forecastTypeId][0][0]) || empty($map[$forecastTypeId][1][$position-1]))
        {
            return false;
        }
        $controller = $map[$forecastTypeId][0][0] . ucfirst($map[$forecastTypeId][1][$position-1]);
        if (method_exists($this, $controller))
        {
            return $this->$controller();
        } else {
            Log::channel('_real_open_err')->error('图片竞猜是否中奖出错', ['error'=>"Controller $controller Not Found!"]);

            return false;
        }
    }

    /**
     * 获取生肖[公共]
     * @param $postition
     * @return mixed|string
     */
    private function shengxiao($postition)
    {
        $shengxiao = explode(' ', $this->historyNumber->attr_sx);
        if ($postition == 'all')
        {
            return $shengxiao;
        }
        return $shengxiao[$postition-1];
    }

    /**
     * 获取波色[公共]
     * @param $postition
     * @return string
     */
    private function bose($postition)
    {
        $boseNumber = explode(' ', $this->historyNumber->attr_bs);
        $initialValue = ['红', '蓝', '绿'];
        $bose = [];
        foreach ($boseNumber as $value)
        {
            $bose[] = $initialValue[$value-1];
        }
        return $bose[$postition-1];
    }

    /**
     * 获取五行[公共]
     * @param $postition
     * @return mixed|string
     */
    private function wuxing($postition){
        return explode(' ', $this->historyNumber->attr_wx)[$postition-1];
    }

    /**
     * 获取号码[公共]
     * @param $postition
     * @return mixed|string
     */
    private function number($postition)
    {
        $number = explode(' ', $this->historyNumber->number);
        if ($postition == 'all')
        {
            return $number;
        }
        return $number[$postition-1];
    }

    /**
     * 获取尾号[公共]
     * @param $postition
     * @return mixed|string
     */
    private function tail($postition)
    {
        $tail = $this->number('all');
        foreach ($tail as &$item)
        {
            $item = substr($item, 1, 1);
        }
        if ($postition == 'all')
        {
            return $tail;
        }
        return $tail[$postition-1];
    }

    /**
     * 单双[公共]
     * @param $postition
     * @return string
     */
    private function danshuang($postition)
    {
        $number = $this->number($postition);
        if ($number % 2 == 1)
        {
            return '单';
        } else {
            return '双';
        }
    }

    /**
     * 大小[公共]
     * @param $postition
     * @return string
     */
    private function daxiao($postition)
    {
        $number = $this->number($postition);
        if ($number > 24)
        {
            return '大';
        } else {
            return '小';
        }
    }

    /**
     * 数据比对[公共]
     * @param $value
     * @return array
     */
    private function than($value)
    {
        foreach ($this->content as &$item)
        {
            if ($item['value'] == $value)
            {
                $item['success'] = 1;
            } else {
                $item['success'] = 0;
            }
        }
        return $this->content;
    }

    /**
     * 数据处理[公共]
     * @param $success
     * @return array
     */
    private function dataProcess($success)
    {
        foreach ($this->content as &$me)
        {
            $me['success'] = $success;
        }
        return $this->content;
    }

    /**
     * 特肖
     * @return array
     */
    private function texiaoMain()
    {
        return $this->than($this->shengxiao(7));
    }

    /**
     * 特码
     * @return array
     */
    private function temaMain()
    {
        return $this->than($this->number(7));
    }

    /**
     * 单双正一
     * @return array
     */
    private function danshuangZheng1()
    {
        return $this->than($this->danshuang(1));
    }

    /**
     * 单双正二
     * @return array
     */
    private function danshuangZheng2()
    {
        return $this->than($this->danshuang(2));
    }

    /**
     * 单双正三
     * @return array
     */
    private function danshuangZheng3()
    {
        return $this->than($this->danshuang(3));
    }

    /**
     * 单双正四
     * @return array
     */
    private function danshuangZheng4()
    {
        return $this->than($this->danshuang(4));
    }

    /**
     * 单双正五
     * @return array
     */
    private function danshuangZheng5()
    {
        return $this->than($this->danshuang(5));
    }

    /**
     * 单双正六
     * @return array
     */
    private function danshuangZheng6()
    {
        return $this->than($this->danshuang(6));
    }

    /**
     * 单双特码
     * @return array
     */
    private function danshuangTema()
    {
        return $this->than($this->danshuang(7));
    }

    /**
     * 平特肖
     * @return array
     */
    private function pingtexiaoMain()
    {
        foreach ($this->content as &$item)
        {
            if (in_array($item['value'], $this->shengxiao('all')))
            {
                $item['success'] = 1;
            } else {
                $item['success'] = 0;
            }
        }
        return $this->content;
    }

    /**
     * 大小正一
     * @return array
     */
    private function daxiaoZheng1()
    {
        return $this->than($this->daxiao(1));
    }

    /**
     * 大小正二
     * @return array
     */
    private function daxiaoZheng2()
    {
        return $this->than($this->daxiao(2));
    }

    /**
     * 大小正三
     * @return array
     */
    private function daxiaoZheng3()
    {
        return $this->than($this->daxiao(3));
    }

    /**
     * 大小正四
     * @return array
     */
    private function daxiaoZheng4()
    {
        return $this->than($this->daxiao(4));
    }

    /**
     * 大小正五
     * @return array
     */
    private function daxiaoZheng5()
    {
        return $this->than($this->daxiao(5));
    }

    /**
     * 大小正六
     * @return array
     */
    private function daxiaoZheng6()
    {
        return $this->than($this->daxiao(6));
    }

    /**
     * 大小特码
     * @return array
     */
    private function daxiaoTema()
    {
        return $this->than($this->daxiao(7));
    }

    /**
     * 波色正一
     * @return array
     */
    private function boseZheng1()
    {
        return $this->than($this->bose(1));
    }

    /**
     * 波色正二
     * @return array
     */
    private function boseZheng2()
    {
        return $this->than($this->bose(2));
    }

    /**
     * 波色正三
     * @return array
     */
    private function boseZheng3()
    {
        return $this->than($this->bose(3));
    }

    /**
     * 波色正四
     * @return array
     */
    private function boseZheng4()
    {
        return $this->than($this->bose(4));
    }

    /**
     * 波色正五
     * @return array
     */
    private function boseZheng5()
    {
        return $this->than($this->bose(5));
    }

    /**
     * 波色正六
     * @return array
     */
    private function boseZheng6()
    {
        return $this->than($this->bose(6));
    }

    /**
     * 波色特码
     * @return array
     */
    private function boseTema()
    {
        return $this->than($this->bose(7));
    }

    /**
     * 连肖
     * @return array
     */
    private function lianxiaoMain()
    {
        $shengxiao = $this->shengxiao('all');
        $count = count($this->content);
        $arrangement = [];
        for ($i = 0; $i <= 7 - $count; $i++)
        {
            $tempArr = [$shengxiao[$i]];
            for ($j = $i + 1; $j < $i + $count; $j++)
            {
                $tempArr[] = $shengxiao[$j];
            }
            $arrangement[] = implode(',', $tempArr);
        }
        $selectsx = [];
        foreach ($this->content as $item)
        {
            $selectsx[] = $item['value'];
        }
        if (in_array(implode(',', $selectsx), $arrangement))
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀特肖
     * @return array
     */
    private function shatexiaoMain()
    {
        $tempArr = [];
        foreach ($this->content as $item)
        {
            $tempArr[] = $item['value'];
        }
        if (in_array($this->shengxiao(7), $tempArr))
        {
            $success = 0;
        } else {
            $success = 1;
        }
        return $this->dataProcess($success);
    }

    /**
     * 一尾
     * @return array
     */
    private function yiweiMain()
    {
        if (in_array(substr($this->content[0]['value'], 0, 1), $this->tail('all')))
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀肖
     * @return array
     */
    private function shaxiaoMain()
    {
        $shengxiao = $this->shengxiao('all');
        array_pop($shengxiao);
        $success = 1;
        foreach ($this->content as $select)
        {
            if (in_array($select['value'], $shengxiao))
            {
                $success = 0;
                break;
            }
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀特尾
     * @return array
     */
    private function shaweiteMain()
    {
        $tempArr = [];
        foreach ($this->content as $select) {
            $tempArr[] = substr($select['value'], 0, 1);
        }
        if (in_array($this->tail(7), $tempArr))
        {
            $success = 0;
        } else {
            $success = 1;
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀尾
     * @return array
     */
    private function shaweiMain()
    {
        $tail = $this->tail('all');
        array_pop($tail);
        $success = 1;
        foreach ($this->content as $select)
        {
            if (in_array(substr($select['value'], 0, 1), $tail))
            {
                $success = 0;
                break;
            }
        }
        return $this->dataProcess($success);
    }

    /**
     * 不中
     * @return array
     */
    private function buzhongMain()
    {
        $number = $this->number('all');
        $success = 1;
        foreach ($this->content as $select)
        {
            if (in_array($select['value'], $number))
            {
                $success = 0;
                break;
            }
        }
        return $this->dataProcess($success);
    }

    /**
     * 天地生肖
     * @return array
     */
    private function tiandishengxiaoMain()
    {
        $select = $this->content[0]['value'];
        $sxArr = [
            ['兔', '马', '猴', '猪', '牛', '龙'],
            ['蛇', '羊', '鸡', '狗', '鼠', '虎']
        ];
        if ($select == '天肖')
        {
            $tempArr = $sxArr[0];
        } else {
            $tempArr = $sxArr[1];
        }
        if (in_array($this->shengxiao(7), $tempArr))
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 家禽野兽
     * @return array
     */
    private function jiaqinyeshouMain()
    {
        $select = $this->content[0]['value'];
        $sxArr = [
            ['羊', '马', '牛', '猪', '狗', '鸡'],
            ['鼠', '虎', '兔', '龙', '蛇', '猴']
        ];
        if ($select == '家禽')
        {
            $tempArr = $sxArr[0];
        } else {
            $tempArr = $sxArr[1];
        }
        if (in_array($this->shengxiao(7), $tempArr))
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 左右生肖
     * @return array
     */
    private function zuoyoushengxiaoMain()
    {
        $select = $this->content[0]['value'];
        $sxArr = [
            ['鼠', '牛', '龙', '蛇', '猴', '鸡'],
            ['虎', '兔', '马', '羊', '狗', '猪']
        ];
        if ($select == '左肖')
        {
            $tempArr = $sxArr[0];
        } else {
            $tempArr = $sxArr[1];
        }
        if (in_array($this->shengxiao(7), $tempArr))
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀一头
     * @return array
     */
    private function shayitouMain()
    {
        $number = $this->number(7);
        $firstNumber = substr($number, 0, 1);
        if ($firstNumber == substr($this->content[0]['value'], 0, 1))
        {
            $success = 0;
        } else {
            $success = 1;
        }
        return $this->dataProcess($success);
    }

    /**
     * 稳禁一段
     * @return array
     */
    private function wenjinyiduanMain()
    {
        $tempArr = [];
        $key = 0;
        for ($i = 1; $i < 50; $i++)
        {
            $tempArr[$key][] = ($i < 10) ? '0'.$i : $i;
            if ($i % 7 == 0)
            {
                $key++;
            }
        }
        $cursor = substr($this->content[0]['value'], 0, 1);
        if (in_array($this->number(7), $tempArr[$cursor-1]))
        {
            $success = 0;
        } else {
            $success = 1;
        }
        return $this->dataProcess($success);
    }

    /**
     * 合数单双
     * @return array
     */
    private function heshudanshuangMain()
    {
        $tema = $this->number(7);
        if (array_sum(str_split($tema)) % 2 == 1)
        {
            $temp = '合单';
        } else {
            $temp = '合双';
        }
        if ($this->content[0]['value'] == $temp)
        {
            $success = 1;
        } else {
            $success = 0;
        }
        return $this->dataProcess($success);
    }

    /**
     * 杀一合
     * @return array
     */
    private function shayiheMain()
    {
        $tema = $this->number(7);
        $sum = array_sum(str_split($tema));
        if ($sum == intval($this->content[0]['value']))
        {
            $success = 0;
        } else {
            $success = 1;
        }
        return $this->dataProcess($success);
    }

    /**
     * 三行中特
     * @return array
     */
    private function sanxingzhongteMain()
    {
        return $this->than($this->wuxing(7));

    }

}
