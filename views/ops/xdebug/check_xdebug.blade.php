<script>
    @if (!extension_loaded('xdebug'))
    top.V_INSTANCE.$message({
        showClose: true,
        message: '请安装扩展: xdebug',
        type: 'error'
    });
    @endif
</script>