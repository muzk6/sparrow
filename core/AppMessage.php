<?php


namespace Core;

/**
 * 提示消息体
 * @package Core
 */
final class AppMessage
{
    /**
     * @var int
     */
    protected $code = 0;

    /**
     * @var int|string
     */
    protected $message = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string|int|array $messageOrCode 状态码或文本消息<br>
     * 带有参数的状态码，使用 array: [10002001, 'name' => 'tom'] 或 [10002001, ['name' => 'tom']]
     * @param array $data 附带数组
     */
    public function __construct($messageOrCode = '', array $data = [])
    {
        if (is_array($messageOrCode)) {
            $this->message = trans($messageOrCode[0],
                isset($messageOrCode[1]) && is_array($messageOrCode[1])
                    ? $messageOrCode[1]
                    : array_slice($messageOrCode, 1));
            $this->code = $messageOrCode[0];
        } elseif (is_numeric($messageOrCode)) {
            $this->message = trans($messageOrCode);
            $this->code = $messageOrCode;
        } else {
            $this->message = $messageOrCode;
            $this->code = 0;
        }

        $this->data = $data;
    }

    /**
     * 状态码
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 文本消息
     * @return int|string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 附带数组
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

}
