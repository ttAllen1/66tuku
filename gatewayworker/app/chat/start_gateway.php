<?php

use \Workerman\Worker;
use \GatewayWorker\Gateway;
use \Workerman\Autoloader;

/**
 * Gateway 进程负责处理客户端连接，监听客户端的Websocket连接
 */

require_once __DIR__ . '/../../vendor/autoload.php';
$context = array(
    // 更多ssl选项请参考手册 https://php.net/manual/zh/context.ssl.php
    'ssl' => array(
        // 请使用绝对路径
        'local_cert'                 => '/www/server/panel/vhost/cert/55.6ctkapi.com/fullchain.pem', // 也可以是crt文件
        'local_pk'                   => '/www/server/panel/vhost/cert/55.6ctkapi.com/privkey.pem',
        'verify_peer'               => false,
        // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
    )
);
$gateway = new Gateway("Websocket://0.0.0.0:7272");
//$gateway->transport = 'ssl';
$gateway->name = 'ChatGateway';
$gateway->count = 4;
$gateway->lanIp = '127.0.0.1'; // 分布式部署时要填写真实IP（非127.0.0.1）
$gateway->startPort = 2300;
$gateway->pingInterval = 50;   // 设置心跳，防止长时间不通讯被路由节点强行断开
$gateway->pingNotResponseLimit = 1;  // 1 客户端必须主动发送心跳 0 服务端不要求客户端必须发送心跳
$gateway->pingData = '{"type":"ping"}';
$gateway->registerAddress = '127.0.0.1:1238'; // 用于和BusinessWorker进程通信，与Gateway进程的注册地址保持一致

// // 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
// $gateway->onConnect = function($connection)
// {
//     $connection->onWebSocketConnect = function($connection , $http_header)
//     {
//         // 可以在这里判断连接来源是否合法，不合法就关掉连接
//         // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
//         if($_SERVER['HTTP_ORIGIN'] != 'http://chat.workerman.net')
//         {
//             $connection->close();
//         }
//         // onWebSocketConnect 里面$_GET $_SERVER是可用的
//         // var_dump($_GET, $_SERVER);
//     };
// };

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
