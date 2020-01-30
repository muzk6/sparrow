<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    @include('ops/inc_header')
</head>
<body style="overflow:hidden;">
<div id="app">
    <el-container>
        <el-aside width="200px">
            <el-radio-group v-model="isCollapse" style="margin-bottom: 20px;">
                <el-radio-button :label="false">展开</el-radio-button>
                <el-radio-button :label="true">收起</el-radio-button>
            </el-radio-group>
            <el-menu ref="menu" default-active="/log/index" @select="handleSelect"
                     :collapse="isCollapse">
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
                <el-menu-item index="xhprof/xhprof_html/index.php">
                    <i class="el-icon-stopwatch"></i>
                    <span slot="title">XHProf</span>
                </el-menu-item>
            </el-menu>
        </el-aside>
        <el-main>
            <iframe :src="contentLink"
                    :style="iframeStyle"></iframe>
        </el-main>
    </el-container>
</div>
</body>
<script>
    var V_INSTANCE;
    (() => {
        V_INSTANCE = new Vue({
            el: '#app',
            data: function () {
                return {
                    isCollapse: true,
                    contentLink: '/log/index',
                    iframeStyle: `margin:0;padding:0;height:${screen.height - 180}px;width:100%;border:0`
                }
            },
            methods: {
                handleSelect: function (index) {
                    this.contentLink = index;
                }
            }
        })
    })();
</script>
</html>
