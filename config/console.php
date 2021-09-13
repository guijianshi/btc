<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'btc:buy' => app\command\btc\Buy::class,
        'btc:auto' => app\command\btc\OrderAutoTask::class,
        'btc:query' => app\command\btc\Query::class,
    ],
];
