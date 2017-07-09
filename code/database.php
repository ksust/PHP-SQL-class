<?php
$dbms='mysql';     //数据库类型
$host='127.0.0.1'; //数据库主机名
$dbName='qq';    //使用的数据库
$user='root';      //数据库连接用户名
$pass='root';          //对应的密码
$dsn="$dbms:host=$host;dbname=$dbName";

return array('dsn' => $dsn, 'user' => $user, 'pass' => $pass);