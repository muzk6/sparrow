<?php


namespace App\Controllers\OPS;


use Core\AppException;

class LogController extends BaseOPSController
{
    public function beforeAction()
    {
        if (!$this->isLogin) {
            redirect('/index/login');
            return false;
        }
    }

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
     * @throws AppException
     */
    public function more()
    {
        $file = input('get.file');
        if (!$file) {
            return redirect('/index.php');
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
    }
}
