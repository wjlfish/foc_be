<?php
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
require '../../utils/email.php';
require '../../utils/sms.php';
include('../../utils/gets.php');
include('../../utils/token.php');

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if(!$config['info']['adminreg']){
    echo json_encode(["success" => false, "message" => "管理员注册已关闭"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $openid = $data['openid'] ?? '';

    if (empty($username) || empty($password) || empty($openid)) {
        echo json_encode(["success" => false, "message" => "缺少必要的参数"]);
        exit();
    }

    // 检查 fy_users 表中是否存在该 openid
    $stmt = $pdo->prepare("SELECT * FROM fy_users WHERE openid = ?");
    $stmt->execute([$openid]);
    $user = $stmt->fetch();

    if ($user) {
        // 加密密码
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 插入新管理员
        $stmt = $pdo->prepare("INSERT INTO fy_admins (username, password, openid) VALUES (?, ?, ?)");
        $success = $stmt->execute([$username, $hashed_password, $openid]);

        if ($success) {
            echo json_encode(["success" => true, "message" => "注册成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "注册失败"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "无效的 openid"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "无效的请求方法"]);
}
?>