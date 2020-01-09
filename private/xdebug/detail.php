<?php
require_once __DIR__ . '/../../init.php';
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
<div style="float: left;">
    <?php
    $file = $_GET['file'] ?? '';
    if ($file) {
        $filename = base64_decode(str_replace(['-', '_'], ['+', '/'], rtrim($file, '.xt')));
        $traceFilename = preg_replace('/^.*?\./', '', $filename);
        echo $traceFilename;
    }
    ?>
</div>
<div style="float: right">
    <a style="margin-left: 20px;" href="/index.php">Home</a>
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
    require_once 'res/XDebugParser.php';
    $parser = new XDebugParser($_GET['file']);
    $parser->parse();
    echo $parser->getTraceHTML();
}
?>

</body>
</html>
