<?php
require_once __DIR__ . '/../../init.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>XDebug Trace</title>
</head>
<body>

<div>XDebug Trace. 点击下面的记录查看详情</div>
<hr>
<div>
    <ul>
        <?php
        $dir = PATH_TRACE;
        $files = glob("$dir/*.xt");
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $expire = strtotime('-3 days');
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime < $expire) { // 删除过期文件
                unlink($file);
                continue;
            }

            $mtime = date('Y-m-d H:i:s', $mtime);

            $filename = base64_decode(str_replace(['-', '_'], ['+', '/'], rtrim($file, '.xt')));
            $traceFilename = preg_replace('/^.*?\./', '', $filename);

            echo "<li><a href='/detail.php?file={$file}'>{$traceFilename}</a>  <small>{$mtime}</small></li>";
        }
        ?>
    </ul>
</div>

</body>
</html>
