<?php

include_once __DIR__ . '/util.php';
include_once __DIR__ . '/api.php';
include_once __DIR__ . '/config.php';

date_default_timezone_set("UTC");


$historyRes = orderHistory();

if (!isSuc($historyRes)) {
    echo "请求失败:" . $historyRes['msg'];
    exit();
}

foreach ($historyRes['data'] as $item) {
    if ('filled' != $item['state']) {
        logger("订单撤销或部分撤销");
        continue;
    }
    if (!existOrder($item['id'])) {
        insertHistory($item);
        echo sprintf("插入订单%s" . PHP_EOL, $item['id']);
    }
}
