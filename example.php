<?php

include 'autoload.php';

use Library\MysqliDriver;
use Library\MysqliResult;

// 初始化
$mysqli = new MysqliDriver();
$conf = ['username' => 'root', 'password' => '123456', 'database' => 'test'];
$mysqli->connect($conf);

// 查询
$query = $mysqli->query('select * from `money`');
print_r($query->result());
print_r($query->row());

// 事务
$mysqli->transStart();
$mysqli->query('update `money` set number = number - ? where uid = ?', 10, 100);
$mysqli->query('insert `history`(uid, number) values(?, ?)', 100, -10);
$mysqli->transComplete();
echo 'Transaction execution status: ' . ($mysqli->transStatus() ? 'true' : 'false');
