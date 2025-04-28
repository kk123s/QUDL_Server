<?php
header('Content-Type: application/json');

try {
    $config = [
        'serverUrl' => rtrim($_POST['serverUrl'], '/'),
        'version' => $_POST['version'],
        'clientIp' => $_POST['clientIp']
    ];

    $testCases = [
        '无效客户端ID' => [
            'url' => "{$config['serverUrl']}/api/manifest?version={$config['version']}",
            'headers' => ['X-Client-ID: invalid']
        ],
        '过期签名' => [
            'url' => "{$config['serverUrl']}/download/1.20.1/test.jar/0/expiredsig"
        ]
    ];

    $results = [];
    foreach ($testCases as $name => $case) {
        $ch = curl_init($case['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $case['headers'] ?? [],
            CURLOPT_HEADER => true
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $results[] = "$name: HTTP $statusCode";
        curl_close($ch);
    }

    echo json_encode([
        'success' => true,
        'message' => "安全测试完成",
        'details' => implode("\n", $results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}