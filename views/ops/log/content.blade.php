<!DOCTYPE html>
<html>
<head>
    @include('ops/inc_header')
</head>
<body style="overflow: hidden">
<div id="app">
    <el-container v-loading.fullscreen.lock="fullscreenLoading">
        <el-header>
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item><a href="/log/index">日志文件</a></el-breadcrumb-item>
                <el-breadcrumb-item><a style="cursor: pointer;" @click="location.reload()">{{ $file }}<i class="el-icon-refresh"></i></a>
                </el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <el-row type="flex" class="row-bg" justify="center">
                <el-col :span="6">
                    <div class="grid-content bg-purple">
                        <el-popover
                                placement="bottom"
                                trigger="manual"
                                :content="errMsg"
                                v-model="showErr">
                            <el-button slot="reference" @click="load" title="往前加载" type="primary"
                                       icon="el-icon-arrow-up"
                                       circle></el-button>
                        </el-popover>
                    </div>
                </el-col>
            </el-row>
            <pre ref="preData" :style="preStyle">@{{ preData }}</pre>
        </el-main>
    </el-container>
</div>
<script>
    (() => {
        let file = '{{ $file }}';
        new Vue({
            el: '#app',
            data: function () {
                return {
                    fullscreenLoading: false,
                    showErr: false,
                    errMsg: '',
                    preData: '',
                    preStyle: `height: ${screen.height - 320}px; overflow: auto`,
                    offset: -1,
                    limit: 50,
                }
            },
            mounted() {
                this.load();
            },
            methods: {
                load() {
                    this.fullscreenLoading = true;
                    $.getJSON('/log/more', {file, offset: this.offset, limit: this.limit}, data => {
                        this.fullscreenLoading = false;
                        if (data.s) {
                            this.preData = data.d.content + "\n" + this.preData;
                            if (this.offset == -1) {
                                setTimeout(() => {
                                    this.$refs['preData'].scrollTop = this.$refs['preData'].scrollHeight;
                                }, 500)
                            }

                            this.offset = data.d.offset;
                        } else {
                            this.errMsg = data.m;
                            this.showErr = true;
                            this.offset = -2;
                            setTimeout(() => {
                                this.showErr = false;
                            }, 1000);
                        }
                    });
                }
            }
        });
    })();
</script>
</body>
</html>
