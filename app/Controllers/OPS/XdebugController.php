<?php


namespace App\Controllers\OPS;


use Core\AppException;

class XdebugController extends BaseOPSController
{
    public function index()
    {
        $files = glob(PATH_TRACE . '/*.xt');
        $expire = strtotime('-2 hours');
        $data = [];

        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime < $expire) { // 删除过期文件
                unlink($file);
                continue;
            }

            $basename = basename($file);
            $filename = base64_decode(str_replace(['-', '_'], ['+', '/'], rtrim($basename, '.xt')));
            $traceData = json_decode($filename, true);

            $data[] = [
                'name' => $basename,
                'mtime_ts' => $mtime,
                'mtime' => date('Y-m-d H:i:s', $mtime),
                'trace' => $traceData['trace'],
                'user_id' => $traceData['user_id'],
                'url' => $traceData['url'],
            ];
        }

        usort($data, function ($a, $b) {
            return $b['mtime_ts'] - $a['mtime_ts'];
        });

        return view('ops/xdebug/index', ['data' => $data]);
    }

    /**
     * 监听页面
     * @return string
     */
    public function listenPage()
    {
        $traceConf = [
            'url' => '',
            'name' => '',
            'user_id' => '',
            'expire_second' => 120,
            'max_depth' => 0,
            'max_data' => 0,
            'max_children' => 0,
        ];

        $traceConfFile = PATH_DATA . '/.tracerc';
        if (file_exists($traceConfFile)) {
            $traceConf = array_merge($traceConf, include($traceConfFile));

            // 已过期
            if ($traceConf['expire'] <= time()) {
                unlink($traceConfFile);
                return $this->listenPage();
            }

            $traceConf['en'] = 1;
        } else {
            $traceConf['en'] = 0;
            $traceConf['max_depth'] = intval(ini_get('xdebug.var_display_max_depth'));
            $traceConf['max_data'] = intval(ini_get('xdebug.var_display_max_data'));
            $traceConf['max_children'] = intval(ini_get('xdebug.var_display_max_children'));
        }

        return view('ops/xdebug/listen', ['traceConf' => $traceConf]);
    }

    /**
     * 监听设置
     * @throws AppException
     */
    public function listen()
    {
        $url = validate('post.url')->required()->get('URL');
        $name = validate('post.name')->required()->get('标签名');
        $userId = input('post.user_id');
        $expireSecond = validate('post.expire_second:i')->numeric()->lte(600)->get('过期秒数');
        $maxDepth = validate('post.max_depth:i')->numeric()->get('Max Depth ');
        $maxData = validate('post.max_data:i')->numeric()->get('Max Data ');
        $maxChildren = validate('post.max_children:i')->numeric()->get('Max Children ');

        $traceConfFile = PATH_DATA . '/.tracerc';

        $conf = [
            'url' => $url,
            'name' => $name,
            'user_id' => $userId,
            'expire' => $expireSecond + TIME,
            'expire_second' => $expireSecond,
            'max_depth' => $maxDepth,
            'max_data' => $maxData,
            'max_children' => $maxChildren,
        ];

        file_put_contents($traceConfFile,
            "<?php\nreturn " . var_export($conf, true) . ";\n");

        return api_success('监听开启');
    }
}
