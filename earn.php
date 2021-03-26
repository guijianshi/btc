<?php

date_default_timezone_set("PRC");

include_once __DIR__ . '/util.php';

$pdo = getPDO();
$earn = $pdo->query( "select left(sum(diff), 7) as earn from orders_log where `diff` >0 and status = 1 and sale_time > date_add(now(), INTERVAL -1 month);")->fetch();
logger(sprintf(PHP_EOL . "总盈利:%s" . PHP_EOL, $earn['earn']));
$earns = $pdo->query( "select left(sale_time, 10) as `date`, left(sum(diff), 7) as earn from orders_log where `diff` >0 and status = 1 and sale_time > date_add(now(), INTERVAL -1 month ) group by `date` order by `date` desc;")->fetchAll();
foreach ($earns as $item) {
    echo sprintf("%s: %s", $item['date'], $item['earn']) . PHP_EOL;
}