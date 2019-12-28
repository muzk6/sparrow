<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>

<h1>{{ $title }}</h1>
<form method="post" action="/demo/doc">
    {!! csrf_field() !!}
    <input type="text" name="first_name" value="{{ $firstName }}">
    <input type="text" name="last_name" value="{{ $lastName }}">
    <button>Doc Submit</button>
    <input type="button" id="ok" value="XHR Submit"/>
</form>
<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.min.js"></script>
<script>
    @if (flash_has('msg'))
    alert('{{ flash_get('msg') }}');
    @endif

    @if (flash_has('data'))
    alert('{!! json_encode(flash_get('data')) !!}');
    @endif

    $(function () {
        $('#ok').on('click', function () {
            let data = {};
            $('form input[name]').each(function () {
                data[$(this).attr('name')] = $(this).val();
            });
            $.post('/demo/xhr', data, function (data) {
                alert(JSON.stringify(data));
            }, 'json');
        });
    });
</script>
</body>
</html>
