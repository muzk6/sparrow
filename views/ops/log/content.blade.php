<!DOCTYPE html>
<html>
<head>
    @include('ops/inc_header')
</head>
<body style="overflow: hidden">
<div id="app">
    <el-container>
        <el-header>
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item><a href="/log/index">日志文件</a></el-breadcrumb-item>
                <el-breadcrumb-item><a style="cursor: pointer;" @click="location.reload()">{{ $file }}</a>
                </el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <el-row type="flex" class="row-bg" justify="center">
                <el-col :span="6">
                    <div class="grid-content bg-purple">
                        <el-button @click="load" title="往前加载" type="primary" icon="el-icon-arrow-up"
                                   circle></el-button>
                    </div>
                </el-col>
            </el-row>
            <pre ref="preData" :style="preStyle">@{{ preData }}</pre>
        </el-main>
    </el-container>
</div>
</body>
<script>
    let file = '{{ $file }}';
    new Vue({
        el: '#app',
        data: function () {
            return {
                preData: '',
                preStyle: `height: ${screen.height - 320}px; overflow: auto`,
                offset: 0,
                limit: 10,
            }
        },
        mounted() {
            this.load();
        },
        methods: {
            load() {
                $.getJSON('/log/more', {file, offset: this.offset, limit: this.limit}, data => {
                    if (data.s) {
                        this.preData = data.d.content + "\n" + this.preData;
                        if (this.offset == 0) {
                            setTimeout(() => {
                                this.$refs['preData'].scrollTop = this.$refs['preData'].scrollHeight;
                            }, 500)
                        }

                        this.offset += this.limit;
                    }
                });
            }
        }
    });
</script>
</html>
