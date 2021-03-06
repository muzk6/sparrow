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
                <el-breadcrumb-item><a style="cursor: pointer" @click="location.reload()">日志文件<i class="el-icon-refresh"></i></a></el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <template>
                <el-table
                        :data="tableData"
                        :style="tableStyle">
                    <el-table-column
                            prop="mtime"
                            label="时间">
                    </el-table-column>
                    <el-table-column
                            prop="name"
                            label="日志名">
                    </el-table-column>
                    <el-table-column
                            label="操作"
                            width="100">
                        <template slot-scope="scope">
                            <el-button @click="handleClick(scope.row)" type="text" size="small">查看</el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </template>
        </el-main>
    </el-container>
</div>
<script>
    (() => {
        let tableData = {!! json_encode($data) !!}
        new Vue({
            el: '#app',
            data: function () {
                return {
                    tableData,
                    tableStyle: `height: ${screen.height - 300}px; overflow: auto`
                }
            },
            methods: {
                handleClick: function (row) {
                    let name = encodeURIComponent(row.name);
                    location.href = `/log/content?file=${name}`;
                }
            }
        })
    })();
</script>
</body>
</html>
