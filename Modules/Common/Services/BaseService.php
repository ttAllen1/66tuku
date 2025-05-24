<?php
/**
 * @Name  服务基类
 * @Description
 */

namespace Modules\Common\Services;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Gai871013\IpLocation\Facades\IpLocation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Models\Check;
use Modules\Admin\Models\ImgPrefix;
use Modules\Admin\Models\LiuheNumber;
use Modules\Admin\Models\User;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CodeData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use OSS\Core\OssException;
use OSS\OssClient;
use Uni\Common\UniException;
use Uni\SMS\UniSMS;

class BaseService
{
    public const PICMODEL = 1;
    public const PICDIAGRAMMODEL = 2;
    public const FORECAST = 3;
    public const CORPUSARTICES = 4;
    public const COMMENTMODEL = 5;
    public const DISCOVERYMODEL = 6;
    public const DISCUSSMODEL = 9;
    public const HUMOROUSMODEL = 11;
    public const AIMODEL = 12;
    public const MASTERMODEL = 13;
    static $_convertToChinese = [
        1 => '一',
        2 => '二',
        3 => '三',
        4 => '四',
        5 => '五',
        6 => '六',
    ];
    public $jiaqin = ["牛", "马", "羊", "鸡", "狗", "猪"];
    public $yeshou = ["鼠", "虎", "兔", "龙", "蛇", "猴"];
    public $tian = ["兔", "马", "猴", "猪", "牛", "龙"];
    public $di = ["鼠", "虎", "蛇", "羊", "鸡", "狗"];
    public $qian = ["鼠", "牛", "虎", "兔", "龙", "蛇"];
    public $hou = ["马", "羊", "猴", "鸡", "狗", "猪"];
    public $_bose = [
        '红' => ['01', '02', '07', '08', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46'],
        '绿' => ['05', '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49'],
        '蓝' => ['03', '04', '09', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48']
    ];
    protected $_fund_password_salt = '123HGE+jf53ewe*93JdHNcs$12';
    protected $_replace_6c_img_url = 'https://baidu-imge.website:8848';
    protected $_max_small = 24;
    protected $_sum_max_small = 174;
    protected $_tail_max_small = 4;
    protected $_pic_base_url = [
        2023 => [
            1 => 'https://tk.fanghuwanglan.com:4949/',
            2 => 'https://tk2.fanghuwanglan.com:4949/',
            3 => 'https://tk3.fanghuwanglan.com:4949/',
            4 => 'https://tk5.fanghuwanglan.com:4949/',
            5 => 'https://am.tuku.fit/galleryfiles/system/big-pic/'
            //            https://baidu-imge.website:8848/galleryfiles/system/big-pic/col/2023/202/ampgt.jpg
        ],
        2022 => [
            1 => 'https://2018.syybp.com:2018/2022/',
            2 => 'https://tk2.fanghuwanglan.com:4949/2022/',
            3 => 'https://tk3.fanghuwanglan.com:4949/2022/',
            4 => 'https://tk5.fanghuwanglan.com:4949/2022/',
            5 => 'https://am.tuku.fit/galleryfiles/system/big-pic/'
        ],
        2021 => [
            1 => 'https://2018.syybp.com:2018/2021/',
            2 => 'https://tk2.fanghuwanglan.com:4949/2021/',
            3 => 'https://tk3.fanghuwanglan.com:4949/2021/',
            4 => 'https://tk5.fanghuwanglan.com:4949/2021/',
            5 => 'https://am.tuku.fit/galleryfiles/system/big-pic/'
        ],
        2020 => [
            1 => 'https://2018.syybp.com:2018/2020/',
            2 => 'https://tk2.fanghuwanglan.com:4949/2020/',
            3 => 'https://tk3.fanghuwanglan.com:4949/2020/',
            4 => 'https://tk5.fanghuwanglan.com:4949/2020/',
            5 => 'https://am.tuku.fit/galleryfiles/system/big-pic/'
        ]
    ];

    protected $_forward_num = 3;    // 每日转发次数
    protected $_follow_num = 10;
    protected $_comment_num = 5;

    protected $_grow = [
        "follow"      => [
            'type'      => 5,
            'score'     => 1,
            'max_times' => 100
        ],
        "create_post" => [
            'type'      => 3,
            'score'     => 10,
            'max_times' => 5
        ],
        "reply_post"  => [
            'type'      => 4,
            'score'     => 1,
            'max_times' => 100
        ]
    ];

    public function __construct()
    {
        $this->_fund_password_salt = env('FUND_PASSWORD_SALT');
    }

    /**
     * @name 查询条件
     * @description
     * @method  GET
     * @param model Model 模型
     * @param params Array 查询参数
     * @param key String 模糊查询参数
     * @return Object
     **/
    function queryCondition(object $model, array $params, string $key = "username"): object
    {
        if (isset($params['register_at']) && !empty($params['register_at'])) {
            $model = $model->whereBetween('register_at', $params['register_at']);
        }
        if (isset($params['last_login_at']) && !empty($params['last_login_at'])) {
            $model = $model->whereBetween('last_login_at', $params['last_login_at']);
        }
        if (!empty($params[$key])) {
            $model = $model->where($key, 'like', '%' . $params[$key] . '%');
        }
        if (isset($params['status']) && $params['status'] != '') {
            $model = $model->where('status', $params['status']);
        }
        return $model;
    }

    /**
     * @name 添加公共方法
     * @description
     * @param model Model  当前模型
     * @param data array 添加数据
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     **/
    public function commonCreate($model, array $data = [], string $successMessage = MessageData::ADD_API_SUCCESS, string $errorMessage = MessageData::ADD_API_ERROR)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['group_id']) && !empty($data['group_id']) && $model == User::query()) {
            try {
                DB::beginTransaction();
                $group_id = $data['group_id'];
                unset($data['group_id']);
                $model->create($data)->groups()->attach($group_id);
                DB::commit();
                return $this->apiSuccess($successMessage);
            } catch (\RuntimeException $exception) {
                DB::rollBack();
                Log::error("用户添加失败", ['message' => $exception->getMessage()]);
                return $this->apiError($errorMessage);
            }
        } else {
            if ($model->insert($data)) {
                return $this->apiSuccess($successMessage);
            }
        }

        return $this->apiError($errorMessage);
    }

    /**
     * @name  成功返回
     * @description  用于所有的接口返回
     * @param status Int 自定义状态码
     * @param message String 提示信息
     * @param JSON Array 返回信息
     * @return JsonResponse
     */
    public function apiSuccess(string $message = '', array $data = array(), int $status = StatusData::Ok)
    {
        if ($message == '') {
            $message = MessageData::Ok;
        }

//        $cipher_method = env('AES_METHOD');
        $cipher_method = config('config.aes_method');
        if (request()->header('Encipher') == 'enable' && !(Str::contains(request()->getHost(), '11.48tkapi.com'))) {
            $data = openssl_encrypt(json_encode($data),$cipher_method,config('config.aes_key'), 0, config('config.aes_iv'));
        }
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
//            'iv'=>$iv
        ], CodeData::OK);
    }

    /**
     * @param string $message
     * @param int $status
     * @return JsonResponse
     * @throws ApiException
     * @description 用于所有的接口返回
     */
    public function apiError(string $message = MessageData::API_ERROR_EXCEPTION, int $status = StatusData::BAD_REQUEST): JsonResponse
    {
        throw new ApiException([
            'status'  => $status,
            'message' => $message
        ]);
    }

    /**
     * @name 编辑公共方法
     * @description
     * @param model Model  当前模型
     * @param id   Int  修改id
     * @param data array 添加数据
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     **/
    public function commonUpdate($model, $id, array $data = [], string $successMessage = MessageData::UPDATE_API_SUCCESS, string $errorMessage = MessageData::UPDATE_API_ERROR)
    {
        try {
            if (!is_array($id)) {
                $id = [$id];
            }
            $data['updated_at'] = date('Y-m-d H:i:s');
            // 判断$data是否包含密码数据
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            if (isset($data['fund_password']) && !empty($data['fund_password'])) {
                $data['fund_password'] = bcrypt($this->_fund_password_salt . $data['fund_password']);
            }
            unset($data['created_at']);
            if ($model->whereIn('id', $id)->update($data)) {
                return $this->apiSuccess($successMessage);
            }
        } catch (\Exception $exception) {
//            dd($exception->getMessage());
            $this->apiError($errorMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * @param $model
     * @param $id
     * @param array $data
     * @param string $successMessage
     * @param string $errorMessage
     * @return JsonResponse
     * @throws ApiException
     * @description
     */
    public function commonStatusUpdate($model, $id, array $data = [], string $successMessage = MessageData::STATUS_API_SUCCESS, string $errorMessage = MessageData::STATUS_API_ERROR): JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        if ($model->whereIn('id', $id)->update($data)) {
            return $this->apiSuccess($successMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * @name 排序公共方法
     * @description
     * @param model Model  当前模型
     * @param id   Int  修改id
     * @param data array 添加数据
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     * @return JSON
     **/
    public function commonSortsUpdate($model, $id, array $data = [], string $successMessage = MessageData::STATUS_API_SUCCESS, string $errorMessage = MessageData::STATUS_API_ERROR)
    {
        if ($model->where('id', $id)->update($data) !== false) {
            return $this->apiSuccess($successMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * @name 真删除公共方法
     * @description
     * @param model Model  当前模型
     * @param ArrId Array  删除id
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     **/
    public function commonDestroy($model, array $ArrId, string $successMessage = MessageData::DELETE_API_SUCCESS, string $errorMessage = MessageData::DELETE_API_ERROR)
    {
        if ($model->whereIn('id', $ArrId)->delete()) {
            return $this->apiSuccess($successMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * @name 假删除公共方法
     * @description
     * @param model Model  当前模型
     * @param idArr Array  删除id
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     * @return JSON
     **/
    public function commonIsDelete($model, array $idArr, string $successMessage = MessageData::DELETE_API_SUCCESS, string $errorMessage = MessageData::DELETE_API_ERROR)
    {
        if ($model->whereIn('id', $idArr)->update(['is_delete' => 1, 'deleted_at' => date('Y-m-d H:i:s')])) {
            return $this->apiSuccess($successMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * @name 假删除恢复公共方法
     * @description
     * @param model Model  当前模型
     * @param idArr Array  删除id
     * @param successMessage string 成功返回数据
     * @param errorMessage string 失败返回数据
     * @return JSON
     **/
    public function commonRecycleIsDelete($model, array $idArr, string $successMessage = MessageData::DELETE_RECYCLE_API_SUCCESS, string $errorMessage = MessageData::DELETE_RECYCLE_API_ERROR)
    {
        if ($model->whereIn('id', $idArr)->update(['is_delete' => 0])) {
            return $this->apiSuccess($successMessage);
        }
        $this->apiError($errorMessage);
    }

    /**
     * 判断字符串是否base64编码
     */
    function func_is_base64($str)
    {
        return $str === base64_encode(base64_decode($str, true)) ? true : false;
    }

    public function getUserIdByName($model, $account_name = '', $field = 'account_name')
    {
        $list = $model->where('status', 1)->where($field, 'like', '%' . $account_name . '%')->select([
            $field, 'id'
        ])->get();

        return $this->apiSuccess('', [
            'list' => $list
        ]);
    }

    public function getUserIdByFullname($model, $account_name = '', $field = 'account_name')
    {
        $list = $model
            ->where('status', 1)
            ->where($field, 'like', '%' . $account_name . '%')
            ->orderBy(DB::raw("CASE WHEN " . $field . " = '" . $account_name . "' THEN 1 ELSE 2 END"))
            ->select([$field, 'nickname', 'id'])
            ->get();

        return $this->apiSuccess('', [
            'list' => $list
        ]);
    }

    /**
     * @name 将编辑器的content的图片转换为相对路径
     * @description
     * @param content String 内容
     * @return string
     **/
    public function getRemvePicUrl(string $content = ''): string
    {
        $con = $this->getHttp();
        if ($content) {
            //提取图片路径的src的正则表达式 并把结果存入$matches中
            preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/", $content, $matches);
            $img = "";
            if (!empty($matches)) {
                //注意，上面的正则表达式说明src的值是放在数组的第三个中
                $img = $matches[1];
            } else {
                $img = "";
            }
            if (!empty($img)) {
                $patterns = array();
                $replacements = array();
                //$default = config('filesystems.disks.qiniu.domains.default');
                foreach ($img as $imgItem) {
                    //if (strpos($imgItem, $default) !== false) {
                    //    $final_imgUrl = $imgItem;
                    // } else {
                    $final_imgUrl = str_replace($con, "", $imgItem);
                    //}
                    $replacements[] = $final_imgUrl;
                    $img_new = "/" . preg_replace("/\//i", "\/", $imgItem) . "/";
                    $patterns[] = $img_new;
                }
                //让数组按照key来排序
                ksort($patterns);
                ksort($replacements);
                //替换内容
                $content = preg_replace($patterns, $replacements, $content);
            }
        }
        return $content;
    }

    /**
     * @name 将编辑器的content的图片转换为绝对路径
     * @description
     * @param content string 内容
     * @return String
     **/
    public function getReplacePicUrl(string $content = ''): string
    {
        $con = $this->getHttp();
        if ($content) {
            //提取图片路径的src的正则表达式 并把结果存入$matches中
            preg_match_all("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/", $content, $matches);
            $img = "";
            if (!empty($matches)) {
                //注意，上面的正则表达式说明src的值是放在数组的第三个中
                $img = $matches[1];
            } else {
                $img = "";
            }
            if (!empty($img)) {
                $patterns = array();
                $replacements = array();
                //$default = config('filesystems.disks.qiniu.domains.default');
                foreach ($img as $imgItem) {
                    //if (strpos($imgItem, $default) !== false) {
                    //    $final_imgUrl = $imgItem;
                    //} else {
                    $final_imgUrl = $con . $imgItem;
                    //}
                    $replacements[] = $final_imgUrl;
                    $img_new = "/" . preg_replace("/\//i", "\/", $imgItem) . "/";
                    $patterns[] = $img_new;
                }
                //让数组按照key来排序
                ksort($patterns);
                ksort($replacements);
                //替换内容
                $content = preg_replace($patterns, $replacements, $content);
            }
        }
        return $content;
    }

    /**
     * @name 生成随机字符串
     * @description
     * @param length Int 生成字符串长度
     * @return String
     **/
    public function GetRandStr(int $length = 11): string
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * @name  处理二维数组转为json字符串乱码问题
     * @description
     * @param data Array  需要转为json字符串的数组
     * @return String
     **/
    public function setJsonEncodes($data): string
    {
        $count = count($data);
        for ($k = 0; $k < $count; $k++) {
            foreach ($data[$k] as $key => $value) {
                $data[$k][$key] = urlencode($value);
            }
        }
        return urldecode(json_encode($data));
    }

    /**
     * @name 传入时间戳,计算距离现在的时间
     * @description
     * @param theTime Int 时间戳
     * @return String
     **/
    public function format_time(int $theTime = 0): string
    {
        $nowTime = time();
        $dur = $nowTime - $theTime;
        if ($dur < 0) {
            return $theTime;
        } else {
            if ($dur < 60) {
                return $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {//昨天
                        //获取今天凌晨的时间戳
                        $day = strtotime(date('Y-m-d', time()));
                        //获取昨天凌晨的时间戳
                        $pday = strtotime(date('Y-m-d', strtotime('-1 day')));
                        if ($theTime > $pday && $theTime < $day) {//是否昨天
                            return $t = '昨天 ' . date('H:i', $theTime);
                        } else {
                            if ($dur < 172800) {
                                return floor($dur / 86400) . '天前';
                            } else {
                                return date('Y-m-d H:i', $theTime);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @name 处理递归数据
     * @description
     * @param array Array  总数据
     * @param pid Int  父级id
     * @return Array
     **/
    public function tree(array $array, int $pid = 0): array
    {
        $tree = array();
        foreach ($array as $key => $value) {
            if ($value['pid'] == $pid) {
                $value['children'] = $this->tree($array, $value['id']);
                if (!$value['children']) {
                    unset($value['children']);
                }
                $tree[] = $value;
            }
        }
        return $tree;
    }

    /**
     * @name 获取用户真实 ip
     * @description
     * @return array|false|mixed|string
     **/
    public function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }
        if (!$ip) {
            return '';
        }
        return $ip;
    }

    /**
     * @name PHP格式化字节大小
     * @description
     * @param size Int  字节数
     * @param delimiter string  数字和单位分隔符
     * @return String 格式化后的带单位的大小
     **/
    public function formatBytes(int $size, string $delimiter = ''): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }

    public function get_attr_num($numbers)
    {
//        $attrs = LiuheNumber::query()->where('year', date('Y'))->get()->toArray();
        // if (date('Y-m-d') == '2025-01-28' && date('H')>=22 && date('i')>=50) {
        //     $attrs = LiuheNumber::query()->where('year', 2025)->get()->toArray();
        // } else {
        //     $attrs = LiuheNumber::query()->where('year', 2024)->get()->toArray();
        // }
       $attrs = LiuheNumber::query()->where('year', 2025)->get()->toArray();
        if (!is_array($numbers)) {
            $numbersArr = explode(' ', $numbers);
        } else {
            $numbersArr = $numbers;
        }

        $sx = [];
        $wx = [];
        $bs = [];
        $number_attr = [];
        $te_attr = [];
        $total_attr = [];
        foreach ($numbersArr as $k => $v) {
            foreach ($attrs as $item) {
                if ($v == $item['number']) {
                    $sx[$k] = $item['zodiac'];
                    $wx[$k] = $item['five_elements'];
                    $bs[$k] = $this->get_bose_num($v, 2);
                    break;
                }
            }
        }
        foreach ($numbersArr as $k => $v) {
            $number_attr[$k]['color'] = $bs[$k];
            $number_attr[$k]['number'] = $v;
            $number_attr[$k]['shengXiao'] = $sx[$k];
            $number_attr[$k]['wuXing'] = $wx[$k];
            if ($k == 6) {
                $te_attr['color'] = $bs[$k];
                $te_attr['number'] = $v;
                $te_attr['shengXiao'] = $sx[$k];
                $te_attr['wuXing'] = $wx[$k];
                $spi_data = $this->spi_data(['number' => $v]);
                $te_attr['oddEven'] = $spi_data['oddEven'];
                $te_attr['bigSmall'] = $spi_data['bigSmall'];
            }
        }
        $total_attr = $this->total_data($numbersArr);

//        print_r($total_attr);
        return [
            'sx'         => $sx, 'wx' => $wx, 'bs' => $bs, 'number_attr' => $number_attr, 'te_attr' => $te_attr,
            'total_attr' => $total_attr
        ];
    }

    /**
     * @param $num
     * @param $type 1 返回红蓝绿   2 返回 123
     * @return int|string
     */
    public function get_bose_num($num, $type = 1)
    {
        $arr = [
            '红' => 1,
            '蓝' => 2,
            '绿' => 3,
        ];
        foreach (LiuheNumber::$_bose as $k => $v) {
            if (in_array($num, $v)) {
                if ($type == 1) {
                    return $k;
                } else {
                    return $arr[$k];
                }
            }
        }
    }

    public function spi_data($numData)
    {
        $numData['oddEven'] = $numData['number'] % 2 == 0 ? '双' : '单';
        $numData['bigSmall'] = $numData['number'] >= 25 ? '大' : '小';
        return $numData;
    }

    public function total_data($numData)
    {
        $data['total'] = array_sum($numData);
        $data['oddEven'] = $data['total'] % 2 == 0 ? '双' : '单';
        $data['bigSmall'] = $data['total'] >= 175 ? '大' : '小';
        return $data;
    }

    public function maps($data)
    {
        $numbers = explode(' ', $data['number']);
        $sx = explode(' ', $data['attr_sx']);
        $wx = explode(' ', $data['attr_wx']);
        $bs = explode(' ', $data['attr_bs']);

        $result = array_map(function ($number, $sx, $wx, $bs) {
            return [
                'color'     => $bs,
                'number'    => $number,
                'shengXiao' => $sx,
                'wuXing'    => $wx,
            ];
        }, $numbers, $sx, $wx, $bs);
        return $result;
    }

    /**
     * 用户登录校验
     * @return true
     * @throws AuthorizationException
     */
    public function must_be_login()
    {
        if (!$this->isLogin()) {
            throw new AuthorizationException('用户未登录，禁止操作', 20000);
        }
        return true;
    }

    /**
     * 判断当前用户是否登录
     * @return bool
     */
    public function isLogin()
    {
        return Auth::guard('user')->check();
    }

    /**
     * 根据日期获取周几
     * @param $date
     * @return string
     */
    public function dayOfWeek($date)
    {
        $dayOfWeek = date('w', strtotime($date)); // 获取当前星期几的数字表示
        $dayOfWeekText = '';

        switch ($dayOfWeek) {
            case 0:
                $dayOfWeekText = '日';
                break;
            case 1:
                $dayOfWeekText = '一';
                break;
            case 2:
                $dayOfWeekText = '二';
                break;
            case 3:
                $dayOfWeekText = '三';
                break;
            case 4:
                $dayOfWeekText = '四';
                break;
            case 5:
                $dayOfWeekText = '五';
                break;
            case 6:
                $dayOfWeekText = '六';
                break;
        }
        return $dayOfWeekText;
    }

    /**
     * 根据二维数组某个字段进行排序
     * @param $array
     * @param $field
     * @param $sortOrder
     * @param $sortType
     * @return void
     */
    function sortArrayByField(&$array, $field, $sortOrder = SORT_ASC, $sortType = SORT_REGULAR)
    {
        $sortField = array_column($array, $field);
        array_multisort($sortField, $sortOrder, $sortType, $array);
    }

    /**
     * 获取正码属性
     * @param array $nums
     * @return array
     */
    function getZhengMaAttr(array $nums)
    {
        $arr = [];
        $arr1 = [];
        foreach ($nums as $k => $num) {
            $arr[$k]['正' . self::$_convertToChinese[$k + 1]] = $this->getSize($num) . $this->getOddEven($num) . ',' . $this->getSumOddEven($num) . ',' . $this->getTailSize($num) . ',' . $this->getBose($num);
        }
        foreach ($nums as $k => $num) {
            $arr1[$k]['正' . ($k + 1) . '特'] = $num;
        }

        return array_merge($arr, $arr1);
    }

    /**
     * 判断某个号码大小
     * @param $num
     * @return string
     */
    function getSize($num)
    {

        return $num <= $this->_max_small ? '小' : '大';
    }

    /**
     * 判断某个号码单双
     * @param $num
     * @return string
     */
    function getOddEven($num)
    {

        return $num % 2 == 0 ? '双' : '单';
    }

    /**
     * 判断某个号码的合数单双
     * @param $num
     * @return string
     */
    function getSumOddEven($num)
    {
        if (strlen((int)$num) == 1) {
            $new_num = $num;
        } else {
            $new_num = $num % 10 + $num / 10;
        }

        return '合' . $this->getOddEven($new_num);
    }

    /**
     * 判断某个号码的尾数大小
     * @param $num
     * @return string
     */
    function getTailSize($num)
    {
        return '尾' . ($num % 10 <= $this->_tail_max_small ? '小' : '大');
    }

    /**
     * 获取某个号码的波色
     * @param $num
     * @param $str
     * @return string|void
     */
    function getBose($num, $str = '波')
    {
        $num = str_pad($num, 2, 0, STR_PAD_LEFT);
        foreach ($this->_bose as $k => $v) {
            if (in_array($num, $v)) {
                return $k . $str;
            }
        }
    }

    /**
     * 判断某个号码的合数大小
     * @param $num
     * @return string
     */
    function getSumSize($num)
    {
        if (strlen((int)$num) == 1) {
            $new_num = $num;
        } else {
            $new_num = $num % 10 + $num / 10;
        };
        return '合' . ($new_num <= $this->_sum_max_small ? '小' : '大');
    }

    /**
     * 判断某个号码的尾数单双
     * @param $num
     * @return string
     */
    function getTailOddEven($num)
    {
        return '尾' . $this->getOddEven($num % 10);
    }

    /**
     * 上传到阿里云 云存储
     * @param $json_name
     * @param $data
     * @return void|null
     */
    public function ALiOss($json_name, $data)
    {
        $accessKeyId = env('OSS_ACCESS_KEY_ID');
        $accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
        $endpoint = 'https://oss-cn-hongkong.aliyuncs.com';

        $bucket = '48tuku';
        $object = "lottery_data_json/" . $json_name;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->putObject($bucket, $object, $data);
        } catch (OssException $e) {
            Log::error('同步到OSS出错', ['errMsg' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('同步到OSS出错', ['errMsg' => $e->getMessage()]);
        }
    }

    /**
     * 上传到阿里云 云存储
     * @param $data
     * @param string $object
     * @param string $filename
     * @return void|null
     */
    public function ALiOssWith($data, string $object='configs', string $filename='config.js', string $bucket='66tuku')
    {
        $accessKeyId = env('OSS_ACCESS_KEY_ID');
        $accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
        $endpoint = 'https://oss-cn-hongkong.aliyuncs.com';

        $object = $object."/".$filename;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->putObject($bucket, $object, $data);
        } catch (OssException|\Exception $e) {
//            dd($e->getMessage());
            Log::error('同步到OSS出错', ['errMsg' => $e->getMessage()]);
        }
    }

    public function custom_strip_tags($html, $allowed_tags = '<p><strong><em><u><ul><ol><br><i><h1><h2><h3><h4><h5><h6><div><span>&nbsp;\n'): string
    {
        // 从 HTML 中去除不在允许标签列表中的标签
        return strip_tags($html, $allowed_tags);
    }

    /**
     * IPV6 地址转换为整数
     * @param $ipv6
     * @return string
     */
    function ip2long6($ipv6)
    {
        $ip_n = inet_pton($ipv6);
        $bits = 15; // 16 x 8 bit = 128bit
        $ipv6long = '';
        while ($bits >= 0) {
            $bin = sprintf("%08b", (ord($ip_n[$bits])));
            $ipv6long = $bin . $ipv6long;
            $bits--;
        }
        return gmp_strval(gmp_init($ipv6long, 2), 10);
    }

    /**
     * 整数转换为 IPV6 地址
     * @param $ipv6long
     * @return string
     */
    function long2ip6($ipv6long)
    {
        $bin = gmp_strval(gmp_init($ipv6long, 10), 2);
        if (strlen($bin) < 128) {
            $pad = 128 - strlen($bin);
            for ($i = 1; $i <= $pad; $i++) {
                $bin = "0" . $bin;
            }
        }
        $bits = 0;
        $ipv6 = '';
        while ($bits <= 7) {
            $bin_part = substr($bin, ($bits * 16), 16);
            $ipv6 .= dechex(bindec($bin_part)) . ":";
            $bits++;
        }
        // compress
        return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
    }

    /**
     * 发送短信验证码
     * @param $params
     * @return bool
     */
    function sendSms($params): bool
    {
        $client = new UniSMS([
            'accessKeyId' => env('SMS_ACCESS_KEY_ID'),
        ]);
        try {
            $resp = $client->send([
                'to'           => '+86' . $params['mobile'],
                'signature'    => $params['signature'] ?? env('SMS_SIGNATURE'),
                'templateId'   => 'pub_verif_ttl3',
                'templateData' => [
                    'code' => $params['code'],
                    'ttl'  => $params['ttl']
                ]
            ]);
            return true;
        } catch (UniException $e) {
            Log::error('短信发送失败', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * 获取方法
     * @return array|string|null
     */
    protected function getIp()
    {
//        if (request()->header('cf-connecting-ip')) {
//            $ip = request()->header('cf-connecting-ip');
//        } else
        if (request()->header('x-real-ip')) {
            $ip = request()->header('x-real-ip');
        } else if (request()->header('x-forwarded-for')) {
            $ip = request()->header('x-forwarded-for');
        } else {
            $ip = request()->ip();
        }

        $explodeIp = explode(',', $ip);
        return trim(array_pop($explodeIp));
    }


    /**
     * 获取ip所在地
     * @return mixed|string
     */
    protected function getIpInCountry(): mixed
    {
        $ipCountry = '';
        $ip = $this->getIp();
        if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            $ipAddress = IpLocation::getLocation($ip);
            $ipCountry = $ipAddress['country'] ?? '';
        }

        return $ipCountry;
    }

    /**
     * 获取第一次访问详情增加的阅读数
     * @return int
     */
    protected function getFirstViews(): int
    {
        return rand(500, 1000);
    }

    /**
     * 获取多次访问详情增加的阅读数
     * @return int
     */
    protected function getSecondViews(): int
    {
        return rand(30, 100);
    }

    /**
     * 获取下一期期数
     * @param $lotteryType
     * @return array|int|mixed|string|string[]
     */
    public function getNextIssue($lotteryType)
    {
//        return 279;
        $arr = Redis::get('real_open_' . $lotteryType);
        if ($arr) {
            $arr = explode(',', $arr);
            if ($lotteryType == 2) {
                $issue = str_replace(date('Y'), '', $arr[8]);
                $issue = str_replace(date('Y') - 1, '', $issue);
            } else {
                $issue = str_pad($arr[8], 3, 0, STR_PAD_LEFT);
            }
        } else {
            $issue = HistoryNumber::query()
                ->where('year', date('Y'))
                ->where('lotteryType', $lotteryType)
                ->max('issue');
            if (!$issue) {
                $issue = HistoryNumber::query()
                    ->where('year', date('Y') - 1)
                    ->where('lotteryType', $lotteryType)
                    ->max('issue');
            }
            $issue++;
        }

        return $issue;
    }

    /**
     * 将本地视频上传到云存储 并 生成视频封面图存db 并 删除本地视频资源
     * @param string $videoUrl
     * @param Model $model
     * @return string
     * @throws ApiException
     * @throws FileNotFoundException
     */
    protected function attachS3Video(string $videoUrl, Model $model): string
    {
        $videoUrl = str_replace([$this->getHttp(), config('config.full_srv_img_prefix')], '', $videoUrl);
        $videoUrl = ltrim($videoUrl, '/');
        if (!file_exists($videoUrl)) {
            throw new FileNotFoundException('视频资源不存在，保存失败');
        }
        try {
            $uploadFile['filename'] = pathinfo($videoUrl)['basename'];
            $v_cover_img = pathinfo($videoUrl)['filename'];
            $uploadFile['saveFile'] = $videoUrl;
            $path = $this->multipartUpload($uploadFile);
            // 关联模型图片
            if ($path) {
                $this->getVideoCover(public_path($videoUrl), public_path('upload/' . $v_cover_img . '.jpg'));
                // 将封面图移动至目标位置
                if (Storage::disk('upload')->exists($v_cover_img . '.jpg')) {
                    $new_full_path_name = 'v_cover/' . date('Ymd') . '/' . $v_cover_img . '.jpg';
                    Storage::disk('upload')->move($v_cover_img . '.jpg', $new_full_path_name);
                    $this->attachImage('upload/' . $new_full_path_name, $model);
                }
            }
            // 删除本地视频
            Storage::disk('api_delete')->delete($videoUrl);
            return $path;
        } catch (\Exception $exception) {
            Storage::disk('upload')->exists($v_cover_img . '.jpg') && Storage::disk('upload')->delete($v_cover_img . '.jpg');
            Log::error('视频上传到云存储失败', ['message' => $exception->getMessage()]);
            throw new ApiException(['message' => '视频上传到云存储失败:' . $exception->getMessage(), 'status'=>400]);
        }
    }

    /**
     * @name 获取当前域名
     * @description
     * @return String
     **/
    public function getHttp(): string
    {
        $http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        return config('config.domain');
        return $http.$_SERVER['HTTP_HOST'];
        return 'https://api1.49tkapi8.com';
    }

    /**
     * 分片上传
     * @param $downloadInfo
     * @return mixed
     */
    private function multipartUpload1($downloadInfo)
    {
        $bucket = env('MINO_BUCKET');
        $s3Client = new S3Client([
            'version'                 => 'latest',
            'region'                  => env('AWS_DEFAULT_REGION'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT'),
            'credentials'             => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ],
        ]);
        $keyname = 'video/' . date('Y_m_d') . '/' . $downloadInfo['filename'];
        $result = $s3Client->createMultipartUpload([
            'Bucket'       => $bucket,
            'Key'          => $keyname,
            'StorageClass' => 'REDUCED_REDUNDANCY',
        ]);
        $uploadId = $result['UploadId'];
        try {
            $file = fopen($downloadInfo['saveFile'], 'r');
            $partNumber = 1;
            $parts = [];
            while (!feof($file)) {
                $result = $s3Client->uploadPart([
                    'Bucket'     => $bucket,
                    'Key'        => $keyname,
                    'UploadId'   => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body'       => fread($file, 5 * 1024 * 1024),
                ]);
                $parts['Parts'][$partNumber] = [
                    'PartNumber' => $partNumber,
                    'ETag'       => $result['ETag'],
                ];
                $partNumber++;
            }
            fclose($file);
            unset($file);
        } catch (S3Exception $e) {
            $s3Client->abortMultipartUpload([
                'Bucket'   => $bucket,
                'Key'      => $keyname,
                'UploadId' => $uploadId
            ]);
        }
        $result = $s3Client->completeMultipartUpload([
            'Bucket'          => $bucket,
            'Key'             => $keyname,
            'UploadId'        => $uploadId,
            'MultipartUpload' => $parts,
        ]);
        $upParse = parse_url($result['Location']);
        unset($parts);
        unset($result);

        return $upParse['path'];
    }

    private function multipartUpload($downloadInfo)
    {
        $bucket = env('MINO_BUCKET');
        $s3Client = new S3Client([
            'version'                 => 'latest',
            'region'                  => env('MINO_DEFAULT_REGION'),
            'endpoint'                => env('MINO_ENDPOINT'),
            'use_path_style_endpoint' => env('MINO_USE_PATH_STYLE_ENDPOINT'),
            'credentials'             => [
                'key'    => env('MINO_ACCESS_KEY_ID'),
                'secret' => env('MINO_SECRET_ACCESS_KEY')
            ],
        ]);
        $keyname = 'video/' . date('Y_m_d') . '/' . $downloadInfo['filename'];
        $result = $s3Client->createMultipartUpload([
            'Bucket'       => $bucket,
            'Key'          => $keyname,
            'StorageClass' => 'REDUCED_REDUNDANCY',
        ]);
        $uploadId = $result['UploadId'];
        try {
            $file = fopen($downloadInfo['saveFile'], 'r');
            $partNumber = 1;
            $parts = [];
            while (!feof($file)) {
                $result = $s3Client->uploadPart([
                    'Bucket'     => $bucket,
                    'Key'        => $keyname,
                    'UploadId'   => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body'       => fread($file, 5 * 1024 * 1024),
                ]);
                $parts['Parts'][$partNumber] = [
                    'PartNumber' => $partNumber,
                    'ETag'       => $result['ETag'],
                ];
                $partNumber++;
            }
            fclose($file);
            unset($file);
        } catch (S3Exception $e) {
            $s3Client->abortMultipartUpload([
                'Bucket'   => $bucket,
                'Key'      => $keyname,
                'UploadId' => $uploadId
            ]);
        }
        $result = $s3Client->completeMultipartUpload([
            'Bucket'          => $bucket,
            'Key'             => $keyname,
            'UploadId'        => $uploadId,
            'MultipartUpload' => $parts,
        ]);
        $upParse = parse_url($result['Location']);
        unset($parts);
        unset($result);

        return $upParse['path'];
    }

    private function getVideoCover($input, $output)
    {
        $res = shell_exec("/usr/local/ffmpeg/bin/ffmpeg -i $input -y -vf \"scale=iw:ih\" -vframes 1 -ss 00:00:01 -t 1 $output");  // 2>&1  /opt/homebrew/bin/ /usr/local/ffmpeg/bin/
//        $res = shell_exec("/opt/homebrew/bin/ffmpeg -i $input -y -f image2 -s 640*480 -vframes 1 $output 2>&1");  // 2>&1
//        dd($res);
    }

    /**
     * 多态图片 添加图片
     * @param string $imageUrl
     * @param Model $model
     * @param bool $water
     * @return void
     * @throws CustomException
     */
    protected function attachImage(string $imageUrl, Model $model, bool $water = false)
    {
        $images = [];
        $imageUrl = str_replace([$this->getHttp(), config('config.full_srv_img_prefix')], '', $imageUrl);
        $imageUrl = ltrim($imageUrl, '/');
        $imageUrl = [$imageUrl];
        foreach ($imageUrl as $k => $v) {
            $imageInfo = (new PictureService())->getImageInfoWithOutHttp($v, $water);
            $images[$k]['img_url'] = $v;
            $images[$k]['width'] = $imageInfo['width'];
            $images[$k]['height'] = $imageInfo['height'];
            $images[$k]['mime'] = $imageInfo['mime'];
        }
        $model->images()->createMany($images);
    }

    protected function setTradeNo()
    {
        return date('Ymd') . substr(time(), -4) . rand(11111111, 99999999);
    }

    /**
     * 将用户操作与用户对应起来 放到缓存
     * @param int $type
     * @param int $target_id
     * @param string $operate
     * @return bool
     * @throws CustomException
     */
    protected function cacheUserOperate(int $type, int $target_id, string $operate)
    {
        try {
            $userId = auth('user')->id();
            if (!$userId) {
                throw new CustomException(['message' => '请先登录']);
            }
            switch ($type) {
                case self::PICMODEL:  // 图片有点赞、收藏 对应cachekey： picture_follows_`id` picture_collects_`id`
                    $cache_key = 'picture_' . $operate . '_' . $target_id;
                    break;
                case self::COMMENTMODEL: // 评论只有点赞 对应cachekey：comment_follows_`id`
                    $cache_key = 'comment_' . $operate . '_' . $target_id;
                    break;
            }
            return $this->appendCache($cache_key, $userId);
        } catch (\Exception $exception) {
            Log::error('缓存设置失败：', ['message' => $exception->getMessage()]);
            throw new CustomException(['message' => '缓存设置失败，请重试']);
        }
    }

    /**
     * 追加缓存
     * @param $cache_key
     * @param $new_val
     * @return bool
     */
    private function appendCache($cache_key, $new_val)
    {
        $currentValue = Cache::get($cache_key);
        if ($currentValue) {
            $value = $currentValue . ' ' . $new_val;
        } else {
            $value = $new_val;
        }
        return Cache::forever($cache_key, $value);
    }

    /**
     * 拼接图片url完整地址
     * @param $color
     * @param $max_issue
     * @param $keyword
     * @param int $lotteryType
     * @param string $ext
     * @param int $year
     * @param bool $isDetail
     * @return string
     */
    public function getPicUrl($color, $max_issue, $keyword, int $lotteryType = 1, string $ext = 'jpg', int $year = 2025, bool $isDetail = false): string
    {
        $current_year = date('Y');
        if ($lotteryType == 6) {
            $url = $this->getImgPrefix()[$year][$lotteryType];
            $url = $url[array_rand($url)];
            return $url . ($color == 1 ? 'col/' : 'black/') . $year . '/' . $max_issue . '/' . $keyword . '.' . $ext;
        }
        if ($lotteryType == 1) {
            $url = $this->getImgPrefix()[$year][$lotteryType];
            $url = $url[array_rand($url)];
            return $url . (!$isDetail ? '/m/' : '') .  ($color == 1 ? 'col/' : 'black/') . $max_issue . '/' . $keyword . '.' . $ext;
        }
        if ($lotteryType == 3 && $year == $current_year) {
            // https://www.tutu.finance/taiwan/2023/col/99/amfql.jpg
            $array = ['https://www.tutu.finance/taiwan/']; // , 'http://lqt.smhuyjhb.com/taiwan/'
            $url = $array[array_rand($array)];
            return $url . $current_year . '/' . ($color == 1 ? 'col/' : 'black/') . $max_issue . '/' . $keyword . '.' . $ext;
//            return $this->getImgPrefix()[$year][$lotteryType].($color == 1 ? 'col/' : 'black/').$year.'/'.$max_issue.'/'.$keyword.'.'.$ext;
        }
//        dd($this->getImgPrefix());
        $url = $this->getImgPrefix()[$year][$lotteryType];
        if (is_array($url)) {
            $url = $url[array_rand($url)];
        }
        if ($lotteryType == 5) {
//            if ($year==2024) {
//                return $url.($color == 1 ? 'col/' : 'black/').$year.'/'.str_pad($max_issue, 3, 0, STR_PAD_LEFT).'/'.$keyword.'.'.$ext;
//            } else {
//                return $url.($color == 1 ? 'col/' : 'black/').$year.'/'.$max_issue.'/'.$keyword.'.'.$ext;
//            }
            return $url . ($color == 1 ? 'col/' : 'black/') . $year . '/' . $max_issue . '/' . $keyword . '.' . $ext;
        }

        if ($year == $current_year && !$isDetail) {
            return $url . "m/" . ($color == 1 ? 'col/' : 'black/') . ($lotteryType==7 ? $year.'/' : '') . $max_issue . '/' . $keyword . '.' . $ext;
        }

        return $url .($lotteryType == 7 ? 'big-pic/' : '') . ($color == 1 ? 'col/' : 'black/') . ($lotteryType==7 ? $year.'/' : '') . $max_issue . '/' . $keyword . '.' . $ext;
    }

    /**
     * 获取图片前缀地址
     * @return array
     */
    protected function getImgPrefix(): array
    {
        $redisData = Redis::get('img_prefix');
        if (!$redisData) {
            $imgPrefix = ImgPrefix::query()->orderBy('year', 'desc')->get()->toArray();
            $redisData = [];
            if ($imgPrefix) {
                foreach ($imgPrefix as $v) {
                    $redisData[$v['year']][1] = $v['xg_img_prefix'];
                    $redisData[$v['year']][2] = $v['xam_img_prefix'];
                    $redisData[$v['year']][3] = $v['tw_img_prefix'];
                    $redisData[$v['year']][4] = $v['xjp_img_prefix'];
                    $redisData[$v['year']][5] = $v['am_img_prefix'];
                    $redisData[$v['year']][6] = $v['kl8_img_prefix'];
                    $redisData[$v['year']][7] = $v['oldam_img_prefix'];
                }
                Redis::set('img_prefix', json_encode($redisData));
            }
        } else {
            $redisData = json_decode($redisData, true);
            foreach ($redisData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $redisData[$k][$kk] = json_decode($vv, true);
                }
            }
//            dd($redisData);
        }

        return $redisData;
    }

    /**
     * 获取对应`status`配置项 判断是否需要审核
     * @param int $type
     * @return mixed
     * @throws CustomException
     */
    protected function getCheckStatus(int $type)
    {
        $target_id = 0;
        if ($type == 1) {
            $target_id = 1;
        } else if ($type == 2) {
            $target_id = 5;
        } else if ($type == 3) {
            $target_id = 6;  // 图片竞猜评论
        } else if ($type == 4) {
            $target_id = 7;
        } else if ($type == 5) {
            $target_id = 4;
        } else if ($type == 6) {
            $target_id = 3; // 发现评论
        } else if ($type == 7) {
            $target_id = 8; // 图片竞猜发布
        } else if ($type == 8) {
            $target_id = 9; // 发现：发布
        } else if ($type == 9) {
            $target_id = 11; // 高手论坛评论
        } else if ($type == 10) {
            $target_id = 10; // 高手论坛发布
        } else if ($type == 11) {
            $target_id = 12; // 幽默竞猜评论
        } else if ($type == 12) {
            $target_id = 2; // 图解发布
        } else if ($type == 13) {
            $target_id = 13; // 会员绑定平台
        } else if ($type == 14) {
            $target_id = 14; // 会员充值
        } else if ($type == 15) {
            $target_id = 15; // 会员提现
        } else if ($type == 16) {
            $target_id = 16; // 会员图像审核
        } else if ($type == 17) {
            $target_id = 17; // 第三方评论审核
        }
        if (!$target_id) {
            throw new CustomException(['message' => 'check-target-id字段缺失']);
        }

        return Check::query()->where('target_id', $target_id)->value('status');
    }

    /**
     * 特码出现次数最多 | 特码遗漏
     * @param $data
     * @param int $count
     * @return array
     */
    protected function tongJi($data, int $count = 0): array
    {
        // 获取波色和生肖
        $year = date('Y', time());
        $liuhe = LiuheNumber::query()->where('year', $year)
            ->select(['number', 'zodiac', 'bose'])->get()->toArray();
        $arr = [];
        foreach ($liuhe as $k => $item) {
            $arr[$item['number']] = $item;
        }
        $res = [];
        foreach ($data as $k => $item) {
            $res[$k]['color'] = $this->get_bose_num($k, 2);
            $res[$k]['count'] = $count != 0 ? $count : $item;
            $res[$k]['number'] = (string)$k;
        }

        return array_values($res);
    }

    /**
     * 二维数组排序
     * @param $array
     * @param $key
     * @param $ascending
     * @return void
     */
    protected function sortByKey(&$array, $key, $ascending = true)
    {
        usort($array, function ($a, $b) use ($key, $ascending) {
            $comparison = $ascending ? 1 : -1;

            return ($a[$key] <=> $b[$key]) * $comparison;
        });
    }

    /**
     * 生肖关联号码【无参】
     * @param $year
     * @return array
     */
    public function getAssocNums($year): array
    {
        $_assocNums = $this->getAssocNumsByYear($year);

        $_red_num_arr = $this->_bose['红'];
        $_blue_num_arr = $this->_bose['绿'];
        $_green_num_arr = $this->_bose['蓝'];

        foreach ($_assocNums as $k => $v) {
            if (in_array($v['zodiac'], $this->jiaqin)) {
                $_jq_num_arr[] = $v['number'];
            }
            if (in_array($v['zodiac'], $this->yeshou)) {
                $_ys_num_arr[] = $v['number'];
            }
            if (in_array($v['zodiac'], $this->tian)) {
                $_tian_num_arr[] = $v['number'];
            }
            if (in_array($v['zodiac'], $this->di)) {
                $_di_num_arr[] = $v['number'];
            }
            if (in_array($v['zodiac'], $this->qian)) {
                $_qian_num_arr[] = $v['number'];
            }
            if (in_array($v['zodiac'], $this->hou)) {
                $_hou_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "兔") {
                $_rabbit_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "虎") {
                $_tiger_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "牛") {
                $_cow_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "鼠") {
                $_mouse_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "猪") {
                $_pig_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "狗") {
                $_dog_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "鸡") {
                $_chicken_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "猴") {
                $_monkey_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "羊") {
                $_sheep_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "马") {
                $_horse_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "龙") {
                $_dragon_num_arr[] = $v['number'];
            }
            if ($v['zodiac'] == "蛇") {
                $_snake_num_arr[] = $v['number'];
            }
        }

        return [
            '_red_num_arr'     => $_red_num_arr,
            '_blue_num_arr'    => $_blue_num_arr,
            '_green_num_arr'   => $_green_num_arr,
            '_jq_num_arr'      => $_jq_num_arr ?? [],
            '_ys_num_arr'      => $_ys_num_arr ?? [],
            '_tian_num_arr'    => $_tian_num_arr ?? [],
            '_di_num_arr'      => $_di_num_arr ?? [],
            '_qian_num_arr'    => $_qian_num_arr ?? [],
            '_hou_num_arr'     => $_hou_num_arr ?? [],
            '_rabbit_num_arr'  => $_rabbit_num_arr ?? [],
            '_tiger_num_arr'   => $_tiger_num_arr ?? [],
            '_cow_num_arr'     => $_cow_num_arr ?? [],
            '_mouse_num_arr'   => $_mouse_num_arr ?? [],
            '_pig_num_arr'     => $_pig_num_arr ?? [],
            '_dog_num_arr'     => $_dog_num_arr ?? [],
            '_chicken_num_arr' => $_chicken_num_arr ?? [],
            '_monkey_num_arr'  => $_monkey_num_arr ?? [],
            '_sheep_num_arr'   => $_sheep_num_arr ?? [],
            '_horse_num_arr'   => $_horse_num_arr ?? [],
            '_dragon_num_arr'  => $_dragon_num_arr ?? [],
            '_snake_num_arr'   => $_snake_num_arr ?? [],
        ];
    }

    /**
     * 根据年份获取生肖对应的号码
     * @param string $year
     * @return array
     */
    public function getAssocNumsByYear(string $year = ''): array
    {
        $year = $year ?: date('Y');

        return LiuheNumber::query()->where('year', $year)->select(["number", "zodiac"])->get()->toArray();
    }

    /**
     * 上传OSS前 判断数据是否改变
     * @param $data
     * @param $json_name
     * @return bool
     */
    private function isChangeData($data, $json_name): bool
    {
        $redisData = Redis::get($json_name . '_data');
        if (!$redisData || md5($data) != $redisData) {
            Redis::set($json_name . '_data', md5($data));
            return true;
        }
        return false;
    }

    public function getOpenType(): int
    {
        $type = Redis::get('forecast_bet_win_type');
        if (!$type) {
            $type = DB::table('auth_activity_configs')->where('k', 'forecast_bet_win_type')->value('v');
        }
        return $type ? (int)$type : 2;
    }

    public function getBets($lotteryType, $issue, $numbersInfo): bool
    {
        $issue = (int)str_replace(date("Y"), '', $issue);
        $res = DB::table("user_bets")
            ->lockForUpdate()
            ->where('year', date("Y"))
            ->where('issue', $issue)
            ->where('lotteryType', $lotteryType)
            ->where('status', 0)
            ->select(['id', 'forecast_bet_name', 'user_id', 'each_bet_money', 'bet_num', 'odd'])
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
        $data = [];
        if (!$res) {
            return false;
        }
        foreach($res as $v) {
            $data[$v['forecast_bet_name']][] = $v;
        }
//        $dataArr = [];
//        foreach($data as $k => $v) {
//            foreach($v as $kk => $vv) {
//                $dataArr[$k][$vv['bet_num']][] = $vv;
//            }
//        }
//        dd($numbersInfo, $data);
        $teSize = $numbersInfo['number_attr'][6]['number'] > 24 ? '特大' : '特小';
        $teSingle = $numbersInfo['number_attr'][6]['number'] % 2 == 0 ? '特双' : '特单';
        $heSize = (floor(($numbersInfo['number_attr'][6]['number']) / 10) + ($numbersInfo['number_attr'][6]['number'] % 10)) > 6 ? '合大' : '合小' ;
        $heSingle = (floor(($numbersInfo['number_attr'][6]['number']) / 10) + ($numbersInfo['number_attr'][6]['number'] % 10)) % 2 == 0 ? '合双' : '合单' ;
        $datas = [];
        foreach ($data as $k => $v) {
            if ($k == '特码') {
                foreach ($v as $kk => $vv) {
                    if ($numbersInfo['number_attr'][6]['number']==49) {
                        $vv['status'] = 2;
                    } else if ($vv['bet_num'] == $teSize) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $teSingle) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $heSize) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $heSingle) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == '天肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->tian) ? 1 : -1;
                    } else if ($vv['bet_num'] == '地肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->di) ? 1 : -1;
                    } else if ($vv['bet_num'] == '家肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->jiaqin) ? 1 : -1;
                    } else if ($vv['bet_num'] == '野肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->yeshou) ? 1 : -1;
                    } else if ($vv['bet_num'] == '前肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->qian) ? 1 : -1;
                    } else if ($vv['bet_num'] == '后肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->hou) ? 1 : -1;
                    } else if ($vv['bet_num'] == '红波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 1 ? 1 : -1;
                    } else if ($vv['bet_num'] == '蓝波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 2 ? 1 : -1;
                    } else if ($vv['bet_num'] == '绿波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 3 ? 1 : -1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            } else if ($k == '特肖') {
                foreach ($v as $kk => $vv) {
                    if ($vv['bet_num'] == $numbersInfo['number_attr'][6]['shengXiao']) {
                        $vv['status'] = 1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            } else if ($k == '平特肖') {
                foreach ($v as $kk => $vv) {
                    if ($vv['bet_num'] == $numbersInfo['number_attr'][0]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][1]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][2]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][3]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][4]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][5]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][6]['shengXiao']
                    ) {
                        $vv['status'] = 1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            }
        }
//        dd($datas);
        if ($datas) {
            $loseIds = [];
            $drawIds = [];
            $winUserInfo = [];
            $winUserIds = [];
            $loseUserInfo = [];
            $drawUserInfo = [];
            $openType = (new BaseService())->getOpenType();
//            dd($openType);
//            DB::beginTransaction();
            try{
                foreach ($datas as $k => $v) {
                    if ($v['status'] == -1) {
                        $loseIds[] = $v['id'];
                        $loseUserInfo[$k]['id'] = $v['id'];
                        $loseUserInfo[$k]['user_id'] = $v['user_id'];
                        $loseUserInfo[$k]['win_money'] = $v['each_bet_money'] * $v['odd'];  // 失败的金额
                    } else if ($v['status'] == 1) {
//                    $winIds[$v['id']] = [
//                        "win_money"     => $v['each_bet_money'] * $v['odd'],
//                        "status"        => 1,
//                    ];
                        DB::table('user_bets')->where('id', $v['id'])->update([
                            'status'        => 1,
                            'win_status'    => $openType == 1 ? 2 : 1,
                            'win_money'     => $v['each_bet_money'] * $v['odd']
                        ]);
                        $winUserIds[] = $v['user_id'];
                        $winUserInfo[$k]['id'] = $v['id'];
                        $winUserInfo[$k]['user_id'] = $v['user_id'];
                        $winUserInfo[$k]['win_money'] = $v['each_bet_money'] * $v['odd'];  // 赢取的金额
                    } else if ($v['status'] == 2) {
                        $drawIds[] = $v['id'];
                        $drawUserInfo[$k]['id'] = $v['id'];
                        $drawUserInfo[$k]['user_id'] = $v['user_id'];
                        $drawUserInfo[$k]['win_money'] = $v['each_bet_money'];  // 退还的金额
                    }
                }

                if ($loseIds) {
                    DB::table('user_bets')->whereIn('id', $loseIds)->update([
                        'status'        => -1,
                        'win_status'    => -1,
                    ]);
                }
                if ($drawIds) {
                    DB::table('user_bets')->whereIn('id', $drawIds)->update([
                        'status'        => 2,
                        'win_status'    => -1,
                    ]);
                }

                if ($loseUserInfo) {  // 金币记录 余额 都不用动

                }
                if ($winUserInfo) {  // 金币记录 余额 都需要动
                    sort($winUserInfo);
                    foreach ($winUserInfo as $k => $v) {
                        if ($openType==1) {
                            $userGoldData = [];
                            $userGoldData['user_id'] = $v['user_id'];
                            $userGoldData['type'] = 14;
                            $userGoldData['gold'] = $v['win_money'];
                            $userGoldData['symbol'] = '+';
                            $userGoldData['user_bet_id'] = $v['id'];
                            $userGoldData['balance'] = DB::table('users')->where('id', $v['user_id'])->value('account_balance') + $v['win_money'];
                            $userGoldData['created_at'] = date("Y-m-d H:i:s");
                            DB::table('user_gold_records')->insert($userGoldData);
                            DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']);
                        }
                    }
                }
                if ($drawUserInfo) {  //  余额 动
                    sort($drawUserInfo);
                    foreach ($drawUserInfo as $k => $v) {
                        $userGoldData = [];
                        $userGoldData['user_id'] = $v['user_id'];
                        $userGoldData['type'] = 14;
                        $userGoldData['gold'] = $v['win_money'];
                        $userGoldData['symbol'] = '+';
                        $userGoldData['user_bet_id'] = $v['id'];
                        $userGoldData['balance'] = DB::table('users')->where('id', $v['user_id'])->value('account_balance') + $v['win_money'];
                        $userGoldData['created_at'] = date("Y-m-d H:i:s");
                        DB::table('user_gold_records')->insert($userGoldData);
                        DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']);
                    }
//                    foreach ($drawUserInfo as $k => $v) {
//                        DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']); // 退还投注金额
//                    }
                }
//                if ($drawUserInfo) {  //  余额 动
//                    foreach ($drawUserInfo as $k => $v) {
//                        DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']); // 退还投注金额
//                    }
//                }
            }catch (\Exception $exception) {
//                DB::rollBack();
                Log::error('开奖失败', ['message'=>$exception->getMessage()]);
            }
//            DB::commit();
        }
        return true;
    }

    /**
     * 上传图片到S3
     * @param $data
     * @param string $dir
     *      open_lottery: 开奖数据
     *      configs: 配置文件
     * @param string $file_name
     * @return bool
     * @throws ApiException
     */
    public function upload2S3($data, string $dir='open_lottery', string $file_name = '', string $bucket='s3'): bool
    {
        try{
            // 指定存储路径
            $path = $dir . '/' . $file_name; // 可以自定义存储路径
            // 使用 Storage 门面将 JSON 数据上传到 S3
            return Storage::disk($bucket)->put($path, $data);
        }catch (\Exception $exception) {
            Log::error('文件上传到S3失败', ['class'=>__class__, 'message'=>$exception->getMessage()]);

            $this->apiError();
        }
    }

    public function getUserRegisterMoreThan3(): bool
    {
        $register_at = DB::table('users')->where('id', auth('user')->id())->value('register_at');
        $specifiedTime = Carbon::parse($register_at);

        $currentTime = Carbon::now();

        $timeDifference = $currentTime->diffInHours($specifiedTime);

        if ($timeDifference > 3) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 随机字符串
     * @param int $length 长度
     * @return string
     */
    public function str_rand(int $length): string
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * 生成邀请码
     * @return string
     */
    protected function randString(): string
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)]
            . strtoupper(dechex(date('m')))
            . date('d') . substr(time(), -5)
            . substr(microtime(), 2, 5)
            . sprintf('%02d', rand(0, 99));
        for (
            $a = md5($rand, true),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord($a[$f]),
            $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
            $f++
        ) ;
        return $d;
    }

    public function createNickName($name)
    {
        while(true) {
            $name = $name . '_' . rand(1000000, 9999999);
            if (!DB::table('users')->where('nickname', $name)->exists()) {
                return $name;
            }
        }

    }
}
