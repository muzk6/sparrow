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
                <el-breadcrumb-item><a style="cursor: pointer" @click="location.reload()">监听设置</a></el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <el-form ref="form" label-position="right" label-width="120px" :model="form">
                <el-form-item label="URL">
                    <el-input v-model="form.url"></el-input>
                </el-form-item>
                <el-form-item label="标签名">
                    <el-input v-model="form.name"></el-input>
                </el-form-item>
                <el-form-item label="用户ID" title="要监听的用户ID">
                    <el-input v-model="form.user_id"></el-input>
                </el-form-item>
                <el-form-item label="过期秒数">
                    <el-input v-model="form.expire"></el-input>
                </el-form-item>
                <el-form-item label="Max Depth">
                    <el-input v-model="form.max_depth"></el-input>
                </el-form-item>
                <el-form-item label="Max Data">
                    <el-input v-model="form.max_data"></el-input>
                </el-form-item>
                <el-form-item label="Max Children">
                    <el-input v-model="form.max_children"></el-input>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="onSubmit">保存</el-button>
                    <el-button @click="onCancel">取消</el-button>
                </el-form-item>
            </el-form>
        </el-main>
    </el-container>
</div>
</body>
<script>
    (() => {
        let form = {!! json_encode($traceConf) !!}
        new Vue({
            el: '#app',
            data: function () {
                return {
                    form,
                }
            },
            methods: {
                onSubmit: function () {
                    $.post('/xdebug/listen', this.form, (data) => {
                        if (data.s) {
                            parent.V_INSTANCE.$message({
                                showClose: true,
                                message: data.m,
                                type: 'success'
                            });
                            location.href = '/xdebug/index';
                        } else {
                            parent.V_INSTANCE.$message({
                                showClose: true,
                                message: data.m,
                                type: 'error'
                            });
                        }
                    }, 'json');
                },
                onCancel: function () {
                    location.href = '/xdebug/index';
                }
            }
        })
    })();
</script>
</html>
