<?php
$dbms='mysql';     //数据库类型
$host='121.42.184.185'; //数据库主机名
$dbName='qq';    //使用的数据库
$user='qq';      //数据库连接用户名
$pass='1341915';          //对应的密码
$dsn="$dbms:host=$host;dbname=$dbName";

return array('dsn' => $dsn, 'user' => $user, 'pass' => $pass);