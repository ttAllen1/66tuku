<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;
use Swoole\Process;

class CorpusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:corpus {--c|corpusType : 资料分类采集} {--corpusType2 : 资料分类采集} {--corpusType3 : 资料分类采集} {--C|corpusArticle : 资料初始化采集} {--corpusArticle2 : 资料初始化采集} {--u|corpusUpdate : 资料更新} {--corpusUpdate2 : 资料更新2} {--corpusUpdate3 : 资料更新3} {--y|yearUpdate : 年份更新} {--a|addUser : 添加所属用户} {--l|lotteryType=1 : 彩票类型:港彩=1,新澳彩=2,台彩=3,新彩=4,澳彩=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '资料：分类、列表、资料采集更新.';

    /**
     * @name  主机HOST
     * @var string
     */
//    static $host = 'https://49208.com';
    static $host = 'https://4920822.com'; //主机
//    static $host = 'https://h5.49217999.com:888'; //主机
    static $host2 = 'https://am.zlapi8.com'; //主机2
    static $host3 = 'https://48c.zlapi8.com'; //主机3
    static $host4 = 'http://246.49j.cc'; //主机4
    static $type = 4; //采集默认参数

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        try {
//            $this->test();exit;
        if ($this->option('corpusType'))
        {
            $this->corpusType($this->option('lotteryType'));
        }
        if ($this->option('corpusType2'))
        {
            $this->corpusType2();
        }
        if ($this->option('corpusType3'))
        {
            $this->corpusType3();
        }
        if ($this->option('corpusArticle2'))
        {
            $this->corpusArticle2($this->option('lotteryType'));
        }
        if ($this->option('corpusArticle'))
        {
            $this->corpusArticle($this->option('lotteryType'));
        }
        if ($this->option('corpusUpdate'))
        {
            $this->corpusUpdate($this->option('lotteryType'));
        }
        if ($this->option('yearUpdate'))
        {
            $this->yearUpdate($this->option('lotteryType'));
        }
        if ($this->option('addUser'))
        {
            $this->addUser($this->option('lotteryType'));
        }
        if ($this->option('corpusUpdate2')) {
            $this->corpusUpdate2();
        }
        if ($this->option('corpusUpdate3')) {
            $this->corpusUpdate3();
        }
//        } catch (\Exception $e) {
//            echo 'ERROR MSG!';
////            var_dump($e);
//            echo $e->getMessage();
//        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

    /**
     * 请求封装
     * @param $url
     * @param $params
     * @param string $method
     * @return mixed
     */
    private function request($url, $params, $method = 'GET')
    {
        try {
            $query = http_build_query($params);
            $options['http'] = [
                'timeout' => 60,
                'method' => $method,
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $query
            ];
            if ($method == 'GET')
            {
                $result = file_get_contents($url . '?' . $query);
            } else {
                $context = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
            }
            $data = json_decode($result, true);
            if (!$data)
            {
                throw new \Exception('data null');
            }
            return $data;
        } catch (\Exception $e) {
            sleep(3);
            echo "HTTP ERROR:" . $e->getMessage() . PHP_EOL;
            return $this->request($url, $params, $method);
        }
    }

    /**
     * 资料列表采集初始化
     * @param int $lotteryType
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    private function corpusType(int $lotteryType)
    {
        if (DB::table('corpus_types')->where('lotteryType', $lotteryType)->first())
        {
            exit("已经采集！\n");
        }
        $response = Http::retry(10, 100)->get(self::$host . '/unite49/h5/article/listArticleType', [
            'type' => self::$type,
            'lotteryType' => $lotteryType,
        ])->throw()->json();
        $dataList = $response['data']['list'];
        for ($i = count($dataList)-1; $i >= 0; $i--)
        {
            $corpusList = $this->corpusList([
                'articleTypeId' => $dataList[$i]['articleTypeId'],
                'pageNum' => 1,
                'pageSize' => 1
            ]);
            if (time() - strtotime($corpusList[0]['createTimeStr']) > 60*60*24*3)
            {
                $collectionType = '1';
            } else {
                $collectionType = '2';
            }
            $insertData[] = ['lotteryType' => strval($lotteryType), 'corpusTypeName' => $dataList[$i]['articleTypeName'], 'sourceId' => $dataList[$i]['articleTypeId'], 'collectionType' => $collectionType];
            echo "正在采集：" . $dataList[$i]['articleTypeName'] . "\n";
        }
        if (DB::table('corpus_types')->insert($insertData))
        {
            echo "采集完成！\n";
        } else {
            echo "采集失败！\n";
        }
    }

    private function corpusType2()
    {
////        $result = $this->request(self::$host2 . '/api/art', ['type' => 'xg']);
//        $result = $this->request(self::$host2 . '/api/art', []);
//        foreach ($result['data'] as &$itemSort)
//        {
//            $thereAre = DB::table('corpus_types')->where('corpusTypeName', $itemSort['name'])->first();
//            if ($thereAre) {
////                foreach ($itemSort as &$item2)
////                {
//                $itemSort['user_id'] = $thereAre->user_id;
//                $itemSort['table_idx'] = $thereAre->table_idx;
////                }
//            } else {
//                dd($itemSort);exit;
//            }
//        }
//        $newdsfjdsa = [];
//        foreach ($result['data'] as $ifd)
//        {
//            array_unshift($newdsfjdsa, $ifd);
//        }
////        dd($newdsfjdsa);
////        exit;
////
//        foreach ($newdsfjdsa as $item2)
//        {
//            $query = ['cateid' => $item2['id']];
//            $list = $this->request(self::$host2 . '/api/artlist', $query, 'POST');
//            if ($list['data']['totalPages'] > 22) {
//                $collectionType = 2;
//            } else {
//                $collectionType = 1;
//            }
//            $insData = [];
//            $insData['lotteryType'] = 5;
//            $insData['corpusTypeName'] = $item2['name'];
//            $insData['sourceId'] = $item2['id'];
//            $insData['collectionType'] = $collectionType;
//            $insData['user_id'] = $item2['user_id'];
//            $insData['table_idx'] = $item2['table_idx'];
//            $insData['website'] = 2;
//            $insData['year'] = '[{"year": "2023"}]';
//            DB::table('corpus_types')->insert($insData);
//            echo $item2['name'] . PHP_EOL;
//        }
//        dd($result['data']);

//        //583
//        $result = $this->request(self::$host2 . '/api/art', ['type' => 'xg']);
//        $result2 = $this->request(self::$host2 . '/api/art', ['type' => 'am']);
//        $result3 = $this->request(self::$host2 . '/api/art', ['type' => 'tw']);
//        $result4 = $this->request(self::$host2 . '/api/art', ['type' => 'xjp']);
//
//        $result = array_map(function($item){
//            $item['lotteryType'] = 1;
//            return $item;
//        }, $result['data']);
//        $result2 = array_map(function($item){
//            $item['lotteryType'] = 2;
//            return $item;
//        }, $result2['data']);
//        $result3 = array_map(function($item){
//            $item['lotteryType'] = 3;
//            return $item;
//        }, $result3['data']);
//        $result4 = array_map(function($item){
//            $item['lotteryType'] = 4;
//            return $item;
//        }, $result4['data']);
//
//        $mergeRes = array_merge($result, $result2, $result3, $result4);
//
//        $sortRes = [];
//        $kk = 0;
//        foreach ($mergeRes as $itemMer)
//        {
//            $status = 0;
//            foreach ($sortRes as $key => $item2)
//            {
//                if ($itemMer['name'] == $item2[0]['name'])
//                {
//                    $sortRes[$key][] = $itemMer;
//                    $status = 1;
//                    break;
//                }
//            }
//            if ($status == 0)
//            {
//                $sortRes[$kk][] = $itemMer;
//                $kk++;
//            }
//        }
//        foreach ($sortRes as &$itemSort)
//        {
//            $thereAre = DB::table('corpus_types')->where('corpusTypeName', $itemSort[0]['name'])->first();
//            if ($thereAre) {
//                foreach ($itemSort as &$item2)
//                {
//                    $item2['user_id'] = $thereAre->user_id;
//                    $item2['table_idx'] = $thereAre->table_idx;
//                }
//            }
//        }
//        $new2 = [];
//        foreach ($sortRes as $item3)
//        {
//            if (isset($item3[0]['table_idx']))
//            {
//                $new2[0][] = $item3;
//            } else {
//
//                $new2[1][] = $item3;
//            }
//        }
//
//        file_put_contents('type.txt', serialize($new2));
//        exit;
        exit;
        $new2 = unserialize(file_get_contents('type.txt'));
//        file_put_contents('type2.txt', json_encode($new2[1]));exit;
//        dd($new2[1]);
//        $n = 0;
////        foreach ($new2 as $it)
////        {
//            foreach ($new2[1] as $it2){
//                $n += count($it2);
//            }
////        }
//        echo $n;exit;
//        file_put_contents('typejson.txt', json_encode($new2));exit;
//        dd($new2);

//        foreach ($new2[0] as $item)
//        {
//            foreach ($item as $item2)
//            {
//                $type = [
//                    1 => 'xg',
//                    2 => 'am',
//                    3 => 'tw',
//                    4 => 'xjp'
//                ];
//                $query = ['cateid' => $item2['id']];
//                $query['type'] = $type[$item2['lotteryType']];
//                $list = $this->request(self::$host2 . '/api/artlist', $query, 'POST');
//                if ($list['data']['totalPages'] > 22) {
//                    $collectionType = 2;
//                } else {
//                    $collectionType = 1;
//                }
//                $insData = [];
//                $insData['lotteryType'] = $item2['lotteryType'];
//                $insData['corpusTypeName'] = $item2['name'];
//                $insData['sourceId'] = $item2['id'];
//                $insData['collectionType'] = $collectionType;
//                $insData['user_id'] = $item2['user_id'];
//                $insData['table_idx'] = $item2['table_idx'];
//                $insData['website'] = 2;
//                DB::table('corpus_types')->insert($insData);
//                echo $item2['name'] . PHP_EOL;
//            }
//        }
//        echo 'ok';exit;
//
////        echo count($new2[1]);exit;
//        $tables = [3, 6, 8, 28, 25, 9, 5, 4];//8
//        dd($new2[1]);
        foreach ($new2[1] as $key => &$item)
        {
            if($key < 9)
            {
                $tablesIdx = 3;
            } else if($key < 18) {
                $tablesIdx = 6;
            } else if($key < 27) {
                $tablesIdx = 8;
            } else if($key < 30) {
                $tablesIdx = 28;
            } else if($key < 33) {
                $tablesIdx = 25;
            } else if($key < 36) {
                $tablesIdx = 9;
            } else if($key < 39) {
                $tablesIdx = 5;
            } else {
                $tablesIdx = 4;
            }

            foreach ($item as &$item2)
            {
                $item2['table_idx'] = $tablesIdx;
            }

        }

        foreach ($new2[1] as $item4)
        {
            $insUser = [
                'name' => $item4[0]['name'],
                'nickname' => $item4[0]['name'],
                'account_name' => $item4[0]['name'],
                'password' => bcrypt('Aa123321.'),
                'invite_code' => $this->randString(),
                'register_at' => date('Y-m-d H:i:s'),
                'last_login_at' => date('Y-m-d H:i:s'),
                'sex' => 1,
                'system' => 1,
            ];
            $uid = DB::table('users')->insertGetId($insUser);
            foreach ($item4 as $item5)
            {
                $type = [
                    1 => 'xg',
                    2 => 'am',
                    3 => 'tw',
                    4 => 'xjp'
                ];
                $query = ['cateid' => $item5['id']];
                $query['type'] = $type[$item5['lotteryType']];
                $list = $this->request(self::$host2 . '/api/artlist', $query, 'POST');
                if ($list['data']['totalPages'] > 22) {
                    $collectionType = 2;
                } else {
                    $collectionType = 1;
                }
                $insData = [];
                $insData['lotteryType'] = $item5['lotteryType'];
                $insData['corpusTypeName'] = $item5['name'];
                $insData['sourceId'] = $item5['id'];
                $insData['collectionType'] = $collectionType;
                $insData['user_id'] = $uid;
                $insData['table_idx'] = $item5['table_idx'];
                $insData['website'] = 2;
                DB::table('corpus_types')->insert($insData);
                echo $item5['name'] . PHP_EOL;
            }
        }
        echo 'ok';exit;
    }

    public function corpusType3()
    {
//        $columns = '[{"name":"中彩规律","id":64},{"name":"固定规律","id":65},{"name":"解说彩票","id":66},{"name":"买码建议","id":67},{"name":"彩票茶话","id":68},{"name":"特码规律","id":69},{"name":"规律秘诀","id":70},{"name":"外围博彩","id":72},{"name":"正版资料","id":3},{"name":"权威资料","id":4},{"name":"香港挂牌","id":1},{"id":5,"name":"看图解码"},{"id":171,"name":"新版看图解"},{"id":93,"name":"新版精选彩"},{"id":7,"name":"幸运彩图"},{"id":172,"name":"新版幸运彩"},{"id":92,"name":"解藏宝图"},{"id":87,"name":"免费大图"},{"id":149,"name":"红姐图库"},{"id":150,"name":"六合图库"},{"id":232,"name":"贴士彩图"},{"id":233,"name":"萍果日报"},{"id":234,"name":"玄机资料"},{"id":151,"name":"管家婆图"},{"id":152,"name":"呱呱图库"},{"id":153,"name":"118图库"},{"id":215,"name":"富婆彩图"},{"id":240,"name":"四不像图"},{"id":173,"name":"二四六图"},{"id":163,"name":"神码图库"},{"id":164,"name":"四九图库"},{"id":165,"name":"马会图库"},{"id":139,"name":"赌神图库"},{"id":166,"name":"平特图库"},{"id":167,"name":"中特图库"},{"id":168,"name":"天下图库"},{"id":169,"name":"九龙图库"},{"id":170,"name":"玄机图库"},{"id":202,"name":"新报玄机"},{"id":216,"name":"曾道彩图"},{"id":203,"name":"马会彩图"},{"id":123,"name":"挂牌大全"},{"id":147,"name":"权威彩图"},{"id":144,"name":"正版彩圖"},{"id":135,"name":"黑白Ｂ图"},{"id":136,"name":"黑白Ｃ图"},{"id":137,"name":"黑白Ｄ图"},{"id":138,"name":"黑白Ｅ图"},{"id":161,"name":"黑白Ｆ图"},{"id":158,"name":"奇计准图"},{"id":159,"name":"金牌彩图"},{"id":160,"name":"诗书报图"},{"id":124,"name":"跑狗藏宝"},{"id":125,"name":"美女彩图"},{"id":217,"name":"贴士皇图"},{"id":126,"name":"福星好图"},{"id":127,"name":"精版好图"},{"id":128,"name":"精选采图"},{"id":129,"name":"平特大图"},{"id":130,"name":"创富专刊"},{"id":131,"name":"经典彩图"},{"id":132,"name":"独家彩图"},{"id":133,"name":"香港来料"},{"id":140,"name":"白小姐图库"},{"id":141,"name":"牛派精报"},{"id":142,"name":"凤凰书刊"},{"id":143,"name":"原装港报"},{"id":145,"name":"精准图库"},{"id":146,"name":"高级彩图"},{"id":148,"name":"发财好图"},{"id":81,"name":"综合挂牌"},{"id":204,"name":"凌波微步"},{"id":205,"name":"广州日报"},{"id":235,"name":"藏宝好图"},{"id":236,"name":"青龙报图"},{"id":237,"name":"论坛高手心水"},{"id":230,"name":"男人味道"},{"id":206,"name":"谜语解特肖"},{"id":231,"name":"⑥合皇图"},{"id":154,"name":"漫画玄机"},{"id":259,"name":"金旺信箱"},{"id":241,"name":"红字精解"},{"id":244,"name":"东城西就"},{"id":239,"name":"马会传真"},{"id":242,"name":"(新版)管家婆"},{"id":162,"name":"正版四不像"},{"id":207,"name":"三十六码"},{"id":208,"name":"三十码中"},{"id":209,"name":"生活幽默"},{"id":199,"name":"财神系列"},{"id":210,"name":"今日闲情"},{"id":211,"name":"广州传真"},{"id":212,"name":"图解平特"},{"id":157,"name":"花仙子图"},{"id":156,"name":"熊出没图"},{"id":213,"name":"脑筋急图"},{"id":218,"name":"发大财图库"},{"name":"本站推荐","id":22},{"name":"报刊大全","id":17},{"name":"高手解密","id":29},{"name":"高手解挂","id":2},{"name":"全年资料","id":76},{"name":"生肖属性","id":75},{"name":"开奖日期","id":77}]';
//        $columnsArr = json_decode($columns, true);
        $result = $this->request(self::$host2 . '/api/art', ['type' => 'oldam']);
        $columnsArr = $result['data'];
//        dd($columnsArr);
//        $commonColumns = array_slice($columnsArr, 0, 8);
//        for ($columni = 0; $columni < 8; $columni++) {
//            unset($columnsArr[$columni]);
//        }
        //$columnsArr 手动改 $columnsArr 香港  $commonColumns 公共
        $dsafa = 0;
        foreach ($columnsArr as $commonValue)
        {
            $dsafa++;
            $doesItExist = DB::table('corpus_types')->where('corpusTypeName', $commonValue['name'])->first();
            if ($doesItExist) {
                $user_id = $doesItExist->user_id;
                $table_idx = $doesItExist->table_idx;
            } else {
                $insUser = [
                    'name' => $commonValue['name'],
                    'nickname' => $commonValue['name'],
                    'account_name' => $commonValue['name'],
                    'password' => bcrypt('Aa123321.'),
                    'invite_code' => $this->randString(),
                    'register_at' => date('Y-m-d H:i:s'),
                    'last_login_at' => date('Y-m-d H:i:s'),
                    'sex' => 1,
                    'system' => 1,
                ];
                $user_id = DB::table('users')->insertGetId($insUser);
                if ($dsafa % 2 == 0) {
                    $table_idx = '3'; //手动改3和8 3common 8hk
                } else {
                    $table_idx = '6'; //手动改3和8 3common 8hk
                }
            }
            $insData = [];
            $insData['lotteryType'] = 7; //手动改1到5 1香港
            $insData['corpusTypeName'] = $commonValue['name'];
            $insData['sourceId'] = $commonValue['id'];
            $insData['collectionType'] = 1;
            $insData['user_id'] = $user_id;
            $insData['table_idx'] = $table_idx;
            $insData['website'] = 2;
            $insData['website_type'] = 0;
            $insData['is_index'] = 0;
            DB::table('corpus_types')->insert($insData);
            echo $commonValue['name'] . PHP_EOL;
        }
        exit('ok');
    }

    /**
     * 资料列表获取
     * @param array $data
     * @return mixed
     */
    private function corpusList(array $data)
    {
        $params = array_merge($data, ['type' => self::$type]);
        $corpusList = $this->request(self::$host . '/unite49/h5/article/search', $params);
        return $corpusList['data']['list'];
    }

    /**
     * 资料更新
     * @param int $lotteryType
     * @return void
     */
    private function corpusUpdate(int $lotteryType)
    {
        while (True) {
            $workNumber = 10;
            $corpusTypes = DB::table('corpus_types')->where(['lotteryType' => $lotteryType, 'website' => 1])->get()->toArray();
            $i = 0;
            $workCorpusTypes = [];
            foreach ($corpusTypes as $k => $corpusType) {
                $workCorpusTypes[$i][] = (array)$corpusType;
                if (($k + 1) % $workNumber == 0) {
                    $i++;
                }
            }
            foreach ($workCorpusTypes as $workCorpusType) {
                $worker = [];
                foreach ($workCorpusType as $corpusItem) {
                    echo $corpusItem['corpusTypeName'] . PHP_EOL;
                    do {
                        $process = new Process(function () use ($corpusItem) {
                            $corpusLists = $this->corpusList([
                                'articleTypeId' => $corpusItem['sourceId'],
                                'pageNum' => 1,
                                'pageSize' => 100,
                                'year' => date('Y'),
                            ]);
//                        var_dump($corpusLists);
                            if (count($corpusLists) > 0) {
                                if ($corpusItem['collectionType'] == 2 && $corpusLists[0]['articleId'] != $corpusItem['lastArticleId']) {
                                    foreach ($corpusLists as $corpusList) {
                                        if ($corpusList['articleId'] != $corpusItem['lastArticleId']) {
                                            $corpusArticleInfo = $this->corpusArticleInfo($corpusList['articleId']);
                                            $data = [
                                                'title' => $this->strReplace($corpusArticleInfo['title'], 1),
                                                'content' => $this->strReplace($corpusArticleInfo['description'], 2),
                                                'corpusTypeId' => $corpusItem['id'],
                                                'sourceArticleId' => $corpusList['articleId'],
                                                'year' => $corpusList['year'],
                                                'user_id' => $corpusItem['user_id'],
                                                'created_at' => date('Y-m-d H:i:s', strtotime($corpusList['createTimeStr'])),
                                            ];

                                            DB::table('users')->where('id', $corpusItem['user_id'])->increment('releases');

                                            echo "正在采集：" . $corpusItem['corpusTypeName'] . ' | ' . $corpusItem['sourceId'] . ' | ' . $corpusItem['id'] . ' | ' . $data['year'] . ' | ' . $data['title'] . ' | ' . $corpusList['articleId'] . PHP_EOL;
                                            DB::table('corpus_articles' . $corpusItem['table_idx'])->insert($data);
                                        } else {
                                            break;
                                        }
                                    }
                                } else if ($corpusItem['collectionType'] == 1) {
                                    foreach ($corpusLists as $corpusList) {
                                        $corpusArticleInfo = $this->corpusArticleInfo($corpusList['articleId']);

                                        $articleExist = DB::table('corpus_articles' . $corpusItem['table_idx'])
                                            ->where(['sourceArticleId' => $corpusList['articleId'], 'corpusTypeId' => $corpusItem['id']])
                                            ->first();
                                        if (!$articleExist)
                                        {
                                            DB::table('users')->where('id', $corpusItem['user_id'],)->increment('releases');
                                        }

                                        echo "正在更新：" . $corpusArticleInfo['title'] . PHP_EOL;
                                        DB::table('corpus_articles' . $corpusItem['table_idx'])->updateOrInsert(
                                            ['sourceArticleId' => $corpusList['articleId'], 'corpusTypeId' => $corpusItem['id']],
                                            [
                                                'title' => $this->strReplace($corpusArticleInfo['title'], 1),
                                                'content' => $this->strReplace($corpusArticleInfo['description'], 2),
                                                //                                                'corpusTypeId' => $corpusItem['id'],
                                                //                                                'sourceArticleId' => $corpusList['articleId'],
                                                'year' => $corpusList['year'],
                                                'user_id' => $corpusItem['user_id'],
                                                'created_at' => date('Y-m-d H:i:s', strtotime($corpusList['createTimeStr']))
                                            ]
                                        );
                                    }
                                }
                                if (isset($corpusLists[0]))
                                {
                                    $updateData = ['lastArticleId' => $corpusLists[0]['articleId']];
                                    $year = (string)date('Y', strtotime($corpusLists[0]['createTimeStr']));
                                    $yearolds = json_decode($corpusItem['year'], true);
                                    $yearoldsCheck = [];
                                    foreach ($yearolds as $yearold) {
                                        $yearoldsCheck[] = $yearold['year'];
                                    }
                                    if (!in_array($year, $yearoldsCheck)) {
//                                        $yearolds[] = $year;
                                        $yearolds[] = ['year' => $year];
                                        $updateData['year'] = json_encode($yearolds);
                                        DB::table('corpus_articles' . $corpusItem['table_idx'])->where([
                                            ['corpusTypeId', '=', $corpusItem['id']],
                                            ['created_at', '<', (intval($year)-4) . '-01-01 00:00:00']
                                        ])->delete();
                                    }
                                    DB::table('corpus_types')->where('id', $corpusItem['id'])->update($updateData);
                                }
                            }
                        });
                        $pid = $process->start();
                        $worker[$pid] = $process;
                    } while (!$pid);
                }
                foreach ($worker as $work) {
                    $status = Process::wait(true);
                    echo "Recycled #{$status['pid']}, code={$status['code']}, signal={$status['signal']}" . PHP_EOL;
                }
            }
            sleep(60*60);
        }
    }

    private function updateProcess($ct)
    {
        $processFun = function() use($ct)
        {
            DB::purge('mysql');
            DB::reconnect('mysql');
            $host = self::$host3;
            $type = $this->lotteryType($ct->lotteryType);
            $parameter = ['cateid' => $ct->sourceId];
            if ($type != 'am2')
            {
                $parameter['type'] = $type;
                $host = self::$host2;
            }
            $pushArr = [];
            if ($ct->collectionType == 1)
            {
                //更新
                $result = $this->request($host . '/api/artlist', $parameter);
                for ($i = 1; $i <= $result['data']['totalPages']; $i++)
                {
                    $parameter['page'] = $i;
                    $result = $this->request($host . '/api/artlist', $parameter);
                    foreach ($result['data']['data'] as $item)
                    {
                        $pushArr[] = $item;
                    }
                }
                echo '【' . $ct->corpusTypeName . '】更新数据：' . count($pushArr) . '条' . PHP_EOL;
            } else {
                //增加
                $result = $this->request($host . '/api/artlist', $parameter);
                $findThe = false;
                foreach ($result['data']['data'] as $item)
                {
                    if ($item['id'] != $ct->lastArticleId)
                    {
                        $pushArr[] = $item;
                    } else {
                        $findThe = true;
                        break;
                    }
                }
                if (!$findThe)
                {
                    for ($i = 2; $i < $result['data']['totalPages']; $i++)
                    {
                        $parameter['page'] = $i;
                        $result = $this->request($host . '/api/artlist', $parameter);
                        foreach ($result['data']['data'] as $item)
                        {
                            if ($item['id'] != $ct->lastArticleId)
                            {
                                $pushArr[] = $item;
                            } else {
                                break 2;
                            }
                        }

                    }
                }
                echo '【' . $ct->corpusTypeName . '】增加数据：' . count($pushArr) . '条' . PHP_EOL;
            }
            if (count($pushArr) > 0)
            {
                for ($i = count($pushArr)-1; $i >= 0; $i--)
                {
                    $corpusTypes = [
                        'title' => $pushArr[$i]['title'],
                        'content' => $pushArr[$i]['content'],
                        //                        'corpusTypeId' => $ct->id,
                        //                        'sourceArticleId' => $pushArr[$i]['id'],
                        'year' => date('Y'),
                        'user_id' => $ct->user_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $articleExist = DB::table('corpus_articles' . $ct->table_idx)
                        ->where(['sourceArticleId' => $pushArr[$i]['id'], 'corpusTypeId' => $ct->id])
                        ->first();
//                    if ($pushArr[$i]['id'] == 2755)
//                    {
//                        echo $ct->id.PHP_EOL;
//                        var_dump($pushArr[$i]['id']);
//                        var_dump($corpusTypes);
//                        echo $ct->id.PHP_EOL;
//                        $articleExist = DB::table('corpus_articles' . $ct->table_idx)
//                            ->where(['sourceArticleId' => $pushArr[$i]['id'], 'corpusTypeId' => $ct->id])
//                            ->first();
//                        var_dump($articleExist);
//                        var_dump($ct);
//                        echo $ct->id.PHP_EOL;
////                        exit;
//                    }
                    if (!$articleExist)
                    {
                        DB::table('users')->where('id', $ct->user_id)->increment('releases');
                    }
                    DB::table('corpus_articles' . $ct->table_idx)->updateOrInsert(
                        ['sourceArticleId' => $pushArr[$i]['id'], 'corpusTypeId' => $ct->id],
                        $corpusTypes
                    );
//                    echo $pushArr[$i]['title'] . PHP_EOL;
                }

                $updateData = ['lastArticleId' => $pushArr[0]['id']];
                $year = (string)date('Y');
                $yearolds = json_decode($ct->year, true);
                $yearoldsCheck = [];
                foreach ($yearolds as $yearold)
                {
                    $yearoldsCheck[] = $yearold['year'];
                }
                if (!in_array($year, $yearoldsCheck))
                {
                    $yearolds[] = ['year' => $year];
                    $updateData['year'] = json_encode($yearolds);
                }
                DB::table('corpus_types')->where('id', $ct->id)->update($updateData);
            }
        };
        $this->pushWork($processFun);
    }

    private function corpusUpdate2()
    {
        while (true)
        {
            try {
//            $hour = date('H'); //当前时
//            $point = date('i'); //当前分
//            if (($hour == 21 && $point > 50) || ($hour == 23 && $point > 10) || ($hour == 10 && $point > 10) || ($hour == 13 && $point > 10))
//            {
                $workNumber = 10;
                $corpusTypes = DB::table('corpus_types')
                    ->where(['website' => 2, 'website_type' => 0])
                    ->where('lotteryType', '<>', 5)
                    ->orderByDesc('id')
                    ->get()
                    ->toArray();
                if (count($corpusTypes) < $workNumber) {
                    $workNumber = count($corpusTypes);
                }
                for ($i = 0; $i < $workNumber; $i++) {
//                    echo $corpusTypes[$i]->corpusTypeName . PHP_EOL;
                    $this->updateProcess($corpusTypes[$i]);
                }
                while (true) {
                    $status = Process::wait(true);
                    unset($this->worker[$status['pid']]);
                    if ($i >= count($corpusTypes)) {
                        if (count($this->worker) == 0) {
                            echo 'ok' . PHP_EOL;
                            break;
                        }
                        continue;
                    }
                    $this->updateProcess($corpusTypes[$i]);
                    $i++;
                }
//            }
                echo '当前时间：' . date('Y-m-d H:i:s') . PHP_EOL;
                sleep(60 * 70);
            } catch (\Exception $exception) {
                sleep(60 * 5);
            }
        }
    }

    private function updateProcess3($ct)
    {
        $processFun = function() use($ct)
        {
            DB::purge('mysql');
            DB::reconnect('mysql');
            $host = self::$host4;
            $parameter = ['get' => 'list', 'ListId' => $ct->sourceId, 'page' => 1];
            $pushArr = [];
            if ($ct->collectionType == 1)
            {
                // 更新暂无
                $result = $this->request($host . '/Api', $parameter);
                if ($result['MaxPage'] > 10) {
                    $maxPage = 10;
                } else {
                    $maxPage = $result['MaxPage'];
                }

                for ($i = 1; $i <= $maxPage; $i++) {
                    $parameter['page'] = $i;
                    $result = $this->request($host . '/Api', $parameter);
                    foreach ($result['ShowList'] as $item) {
                        $infoParameter = ['get' => 'text', 'Id' => $item['id']];
                        $articleInfo = $this->request($host . '/Api', $infoParameter);
                        $pushArr[] = [
                            'title' => $articleInfo['Title'],
                            'content' => $articleInfo['Text'],
                            'year' => date('Y', strtotime($articleInfo['AddTime'])),
                            'created_at' => date('Y-m-d H:i:s', strtotime($articleInfo['AddTime'])),
                            'id' => $articleInfo['id'],
                        ];
                    }
                }

            } else {
                // 增加
                $result = $this->request($host . '/Api', $parameter);
                if ($result['MaxPage'] > 30) {
                    $maxPage = 30;
                } else {
                    $maxPage = $result['MaxPage'];
                }
                $articleFirst = DB::table('corpus_articles' . $ct->table_idx)
                    ->where(['corpusTypeId' => $ct->id])
                    ->first();

                // 判断是否为第一次，第一次直接采集全部数据
                if (!$articleFirst) {

                    for ($i = 1; $i <= $maxPage; $i++) {
                        $parameter['page'] = $i;
                        $result = $this->request($host . '/Api', $parameter);
                        foreach ($result['ShowList'] as $item) {
                            $infoParameter = ['get' => 'text', 'Id' => $item['id']];
                            $articleInfo = $this->request($host . '/Api', $infoParameter);
//                            echo '抓取：' . $articleInfo['Title'] . PHP_EOL;
                            $pushArr[] = [
                                'title' => $articleInfo['Title'],
                                'content' => $articleInfo['Text'],
                                'year' => date('Y', strtotime($articleInfo['AddTime'])),
                                'created_at' => date('Y-m-d H:i:s', strtotime($articleInfo['AddTime'])),
                                'id' => $articleInfo['id'],
                            ];
                        }
                    }

                } else {

                    $findThe = false;
                    foreach ($result['ShowList'] as $item) {
                        if ($item['id'] != $ct->lastArticleId) {
                            $infoParameter = ['get' => 'text', 'Id' => $item['id']];
                            $articleInfo = $this->request($host . '/Api', $infoParameter);
                            $pushArr[] = [
                                'title' => $articleInfo['Title'],
                                'content' => $articleInfo['Text'],
                                'year' => date('Y', strtotime($articleInfo['AddTime'])),
                                'created_at' => date('Y-m-d H:i:s', strtotime($articleInfo['AddTime'])),
                                'id' => $articleInfo['id'],
                            ];
                        } else {
                            $findThe = true;
                            break;
                        }
                    }
                    if (!$findThe) {
                        for ($i = 2; $i < $maxPage; $i++) {
                            $parameter['page'] = $i;
                            $result = $this->request($host . '/Api', $parameter);
                            foreach ($result['ShowList'] as $item) {
                                if ($item['id'] != $ct->lastArticleId) {
                                    $infoParameter = ['get' => 'text', 'Id' => $item['id']];
                                    $articleInfo = $this->request($host . '/Api', $infoParameter);
                                    $pushArr[] = [
                                        'title' => $articleInfo['Title'],
                                        'content' => $articleInfo['Text'],
                                        'year' => date('Y', strtotime($articleInfo['AddTime'])),
                                        'created_at' => date('Y-m-d H:i:s', strtotime($articleInfo['AddTime'])),
                                        'id' => $articleInfo['id'],
                                    ];
                                } else {
                                    break 2;
                                }
                            }

                        }
                    }
                }
                echo '【' . $ct->corpusTypeName . '】增加数据：' . count($pushArr) . '条' . PHP_EOL;
            }
            if (count($pushArr) > 0)
            {
                for ($i = count($pushArr)-1; $i >= 0; $i--)
                {
                    $corpusTypes = [
                        'title' => $pushArr[$i]['title'],
                        'content' => $pushArr[$i]['content'],
                        'year' => $pushArr[$i]['year'],
                        'user_id' => $ct->user_id,
                        'created_at' => $pushArr[$i]['created_at'],
                    ];
                    $articleExist = DB::table('corpus_articles' . $ct->table_idx)
                        ->where(['sourceArticleId' => $pushArr[$i]['id'], 'corpusTypeId' => $ct->id])
                        ->first();
                    if (!$articleExist)
                    {
                        DB::table('users')->where('id', $ct->user_id)->increment('releases');
                    }
                    DB::table('corpus_articles' . $ct->table_idx)->updateOrInsert(
                        ['sourceArticleId' => $pushArr[$i]['id'], 'corpusTypeId' => $ct->id],
                        $corpusTypes
                    );
//                    echo '存入：' . $pushArr[$i]['title'] . PHP_EOL;
                }

                $updateData = ['lastArticleId' => $pushArr[0]['id']];
                $year = (string)$pushArr[0]['year'];
                $yearolds = json_decode($ct->year, true);
                if ($yearolds) {
                    $yearoldsCheck = [];
                    foreach ($yearolds as $yearold) {
                        $yearoldsCheck[] = $yearold['year'];
                    }
                } else {
                    $yearoldsCheck = [];
                    $yearolds = [];
                }
                if (!in_array($year, $yearoldsCheck))
                {
                    $yearolds[] = ['year' => $year];
                    $updateData['year'] = json_encode($yearolds);
                }
                DB::table('corpus_types')->where('id', $ct->id)->update($updateData);
            }
        };
        $this->pushWork($processFun);
    }

    private function corpusUpdate3()
    {

        while (true)
        {
//            $hour = date('H'); //当前时
//            $point = date('i'); //当前分
//            if (($hour == 21 && $point > 50) || ($hour == 23 && $point > 10) || ($hour == 10 && $point > 10) || ($hour == 13 && $point > 10))
//            {
            $workNumber = 10;
            $corpusTypes = DB::table('corpus_types')
                ->where(['website' => 2, 'website_type' => 1])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            if (count($corpusTypes) < $workNumber)
            {
                $workNumber = count($corpusTypes);
            }
            for ($i = 0; $i < $workNumber; $i++) {
//                    echo $corpusTypes[$i]->corpusTypeName . PHP_EOL;
                $this->updateProcess3($corpusTypes[$i]);
            }
            while (true) {
                $status = Process::wait(true);
                unset($this->worker[$status['pid']]);
                if ($i >= count($corpusTypes)) {
                    if (count($this->worker) == 0) {
                        echo 'ok' . PHP_EOL;
                        break;
                    }
                    continue;
                }
                $this->updateProcess3($corpusTypes[$i]);
                $i++;
            }
//            }
            echo '当前时间：' . date('Y-m-d H:i:s') . PHP_EOL;
            sleep(60*70);
        }
    }

    /**
     * 资料采集初始化
     * @param int $lotteryType
     * @return void
     */
    private function corpusArticle(int $lotteryType)
    {
//        if (DB::table('corpus_articles')->first())
//        {
//            exit("已经采集！\n");
//        }
        exit;
        $work_number = 10;
        $years = ['2019', '2020', '2021', '2022', '2023'];
        $corpusTypes = DB::table('corpus_types')->where('lotteryType', $lotteryType)->get()->toArray();

        $table = new \Swoole\Table($work_number);
        $table->column('title', \Swoole\Table::TYPE_STRING, 25600);
        $table->column('content', \Swoole\Table::TYPE_STRING, 409600);
        $table->column('corpusTypeId', \Swoole\Table::TYPE_INT);
        $table->column('sourceArticleId', \Swoole\Table::TYPE_INT);
        $table->column('year', \Swoole\Table::TYPE_STRING, 128);
        $table->column('created_at', \Swoole\Table::TYPE_STRING, 128);
        $table->create();

        foreach ($corpusTypes as $corpusType)
        {
            if ((in_array($corpusType->sourceId, ['8657', '8641'])) || $corpusType->id < 216)
            {
                continue;
            }
            $lastCorpusId = 0;
            foreach ($years as $year)
            {
                if (in_array($corpusType->sourceId, ['8266']) && in_array($year, ['2019','2020','2021']))
                {
                    continue;
                }
                $insertData = [];
                $corpusList = $this->corpusList([
                    'articleTypeId' => $corpusType->sourceId,
                    'pageNum' => 1,
                    'pageSize' => 10000,
                    'year' => $year,
                ]);
                $countCorpusList = count($corpusList);
                if ($countCorpusList > 0)
                {
                    $lastCorpusId = $corpusList[0]['articleId'];
                    $workData = [];
                    $countNumber = 0;
                    $keyNumber = 0;
                    for ($i = $countCorpusList - 1; $i >= 0; $i--)
                    {
                        $workData[$keyNumber][] = $corpusList[$i];
                        $countNumber++;
                        if (($countNumber % $work_number) == 0)
                        {
                            $keyNumber++;
                        }
                    }
                    foreach ($workData as $v1)
                    {
                        $worker = [];
                        foreach ($v1 as $k => $v2)
                        {
                            $obj = $this;
                            do
                            {
                                $process = new Process(function (Process $proc) use ($v2, $year, $corpusType, $obj) {
                                    $corpusArticleInfo = $obj->corpusArticleInfo($v2['articleId']);
                                    $data = [
                                        'title' => $obj->strReplace($corpusArticleInfo['title'], 1),
                                        'content' => $obj->strReplace($corpusArticleInfo['description'], 2),
                                        'corpusTypeId' => $corpusType->id,
                                        'sourceArticleId' => $v2['articleId'],
                                        'year' => $year,
                                        'created_at' => date('Y-m-d H:i:s', strtotime($v2['createTimeStr'])),
                                    ];
                                    echo "正在采集：" . $corpusType->corpusTypeName . ' | ' . $corpusType->sourceId . ' | ' . $corpusType->id . ' | ' . $year . ' | ' . $corpusArticleInfo['title'] . ' | ' . $v2['articleId'] . PHP_EOL;
                                    DB::table('corpus_articles')->insert($data);
                                }, false, 1, true);
                                $pid = $process->start();
                                $worker[$pid] = $process;
                            } while (!$pid);
                        }
                        foreach ($worker as $work)
                        {
                            $status = Process::wait(true);
//                            echo "Recycled #{$status['pid']}, code={$status['code']}, signal={$status['signal']}" . PHP_EOL;
                        }
                    }
                }
            }
            DB::table('corpus_types')->where('id', $corpusType->id)->update(['lastArticleId' => $lastCorpusId]);
        }
    }

    private function initProcess($ct)
    {
        $processFun = function() use($ct)
        {
            $type = $this->lotteryType($ct->lotteryType);
//            $result = $this->request(self::$host2 . '/api/artlist', ['cateid' => $ct->sourceId, 'type' => $type]);
            $result = $this->request('https://48c.zlapi8.com/api/artlist', ['cateid' => $ct->sourceId]);
            for ($i = $result['data']['totalPages']; $i > 0; $i--)
            {
//                $result = $this->request(self::$host2 . '/api/artlist', ['cateid' => $ct->sourceId, 'type' => $type, 'page' => $i]);
                $result = $this->request('https://48c.zlapi8.com/api/artlist', ['cateid' => $ct->sourceId, 'page' => $i]);
                $data = $result['data']['data'];
                for ($j = count($data)-1; $j >= 0; $j--)
                {
                    $corpusTypes = [
                        'title' => $data[$j]['title'],
                        'content' => $data[$j]['content'],
                        'corpusTypeId' => $ct->id,
                        'sourceArticleId' => $data[$j]['id'],
                        'year' => '2023',
                        'user_id' => $ct->user_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    DB::table('corpus_articles' . $ct->table_idx)->insert($corpusTypes);
                    echo $data[$j]['title'] . PHP_EOL;
                }
            }
            $updateData = ['lastArticleId' => $data[0]['id'], 'year' => json_encode([["year" => "2023"]])];
            $updateStatus = DB::table('corpus_types')->where('id', $ct->id)->update($updateData);
            if (!$updateStatus)
            {
                echo "修改类型失败 " . $ct->id . PHP_EOL;
                dump($updateStatus);
                dump($updateData);
                sleep(5);
            }
            echo $ct->corpusTypeName . PHP_EOL;
        };
        $this->pushWork($processFun);
    }

    private function corpusArticle2(int $lotteryType)
    {

//        $types = DB::table('corpus_types')->where(['website' => 2, 'lotteryType' => '5'])->get()->toArray();
//        foreach ($types as $typeItem)
//        {
//            DB::table('corpus_articles' . $typeItem->table_idx)->where('corpusTypeId', $typeItem->id)->delete();
////            DB::table('corpus_types')->where('id', $typeItem->id)->update(['lastArticleId' => 0, 'year' => '']);
//            DB::table('corpus_types')->where('id', $typeItem->id)->delete();
//        }
//        exit;
//        dd($types);
        exit;

        $workNumber = 10;

        $corpusTypes = DB::table('corpus_types')
            ->where(['lotteryType' => $lotteryType, 'website' => 2])
            ->get()
            ->toArray();

        for ($i = 0; $i < $workNumber; $i++)
        {
            $this->initProcess($corpusTypes[$i]);
        }

        while (true)
        {
            $status = Process::wait(true);
            unset($this->worker[$status['pid']]);
            if ($i >= count($corpusTypes))
            {
                if (count($this->worker) == 0)
                {
                    echo 'ok'.PHP_EOL;
                    break;
                }
                continue;
            }
            $this->initProcess($corpusTypes[$i]);
            $i++;
        }

    }

    private function pushWork($processFun)
    {
        $pid = $this->createWork($processFun);
        $this->worker[$pid] = $pid;
//        echo $pid . PHP_EOL;
    }

    private function createWork($processFun)
    {
        do
        {
            $process = new Process($processFun, false, 1, true);
            $pid = $process->start();
            if ($pid)
            {
                return $pid;
            }
        } while (!$pid);
    }


    /**
     * 资料详情获取
     * @param int $articleId
     * @return false|mixed
     */
    private function corpusArticleInfo(int $articleId)
    {
        $corpusArticleInfo = $this->request(self::$host . '/unite49/h5/article/detail', ['articleId' => $articleId]);
        if (isset($corpusArticleInfo['data']))
        {
            return $corpusArticleInfo['data'];
        }
        return false;
    }

    /**
     * 过滤内容
     * @param string $str
     * @param int $type
     * @return array|string|string[]|null
     */
    private function strReplace(string $str, int $type = 1)
    {
        if ($type == 1)
        {
            return preg_replace('/\d+\.[a-zA-Z]+/i', '', $str);
        } else {
            return preg_replace('/\d+\.com|\d+\.cc|<script(.*?)>(.*?)<\/script>/i', '', $str);
        }
    }

    /**
     * 资料分类更新年份
     * @param int $lotteryType
     * @return void
     */
    private function yearUpdate(int $lotteryType)
    {
        $corpusTypes = DB::table('corpus_types')->where('lotteryType', $lotteryType)->get()->toArray();
        foreach ($corpusTypes as $v)
        {
            $years = DB::table('corpus_articles')->select('year')->where('corpusTypeId', $v->id)->orderBy('year', 'asc')->groupBy('year')->get()->toArray();
            if ($years)
            {
                DB::table('corpus_types')->where('id', $v->id)->update(['year' => json_encode($years)]);
            }
            echo $v->corpusTypeName . PHP_EOL;
        }
    }

    /**
     * 资料分配用户
     * @param int $lotteryType
     * @return void
     */
    private function addUser(int $lotteryType)
    {
        $corpusTypes = DB::table('corpus_types')->where('lotteryType', $lotteryType)->get()->toArray();
        foreach ($corpusTypes as $v)
        {
            DB::table('corpus_articles')->where('corpusTypeId', $v->id)->update(['user_id'=>$v->user_id]);
            echo $v->corpusTypeName . PHP_EOL;
        }
    }

    /**
     * 彩种
     * @param $lotteryType
     * @return string
     */
    private function lotteryType($lotteryType)
    {
        $type = '';
        switch ($lotteryType)
        {
            case 1:
                $type = 'xg';
                break;

            case 2:
                $type = 'am';
                break;

            case 3:
                $type = 'tw';
                break;

            case 4:
                $type = 'xjp';
                break;

            case 5:
            case 6:
                $type = 'am2';
                break;

            case 7:
                $type = 'oldam';
                break;
        }
        return $type;
    }

    /**
     * 生成邀请码
     * @return string
     */
    protected function randString():string
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d').substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for (
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }

}
