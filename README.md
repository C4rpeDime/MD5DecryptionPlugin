最近，我在折腾MD5解密平台，突然想到好多年前的一个名为 [Md5Decrypt](https://github.com/tyekrgk/Md5Decrypt) 的GitHub项目，它是一个使用多个在线API来解密MD5的开源工具。受到启发，我决定写一份PHP版本的多接口MD5解密工具。在这篇博文中，我将详细介绍构建这个工具的过程。

## 项目概述

目标是创建一个网页工具，接受MD5哈希作为输入，并通过多个在线API尝试解密。这增加了成功找到原始字符串的机会，因为不同的API可能有不同的哈希数据库。

我计划：

- 使用PHP处理与外部API的服务器端请求。
- 使用HTML、CSS（Bootstrap）和JavaScript构建用户友好的前端。
- 确保工具能够有效解析和显示结果，即使不同API返回的数据格式不同。

## 构建后端：

### 设置PHP脚本

后端脚本`proxy.php`充当前端和外部MD5解密API之间的代理。它接收来自前端的POST请求，将其转发到适当的API，并返回结果。

以下是`proxy.php`的基本结构：

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 根据POST参数确定目标API
    // 准备并发送请求到外部API
    // 处理响应并将结果返回给前端
}
?>
```

### 处理不同的API

我支持了几个API：

1. **棉花糖MD5解密**
2. **T007解密**
3. **MD5.li解密**
4. **My-Addr解密**

每个API都有自己的端点和预期参数。脚本通过检查特定的POST参数来决定调用哪个API。

```php
if (isset($_POST['md5'])) {
    $url = 'https://vip.bdziyi.com/hygj/md5api.php';
} elseif (isset($_POST['type']) && $_POST['type'] == 1) {
    $url = 'https://t007.cn/home/index/doEnDecode';
} elseif (isset($_POST['md5li']) && $_POST['md5li'] == 1) {
    $url = 'https://md5.li/';
} elseif (isset($_POST['myaddr']) && $_POST['myaddr'] == 1) {
    $url = 'http://md5.my-addr.com/md5_decrypt-md5_cracker_online/md5_decoder_tool.php';
    $postData = http_build_query(['md5' => $_POST['hash'], 'x' => 24, 'y' => 7]);
} else {
    echo json_encode(['error' => '无效的请求参数']);
    exit;
}
```

### 发送请求并处理响应

为每个API准备请求数据，并发送HTTP请求。处理响应时，需要根据API返回的数据格式解析结果。

```php
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                     "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\r\n",
        'method'  => 'POST',
        'content' => $postData,
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo json_encode(['error' => '请求失败']);
} else {
    // 根据不同API的响应格式解析结果
}
```

## 构建前端

前端使用Bootstrap来创建一个简单直观的界面，用户可以在此输入MD5值并查看解密结果。

### HTML结构

```html
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">MD5 解密服务</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="#">主页 <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">关于</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">联系</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- 主内容 -->
    <div class="container mt-5">
        <h1 class="mb-4">MD5解密</h1>
        <div class="form-group">
            <label for="md5Input">输入 MD5 值</label>
            <input type="text" class="form-control" id="md5Input" placeholder="请输入 MD5 值">
        </div>
        <button id="decryptBtn" class="btn btn-primary">解密</button>
        <div id="resultSection" class="mt-4">
            <h3>解密结果：</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>平台名</th>
                        <th>MD5值</th>
                        <th>解密结果</th>
                    </tr>
                </thead>
                <tbody id="resultTable">
                    <!-- 解密结果将插入到这里 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 页脚 -->
    <footer>
        <p>&copy; 2025 MD5 解密服务. 保留所有权利.</p>
    </footer>
</body>
```

### JavaScript逻辑

使用JavaScript处理用户输入并与后端交互，获取解密结果。

```javascript
 document.getElementById('decryptBtn').addEventListener('click', function() {
            const md5Value = document.getElementById('md5Input').value;
            const platforms = [
                { name: '棉花糖', param: 'md5' },
                { name: 'T007', param: 'type=1&txtInput' },
                { name: 'MD5.li', param: 'md5li=1&hash' },
                { name: 'My-Addr', param: 'myaddr=1&hash' }
            ];

            // 清除旧结果
            document.getElementById('resultTable').innerHTML = '';
            document.getElementById('resultSection').style.display = 'none';

            platforms.forEach(platform => {
                fetch("proxy.php", {
                    headers: {
                        "accept": "application/json, text/javascript, */*; q=0.01",
                        "content-type": "application/x-www-form-urlencoded; charset=UTF-8"
                    },
                    body: `${platform.param}=${md5Value}`,
                    method: "POST"
                })
                .then(response => response.json())
                .then(data => {
                    let result;
                    if (data.result) {
                        result = data.result;
                    } else if (data.error && data.link) {
                        result = `需要1积分查看，请访问 <a href="${data.link}" target="_blank">md5.li</a>`;
                    } else {
                        result = '很遗憾，没有解密成功';
                    }
                    const row = `<tr><td>${platform.name}</td><td>${md5Value}</td><td>${result}</td></tr>`;
                    document.getElementById('resultTable').insertAdjacentHTML('beforeend', row);
                    document.getElementById('resultSection').style.display = 'block'; // 显示结果
                })
                .catch(error => {
                    const row = `<tr><td>${platform.name}</td><td>${md5Value}</td><td>错误: ${error}</td></tr>`;
                    document.getElementById('resultTable').insertAdjacentHTML('beforeend', row);
                    document.getElementById('resultSection').style.display = 'block'; // 显示结果
                });
            });
        });
```
最后的结果如图
![微信图片_20250102074306.png](https://www.1042.net/usr/uploads/2025/01/1935259974.png)
代码：
Proxy.php
```
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查请求参数以决定目标 URL
    if (isset($_POST['md5'])) {
        $url = 'https://vip.bdziyi.com/hygj/md5api.php'; // 棉花糖MD5解密
    } elseif (isset($_POST['type']) && $_POST['type'] == 1) {
        $url = 'https://t007.cn/home/index/doEnDecode'; // T007解密
    } elseif (isset($_POST['md5li']) && $_POST['md5li'] == 1) {
        $url = 'https://md5.li/'; // MD5.li解密
    } elseif (isset($_POST['myaddr']) && $_POST['myaddr'] == 1) {
        $url = 'http://md5.my-addr.com/md5_decrypt-md5_cracker_online/md5_decoder_tool.php'; // My-Addr解密
        $postData = http_build_query(['md5' => $_POST['hash'], 'x' => 24, 'y' => 7]);
    } else {
        echo json_encode(['error' => '无效的请求参数']);
        exit;
    }

    if (!isset($postData)) {
        $postData = http_build_query($_POST);
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                         "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36\r\n",
            'method'  => 'POST',
            'content' => $postData,
            'ignore_errors' => true, // 忽略错误以获取响应内容
        ],
        'ssl' => [
            'verify_peer' => false, // 禁用 SSL 证书验证
            'verify_peer_name' => false, // 禁用对主机名的验证
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        echo json_encode(['error' => '请求失败']);
    } else {
        if (strpos($url, 'my-addr') !== false) {
            // 处理 My-Addr 的 HTML 响应
            preg_match('/<div class=\'white_bg_title\'><span class=\'middle_title\'>Hashed string<\/span>: ([^<]+)<\/div>/', $result, $matches);
            if (isset($matches[1])) {
                echo json_encode(['result' => $matches[1]]);
            } else {
                echo json_encode(['error' => '未找到解密结果']);
            }
        } elseif (strpos($url, 't007') !== false) {
            // 处理 T007 的响应
            $data = json_decode($result, true);
            if (isset($data['code']) && $data['code'] === 1) {
                // 提取解密成功的结果
                preg_match('/解密成功，结果是：(.+)/', $data['desc'], $matches);
                if (isset($matches[1])) {
                    echo json_encode(['result' => $matches[1]]);
                } else {
                    echo json_encode(['error' => '很遗憾，没有解密成功']);
                }
            } else {
                echo json_encode(['error' => '很遗憾，没有解密成功']);
            }
        } elseif (strpos($url, 'md5.li') !== false) {
            // 处理 MD5.li 的 HTML 响应
            if (strpos($result, 'MD5解密成功，需要<strong>1</strong>积分查看') !== false) {
                echo json_encode(['error' => '需要1积分查看', 'link' => 'https://md5.li/']);
            } else {
                preg_match('/<strong>明文:<\/strong>\s*([^<]+)<\/div>/', $result, $matches);
                if (isset($matches[1])) {
                    echo json_encode(['result' => trim($matches[1])]);
                } else {
                    echo json_encode(['error' => '未找到解密结果']);
                }
            }
        } else {
            // 假设棉花糖返回的是 HTML，需要解析
            preg_match('/解密为：(\w+)/', $result, $matches);
            if (isset($matches[1])) {
                echo json_encode(['result' => $matches[1]]);
            } else {
                echo json_encode(['error' => '未找到解密结果']);
            }
        }
    }
}
?>
```
index.html
```
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MD5解密</title>
    <link href="https://cdn.staticfile.net/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        #resultSection {
            display: none;
        }
        footer {
            background-color: #343a40;
            color: white;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">MD5 解密服务</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="#">主页 <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">关于</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">联系</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- 主内容 -->
    <div class="container mt-5">
        <h1 class="mb-4">MD5解密</h1>
        <div class="form-group">
            <label for="md5Input">输入 MD5 值</label>
            <input type="text" class="form-control" id="md5Input" placeholder="请输入 MD5 值">
        </div>
        <button id="decryptBtn" class="btn btn-primary">解密</button>
        <div id="resultSection" class="mt-4">
            <h3>解密结果：</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>平台名</th>
                        <th>MD5值</th>
                        <th>解密结果</th>
                    </tr>
                </thead>
                <tbody id="resultTable">
                    <!-- 解密结果将插入到这里 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 页脚 -->
    <footer>
        <p>&copy; 2025 MD5 解密服务. 保留所有权利.</p>
    </footer>

    <script src="https://cdn.staticfile.net/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.staticfile.net/twitter-bootstrap/4.6.1/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('decryptBtn').addEventListener('click', function() {
            const md5Value = document.getElementById('md5Input').value;
            const platforms = [
                { name: '棉花糖', param: 'md5' },
                { name: 'T007', param: 'type=1&txtInput' },
                { name: 'MD5.li', param: 'md5li=1&hash' },
                { name: 'My-Addr', param: 'myaddr=1&hash' }
            ];

            // 清除旧结果
            document.getElementById('resultTable').innerHTML = '';
            document.getElementById('resultSection').style.display = 'none';

            platforms.forEach(platform => {
                fetch("proxy.php", {
                    headers: {
                        "accept": "application/json, text/javascript, */*; q=0.01",
                        "content-type": "application/x-www-form-urlencoded; charset=UTF-8"
                    },
                    body: `${platform.param}=${md5Value}`,
                    method: "POST"
                })
                .then(response => response.json())
                .then(data => {
                    let result;
                    if (data.result) {
                        result = data.result;
                    } else if (data.error && data.link) {
                        result = `需要1积分查看，请访问 <a href="${data.link}" target="_blank">md5.li</a>`;
                    } else {
                        result = '很遗憾，没有解密成功';
                    }
                    const row = `<tr><td>${platform.name}</td><td>${md5Value}</td><td>${result}</td></tr>`;
                    document.getElementById('resultTable').insertAdjacentHTML('beforeend', row);
                    document.getElementById('resultSection').style.display = 'block'; // 显示结果
                })
                .catch(error => {
                    const row = `<tr><td>${platform.name}</td><td>${md5Value}</td><td>错误: ${error}</td></tr>`;
                    document.getElementById('resultTable').insertAdjacentHTML('beforeend', row);
                    document.getElementById('resultSection').style.display = 'block'; // 显示结果
                });
            });
        });
    </script>
</body>
</html>
```
