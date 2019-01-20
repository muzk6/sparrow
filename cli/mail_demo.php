<?php

/**
 * 邮件发送
 * 简单文档 https://swiftmailer.symfony.com/docs/sending.html
 * 消息文档 https://swiftmailer.symfony.com/docs/messages.html
 */

require_once dirname(__DIR__) . '/boot/init.php';

$rs = swift_mailer()->send(swift_message('subject: swift mail testing... ' . date('H:i:s'))
    ->setTo(['393826660@qq.com'])
    ->setBody('body: swift mail testing... ' . date('H:i:s')));

var_dump($rs);