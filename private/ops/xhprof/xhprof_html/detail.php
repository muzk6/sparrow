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

// param name, its type, and default value
$params = [
    'run' => [XHPROF_STRING_PARAM, ''],
    'wts' => [XHPROF_STRING_PARAM, ''],
    'symbol' => [XHPROF_STRING_PARAM, ''],
    'sort' => [XHPROF_STRING_PARAM, 'wt'], // wall time
    'run1' => [XHPROF_STRING_PARAM, ''],
    'run2' => [XHPROF_STRING_PARAM, ''],
    'source' => [XHPROF_STRING_PARAM, ''],
    'all' => [XHPROF_UINT_PARAM, 0],
];

// pull values of these params, and create named globals for each param
xhprof_param_init($params);

/* reset params to be a array of variable names to values
   by the end of this page, param should only contain values that need
   to be preserved for the next page. unset all unwanted keys in $params.
 */
foreach ($params as $k => $v) {
    $params[$k] = $$k;

    // unset key from params that are using default values. So URLs aren't
    // ridiculously long.
    if ($params[$k] == $v[1]) {
        unset($params[$k]);
    }
}

?>
<html>
<head>
    <?php
    echo view('ops/inc_header');
    xhprof_include_js_css();
    ?>
</head>
<div id="app">
    <el-container>
        <el-header>
            <el-breadcrumb separator-class="el-icon-arrow-right">
                <el-breadcrumb-item><a style="cursor: pointer" href="/xhprof/xhprof_html/index.php">性能记录</a>
                </el-breadcrumb-item>
                <el-breadcrumb-item><a style="cursor: pointer"
                                       @click="handleClick"><?php
                        list($url, $cost_time) = explode(';', xhprof_decode_run_name($run));
                        echo $url;
                        ?><i class="el-icon-refresh"></i></a>
                </el-breadcrumb-item>
                <el-breadcrumb-item>
                    <el-input
                            id="search"
                            placeholder="搜索 Function Name"
                            prefix-icon="el-icon-search"
                            v-model="keyword">
                    </el-input>
            </el-breadcrumb>
        </el-header>
        <el-main v-pre>
            <?php

            $vbar = ' class="vbar"';
            $vwbar = ' class="vwbar"';
            $vwlbar = ' class="vwlbar"';
            $vbbar = ' class="vbbar"';
            $vrbar = ' class="vrbar"';
            $vgbar = ' class="vgbar"';

            $xhprof_runs_impl = new XHProfRuns_Default();

            displayXHProfReport($xhprof_runs_impl, $params, $source, $run, $wts,
                $symbol, $sort, $run1, $run2);

            global $base_path;
            $base_url_params = xhprof_array_unset(xhprof_array_unset($params, 'symbol'), 'all');
            $top_link_query_string = "{$base_path}/detail.php?" . http_build_query($base_url_params);

            ?>
        </el-main>
    </el-container>
</div>
<script>
    (() => {
        new Vue({
            el: '#app',
            data: function () {
                return {
                    keyword: '',
                }
            },
            methods: {
                handleClick: function () {
                    location.href = '<?php echo $top_link_query_string ?>';
                }
            }
        })
    })();
</script>
</body>
</html>
