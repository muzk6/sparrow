<?php


namespace Core;

/**
 * Xdebug Trace
 * @package Core
 */
class Xdebug
{
    protected $maxDepth;
    protected $maxData;
    protected $maxChildren;

    public function __construct()
    {
        $this->initDisplaySetting();
    }

    /**
     * 最大能显示的 数组、对象 维度
     * @param int $maxDepth
     * @return $this
     */
    public function setMaxDepth(int $maxDepth)
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    /**
     * 最大能显示的字符串长度
     * @param int $maxData
     * @return $this
     */
    public function setMaxData(int $maxData)
    {
        $this->maxData = $maxData;

        return $this;
    }

    /**
     * 最多能显示的 数组、对象 成员数
     * @param int $maxChildren
     * @return $this
     */
    public function setMaxChildren(int $maxChildren)
    {
        $this->maxChildren = $maxChildren;

        return $this;
    }

    /**
     * 初始化为默认的显示设置
     */
    protected function initDisplaySetting()
    {
        $this->setMaxDepth(intval(ini_get('xdebug.var_display_max_depth')));
        $this->setMaxData(intval(ini_get('xdebug.var_display_max_data')));
        $this->setMaxChildren(intval(ini_get('xdebug.var_display_max_children')));
    }

    /**
     * 按前置条件自动开启跟踪
     */
    public function auto()
    {
        $traceStart = false;
        $name = '';

        /** @var Whitelist $whitelist */
        $whitelist = app(Whitelist::class);

        // 从 cgi 开启
        if ($whitelist->isSafeIp() || $whitelist->isSafeCookie()) {
            if (isset($_REQUEST['_xt'])) {
                $name = $_REQUEST['_xt'];
            } elseif (isset($_COOKIE['_xt'])) {
                $name = $_COOKIE['_xt'];
            }

            if ($name) {
                $traceStart = true;

                isset($_REQUEST['_max_depth']) && $this->setMaxDepth(intval($_REQUEST['_max_depth']));
                isset($_REQUEST['_max_data']) && $this->setMaxData(intval($_REQUEST['_max_data']));
                isset($_REQUEST['_max_children']) && $this->setMaxChildren(intval($_REQUEST['_max_children']));
            }
        }

        // 从 cli/trace.php 开启
        $traceConfFile = PATH_DATA . '/.tracerc';
        if (!$traceStart && file_exists($traceConfFile)) {
            $traceConf = include($traceConfFile);

            /** @var Auth $auth */
            $auth = app(Auth::class);

            $url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $traceConf['url'] = preg_replace('#https?://#', '', $traceConf['url']);
            if ($traceConf['expire'] > time() // 检查过期
                && preg_match("#^{$traceConf['url']}#i", $url) // 检查 url path 是否匹配
                && (!$traceConf['user_id'] || ($auth->isLogin() && $traceConf['user_id'] == $auth->getUserId())) // 有指定用户时，检查特定用户
            ) {
                $traceStart = true;

                $this->setMaxDepth($traceConf['max_depth']);
                $this->setMaxData($traceConf['max_data']);
                $this->setMaxChildren($traceConf['max_children']);

                $name = $traceConf['name'];
            }
        }

        if ($traceStart) {
            $this->start($name);
        }
    }

    /**
     * 手动跟踪
     * @param string $traceName 日志名，即日志文件名的 xt: 的值
     * <p>建议把 uniqid() 作为 $name</p>
     * @return bool
     */
    public function start($traceName)
    {
        if (!extension_loaded('xdebug')) {
            trigger_error('请安装扩展: xdebug', E_USER_WARNING);
            return false;
        }

        if (!file_exists(PATH_TRACE)) {
            mkdir(PATH_TRACE, 0777, true);
        }

        ini_set('xdebug.var_display_max_depth', $this->maxDepth);
        ini_set('xdebug.var_display_max_data', $this->maxData);
        ini_set('xdebug.var_display_max_children', $this->maxChildren);
        $this->initDisplaySetting();

        ini_set('xdebug.trace_format', 1);
        ini_set('xdebug.collect_return', 1);
        ini_set('xdebug.collect_params', 4);
        ini_set('xdebug.collect_assignments', 1);
        ini_set('xdebug.show_mem_delta', 1);
        ini_set('xdebug.collect_includes', 1);

        $url = '';
        if (PHP_SAPI == 'cli') {
            $cmd = basename($_SERVER['argv'][0]);
            $url = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                $url .= $_SERVER['HTTP_HOST'];
            }

            if (isset($_SERVER['REQUEST_URI'])) {
                $url .= $_SERVER['REQUEST_URI'];
            }
        }

        $traceData = [
            'uuid' => uniqid(),
            'trace' => $traceName,
            'user_id' => app(Auth::class)->getUserId(),
            'url' => $url,
        ];
        $traceFilename = rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode(json_encode($traceData))), '=');

        register_shutdown_function(function () {
            xdebug_stop_trace();
        });

        xdebug_start_trace(PATH_TRACE . '/' . $traceFilename);

        return true;
    }
}
