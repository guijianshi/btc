<?php

date_default_timezone_set('PRC');

include_once __DIR__ . '/util.php';
include_once __DIR__ . '/config.php';

const STATUS_WAIT = 0; // 初始化
const STATUS_BUY_ING = 1; // 购买中
const STATUS_SALE_WAIT = 2; // 待出售
const STATUS_SALE_ING = 3; // 出售中
const STATUS_CANCEL = 4; // 取消

function action()
{
    $start = microtime(true);

    $pdo = getPDO();

//    $pdo = new PDO('mysql:dbname=crawler;host=localhost', 'root', '');
    $orders = $pdo->query(sprintf('select * from orders where status != %d', STATUS_CANCEL))->fetchAll();
    foreach ($orders as $order) {
        if ($order['buy_price'] > $order['sale_price']) {
            loggerErr(sprintf('id:%s 卖出金额 %s 小于买入 %s金额', $order['id'], $order['sale_price'], $order['buy_price']));
            continue;
        }
        if (STATUS_WAIT == $order['status']) {
            orderBuy($order, $pdo);
        } elseif (STATUS_BUY_ING == $order['status']) {
            if (random_int(10, 20) % 1 != 0) {
                logger(sprintf('跳过:' . $order['id']));
                continue;
            }
            queryBuy($order, $pdo);
        } elseif (STATUS_SALE_WAIT == $order['status']) {
            orderSale($order, $pdo);
        } elseif (STATUS_SALE_ING == $order['status']) {
            if (random_int(10, 20) % 1 != 0) {
                logger(sprintf('跳过:' . $order['id']));
                continue;
            }
            querySale($order, $pdo);
        } else {
            loggerErr('状态错误', $order);
        }
        usleep(1000 * 50);
    }
    logger(  sprintf('结束一轮,耗时 %0.2fs', microtime(true) - $start));
    logger('');
    logger('-----------------------------------------');
}


/**
 * 获取均值
 * @param string $symbol
 * @return int|mixed
 */
function getAvg(string $symbol)
{
    $res = kline($symbol, '1min', 1);
    if (!isSuc($res)) {
        return 0;
    }
    return $res['data'][0]['high'];
}

/**
 * @param string $symbol
 * @param string $type 'buy-limit'| 'sell-limit'
 * @param string $price 单价
 * @param string $amount 数量
 * @return array
 */
function buy(string $symbol, string $type, string $price, string $amount)
{
    $path = '/v1/order/orders/place';
    $post = [
        'account-id' => ACCOUNT_ID,
        'symbol'     => $symbol,
        'type'       => $type, // 限价买入
        'amount'     => $amount,
        'price'      => $price,
    ];
    $param = makeSign("POST", HOST, $path, []);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => getRealUrl($path, $param),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        logger('生成订单返回错误:' . $err);
        return err($err);

    } else {
        logger('生成订单返回:' . $response);
        $res = json_decode($response, true);
        logger('生成订单返回:', $res);
        return suc(['order_id' => $res['data']]);
    }
}

function query($order_id)
{
    $path = '/v1/order/orders/' . $order_id;
    $param = [
    ];
    $param = makeSign("GET", HOST, $path, $param);
    $url = getRealUrl($path, $param);
    $res_str = file_get_contents($url);
    $res = json_decode($res_str, true);
    logger('查询订单状态 id:' . $order_id, $res);
    $state = $res['data']['state'];
    $amount = sprintf('%.4f', $res['data']['field-cash-amount']);
    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc(['state' => $state, 'field_cash_amount' => $amount]);
}

function makeSign($method, $baseUrl, $path, $param)
{
    $date = implode('T', explode(' ', date('Y-m-d H:i:s', time())));
    $param['AccessKeyId'] = ACCESS_KEY;
    $param['SignatureMethod'] = 'HmacSHA256';
    $param['SignatureVersion'] = '2';
    $param['Timestamp'] = $date;
    ksort($param);
    $param_str = http_build_query($param);
    $param_str = "$method\n$baseUrl\n$path\n$param_str";
    $sign = base64_encode(hash_hmac('sha256', $param_str, SECRET, true));
    $param['Signature'] = $sign;
    return $param;
}



function getRealUrl($path, $paramSign)
{
    return 'https://' . HOST . $path . '?' . http_build_query($paramSign);
}

function getQuery($url)
{
    $res_str = file_get_contents($url);
    logger(sprintf('请求返回: %s', $res_str));
    return json_decode($res_str, true);
}



/* ----------- */
function orderBuy($order, PDO $pdo)
{
    $avg_price = getAvg($order['symbol']);
    if ($order['buy_price'] > $avg_price) {
        loggerErr(sprintf('创建订单失败, 买入价格大于最高价 price %s', $avg_price), $order);
        return;
    }

    // /v1/order/orders/place
    $res = buy($order['symbol'], 'buy-limit', $order['buy_price'], $order['num']);
    if (!isSuc($res)) {
        loggerErr('创建订单失败'. $res['msg']);
        return;
    }
    if ($order['buy_price'])
        logger('创建购买订单 start');
    // 创建成功,保存购买订单号,订单流水,
    $pdo->beginTransaction();
    $order_id = $res['data']['order_id'];
    $stmt = $pdo->prepare(
        "update orders set `buy_order_id`  = ?, `status` = ? where id = ?"
    );
    $stmt->execute([$order_id, STATUS_BUY_ING, $order['id']]);
    if (false === $stmt->execute([$order_id, STATUS_BUY_ING, $order['id']])) {
        loggerErr('更新订单失败', ['id' => $order['id'], 'msg' => $stmt->errorInfo()]);
        $pdo->rollBack();
        return;
    }
    $stmt = $pdo->prepare(
        "insert into orders_log (symbol, buy_price, buy_total, buy_order_id, buy_time, num) VALUES (?, ?, ?, ?, now(), ?)"
    );
    if (false === $stmt->execute([$order['symbol'], $order['buy_price'], $order['buy_price'] * $order['num'], $order_id, $order['num']])) {
        loggerErr('创建购买流水失败', ['id' => $order['id'], 'msg' => $stmt->errorInfo()]);
        $pdo->rollBack();
    }
    $pdo->commit();
}

/**
 * @param $order
 * @param PDO $pdo
 */
function queryBuy($order, PDO $pdo)
{
    $res = query($order['buy_order_id']);

    if (!isSuc($res)) {
        loggerErr('查询买入订单失败' . $order['id']);
        return;
    }
    // 撤销订单,将购买任务取消
    if ('canceled' === $res['data']['state']) {
        orderCancel($order, $pdo);
        logger('买入订单取消 id:' . $order['id']);
        return;
    } elseif ('filled' !== $res['data']['state']) {
        logger('买入订单未完成 id:'  . $order['id']);
        return;
    }
    $buy_total = $res['data']['field_cash_amount'];


    logger('查询买入完成' . $order['id']);

    $pdo->beginTransaction();
    // 订单售出状态
    $num = $order['num'];
    if ($order['sale_price'] / $order['buy_price'] > 1.10) {
        $num = $num * 0.98;
        if ($num > 100) {
            $num = intval($num);
        } elseif ($num > 0.1) {
            $num = sprintf("%.3f", $num);
        } else {
            $num = $order['num'];
        }
    }
    global $map;
    if (is_callable($map[$order['symbol']])) {
        $num = $map[$order['symbol']]($num);
    }
    $pdo->prepare(
        "update orders set status = ?, `num` = ?, `direction` = 'sale' where `id` = ?;"
    )->execute([STATUS_SALE_WAIT, $num, $order['id']]);
    // 订单售出信息更新

    // 订单售出信息更新

    $flag = $pdo->prepare(
        "update orders_log set `buy_total` = ?, buy_time = now() where buy_order_id = ?"
    )->execute([$buy_total, $order['buy_order_id']]);
    if (!$flag) {
        loggerErr('买入查询失败', [$pdo->errorInfo(), $order]);
        $pdo->rollBack();
        return;
    }
    $pdo->commit();
    return;
}

function orderSale($order, PDO $pdo)
{
    // 两个特殊币不挣钱,仅为了存币
    if (in_array($order['symbol'], ['shib', 'doge'])) {
        if ($order['sale_price'] / $order['buy_price'] > 1.08) {
            $order['num'] = intval($order['num'] / ($order['sale_price'] / $order['buy_price'] - 0.02));
        }
    }
    $res = buy($order['symbol'], 'sell-limit', $order['sale_price'], $order['num']);
    if (!isSuc($res)) {
        loggerErr('创建订单失败:' . $order['id']);
        return;
    }
    logger('创建卖出订单 start');

    $order_id = $res['data']['order_id'];
    $pdo->beginTransaction();
    $id = $pdo->prepare("update orders set `sale_order_id`  = ?, `status` = ? where `id` = ?;")
        ->execute([$order_id, STATUS_SALE_ING, $order['id']]);
    $stmt = $pdo->prepare("update orders_log set `sale_price`  = ?, `sale_total` = ?, `sale_order_id` = ?, `status` = ? where `buy_order_id` = ?")
        ->execute([$order['sale_price'], $order['sale_price'] * $order['num'], $order_id, 1, $order['buy_order_id']]);
    $pdo->commit();
    return;
}

function querySale($order, PDO $pdo)
{
    $res = query($order['sale_order_id']);

    if (!isSuc($res)) {
        loggerErr('查询卖出订单失败:' . $order['id']);
        return;
    }
    // 撤销订单,将购买任务取消
    if ('canceled' === $res['data']['state']) {
        orderCancel($order, $pdo);
        logger('卖出订单取消', $order);
        return;
    } elseif ('filled' !== $res['data']['state']) {
        logger('卖出订单未完成: buy_order_id' . $order['buy_order_id']);
        return;
    }

    $sale_total = $res['data']['field_cash_amount'];
    logger('查询卖出完成: buy_order_id' . $order['buy_order_id']);
    $num = $order['num'];
    if ($order['sale_price'] > $order['buy_price']) {
        $num = $num * sprintf("%.3f", $order['sale_price'] / $order['buy_price']);
        if ($num > 100) {
            $num = intval($num);
        } elseif ($num > 0.1) {
            $num = sprintf("%.3f", $num);
        } else {
            $num = $order['num'];
        }
    }
    global $map;
    if (is_callable($map[$order['symbol']])) {
        $num = $map[$order['symbol']]($num);
    }
    $pdo->beginTransaction();
    // 订单买入状态
    $pdo->prepare(
        "update orders set buy_order_id = '', `sale_order_id` = '', `direction` = 'buy', `status` = ?, `num` = ? where `id` = ?;"
    )->execute([STATUS_WAIT, $num, $order['id']]);
    // 订单售出信息更新
    $pdo->prepare(
        "update orders_log set `sale_total` = ? , `sale_time` = now(), `diff` = left((? - `buy_total`), 7) where `buy_order_id` = ?;"

    )->execute([$sale_total, $sale_total * 0.996, $order['buy_order_id']]);
    $pdo->commit();
    return;
}


function orderCancel($order, PDO $pdo)
{
    $pdo->beginTransaction();
    $flag = $pdo->prepare(
        "update orders set status = ?  where `id` = ?;"
    )->execute([STATUS_CANCEL, $order['id']]);
    if (false === $flag) {
        loggerErr('取消订单失败:id' . $order['id']);
        return;
    }
    $flag = $pdo->prepare(
        "update orders_log set `status` = ?  where `buy_order_id` = ?;"
    )->execute([STATUS_CANCEL, $order['buy_order_id']]);
    if (false === $flag) {
        loggerErr('取消订单流水失败:id' . $order['buy_order_id']);
        return;
    }
    $pdo->commit();
}
/* ----------- */



function kline(string $symbol, string $period = '1min', int $size = 10)
{
    $path = '/market/history/kline';
    $param = [
        'symbol' => $symbol,
        'period' => $period,
        'size' => $size,
    ];
    $param = makeSign("GET", HOST, $path, $param);
    $res = getQuery(getRealUrl($path, $param));

    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc($res['data']);
}

function history()
{
    $path = '/v1/account/history';
    $param = [
        'account-id' => ACCOUNT_ID,
        'size' => 30,
    ];
    $param = makeSign("GET", HOST, $path, $param);
    $res = getQuery(getRealUrl($path, $param));

    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc($res['data']);
}


function accounts()
{
    $path = '/v1/account/accounts';
    $method = 'GET';
    $param = [
    ];
    $param = makeSign($method, HOST, $path, $param);

    $res = getQuery(getRealUrl($path, $param));

    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc($res['data']);
}

function balance()
{
    $path = sprintf('/v1/account/accounts/%s/balance', ACCOUNT_ID);
    $param = [

    ];
    $param = makeSign("GET", HOST, $path, $param);
    $res = getQuery(getRealUrl($path, $param));

    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc($res['data']);
}

function testApi()
{
    $path = '/v1/common/currencys';
    $method = 'GET';
    $param = [
    ];
    $param = makeSign($method, HOST, $path, $param);
    $res = getQuery(getRealUrl($path, $param));

    if ('ok' !== $res['status']) {
        return err($res['err-msg']?? '请求错误');
    }
    return suc($res['data']);
}