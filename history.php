<?php

date_default_timezone_set('PRC');

include_once __DIR__ . '/api.php';
include_once __DIR__ . '/util.php';
include_once __DIR__ . '/config.php';

$historyRes = orderHistory();

if (!isSuc($historyRes)) {
    echo "请求失败:" . $historyRes['msg'];
    exit();
}

foreach ($historyRes['data'] as $item) {
    if ('filled' != $item['filled']) {
        logger("订单撤销或部分撤销");
        continue;
    }
    if (!existOrder($item['id'])) {
        insertHistory($item);
        echo sprintf("插入订单%s" . PHP_EOL, $item['id']);
    }
}
