<?php

include_once __DIR__ . '/util.php';
include_once __DIR__ . '/api.php';

while (true) {
    fwrite(STDOUT, "请输入操作类型:b/e:");
    $op = trim(fgets(STDIN));  // 从控制台读取输入
    if (!in_array($op, ['b', 'e'])) {
        continue;
    }
    if ('b' == $op) {
        buy();
    } else {
        edit();
    }
    break;
}

function edit()
{
    $columnLenMap = [
        'id' => 4,
        'status' => 6,
        'num' => 6,
    ];

    $pdo = getPDO();
    $sql = 'select id, symbol, buy_price, sale_price, num, status, direction from orders where status = 4';
    $i = 0;
    $symbols = [];
    foreach ($pdo->query($sql, PDO::FETCH_ASSOC) as $row) {
        if (0 == $i++) {
            $keys = array_keys($row);
            foreach ($keys as $key) {
                if (in_array($key, ['ctime', 'mtime'])) {
                    continue;
                }
                $len = 9;
                if (isset($columnLenMap[$key])) {
                    $len = $columnLenMap[$key];
                }
                echo str_pad($key, $len) . "\t";
            }
            echo "\n";
        }
        $symbols[intval($row['id'])] = $row;
        foreach ($row as $key => $value) {
            if (in_array($key, ['ctime', 'mtime'])) {
                continue;
            }
            $len = 9;
            if (isset($columnLenMap[$key])) {
                $len = $columnLenMap[$key];
            }
            echo str_pad($value, $len) . " \t";
        }
        echo "\n";
    }
    while (true) {
        fwrite(STDOUT, "请输入编辑的id:");;
        $id = intval(trim(fgets(STDIN)));  // 从控制台读取输入

        if (!isset($symbols[$id])) {
            fwrite(STDOUT, "输入id不存在" . PHP_EOL);
            continue;
        }
        $symbol = $symbols[$id];
        break;
    }
    while (true) {
        fwrite(STDOUT, sprintf("请输入%s买入价格:", $symbol['symbol']));
        $buy_price = trim(fgets(STDIN));  // 买入价格
        if (is_numeric($buy_price)) {
            break;
        }
        fwrite(STDOUT, '价格必须是数字' . PHP_EOL);
    }

    while (true) {

        fwrite(STDOUT, sprintf("请输入%s卖出价格:", $symbol['symbol']));
        $sale_price = trim(fgets(STDIN));  // 卖出价格
        if (!is_numeric($sale_price)) {
            fwrite(STDOUT, '价格必须是数字' . PHP_EOL);
            continue;
        }
        if ($sale_price < ($buy_price * 1.004)) {
            fwrite(STDOUT, '卖出价不低于买入价的1.004倍' . PHP_EOL);
            continue;
        }
        break;
    }

    while (true) {
        fwrite(STDOUT, sprintf("请输入%s买入数量:", $symbol['symbol']));
        $num = trim(fgets(STDIN));  // 买入价格
        if (!is_numeric($num)) {
            fwrite(STDOUT, '数量必须是数字');
            continue;
        }
        if (intval($num * $buy_price) < 5) {
            fwrite(STDOUT, '总价必须大于5usdt' . PHP_EOL);
            continue;
        }
        break;
    }

    $earn = substr(($sale_price - $buy_price) * $num, 0, 5);
    fwrite(STDOUT, sprintf('买入%s,买入价格%s,卖出价格%s,数量%s,预计收益%s' . PHP_EOL, $symbol['symbol'], $buy_price, $sale_price, $num, $earn));
    fwrite(STDOUT, "确认购买请输入Y/N:");
    $check = trim(fgets(STDIN));
    if ('Y' === strtoupper($check)) {
        $pdo = getPDO();
        $flag = $pdo->exec(
            sprintf(
                "update  orders set buy_price = '%s', sale_price = '%s', num = '%s' , status = 0 where id = %d and symbol = '%s' and status = 4",
                $buy_price,
                $sale_price,
                $num,
                $symbol['id'],
                $symbol['symbol']
            )
        );
        if ($flag) {
            fwrite(STDOUT, "更新任务成功" . PHP_EOL);
        } else {
            fwrite(STDOUT, "更新任务失败" . PHP_EOL);
        }
    } else {
        fwrite(STDOUT, "任务已取消" .  PHP_EOL);
    }
}

function buy()
{
    while (true) {
        fwrite(STDOUT, "请输入购买虚拟币名称,小写字母:");;
        $symbol = trim(fgets(STDIN)) . 'usdt';  // 从控制台读取输入
        $res = kline($symbol);
        if (!isSuc($res)) {
            fwrite(STDOUT, "输入虚拟币不存在");
            continue;
        }
        fwrite(STDOUT, "当前最高价为:" . $res['data'][0]['high']  . PHP_EOL);
        break;
    }
    while (true) {
        fwrite(STDOUT, sprintf("请输入%s买入价格:", $symbol));
        $buy_price = trim(fgets(STDIN));  // 买入价格
        if (is_numeric($buy_price)) {
            break;
        }
        fwrite(STDOUT, '价格必须是数字' . PHP_EOL);
    }



    while (true) {

        fwrite(STDOUT, sprintf("请输入%s卖出价格:", $symbol));
        $sale_price = trim(fgets(STDIN));  // 卖出价格
        if (!is_numeric($sale_price)) {
            fwrite(STDOUT, '价格必须是数字' . PHP_EOL);
            continue;
        }
        if ($sale_price < ($buy_price * 1.004)) {
            fwrite(STDOUT, '卖出价不低于买入价的1.004倍' . PHP_EOL);
            continue;
        }
        break;
    }

    while (true) {
        fwrite(STDOUT, sprintf("请输入%s买入数量:", $symbol));
        $num = trim(fgets(STDIN));  // 买入价格
        if (!is_numeric($num)) {
            fwrite(STDOUT, '数量必须是数字');
            continue;
        }
        if (intval($num * $buy_price) < 5) {
            fwrite(STDOUT, '总价必须大于5usdt' . PHP_EOL);
            continue;
        }
        break;
    }

    $earn = substr(($sale_price - $buy_price) * $num, 0, 5);
    fwrite(STDOUT, sprintf('买入%s,买入价格%s,卖出价格%s,数量%s,预计收益%s' . PHP_EOL, $symbol, $buy_price, $sale_price, $num, $earn));
    fwrite(STDOUT, "确认购买请输入Y/N:");
    $check = trim(fgets(STDIN));
    if ('Y' === strtoupper($check)) {
        $pdo = getPDO();
        $flag = $pdo->exec(
            sprintf(
                "insert into orders (symbol, buy_price, sale_price, buy_order_id, num) values ('%s', %s, %s, '', %s)",
                $symbol,
                $buy_price,
                $sale_price,
                $num
            )
        );
        if ($flag) {
            fwrite(STDOUT, "新增任务成功" . PHP_EOL);
        } else {
            fwrite(STDOUT, "新增任务失败" . PHP_EOL);
        }
    } else {
        fwrite(STDOUT, "任务已取消" .  PHP_EOL);
    }
}