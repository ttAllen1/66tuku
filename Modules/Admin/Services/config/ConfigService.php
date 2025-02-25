<?php

/**
 * @Name 系统配置
 * @Description
 */

namespace Modules\Admin\Services\config;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Models\AuthImage;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Services\BaseService;

class ConfigService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index()
    {
        $info = AuthConfig::select('id', 'name', 'register_send_sms', 'login_send_sms', 'found_send_sms', 'register_gift', 'image_status', 'logo_id', 'wide_logo_id', 'not_find_img_id', 'avatar_id', 'swiper_ids', 'about_us', 'video_url', 'ws_url', 'server_url', 'h5_url', 'download_url', 'main_domain', 'xg_live', 'am_live', 'xam_live', 'oldam_live', 'ios_version', 'android_version', 'ios_must_update', 'android_must_update', 'ios_update_manual', 'android_update_manual', 'ios_download_url', 'android_download_url', 'mobile_max_sends', 'app_first_gift', 'wydun', 'wydun_captcha_id', 'mobile_blacklist', 'old_ws_url', 'config_js', 'config_add_js', 'cloud_url', 'ios_resource_download_url', 'ad_img_url', 'vpn_url', 'xg_report', 'xin_ao_report', 'tian_ao_report', 'tw_report', 'xjp_report', 'kl8_report', 'lao_ao_report', 'bet_url', 'sys_update', 'config_lottery_js', 'aws_video_url')->with([
            'logo_one'     => function ($query) {
                $query->select('id', 'url', 'open');
            },
            'user_avatar'  => function ($query) {
                $query->select('id', 'url', 'open');
            },
            'wide_logo'    => function ($query) {
                $query->select('id', 'url', 'open');
            },
            'not_find_img' => function ($query) {
                $query->select('id', 'url', 'open');
            }
        ])->find(1)->toArray();
        $info['ios_must_update'] = (bool)$info['ios_must_update'];
        $info['android_must_update'] = (bool)$info['android_must_update'];
        $info['register_send_sms'] = (bool)$info['register_send_sms'];
        $info['login_send_sms'] = (bool)$info['login_send_sms'];
        $info['found_send_sms'] = (bool)$info['found_send_sms'];
        $info['register_gift'] = (bool)$info['register_gift'];
        $info['app_first_gift'] = (bool)$info['app_first_gift'];
        $info['xg_report'] = (bool)$info['xg_report'];
        $info['xin_ao_report'] = (bool)$info['xin_ao_report'];
        $info['tian_ao_report'] = (bool)$info['tian_ao_report'];
        $info['tw_report'] = (bool)$info['tw_report'];
        $info['xjp_report'] = (bool)$info['xjp_report'];
        $info['kl8_report'] = (bool)$info['kl8_report'];
        $info['lao_ao_report'] = (bool)$info['lao_ao_report'];
        $info['wydun'] = (bool)$info['wydun'];
        $info['sys_update'] = (bool)$info['sys_update'];
        if ($info['logo_one']['open'] == 1) {
            $info['logo_url'] = $this->getHttp() . $info['logo_one']['url'];
        } else {
            $info['logo_url'] = $info['logo_one']['url'];
        }
        if ($info['user_avatar']) {
            if ($info['user_avatar']['open'] == 1) {
                $info['user_avatar_url'] = $this->getHttp() . $info['user_avatar']['url'];
            } else {
                $info['user_avatar_url'] = $info['user_avatar']['url'];
            }
        } else {
            $info['user_avatar_url'] = '';
        }
        if ($info['wide_logo']) {
            if ($info['wide_logo']['open'] == 1) {
                $info['wide_logo_url'] = $this->getHttp() . $info['wide_logo']['url'];
            } else {
                $info['wide_logo_url'] = $info['wide_logo']['url'];
            }
        } else {
            $info['wide_logo_url'] = '';
        }
        if ($info['not_find_img']) {
            if ($info['not_find_img']['open'] == 1) {
                $info['not_find_img_url'] = $this->getHttp() . $info['not_find_img']['url'];
            } else {
                $info['not_find_img_url'] = $info['not_find_img']['url'];
            }
        } else {
            $info['not_find_img_url'] = '';
        }
        if ($info['swiper_ids']) {
            $swipers = AuthImage::query()->whereIn('id', explode(',', $info['swiper_ids']))->select([
                'id', 'url', 'open'
            ])->get()->toArray();
            foreach ($swipers as $k => $swiper) {
                $info['swiper_url'][$k]['id'] = $swiper['id'];
                $info['swiper_url'][$k]['open'] = $swiper['open'];
                if ($swiper['open'] == 1) {
                    $info['swiper_url'][$k]['url'] = $this->getHttp() . $swiper['url'];
                } else {
                    $info['swiper_url'][$k]['url'] = $swiper['url'];
                }
            }
        }
        $info['about_us'] = $this->getReplacePicUrl($info['about_us']);
        // 获取oss内容
//        $service_url = file_get_contents('https://48tuku.oss-cn-hongkong.aliyuncs.com/configs/config.js');
//        $info['service_url'] = $service_url ? : '';

        return $this->apiSuccess('', $info);
    }

    /**
     * 修改提交
     * @param array $data
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function update(array $data): ?JsonResponse
    {
        try{
            $data['about_us'] = $this->getRemvePicUrl($data['about_us']);
            $data['ios_must_update'] = $data['ios_must_update'] ? 1 : 0;
            $data['android_must_update'] = $data['android_must_update'] ? 1 : 0;
            $data['register_send_sms'] = $data['register_send_sms'] ? 1 : 0;
            $data['login_send_sms'] = $data['login_send_sms'] ? 1 : 0;
            $data['found_send_sms'] = $data['found_send_sms'] ? 1 : 0;
            $data['register_gift'] = $data['register_gift'] ? 1 : 0;
            $data['app_first_gift'] = $data['app_first_gift'] ? 1 : 0;

            $data['xg_report'] = $data['xg_report'] ? 1 : 0;
            $data['xin_ao_report'] = $data['xin_ao_report'] ? 1 : 0;
            $data['tian_ao_report'] = $data['tian_ao_report'] ? 1 : 0;
            $data['tw_report'] = $data['tw_report'] ? 1 : 0;
            $data['xjp_report'] = $data['xjp_report'] ? 1 : 0;
            $data['kl8_report'] = $data['kl8_report'] ? 1 : 0;
            $data['lao_ao_report'] = $data['lao_ao_report'] ? 1 : 0;
            $data['sys_update'] = $data['sys_update'] ? 1 : 0;
            Redis::set('xg_report', $data['xg_report']);
            Redis::set('xin_ao_report', $data['xin_ao_report']);
            Redis::set('tian_ao_report', $data['tian_ao_report']);
            Redis::set('tw_report', $data['tw_report']);
            Redis::set('xjp_report', $data['xjp_report']);
            Redis::set('kl8_report', $data['kl8_report']);
            Redis::set('lao_ao_report', $data['lao_ao_report']);
            Redis::set('sys_update', $data['sys_update']);

            $data['wydun'] = $data['wydun'] ? 1 : 0;
            $data['ad_img_url'] = str_replace(['http://', 'https://'], '', $data['ad_img_url']);
//            dd($data);
            $result = $this->commonUpdate(AuthConfig::query(), 1, $data);
            if ($result) {
                file_put_contents('config.js', "var baseConfigs='" . json_encode(\Modules\Api\Models\AuthConfig::getinfo()) . "';");
                (new BaseService())->ALiOssWith($data['config_js'], 'configs', 'config.js', '66tuku-config');
                (new BaseService())->ALiOssWith($data['config_add_js'], 'configs', 'configAdd.js', '66tuku-config');
                (new BaseService())->Upload2S3($data['config_js'], 'configs', 'config.js');
                (new BaseService())->Upload2S3($data['config_add_js'], 'configs', 'configAdd.js');
                (new BaseService())->Upload2S3($data['config_lottery_js'], 'configs', 'configLottery.js');
            }
            return $result;
        }catch (\Exception $e){
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
