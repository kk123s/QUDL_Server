<?php
header('Content-Type: application/json');

try {
    $version = $_GET['version'] ?? '1.20.1';
    $modDir = "(源码存放目录)/mods/{$version}";

    if (!is_dir($modDir)) {
        throw new Exception("指定版本不存在");
    }

    $manifest = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($modDir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relPath = substr($file->getPathname(), strlen($modDir) + 1);
            $manifest[] = [
                'filename' => str_replace('\\', '/', $relPath),
                'hash' => hash_file('sha256', $file),
                'size' => $file->getSize(),
                'url' => "/mods/{$version}/" . urlencode($relPath)
            ];
        }
    }

    echo json_encode([
        'version' => $version,
        'timestamp' => time(),
        'files' => $manifest
    ]);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
}