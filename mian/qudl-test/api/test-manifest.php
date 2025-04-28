<?php
header('Content-Type: application/json');

try {
    $config = [
        'serverUrl' => rtrim($_POST['serverUrl'], '/'),
        'version' => $_POST['version'],
    ];
    
    // 发起清单请求
    $url = "{$config['serverUrl']}/api/manifest.php?version={$config['version']}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true
    ]);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($statusCode !== 200) {
        throw new Exception("HTTP状态码异常: $statusCode");
    }

    $body = json_decode(substr($response, $headerSize), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("无效的JSON响应");
    }

    echo json_encode([
        'success' => true,
        'message' => "成功获取 {$body['version']} 版本清单",
        'details' => "发现文件数: " . count($body['files'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => "URL: $url\nClient-ID: $clientId"
    ]);
}