<?php

try{
    $output = exec('cd /www/wwwroot/11.48tkapi.com && /usr/bin/git pull');
    // 将输出写入日志文件
//    file_put_contents('/www/wwwlogs/11.48tkapi.com/public/git_log.txt', $output, FILE_APPEND);
    exit;
}catch(\Throwable $e) {
//    file_put_contents('/www/wwwlogs/11.48tkapi.com/public/git_log.txt', $e->getMessage(), FILE_APPEND);
    exit;
}
// 配置密钥，用于验证请求的合法性
$secret = "fjHHG732Frw4934#@!b012^&Khe65Ge71";

// 获取请求的HTTP头部中的签名信息
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

// 验证签名和事件类型
if (verifySignature($secret, $signature, file_get_contents('php://input')) && $event === 'push') {

    file_put_contents('/www/wwwlogs/11.48tkapi.com/git_log.txt', 1, FILE_APPEND);
    exit;
    // 例如，可以使用shell命令执行git pull来自动部署代码
    $output = shell_exec('cd /www/wwwroot/11.48tkapi.co && git pull');

    // 将输出写入日志文件
    file_put_contents('/www/wwwlogs/11.48tkapi.co/git_log.txt', $output, FILE_APPEND);
} else {
    file_put_contents('/www/wwwlogs/11.48tkapi.co/git_log.txt', 2, FILE_APPEND);
    exit;
    // 签名验证失败或事件类型不是push，返回错误响应
    header("HTTP/1.1 403 Forbidden");
    echo "Forbidden";
}

// 验证签名的函数
function verifySignature($secret, $signature, $payload) {
    if (empty($secret) || empty($signature)) {
        return false;
    }

    list($algo, $hash) = explode('=', $signature, 2);
    $payloadHash = hash_hmac($algo, $payload, $secret);

    return hash_equals($payloadHash, $hash);
}
