<?php

include_once __DIR__ . '/util.php';

function existOrder($orderId)
{
    $pdo = getPDO();
    $orders = $pdo->query(sprintf('select * from history where order_id = %s limit 1', $orderId))->fetchAll();
    return !empty($orders);
}


function insertHistory(array $history)
{
    $pdo = getPDO();
    $detail = json_encode($history);
    $sql = "insert into order_history (order_id, symbol, amount, created_at, field_amount, field_cash_amount, field_fees, finished_at, state, detail) 
value ('{$history['id']}', '{$history['symbol']}', '{$history['amount']}',  '{$history['created-at']}',
'{$history['field-amount']}', '{$history['field-cash-amount']}', 
'{$history['field-fees']}', '{$history['finished-at']}', '{$history['state']}', '{$detail}'), 
";
    echo $sql . PHP_EOL;
    $pdo->prepare($sql)->execute();
}
