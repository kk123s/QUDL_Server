<?php
session_start();

// 硬编码登录验证
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && $password === 'password123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['message'] = "无效的登录凭证！";
        $_SESSION['message_type'] = "error";
        header('Location: index.php');
        exit;
    }
}

// 登录检查
if (!isset($_SESSION['admin_logged_in'])) {
    // 显示登录表单
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>登录 - QUDL 管理面板</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                background: #f0f4f8;
                height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', sans-serif;
            }
            .login-container {
                background: white;
                padding: 2.5rem;
                border-radius: 1rem;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                width: 380px;
            }
            .login-form input {
                width: 100%;
                padding: 0.75rem;
                margin: 0.5rem 0;
                border: 1px solid #e2e8f0;
                border-radius: 0.375rem;
                transition: border-color 0.2s;
            }
            .login-form input:focus {
                outline: none;
                border-color: #2196F3;
                box-shadow: 0 0 0 3px rgba(33,150,243,0.1);
            }
            .btn-login {
                background: #2196F3;
                color: white;
                width: 100%;
                padding: 0.75rem;
                border: none;
                border-radius: 0.375rem;
                font-weight: 500;
                cursor: pointer;
                transition: opacity 0.2s;
            }
            .btn-login:hover {
                opacity: 0.9;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2 style="text-align: center; margin-bottom: 1.5rem; color: #1e293b;">管理员登录</h2>
            <form method="post" class="login-form">
                <input type="text" name="username" placeholder="用户名" required>
                <input type="password" name="password" placeholder="密码" required>
                <button type="submit" name="login" class="btn-login">登录</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 配置上传限制
ini_set('max_file_uploads', 1000);
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $version = $_POST['version'];
    $baseDir = realpath('../mods/');
    $uploadDir = $baseDir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadSuccess = true;
    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        // 检查文件上传错误
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
            $uploadSuccess = false;
            continue;
        }

        $filename = basename($_FILES['files']['name'][$key]);
        $targetPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($tmpName, $targetPath)) {
            $uploadSuccess = false;
        }
    }

    if ($uploadSuccess) {
        $_SESSION['message'] = "文件上传成功！";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "部分文件上传失败，请检查文件格式和权限！";
        $_SESSION['message_type'] = "error";
    }
    header("Location: index.php");
    exit;
}

// 处理文件删除
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $file = $_GET['file'];
    $version = $_GET['version'];
    $basePath = realpath("../mods/");
    $targetPath = realpath("../mods/{$version}/" . basename($file));
    
    if ($targetPath && strpos($targetPath, $basePath) === 0) {
        if (unlink($targetPath)) {
            $_SESSION['message'] = "文件删除成功！";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "文件删除失败！";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "无效的文件路径！";
        $_SESSION['message_type'] = "error";
    }
    header("Location: index.php");
    exit;
}

// 获取现有版本和模组数量
$modVersions = [];
$totalMods = 0;
foreach (glob('../mods/*', GLOB_ONLYDIR) as $dir) {
    $versionName = basename($dir);
    $files = array_filter(glob($dir . '/*'), 'is_file');
    $fileCount = count($files);
    $modVersions[] = [
        'name' => $versionName,
        'path' => $dir,
        'count' => $fileCount
    ];
    $totalMods += $fileCount;
}

// 文件大小格式化函数
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>QUDL 管理面板</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6bdf;
            --danger: #e74c3c;
            --background: #f5f7fa;
            --version-header: #5d78ff;
            --version-bg: #edf0ff;
            --file-bg: #ffffff;
            --file-border: #e0e4ee;
            --text-dark: #1e293b;
            --text-medium: #475569;
            --text-light: #64748b;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--background);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
        }
        .alert-success {
            color: #166534;
            background-color: #dcfce7;
            border-color: #bbf7d0;
        }
        .alert-error {
            color: #991b1b;
            background-color: #fee2e2;
            border-color: #fecaca;
        }

        .stats {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
        }
        .stat-item {
            font-size: 0.9rem;
            color: var(--text-medium);
        }
        .stat-number {
            font-weight: 600;
            color: var(--text-dark);
        }

        .search-container {
            margin-bottom: 2rem;
            display: flex;
            gap: 0.5rem;
        }
        .search-input {
            flex-grow: 1;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,107,223,0.1);
        }
        .search-select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
        }
        .search-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .version {
            margin-bottom: 1.5rem;
        }

        .version-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: var(--version-header);
            color: white;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .version-header:hover {
            background-color: #3a56d0;
        }

        .version-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .version-count {
            background: white;
            color: var(--version-header);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .version-toggle {
            transition: transform 0.3s ease;
        }

        .version-toggle.collapsed {
            transform: rotate(-90deg);
        }

        .version-content {
            background: var(--version-bg);
            padding: 1.5rem;
            border-radius: 0 0 0.75rem 0.75rem;
            border: 1px solid var(--file-border);
            border-top: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .version-content.collapsed {
            max-height: 0 !important;
            padding-top: 0;
            padding-bottom: 0;
            border: none;
            opacity: 0;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--file-bg);
            border: 1px solid var(--file-border);
            border-radius: 0.5rem;
            margin: 0.75rem 0;
            transition: all 0.2s ease;
        }
        .file-item:hover {
            transform: translateX(4px);
        }
        .file-name {
            font-weight: 500;
            color: var(--text-dark);
        }
        .file-hash {
            font-family: monospace;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }

        .highlight {
            background-color: #ffeb3b;
            padding: 0 2px;
            border-radius: 2px;
        }

        .no-results {
            text-align: center;
            padding: 1rem;
            color: var(--text-medium);
            display: none;
        }

        .version:nth-child(odd) .version-header {
            background-color:rgb(90, 175, 230);
        }
        .version:nth-child(odd) .version-header:hover {
            background-color: rgb(80, 165, 220);
        }
        .version:nth-child(odd) .version-count {
            color: rgb(90, 175, 230);
        }
        .version:nth-child(odd) .version-content {
            background: #f3f1ff;
        }

        .version:nth-child(even) .version-header {
            background-color: rgb(90, 175, 230);
        }
        .version:nth-child(even) .version-header:hover {
            background-color: rgb(80, 165, 220);
        }
        .version:nth-child(even) .version-count {
            color: rgb(90, 175, 230);
        }
        .version:nth-child(even) .version-content {
            background: #e8f8f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="stats">
            <div class="stat-item">
                总模组数量: <span class="stat-number"><?= $totalMods ?></span>
            </div>
            <div class="stat-item">
                支持版本数: <span class="stat-number"><?= count($modVersions) ?></span>
            </div>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] === 'success' ? 'success' : 'error' ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="搜索模组名称..." onkeyup="searchMods()">
            <select id="search-scope" class="search-select" onchange="searchMods()">
                <option value="global">全局搜索</option>
                <?php foreach ($modVersions as $version): ?>
                    <option value="<?= $version['name'] ?>"><?= $version['name'] ?>版本</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="file-list">
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">现有模组版本</h2>
            <div id="no-results" class="no-results">没有找到匹配的模组</div>
            
            <?php foreach ($modVersions as $index => $version): ?>
                <div class="version" id="version-<?= $version['name'] ?>">
                    <div class="version-header" onclick="toggleVersion(this)">
                        <div class="version-title">
                            <i class="fas fa-cube"></i>
                            <span><?= $version['name'] ?></span>
                            <span class="version-count"><?= $version['count'] ?> 模组</span>
                        </div>
                        <i class="fas fa-chevron-down version-toggle"></i>
                    </div>
                    
                    <div class="version-content">
                        <?php foreach (new DirectoryIterator($version['path']) as $file): ?>
                            <?php if ($file->isFile()): ?>
                                <div class="file-item" data-version="<?= $version['name'] ?>" data-name="<?= $file->getFilename() ?>">
                                    <div style="flex-grow: 1;">
                                        <div class="file-name"><?= $file->getFilename() ?></div>
                                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                            <span style="color: var(--text-light);">
                                                <?= formatSize($file->getSize()) ?>
                                            </span>
                                            <span class="file-hash" title="SHA1哈希：<?= sha1_file($file->getPathname()) ?>">
                                                哈希：<?= substr(sha1_file($file->getPathname()), 0, 12) ?>...
                                            </span>
                                        </div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="?action=delete&version=<?= $version['name'] ?>&file=<?= urlencode($file->getFilename()) ?>" 
                                           class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // 版本折叠功能
        function toggleVersion(header) {
            const versionContainer = header.parentElement;
            const content = versionContainer.querySelector('.version-content');
            const toggleIcon = header.querySelector('.version-toggle');
            
            content.classList.toggle('collapsed');
            toggleIcon.classList.toggle('collapsed');
        }

        // 初始化折叠状态
        document.addEventListener('DOMContentLoaded', function() {
            // 设置所有版本内容的初始高度
            document.querySelectorAll('.version-content').forEach(content => {
                content.style.maxHeight = content.scrollHeight + 'px';
            });
        });

        // 搜索功能 - 修复版
        function searchMods() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const searchScope = document.getElementById('search-scope').value;
            const noResultsMsg = document.getElementById('no-results');
            let hasResults = false;
            
            // 重置所有项目
            document.querySelectorAll('.file-item').forEach(item => {
                item.style.display = '';
            });
            
            // 移除之前的高亮
            document.querySelectorAll('.highlight').forEach(el => {
                const parent = el.parentNode;
                parent.textContent = parent.textContent;
            });
            
            // 隐藏"没有结果"消息
            noResultsMsg.style.display = 'none';
            
            if (!searchTerm) {
                // 如果没有搜索词，显示所有项目并展开所有版本
                document.querySelectorAll('.version').forEach(version => {
                    version.style.display = '';
                    const content = version.querySelector('.version-content');
                    content.classList.remove('collapsed');
                    version.querySelector('.version-toggle').classList.remove('collapsed');
                });
                return;
            }
            
            // 搜索逻辑
            document.querySelectorAll('.version').forEach(version => {
                const versionName = version.id.replace('version-', '');
                const versionHeader = version.querySelector('.version-header');
                const versionContent = version.querySelector('.version-content');
                const versionToggle = version.querySelector('.version-toggle');
                let versionHasMatches = false;
                
                // 检查是否在搜索范围内
                const inScope = (searchScope === 'global') || (versionName === searchScope);
                
                if (inScope) {
                    version.style.display = '';
                    versionContent.classList.remove('collapsed');
                    versionToggle.classList.remove('collapsed');
                    
                    // 检查该版本下的文件
                    version.querySelectorAll('.file-item').forEach(item => {
                        const fileName = item.getAttribute('data-name').toLowerCase();
                        const fileDiv = item.querySelector('.file-name');
                        
                        if (fileName.includes(searchTerm)) {
                            item.style.display = '';
                            versionHasMatches = true;
                            hasResults = true;
                            
                            // 高亮匹配的文本
                            const originalText = fileDiv.textContent;
                            const regex = new RegExp(searchTerm, 'gi');
                            const highlightedText = originalText.replace(regex, match => 
                                `<span class="highlight">${match}</span>`
                            );
                            fileDiv.innerHTML = highlightedText;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // 根据是否有匹配项显示/隐藏整个版本
                    if (!versionHasMatches && searchScope !== 'global') {
                        version.style.display = 'none';
                    }
                } else {
                    version.style.display = 'none';
                }
            });
            
            // 显示"没有结果"消息
            if (!hasResults) {
                noResultsMsg.style.display = 'block';
            }
        }
    </script>
</body>
</html>