<?php
// login.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // 提前结束响应，处理 OPTIONS 预检请求
}

$config = include('../../config.php');
include('../../db.php');
include('../../utils/token.php');

$appid = $config['wechat']['app_id'];
$secret = $config['wechat']['app_secret'];

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$code = $data['code'] ?? null;
$time = date("Y-m-d H:i:s");

$url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";

$response = file_get_contents($url);
$responseData = json_decode($response, true);
//echo $response;
// $responseData['openid'] = "11456"; 测试用openid
if (isset($responseData['openid'])) {
    $stmt = $pdo->prepare('SELECT * FROM fy_users WHERE openid = ?');
    $stmt->execute([$responseData['openid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
     
    if (!$user) {
        //未注册，则写到数据库里，并发一个access_token
        $stmt = $pdo->prepare('INSERT INTO fy_users (openid, role, status) VALUES (?, ?, ?)');
        $stmt->execute([$responseData['openid'], 'user', 'pending']);
        $tokenData = generateToken($responseData['openid'], $config['token']['salt']);
        $token = $tokenData['token']; 
        echo json_encode([
            'success' => true,
            'registered' => false,
            'openid' => $responseData['openid'],
            'access_token' => $token,
            'uid' => '',
            'email' => '',
            'avatar' => '',
            'available' => '',
            'campus' => '',
            'phone' => '',
            'role' => '',
            'nickname' => ''
        ]);
    } else {
        // 判断用户status，为pending时和未注册的逻辑一样
        if ($user['status'] === 'pending') {
            $tokenData = generateToken($responseData['openid'], $config['token']['salt']);
            $token = $tokenData['token']; 
            echo json_encode([
                'success' => true,
                'registered' => false,
                'openid' => $responseData['openid'],
                'access_token' => $token,
                'uid' => '',
                'email' => '',
                'avatar' => '',
                'available' => '',
                'campus' => '',
                'phone' => '',
                'role' => '',
                'nickname' => ''
            ]);
        } else {
            $tokenData = generateToken($responseData['openid'], $config['token']['salt']);
            $token = $tokenData['token']; 
            $user['available'] = $user['available'] == 1 ? true : false;
            echo json_encode([
                'success' => true,
                'registered' => true,
                'openid' => $responseData['openid'],
                'access_token' => $token,
                'uid' => $user['id'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'available' => $user['available'],
                'campus' => $user['campus'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'nickname' => $user['nickname']
            ]);
        }        
    }
    
} else {
    echo json_encode([
        'success' => false,
        'error' => $responseData,
        'registered' => false
    ]);
}
?>
