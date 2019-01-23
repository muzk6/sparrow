<?php

namespace Core;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

/**
 * Swift_Mailer 二次封装
 * 简单文档 https://swiftmailer.symfony.com/docs/sending.html
 * 消息文档 https://swiftmailer.symfony.com/docs/messages.html
 * @package Core
 */
class AppEmail extends Swift_Mailer
{
    /**
     * @var array 配置文件
     */
    protected $conf;

    /**
     * @param array $conf 配置文件，格式 config/dev/email.php
     */
    public function __construct(array $conf)
    {
        $this->conf = $conf;

        $transport = (new Swift_SmtpTransport($this->conf['host'], $this->conf['port'], $this->conf['encryption']))
            ->setUsername($this->conf['user'])
            ->setPassword($this->conf['passwd']);

        parent::__construct($transport);
    }

    /**
     * 邮件消息实例
     * @param string $subject 标题
     * @return Swift_Message
     */
    public function message(string $subject)
    {
        $message = (new Swift_Message($subject))
            ->setFrom([$this->conf['user'] => $this->conf['name']]);

        return $message;
    }

    /**
     * 发送普通文本
     * @param string|array $to 目标地址<br>
     * 支持单个('name@qq.com')<br>
     * 多个(['name1@qq.com', 'name2@qq.com'])
     * @param string $subject 标题
     * @param string $body 内容
     * @return int
     */
    public function sendText($to, string $subject, string $body)
    {
        $message = $this->message($subject)
            ->setTo(is_string($to) ? [$to] : $to)
            ->setBody($body);
        return $this->send($message);
    }

}

