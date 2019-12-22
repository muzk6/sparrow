<?php


namespace Core;

/**
 * 异常错误 handler
 * @package Core
 */
class ErrorHandler
{
    /**
     * set_error_handler
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return bool
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // 在回调中检查起到延后作用
        if ($this->is_display_errors()) {
            return false; // 打印交还给 display_errors
        }

        $lineCode = '';
        $handle = @fopen($errfile, 'r');
        if ($handle) {
            $current = 0;
            while (is_resource($handle) && !feof($handle)) {
                $buffer = fgets($handle, 1024);
                $current++;

                if ($errline == $current) {
                    $lineCode = trim($buffer);
                    break;
                }
            }

            fclose($handle);
        }

        $errorType = array(
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSING ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
        );

        $data = [
            'level' => $errorType[$errno] ?? 'CAUGHT EXCEPTION',
            'message' => $errstr,
            'line' => $lineCode,
            'file' => "{$errfile}:{$errline}",
        ];

        logfile('error_handler', $data, 'error');
        return true;
    }

    /**
     * set_exception_handler
     * @param \Throwable $exception
     */
    public function exceptionHandler(\Throwable $exception)
    {
        $filename = $exception->getFile();
        $lineNum = $exception->getLine();

        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => "{$filename}:{$lineNum}",
        ];

        logfile('exception_handler', $data, 'error');
    }

    /**
     * display_errors 配置是否有打开
     * @return bool
     */
    public function is_display_errors()
    {
        return in_array(strtolower(ini_get('display_errors')), ['1', 'on']);
    }

}
