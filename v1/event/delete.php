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
include('../../utils/token.php');
include('../../utils/headercheck.php');
include('../../utils/gets.php');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$response = [];

if (isset($data['id'])) {
    $activity_id = $data['id'];

    $has_permission = false;

    if ($userinfo['is_admin']) {
        $has_permission = true;
    }

    if ($has_permission) {
        $activity = getAll($activity_id, 'fy_activities', 'id');

        if ($activity) {
            $deleteStmt = $pdo->prepare("DELETE FROM fy_activities WHERE id = :id");
            $deleteStmt->execute([':id' => $activity_id]);

            $response = [
                'success' => true,
                'message' => '活动删除成功'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => '活动未找到'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Permission denied'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => '未提供活动ID'
    ];
}

echo json_encode($response);
?>