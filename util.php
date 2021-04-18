<?php

include_once __DIR__ . '/config.php';

date_default_timezone_set("UTC");


function loggerErr(string $msg, $data = [])
{
    echo sprintf("level:ERROR time:%s msg:%s data: %s  ", date("Y-m-d H:i:s"), $msg, json_encode($data, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}

function logger(string $msg, $data = [])
{
    if (empty($msg) && empty($data)) {
        echo PHP_EOL;
        return;
    }
    echo sprintf("level:INFO time:%s msg:%s data: %s ", date("Y-m-d H:i:s"), $msg, json_encode($data, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}


function returnData($code, $msg, $data)
{
    return ['code' => $code, 'msg' => $msg, 'data' => $data];
}


function suc($data)
{
    return returnData(0, 'success', $data);
}

function err($msg)
{
    return returnData(-1, $msg, new stdClass());
}

function isSuc(array $res)
{
    return 0 === $res['code'];
}

function getPDO()
{
    return new PDO(DB_DSN, DB_NAME, DB_PWD);
}