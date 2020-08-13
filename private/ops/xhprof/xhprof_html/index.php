<?php
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

//
// XHProf: A Hierarchical Profiler for PHP
//
// XHProf has two components:
//
//  * This module is the UI/reporting component, used
//    for viewing results of XHProf runs from a browser.
//
//  * Data collection component: This is implemented
//    as a PHP extension (XHProf).
//
//
//
// @author(s)  Kannan Muthukkaruppan
//             Changhao Jiang
//

// by default assume that xhprof_html & xhprof_lib directories
// are at the same level.
$GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/../xhprof_lib';

require_once $GLOBALS['XHPROF_LIB_ROOT'] . '/display/xhprof.php';
?>
<html>
<head>
    <?php
    echo view('ops/inc_header');
    ?>
    <style>
        .el-table__header {
            font-size: 14px;
        }

        .el-table__body {
            font-size: 14px;
            color: #606266;
        }
    </style>
</head>
<body>
<div id="app">
    <el-container>
        <el-header>
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item><a style="cursor: pointer" @click="location.reload()">性能记录<i
                                class="el-icon-refresh"></i></a>
                </el-breadcrumb-item>
            </el-breadcrumb>
        </el-header>
        <el-main>
            <template>
                <el-table
                        :data="tableData"
                        :style="tableStyle">
                    <el-table-column
                            prop="ms"
                            label="耗时(ms)">
                    </el-table-column>
                    <el-table-column
                            prop="url"
                            label="URL">
                    </el-table-column>
                    <el-table-column
                            prop="date"
                            label="时间">
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
    <?php if (!extension_loaded('tideways_xhprof')): ?>
    top.V_INSTANCE.$message({
        showClose: true,
        message: '请安装扩展: tideways_xhprof',
        type: 'error'
    });
    <?php endif ?>
    (() => {
        let tableData = [];
        <?php
        $xhprof_runs_impl = new XHProfRuns_Default();
        $data = $xhprof_runs_impl->list_runs();
        if ($data) {
            echo 'tableData = ' . json_encode($data) . ';';
        }
        ?>
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
                    location.href = row['href'];
                }
            }
        })
    })();
</script>
</body>
</html>
