<!DOCTYPE html>
<html>
<head>
    @include('ops/inc_header')
</head>
<body style="overflow:hidden;">
<div id="app">
    <el-container>
        <el-main>
            <el-card style="width: 300px; margin: 0 auto">
                <div slot="header">
                    <span>OPS 登录</span>
                </div>
                <div>
                    <el-form @keyup.enter.native="onSubmit" :model="form" @submit.native.prevent>
                        <el-form-item>
                            <el-input placeholder="请输入密码" v-model="form.passwd" show-password></el-input>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="onSubmit">登录</el-button>
                        </el-form-item>
                    </el-form>
                </div>
            </el-card>
        </el-main>
    </el-container>
</div>
<script>
    (() => {
        new Vue({
            el: '#app',
            data: function () {
                return {
                    form: {}
                }
            },
            methods: {
                onSubmit: function () {
                    $.post('/index/login', this.form, (data) => {
                        if (data.s) {
                            location.href = '/';
                        } else {
                            this.$message({
                                showClose: true,
                                message: data.m,
                                type: 'error'
                            });
                        }
                    }, 'json');
                }
            }
        })
    })();
</script>
</body>
</html>
