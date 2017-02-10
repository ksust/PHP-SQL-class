# PHP-SQL-class
SQL操作类：基于PDO参考手册
一、	基础：
1.	基于PHP PDO，主文件一个，默认数据库连接在同目录database.php中。
2.	使用：直接上传配置database.php即可使用，初始化M()方法。
二、	初始化和使用：
1.	初始化：$sql=M($tableName[,$conn]);//第一个参数时数据表名，第二个参数时数据库连接信息，默认为空时使用database.php中的连接信息，可参考database.php数组形式直接使用新连接。
2.	连贯操作：使用连贯操作，例如：$sql->where($where)->limit(10,10)->select();
3.	where方法：where($w = array(), $type = array('logic' => '='), $link = "AND");//$w为数据键值对，键为列名；$type为键值比较逻辑关系，默认 $key=$v，即 WHERE $key = $v，$type可写某字段使用逻辑关系，例如 $type = array('id' => '<')则表示WHERE $key < $v；$link表示多条件连接时使用连接关系，默认为AND ，可设 OR。
4.	limit方法：limit($start, $length = 100);//
5.	find($returnFind = false, $dissql = false)：//查找最新一条，一般情况返回true，没有找到返回false；若指明返回找到的行，则返回行(一维)
6.	select($dissql = false) //select方法，与where  limit连贯使用（二维数组，数据行）
7.	insert($data, $dissql = false) //insert方法，单独使用，传入要设置的键值索引数组，没传入的键默认空或者时间，默认返回最新自增id，否则如果插入失败返回false（根据影响行数）
8.	update($data, $dissql = false) //必须结合where使用，错误返回false，否则返回影响行数>=1
9.	delete($dissql = false) //必须配合where使用,返回删除条数，失败返回false
