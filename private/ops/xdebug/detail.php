<?php
require_once __DIR__ . '/../../../init.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>XDebug Trace</title>
    <link rel="stylesheet" href="res/style.css">
    <script src="https://code.jquery.com/jquery-2.2.1.min.js"></script>
    <script src="res/script.js"></script>
</head>
<body>
<div style="float: left;word-break: break-all; width: 550px">
    <ul>
        <?php
        $file = $_GET['file'] ?? '';
        if ($file) {
            $filename = base64_decode(str_replace(['-', '_'], ['+', '/'], rtrim($file, '.xt')));
            $traceFilename = preg_replace('/^.*?\./', '', $filename);
            $traceData = json_decode($traceFilename, true);

            echo "<li>URL: {$traceData['url']}</li>";
            echo "<li>标签名: {$traceData['trace']}</li>";
            echo "<li>用户ID: {$traceData['user_id']}</li>";
        }
        ?>
    </ul>
</div>
<div style="float: right">
    <a style="margin-left: 20px;" href="/xdebug/index">>>>返回跟踪列表</a>
    <hr>
    <ul>
        <li>load a trace file from the dropdown</li>
        <li>click a left margin to collapse a whole sub tree</li>
        <li>click a function name to collapse all calls to the same function</li>
        <li>click the parameter list to expand it</li>
        <li>click the return list to expand it</li>
        <li>click the time to mark the line important</li>
        <li>use checkboxes to hide all PHP internal functions or limit to important lines</li>
    </ul>
</div>

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

</body>
</html>
