<?php

/**
 * Log
 */

/**
 * 日志文件列表
 */
route_get('/log/index', function () {
    $files = glob(PATH_DATA . '/log/*.log');

    // 时间倒序
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $expire = strtotime('-1 month');
    $data = [];

    foreach ($files as $file) {
        $mtime = filemtime($file);
        if ($mtime < $expire) {
            unlink($file); // 删除旧日志
            continue;
        }

        $data[] = [
            'name' => urlencode(basename($file)),
            'mtime' => date('Y-m-d H:i:s', $mtime),
        ];
    }

    return view('ops/log/index', ['data' => $data]);
});

/**
 * 日志页面
 */
route_get('/log/content', function () {
    $file = input('get.file');
    if (!$file) {
        redirect('/index.php');
    }

    return view('ops/log/content', ['file' => $file]);
});

/**
 * 更多日志内容，分页
 */
route_get('/log/more', function () {
    $file = input('get.file');
    if (!$file) {
        redirect('/index.php');
    }

    $offset = input('get.offset:i', -1); // -1.最后一行; -2.已经超过文件顶部，即没有内容
    $limit = input('get.limit:i', 10);

    if ($offset == -2) {
        panic('已经到顶啦');
    }

    $fo = new \SplFileObject(PATH_DATA . "/log/{$file}", 'rb');
    if ($offset == -1) {
        $fo->seek(PHP_INT_MAX);
        $offset = $fo->key() - 1;
    }

    $buf = [];
    $i = 0;
    while ($i++ < $limit) {
        if ($offset < 0) {
            break;
        }

        $fo->seek($offset);
        $buf[] = $fo->current();

        $offset--;
    }

    $data['offset'] = $offset < 0 ? -2 : $offset;
    $buf = array_reverse($buf);

    $content = [];
    foreach ($buf as $v) {
        $json = json_decode($v, true);
        if (is_null($json)) {
            $content[] = $v;
        } else {
            $content[] = print_r($json, true);
        }
    }
    $data['content'] = implode("\n", $content);

    return $data;
});