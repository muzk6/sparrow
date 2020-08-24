<?php

require __DIR__ . '/../../../init.php';

if (!app(\Core\Auth::class)->isLogin()) {
    redirect('/index/login');
}

$file = $_GET['file'] ?? '';
if ($file) {
    $filename = base64_decode(str_replace(['-', '_'], ['+', '/'], rtrim($file, '.xt')));
    $traceFilename = preg_replace('/^.*?\./', '', $filename);
    $traceData = json_decode($traceFilename, true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>XDebug Trace</title>
    <link rel="stylesheet" href="res/style.css">
    <?php
    echo view('ops/inc_header');
    ?>
    <script src="res/script.js"></script>
</head>
<body>
<div id="app">
    <el-container>
        <el-header>
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item><a style="cursor: pointer" href="/xdebug/index">跟踪文件</a></el-breadcrumb-item>
                <el-breadcrumb-item><a style="cursor: pointer"
                                       @click="location.reload()"><?php echo $traceData['url'] ?><i
                                class="el-icon-refresh"></i></a></el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main v-pre>
            <form class="options">
                <input type="checkbox" value="1" checked="checked" id="internal">
                <label for="internal">Show internal functions</label>

                <input type="checkbox" value="1" id="marked">
                <label for="marked">Show important only (slow)</label>
            </form>

            <?php
            if (!empty($_GET['file'])) {
                require_once __DIR__ . '/res/XDebugParser.php';
                $parser = new XDebugParser(PATH_TRACE . "/{$_GET['file']}");
                $parser->parse();
                echo $parser->getTraceHTML();
            }
            ?>
        </el-main>
    </el-container>
</div>
<script>
    (() => {
        new Vue({
            el: '#app',
            data: function () {
                return {}
            }
        })
    })();
</script>
</body>
</html>
