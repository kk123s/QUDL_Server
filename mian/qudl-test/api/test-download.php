<?php
header('Content-Type: application/json');

try {
    $config = [
        'serverUrl' => rtrim($_POST['serverUrl'], '/'),
        'version' => $_POST['version'],
        'clientIp' => $_POST['clientIp']
    ];

    // 获取清单
    $manifestUrl = "{$config['serverUrl']}/manifest?version={$config['version']}";
    $clientId = hash('sha256', $config['clientIp'] . 'QUDL_SALT');
    
    $manifest = json_decode(file_get_contents($manifestUrl, false, stream_context_create([
        'http' => ['header' => "X-Client-ID: $clientId\r\n"]
    ])), true);

    // 下载测试文件
    $testFile = $manifest['files'][0];
    $fileUrl = "{$config['serverUrl']}{$testFile['url']}";
    
    $tempFile = sys_get_temp_dir() . '\\' . basename($testFile['filename']);
    file_put_contents($tempFile, fopen($fileUrl, 'r'));

    // 验证文件
    $actualSize = filesize($tempFile);
    $actualHash = hash_file('sha256', $tempFile);
    
    $details = [
        "文件: {$testFile['filename']}",
        "预期大小: {$testFile['size']}",
        "实际大小: $actualSize",
        "预期哈希: {$testFile['hash']}",
        "实际哈希: $actualHash"
    ];

    if ($actualSize !== $testFile['size'] || $actualHash !== $testFile['hash']) {
        throw new Exception("文件验证失败");
    }

    echo json_encode([
        'success' => true,
        'message' => "文件下载验证通过",
        'details' => implode("\n", $details)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => $details ?? []
    ]);
}