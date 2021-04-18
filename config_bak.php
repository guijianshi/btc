<?php

// 数据库dsn
const DB_DSN = 'mysql:dbname=btc;host=127.0.0.1:3306';
// 数据库账号
const DB_NAME = 'root';
// 数据库密码
const DB_PWD = '';

// 自己账号值
const ACCOUNT_ID = '';

// 固定
const HOST = 'api.huobipro.com';

//  开发者SECRET
const SECRET = "";

// 开发者key
const ACCESS_KEY = "";

// 特殊币最低数量
$map = [
    'arusdt' => function($num) {
        return sprintf("%.2f", $num);
    }
];