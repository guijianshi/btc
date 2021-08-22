<?php

include_once __DIR__ . '/config.php';

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

function makeSign($method, $baseUrl, $path, $param)
{
    $date = implode('T', explode(' ', date('yy-m-d H:i:s', time())));
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

function orderHistory(string $symbol = "all", string $period = '1min', int $size = 50)
{
    $path = '/v1/order/history';
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