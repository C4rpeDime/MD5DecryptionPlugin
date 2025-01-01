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