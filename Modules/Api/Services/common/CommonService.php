<?php

namespace Modules\Api\Services\common;

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Intervention\Image\Exception\ImageException;
use Modules\Api\Models\Activite;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\AuthImage;
use Modules\Api\Models\StationMsg;
use Modules\Api\Models\UserMessage;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Models\AuthConfig as AuthConfigModel;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\RedPacket;
use Modules\Common\Services\BaseService;

class CommonService extends BaseApiService
{
    /**
     * 公共配置
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        $currentH = date('H');
        $cfgRes = Redis::get('auth_config_info');
        if (!$cfgRes) {
            $config = AuthConfigModel::getinfo();
            if ($currentH == 21) {
                Redis::setex('auth_config_info', 1200, json_encode($config));
            }
        } else {
            $config = json_decode($cfgRes, true);
        }
//        $config = AuthConfigModel::getinfo();

        $host = '';
        if ($this->is_ssl())
        {
            $host .= 'https://';
        } else {
            $host .= 'http://';
        }
        $host = 'https://api1.49tkapi8.com';
        $host2 = config('config.full_srv_img_prefix');
        $config['host'] = $host;
        $config['host2'] = $host2;
        $game_platform_collection_rdx = Redis::get('game_platform_collection_rdx');
        if (!$game_platform_collection_rdx) {
            $config['game_platform_collection'] = AuthGameConfig::val('game_platform_collection');
            if ($currentH == 21) {
                Redis::setex('game_platform_collection_rdx', 2400, json_encode($config['game_platform_collection']));
            }
        } else {
            $config['game_platform_collection'] = json_decode($game_platform_collection_rdx, true);
        }

        $accRdx = Redis::get('acc_rdx');
        if (!$accRdx) {
            $acc = AuthActivityConfig::val('five_bliss_show_start,five_bliss_show_end');
            if ($currentH == 21) {
                Redis::setex('acc_rdx', 2400, json_encode($acc));
            }
        } else {
            $acc = json_decode($accRdx, true);
        }
        $config['five'] = (time() >= strtotime($acc['five_bliss_show_start']) && time() < strtotime($acc['five_bliss_show_end'])) ? 1 : 0;

        // 今日是否有红包
        $userId = auth('user')->id();
        try{
            $today = date('Y-m-d');
            $redInfo = RedPacket::query()
                ->where('room_id', 5)
                ->whereDate('start_date', $today)
                ->whereIn('status', [-1, 1])
                ->where('last_nums', '>', 0)
                ->selectRaw("id, valid_date, JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) as least_time")
                ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) ASC")
                ->first();
            if (!$redInfo) {
                // 没有红包活动
                $config['red']['status'] = 0;
            } else {
                // 获取当天的活动开始时间和结束时间
                $startEndTimes = DB::table('red_packets')
                    ->where('room_id', 5)
                    ->whereDate('start_date', $today)
                    ->whereIn('status', [-1, 1])
                    ->selectRaw("
                        MIN(JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]'))) as start_time,
                        MAX(JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]'))) as end_time
                    ")
                    ->first();
                $config['red']['start_time'] = $startEndTimes->start_time ?? null;
                $config['red']['end_time'] = $startEndTimes->end_time ?? null;
                if ($userId) {
                    // 检查用户是否已经领取了最近的红包
                    $isReceived = DB::table('user_reds')
                        ->where('user_id', $userId)
                        ->where('red_id', $redInfo->id)
                        ->exists();
                    if ($isReceived) {
                        // 如果已经领取，寻找下一个未领取的红包
                        $nextRedInfo = RedPacket::query()
                            ->where('room_id', 5)
                            ->whereDate('start_date', $today)
                            ->whereIn('status', [-1, 1])
                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) > ?", [$redInfo->least_time])
                            ->first();
                        if (!$nextRedInfo) {
                            // 没有下一个未领取的红包
                            $config['red']['status'] = 0;
                        } else {
                            // 设置下一个红包的信息
                            $config['red']['id'] = $nextRedInfo->id;
                            $config['red']['status'] = 1;
                            $config['red']['from'] = 'index';
                            $config['red']['least'] = Carbon::parse($nextRedInfo->valid_date[0])->toDateTimeString();
//                            $config['red'] = [
//                                'id' => $nextRedInfo->id,
//                                'status' => 1,
//                                'from' => 'index',
//                                'least' => Carbon::parse($nextRedInfo->valid_date[0])->toDateTimeString(),
//                            ];
                        }
                    } else {
                        // 如果还没有领取
                        $config['red']['id'] = $redInfo->id;
                        $config['red']['status'] = 1;
                        $config['red']['from'] = 'index';
                        $config['red']['least'] = Carbon::parse($redInfo->valid_date[0])->toDateTimeString();
//                        $config['red'] = [
//                            'id' => $redInfo->id,
//                            'status' => 1,
//                            'from' => 'index',
//                            'least' => Carbon::parse($redInfo->valid_date[0])->toDateTimeString(),
//                        ];
                    }
                } else {
                    // 未登录用户的红包信息
                    $config['red']['id'] = $redInfo->id;
                    $config['red']['status'] = 1;
                    $config['red']['from'] = 'index';
                    $config['red']['least'] = Carbon::parse($redInfo->valid_date[0])->toDateTimeString();
//                    $config['red'] = [
//                        'id' => $redInfo->id,
//                        'status' => 1,
//                        'from' => 'index',
//                        'least' => Carbon::parse($redInfo->valid_date[0])->toDateTimeString(),
//                    ];
                }
            }
        }catch (\Exception $exception) {
            $config['red']['status'] = 0;
        }


        // 记录请求该接口的活动时间
        if ($userId) {
            $ip = $this->getIp();
            $ip = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0;
            Activite::query()->updateOrInsert([
                'user_id'       => $userId,
            ],[
                'request_ip'    => $ip,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $config);
    }

    /**
     * @param $params
     * @return JsonResponse
     */
    public function version($params): JsonResponse
    {
        if ($params['type'] == 1){
            $selectArr = ['id', 'ios_version', 'ios_must_update', 'ios_update_manual', 'ios_download_url'];
        } else {
            $selectArr = ['id', 'android_version', 'android_must_update', 'android_update_manual', 'android_download_url'];
        }
        $res = AuthConfigModel::query()->select($selectArr)->find(1)->toArray();
        $appVersion = explode('.', $params['version']);
        if (count($appVersion) < 3) {
            return response()->json(['message'=>'客户端版本号不正确', 'status'=>40000], 400);
        }
        if ($params['type'] == 1) {
            $res['ios_update_manual'] = str_replace(PHP_EOL, '<br/>', $res['ios_update_manual']);
            $sysVersion = explode('.', $res['ios_version']);
        } else {
            $res['android_update_manual'] = str_replace(PHP_EOL, '<br/>', $res['android_update_manual']);
            $sysVersion = explode('.', $res['android_version']);
        }
        if ($appVersion[0]>$sysVersion[0]) {
            return response()->json(['message'=>'客户端版本号不正确', 'status'=>40000], 400);
        } else if ($appVersion[0]==$sysVersion[0] && $appVersion[1]>$sysVersion[1]) {
            return response()->json(['message'=>'客户端版本号不正确', 'status'=>40000], 400);
        } else if ($appVersion[0]==$sysVersion[0] && $appVersion[1]==$sysVersion[1] && $appVersion[2]>$sysVersion[2]) {
            return response()->json(['message'=>'客户端版本号不正确', 'status'=>40000], 400);
        }
        if ($appVersion[0]==$sysVersion[0] && $appVersion[1]==$sysVersion[1] && $appVersion[2]==$sysVersion[2]) {
            return $this->apiSuccess('版本号相同，无需操作');
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 图片上传
     * @description
     * @param request Request 图片资源完整信息
     * @param request.file Resource  图片资源
     **/
    public function fileImage(object $request)
    {
        if ($request->isMethod('POST')){
            $fileCharater = $request->file('file');
            if ($fileCharater && $fileCharater->isValid()){
                $extension = $request->file->extension();
                $location = $request->input('location', 'images');
                if (!in_array($extension, ['jpg', 'png', 'jpeg', 'bmp', 'gif']))
                {
                    return $this->apiError('请上传图片！');
                }
                $imageStatus = AuthConfigModel::where('id',1)->value('image_status');
                if($imageStatus == 1){
                    $path = $request->file('file')->store($location . '/'.date('Ymd'),'upload');
                    if ($path){
                        $url = '/upload/'.$path;
                    }
                }else if($imageStatus == 2){
                    $url = $this->addQiniu($fileCharater);
                }
                if(isset($url)){
                    $image_id = AuthImage::insertGetId([
                        'url'=>$url,
                        'open'=>$imageStatus,
                        'status'=>1,
                        'created_at'=> date('Y-m-d H:i:s')
                    ]);
                    if($image_id){
                        if($imageStatus == 1){
                            $url = $this->getHttp().$url;
                        }
                        return $this->apiSuccess('上传成功！',
                            [
                                'image_id'  =>$image_id,
                                'type'      => $request->input('type', 1),
                                'url'       => $url
                            ]);
                    }
                }
            }
            $this->apiError('上传失败！');
        }
        $this->apiError('上传失败！');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function imageUpload(Request $request): JsonResponse
    {
//        return $this->apiSuccess('', ['a'=>123]);
        try{
            $files = $request->file('file');
            $imageStatus = AuthConfigModel::image_status();
            $location = $request->input('location', 'images');
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $file) {
                if($imageStatus == 1){
                    $paths[] = 'upload/'.$file->store($location.'/' . date('Ymd'),'upload');
                }else{
                    $paths[] = $this->addQiniu($file);
                }
            }
            if (!$paths){
                throw new ImageException('文件保存失败，请重试');
            }

        }catch (ImageException $exception) {
            Log::info(__CLASS__,['图片上传错误'=>$exception->getMessage()]);
            throw new CustomException(['message'=>'文件上传异常，请重试']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['url'=>$paths, 'http_prefix'=>$imageStatus == 1 ? (new BaseService())->getHttp().'/' : '']);
    }

    /**
     * MinIo 临时凭据
     * @param $params
     * @return JsonResponse
     */
    public function tempCred($params): JsonResponse
    {
        $file_name = $params['file_name'];
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'credentials' => new Credentials(env('AWS_ACCESS_KEY_ID'), env('AWS_SECRET_ACCESS_KEY')),
        ]);
        $bucketName = env('AWS_BUCKET');

        // 设置临时凭证有效期为1小时
        $expires = '+1 hour';

        // 生成临时凭证
        $command = $s3Client->getCommand('PutObject', [
            'Bucket' => $bucketName,
            'Key' => $file_name,
        ]);

        $request = $s3Client->createPresignedRequest($command, $expires);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['url'=>(string) $request->getUri()]);
    }

    /**
     * MinIo 合并分片
     * @param $params
     * @return JsonResponse
     */
    public function videoComplete($params): JsonResponse
    {
        $bucket = env('AWS_BUCKET');
        $file_name = $params['file_name'];
        $uploadId = $params['upload_id'];
        $parts['Parts'] = $params['parts'];

        $filePath = storage_path('app').'/S0FOri64KLswsG2AR2SUbCIaCJMoSfwvQ1x3wfJj.mp4';
//
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => 'ap-northeast-2',
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => true,
        ]);
//
//        $result = $s3->createMultipartUpload([
//            'Bucket'       => $bucket,
//            'Key'          => $file_name,
//            'StorageClass' => 'REDUCED_REDUNDANCY',
//            'Metadata'     => [
//                'param1' => 'value 1',
//                'param2' => 'value 2',
//                'param3' => 'value 3'
//            ]
//        ]);
//        $uploadId = $result['UploadId'];
//        try {
//            $file = fopen($filePath, 'r');
//            $partNumber = 1;
//            while (!feof($file)) {
//                $result = $s3->uploadPart([
//                    'Bucket'     => $bucket,
//                    'Key'        => $file_name,
//                     'UploadId'  => $uploadId,
//                    'PartNumber' => $partNumber,
//                    'Body'       => fread($file, 50 * 1024 * 1024),
//                ]);
//                $parts['Parts'][$partNumber] = [
//                    'PartNumber' => $partNumber,
//                    'ETag' => $result['ETag'],
//                ];
//                $partNumber++;
//
//                echo "Uploading part {$partNumber} of {$file_name}." . PHP_EOL;
//            }
//            fclose($file);
//        } catch (S3Exception $e) {
//            $result = $s3->abortMultipartUpload([
//                'Bucket'   => $bucket,
//                'Key'      => $file_name,
//                'UploadId' => $uploadId
//            ]);
//
//            echo "Upload of {$file_name} failed." . PHP_EOL;
//        }
//        dd($parts, $uploadId);
//        $uploadId = 'MTc3ZTdlOTEtNDNiZi00ZTNkLTgxOGUtZmNmODdmZjBiNTU1LjBhNTQ1NzYyLWUzMGQtNGMwYS1iNDM1LTk1MjdhOGQ0ZTFiNw';
//
//        $parts1['Parts'] = [
//            [
//                'PartNumber'    => 1,
//                'ETag'    => '258020b77f51ae2f0b09b6a08025ac4b',
//            ],
//            [
//                'PartNumber'    => 2,
//                'ETag'    => 'b84eaec2a676c79b71a0e26ee19dc837',
//            ]
//        ];
        try{
            $result = $s3->completeMultipartUpload([
                'Bucket'   => $bucket,
                'Key'      => $file_name,
                'UploadId' => $uploadId,
                'MultipartUpload'    => $parts,
            ]);
            $url = $result['Location'];
        }catch (S3Exception $exception) {
            Log::error('MinIo：视频合并错误', ['message'=>$exception->getMessage()]);
            throw new CustomException(['message'=>'视频合并失败']);
        }

        return $this->apiSuccess(ApiMsgData::COMPLETE_API_SUCCESS, ['url'=>$url]);
    }

    /**
     * 七牛云图片上传
     * @param object $fileCharater
     * @return false
     * @throws \Modules\Common\Exceptions\ApiException
     */
    private function addQiniu(object $fileCharater)
    {
        $this->apiError('七牛云存储暂未开放！');
        // 初始化
        $disk = QiniuStorage::disk('qiniu');
        // 重命名文件
        $fileName = md5($fileCharater->getClientOriginalName().time().rand()).'.'.$fileCharater->getClientOriginalExtension();
        // 上传到七牛
        $bool = $disk->put('iwanli/image_'.$fileName,file_get_contents($fileCharater->getRealPath()));
        // 判断是否上传成功
        if($bool){
            return $disk->downloadUrl('iwanli/image_'.$fileName);
        }else{
            return false;
        }
    }

    /**
     * 公告｜消息
     * @param int $type
     * @return JsonResponse
     */
    public function getMessage(int $type)
    {
        $msgRdx = Redis::get('msg_rdx');
        if ($msgRdx && date('H') == 21 && date('i') > 28 && date('i') < 37) {
            $msgRdx = json_decode($msgRdx, true);
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $msgRdx);
        }
        $field = ['station_msgs.id', 'station_msgs.title', 'station_msgs.content', 'station_msgs.sort', 'station_msgs.type', 'station_msgs.appurtenant', 'station_msgs.created_at'];
        if ($type == 1)
        {
            $field[] = 'valid_date_start';
            $field[] = 'valid_date_end';
        }
        $msgList = StationMsg::getMsgList($field, $type, $this->userinfo());
        if ($msgList && $msgList['data']) {
            foreach ($msgList['data'] as $k => $v) {
                $msgList['data'][$k]['content'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['content']);
            }
        }

        $arr = [
            'last_page' => $msgList['last_page'],
            'current_page' => $msgList['current_page'],
            'total' => $msgList['total'],
            'list' => $msgList['data']
        ];
        if (date('H') == 21 && date('i') > 28 && date('i') < 37 && !$this->userinfo()) {
            Redis::setex('msg_rdx', 2400, json_encode($arr));
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $arr);
    }

    /**
     * 消息已读
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function setMessage()
    {
        UserMessage::where(['user_id' => request()->userinfo->id])->update(['view' => 1]);
        return $this->apiSuccess(ApiMsgData::POST_API_SUCCESS);
    }

    /**
     * 消息数
     * @param int $type
     * @return JsonResponse
     */
    public function getMessageBadge()
    {
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['msg' => StationMsg::getMessageBadge()]);
    }

}
