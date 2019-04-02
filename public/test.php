<?php

/**
 * php-cgi 测试入口
 * http://{HOST}/test.php
 */

use Core\AppException;

require_once dirname(__DIR__) . '/init.php';

if (!whitelist()->isSafeIp()) {
    http_response_code(404);
    exit;
}

try {
    //todo...
    $code = 'PB KBY';
    $amount = 5;

    $where0 = '';
    $where1 = [];
    if ($code) {
        $where0 .= ' and pd.code=?';
        $where1[] = $code;
    }
    if ($amount) {
        $where0 .= ' and pd.amount=?';
        $where1[] = $amount;
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS pd.*
FROM product_online.order
  INNER JOIN product_online.cart ON cart.id = order.cart_id
  LEFT JOIN product_online.cart_item AS ci ON ci.cart_id = cart.id
  LEFT JOIN product_online.user_product AS upd ON upd.id = ci.user_product_id
  LEFT JOIN product_online.product AS pd ON pd.id = upd.product_id
WHERE 1=1 {$where0} LIMIT 3";

    $st = db()->prepare($sql);
    $st->execute($where1);
    var_dump(db()->foundRows(), $st->fetchAll(2));
} catch (AppException $exception) {
    var_dump(format2api($exception));
}
