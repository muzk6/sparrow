<!DOCTYPE html>
<html>
<head>
    @include('ops/inc_header')
</head>
<body style="overflow:hidden;">
<div id="app">
    <el-container>
        <el-aside width="200px">
            <el-menu ref="menu" default-active="/log/index" @select="handleSelect">
                <el-menu-item index="/log/index">
                    <i class="el-icon-notebook-2"></i>
                    <span slot="title">日志</span>
                </el-menu-item>
                <el-submenu>
                    <template slot="title">
                        <i class="el-icon-search"></i>
                        <span slot="title">XDebug</span>
                    </template>
                    <el-menu-item index="/xdebug/index">跟踪文件</el-menu-item>
                    <el-menu-item index="/xdebug/listenPage">监听</el-menu-item>
                </el-submenu>
                <el-menu-item index="/xhprof/xhprof_html/index.php">
                    <i class="el-icon-stopwatch"></i>
                    <span slot="title">XHProf</span>
                </el-menu-item>
                <el-menu-item index="/index/logout">
                    <i class="el-icon-lock"></i>
                    <span slot="title">退出登录</span>
                </el-menu-item>
            </el-menu>
        </el-aside>
        <el-main>
            <iframe :src="contentLink"
                    :style="iframeStyle"></iframe>
        </el-main>
    </el-container>
</div>
<script>
    var V_INSTANCE;
    (() => {
        V_INSTANCE = new Vue({
            el: '#app',
            data: function () {
                return {
                    contentLink: '/log/index',
                    iframeStyle: `margin:0;padding:0;height:${screen.height - 180}px;width:100%;border:0`
                }
            },
            methods: {
                handleSelect: function (index) {
                    if (index == '/index/logout') {
                        location.href = index;
                    } else {
                        this.contentLink = index;
                    }
                }
            }
        })
    })();
</script>
</body>
</html>
