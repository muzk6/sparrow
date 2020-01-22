<?php


namespace App\Controllers\OPS;


class LogController extends BaseOPSController
{
    /**
     * 日志文件列表
     * @return string
     */
    public function index()
    {
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
    }

    /**
     * 日志页面
     */
    public function content()
    {
        $file = input('get.file');
        if (!$file) {
            return redirect('/index.php');
        }

        return view('ops/log/content', ['file' => $file]);
    }

    /**
     * 更多日志内容，分页
     */
    public function more()
    {
        $file = input('get.file');
        if (!$file) {
            return redirect('/index.php');
        }

        $offset = input('get.offset:i', 0);
        $limit = input('get.limit:i', 10);

        $fo = new \SplFileObject(PATH_DATA . "/log/{$file}", 'rb');
        $fo->seek(PHP_INT_MAX);

        $start = $fo->key() - ($offset + $limit);
        $end = $start + $limit;
        $buf = [];
        for ($i = $start; $i < $end; $i++) {
            $fo->seek($i);
            $buf[] = $fo->current();
            $fo->next();
        }

        $isJson = strpos($file, 'unhandled_') === false;
        if ($isJson) {
            $content = [];
            foreach ($buf as $v) {
                $content[] = print_r(json_decode($v, true), true);
            }
            $data['content'] = implode("\n", $content);
        } else {
            $data['content'] = implode("\n", $buf);
        }

        return $data;
    }
}
