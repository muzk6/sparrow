<!DOCTYPE html>
<html>
<head>
    <title>My Webpage</title>
</head>
<body>
<ul id="navigation">
    Hello {{ $name }}.
</ul>

<h1>My Webpage</h1>
<form method="post" action="/index/index">
    {!! app(\Core\AppCSRF::class)->field() !!}
    <button type="submit">submit</button>
</form>
</body>
</html>
