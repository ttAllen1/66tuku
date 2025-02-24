<?php
/**
 * 系统配置服务
 * @Description
 */

namespace Modules\Admin\Services\auth;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\AuthArea;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Models\AuthOperationLog;
use Modules\Admin\Models\AuthProject;
use Modules\Admin\Models\AuthRule;
use Modules\Admin\Models\User;
use Modules\Admin\Services\BaseApiService;
use Illuminate\Support\Facades\Cache;
use Modules\Admin\Models\AuthImage as AuthImageModel;
use Modules\Api\Models\IpView;
use Modules\Api\Models\IpViewsCount;
use Modules\Api\Models\IpViewUv;
use Modules\Api\Models\Statistics;

class ConfigService extends BaseApiService
{
    /**
     * 清除缓存
     * @return JsonResponse
     */
    public function outCache(): JsonResponse
    {
//        Cache::forget('areaList');
        return $this->apiSuccess('清除成功！');
    }

    /**
     * 获取地区数据
     * @return JsonResponse
     */
    public function getAreaData(): JsonResponse
    {
        $list = Cache::get('auth_rule');
        if(!$list){
            $list = AuthArea::query()->orderBy('sort','asc')
                ->orderBy('id','asc')
                ->where('status',1)
                ->get()
                ->toArray();
            if(count($list)){
                $list = $this->tree($list);
                Cache::put('areaList',$list);
            }
        }

        return $this->apiSuccess('',$list);
    }

    /**
     * 获取平台信息
     * @return JsonResponse
     */
    public function getMain(): JsonResponse
    {
        $info = AuthConfig::query()->select('id','name','logo_id')->with([
            'logo_one'=>function($query){
                $query->select('id','url','open');
            }
        ])->find(1)->toArray();
        $http = $this->getHttp();
        if($info['logo_one']['open'] == 1){
            $info['logo_url'] = $http.$info['logo_one']['url'];
        }else{
            $info['logo_url'] = $info['logo_one']['url'];
        }

        return $this->apiSuccess('',$info);
    }

    /**
     * 数据看板
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $v = "version()";
        $mysqlV= DB::select("select version()")[0]->$v;
        $info = [
            'model_count'=>AuthRule::query()->where('type',1)->count(),//模块数量
            'menu_count'=>AuthRule::query()->where('type',3)->count(),//可控菜单
            'project_count'=>AuthProject::query()->count(),// 当前项目数
            'log_count'=>AuthOperationLog::query()->count(), // 累计接口请求数
        ];
        $list = [
            [
                'name'=>'服务器版本',
                'value'=>php_uname('s').php_uname('r')
            ],
            [
                'name'=>'服务域名',
                'value'=>$this->getHttp()
            ],
            [
                'name'=>'请求页面时通信协议的名称和版本',
                'value'=>$_SERVER['SERVER_PROTOCOL']
            ],
            [
                'name'=>'服务器解译引擎',
                'value'=>$_SERVER['SERVER_SOFTWARE']
            ],
            [
                'name'=>'php版本',
                'value'=>PHP_VERSION
            ],
            [
                'name'=>'数据库版本',
                'value'=>config('database.default').$mysqlV
            ],
            [
                'name'=>'laravel版本',
                'value'=>app()::VERSION
            ],
            [
                'name'=>'项目路径',
                'value'=>DEFAULT_INCLUDE_PATH
            ],
            [
                'name'=>'PHP运行方式',
                'value'=>php_sapi_name()
            ],
            [
                'name'=>'最大上传限制',
                'value'=>get_cfg_var ("upload_max_filesize")?get_cfg_var ("upload_max_filesize"):"不允许"
            ],
            [
                'name'=>'最大执行时间',
                'value'=>get_cfg_var("max_execution_time")."秒 "
            ],
            [
                'name'=>'脚本运行占用最大内存',
                'value'=>get_cfg_var ("memory_limit")?get_cfg_var("memory_limit"):"无"
            ]
        ];
        $results = DB::select('
                        SELECT
                            SUM(CASE WHEN device = 1 THEN 1 ELSE 0 END) AS count_device_1,
                            SUM(CASE WHEN device = 2 THEN 1 ELSE 0 END) AS count_device_2
                        FROM lot_statistics
                    ');
        $info['ios_download'] = $results[0]->count_device_1;
        $info['android_download'] = $results[0]->count_device_2;

        $resStatistics = DB::table('statistics')->whereDate('created_at', date('Y-m-d'))->get();

        $info['ios_download_today'] = $resStatistics->where('device', 1)->count();
        $info['android_download_today'] = $resStatistics->where('device', 2)->count();

        // 历史访问次数最多城市
//        $maxCityRecord = IpView::query()->select('city', DB::raw('COUNT(*) as city_count'))
//            ->where('city', '<>', '')
//            ->groupBy('city')
//            ->orderByDesc('city_count')
//            ->first();
//        $info['max_record_city'] = '';
//        $info['max_record_num'] = 0;
//        if ($maxCityRecord) {
//            $info['max_record_city'] = $maxCityRecord->city;
//            $info['max_record_num'] = $maxCityRecord->city_count;
//        }

        // 今日明星城市
//        $todayCityRecord = IpView::query()->select('city', DB::raw('COUNT(*) as city_count'))
//            ->where('city', '<>', '')
//            ->groupBy('city')
//            ->whereDate('created_at', '=', date('Y-m-d'))
//            ->orderByDesc('city_count')
//            ->first();
//        $info['today_record_city'] = '';
//        $info['today_record_num'] = 0;
//        if ($maxCityRecord) {
//            $info['today_record_city'] = $todayCityRecord->city;
//            $info['today_record_num'] = $todayCityRecord->city_count;
//        }

        // 昨天访问量
        $info['yesterday_view_access'] = Redis::get('yesterday_view_access') ?? 0;

        $info['yesterday_view_access_uv'] = Redis::get('yesterday_view_access_uv') ?? 0;

        // 今天实时访问量
        $today_view_access = IpView::query()->whereDate('created_at', '=', date('Y-m-d'))
            ->count();
        $info['today_view_access'] = $today_view_access;

        $info['today_view_access_uv'] = IpViewUv::query()->whereDate('created_at', '=', date('Y-m-d'))
            ->count();

        // 今日注册
        $info['today_register_num'] = User::query()->whereDate('created_at', '=', date('Y-m-d'))
            ->whereIn("web_sign", ["48", "49"])
            ->count();

        // 历史访问量访问量
//        $info['history_view_access'] = Redis::get('history_view_access') ? (Redis::get('history_view_access')+$info['yesterday_view_access']+$today_view_access) : 0;

        return $this->apiSuccess('',['list'=>$list,'info'=>$info]);
    }

    /**
     * 接口请求图表数据
     * @return JsonResponse
     */
    public function getLogCountList(): JsonResponse
    {
        $data = AuthOperationLog::query()->whereBetween('created_at',[date("Y-m-d",strtotime("-7 day")).' 00:00:00',date('Y-m-d'). ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date,COUNT(*) as value')
            ->groupBy('date')->get()->toArray();

        return $this->apiSuccess('',$data);
    }

    public function getViewCountList($params)
    {
        // 当前月 直接查库
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        if ( $year == date('Y') && $month == date('m') ) {
            $dailyViews = IpView::query()->select(
                'year', 'month', 'day',
                DB::raw('COUNT(*) as value')
            )
                ->where('year', $year)
                ->where('month', $month)
                ->groupBy('day')
                ->get()->toArray();
            if ($dailyViews) {
                $data = [];
                foreach ($dailyViews as $k => $v) {
                    $data[$k]['date'] = $v['year'].'-'.$v['month'].'-'.$v['day'];
                    $data[$k]['value'] = $v['value'];
                }
                return $this->apiSuccess('', $data);
            }
            return $this->apiSuccess();
        } else {
            $dailyViews = IpViewsCount::query()
                ->select(['date', 'value'])
                ->whereYear('date', $year)->whereMonth('date', $month)
                ->orderBy('date')->get()->toArray();

            return $this->apiSuccess('', $dailyViews);
        }
    }

    /**
     * 下载量折线图
     * @param $params
     * @return JsonResponse
     */
    public function getDownloads($params): JsonResponse
    {
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');
        $dailyViews = Statistics::query()
            ->select(
            'device', 'created_at', DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('COUNT(*) as value')
            )
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('device', 'date')
            ->get()->toArray();
//        dd($dailyViews);
        if ($dailyViews) {
            $data = [];
            foreach ($dailyViews as $k => $v) {
                $data[$v['device']==1?'ios':'android'][$k]['date'] = Carbon::make($v['created_at'])->format("Y-m-d");
                $data[$v['device']==1?'ios':'android'][$k]['value'] = $v['value'];
            }
            if(!empty($data['ios'])) {
                sort($data['ios']);
                $iosDate = [];
                foreach ($data['ios'] as  $v) {
                    $iosDate[] = $v['date'];
                }
                foreach ($data['ios'] as $v) {
                    if ( !in_array($v['date'], $iosDate) ) {
                        $data['ios'][] = ["date" => $v['date'], "value" => 0];
                    }
                }
            }
            if(!empty($data['android'])) {
                sort($data['android']);
                $androidDate = [];
                foreach ($data['android'] as $v) {
                    $androidDate[] = $v['date'];
                }
                foreach ($data['android'] as $v) {
                    if ( !in_array($v['date'], $androidDate) ) {
                        $data['android'][] = ["date" => $v['date'], "value" => 0];
                    }
                }
            }

            return $this->apiSuccess('', $data);
        }
        return $this->apiSuccess();
    }
    /**
     * 转换编辑器内容
     * @param string $content
     * @return JsonResponse
     */
    public function setContentU(string $content): JsonResponse
    {
        $http = $this->getHttp();
        $info = $this->content($content);
        $urlArr = $info['urlArr'];
        $arr = [];
        foreach($urlArr as $k=>$v){
            $image_id = AuthImageModel::query()->insertGetId([
                'url'=>$v,
                'open'=>1,
                'status'=>0,
                'created_at'=> date('Y-m-d H:i:s')
            ]);
            $arr[] = [
                'id'=>$image_id,
                'url'=>$http.$v
            ];
        }
        $info['urlArr'] = $arr;
        return $this->apiSuccess('',$info);
    }

    /**
     * 转换编辑器内容
     * @param string $content
     * @return array
     */
    public function content(string $content): array
    {
        $project_id = (new TokenService())->my()->project_id;
        preg_match_all('/<img (.*?)+src=[\'"](.*?)[\'"]/i',$content,$matches);
        $img = "";
        $replacements = [];
        if(!empty($matches)) {
            //注意，上面的正则表达式说明src的值是放在数组的第三个中
            $img = $matches[2];
        }
        if (!empty($img)) {
            $http = $this->getHttp();
            $patterns= array();
            $replacements = array();
            $date = date('YmdH');
            iconv("UTF-8", "GBK",$date);
            if(!file_exists(public_path('upload/images') . '/' . $project_id)){
                mkdir(public_path('upload/images') . '/' . $project_id . '',0777,true);
            }
            if(!file_exists(public_path('upload/images') . '/' . $project_id. '/content')){
                mkdir(public_path('upload/images') . '/' . $project_id. '/content',0777,true);
            }
            $dir = public_path('upload/images') . '/' . $project_id . '/content/' . $date;
            if (!file_exists($dir)){
                mkdir($dir,0777,true);
            }
            foreach($img as $imgItem){
                $url = $this->getRandStr(20).rand(1,99999).'.png';
                if($fileInfo = @file_get_contents(str_replace('&amp;','&',$imgItem))){
                    file_put_contents($dir.'/'.$url,$fileInfo);
                    $replacements[] = '/upload/images/' .$project_id . '/content/' . $date.'/'.$url;
                    $img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";
                    $patterns[] = $img_new;
                }
            }
            //让数组按照key来排序
            ksort($patterns);
            ksort($replacements);
            $replacementsArr = [];
            foreach ($replacements as $k=>$v){
                $replacementsArr[] = $http.$v;
            }
            //替换内容
            $content = preg_replace($patterns,$replacementsArr, $content);
        }
        return [
            'content'=>$content,
            'urlArr'=>$replacements
        ];
    }
}
