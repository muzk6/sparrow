<?php


namespace Core;

/**
 * Xdebug Trace
 * @package Core
 */
class AppXdebug
{
    /**
     * 日志记录开关，是否有打开
     * @return bool
     */
    public function isOpen()
    {
        $traceStart = false;

        // cli/trace.php 开启的 xdebug trace
        $traceConfFile = PATH_DATA . '/.tracerc';
        if (file_exists($traceConfFile)) {
            $traceConf = include($traceConfFile);

            if ($traceConf['expire'] > TIME // 检查过期
                && strpos(strval($_SERVER['REQUEST_URI']), $traceConf['uri']) !== false // 检查 uri path 是否匹配
                && (!$traceConf['user_id'] || (auth()->isLogin() && $traceConf['user_id'] == auth()->userId())) // 有指定用户时，检查特定用户
            ) {
                $traceStart = true;

                ini_set('xdebug.var_display_max_depth', $traceConf['max_depth']);
                ini_set('xdebug.var_display_max_data', $traceConf['max_data']);
                ini_set('xdebug.var_display_max_children', $traceConf['max_children']);
            }
        }

        if ($traceStart || (whitelist()->isSafeIp() && $this->getName())) {
            return true;
        }

        return false;
    }

    /**
     * 参数 _xt=<value> 开启
     * @return string
     */
    protected function getName()
    {
        $name = '';
        if (isset($_REQUEST['_xt'])) {
            $name = $_REQUEST['_xt'];
        } elseif (isset($_COOKIE['_xt'])) {
            $name = $_COOKIE['_xt'];
        }

        return $name;
    }

    /**
     * 开启日志跟踪
     * @param string $name 日志 :xt 段的名
     */
    public function trace($name = '')
    {
        if (!file_exists(PATH_TRACE)) {
            mkdir(PATH_TRACE, 0777, true);
        }

        ini_set('xdebug.trace_format', 0);
        ini_set('xdebug.collect_return', 1);
        ini_set('xdebug.collect_params', 4);
        ini_set('xdebug.collect_assignments', 1);
        ini_set('xdebug.show_mem_delta', 1);
        ini_set('xdebug.collect_includes', 1);

        $traceFilename = sprintf('%s.time:%s.xt:%s.uid:%s.uri:%s',
            uniqid(), // 目的是排序用，和保证文件名唯一
            date('ymd_His'),
            $name ?: $this->getName(),
            auth()->userId(),
            str_replace('/', '_', $_SERVER['REQUEST_URI'])
        );
        xdebug_start_trace(PATH_TRACE . '/' . $traceFilename);

        register_shutdown_function(function () {
            xdebug_stop_trace();
        });
    }
}