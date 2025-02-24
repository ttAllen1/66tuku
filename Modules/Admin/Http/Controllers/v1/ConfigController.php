<?php
/**
 * 系统配置
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;

class ConfigController extends BaseApiController
{
    /**
     * 配置页面
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return (new ConfigService())->index();
    }

    /**
     * 修改提交
     * @param Request $request
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function update(Request $request)
    {
        return (new ConfigService())->update($request->only(['name', 'register_send_sms', 'login_send_sms', 'found_send_sms', 'register_gift', 'image_status','logo_id','avatar_id','swiper_ids','about_us','wide_logo_id', 'not_find_img_id', 'video_url', 'ws_url', 'server_url', 'h5_url', 'download_url', 'main_domain', 'xg_live', 'am_live', 'xam_live', 'oldam_live', 'ios_version', 'android_version', 'ios_must_update', 'android_must_update', 'ios_update_manual', 'android_update_manual', 'ios_download_url', 'android_download_url', 'mobile_max_sends', 'app_first_gift', 'wydun', 'wydun_captcha_id', 'mobile_blacklist', 'old_ws_url', 'config_js', 'config_add_js', 'cloud_url', 'ios_resource_download_url', 'ad_img_url', 'vpn_url', 'xg_report', 'xin_ao_report', 'tian_ao_report', 'tw_report', 'xjp_report', 'kl8_report', 'lao_ao_report', 'bet_url', 'sys_update', 'config_lottery_js', 'aws_video_url']));
    }
}
