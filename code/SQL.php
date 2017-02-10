<?php
/*
 * SQL操作类：基于PDO
 *
 * 2017-2-8   admin@ksust.com
 * */

/*
 * 操作类：根据表名初始化
 * 类似thinkphpM方法，使用M方法初始化*/

Class SQL
{
    //约定 每个sql与句段结束后空格
    public $tableName;//数据表名
    public $tableColum = array();//数据表列名,每个列名含如["Field"]=> string(2) "id" ["Type"]=> string(7) "int(11)" ["Null"]=> string(2) "NO" ["Key"]=> string(3) "PRI" ["Default"]=> NULL ["Extra"]=> string(14) "auto_increment"
    public $db = null;//数据库连接
    public $sql;//最后构造的sql语句
    public $where = "";//字符串，在where方法中，每行 ['Field'],['Value'],['Type'](逻辑类型，如 =，<,默认=)，被构造后带引号
    public $limit = "LIMIT 100";//字符串，默认100条,格式  limit  起始,条数
    public $field = "*";//字段值，默认*

    function __construct($tableName, $conn = array())
    {
        //构造方法默认使用配置数据库连接
        if (count($conn) == 0) $conn = include("database.php");//连接信息，来自文件的返回数组
        $this->tableName = $tableName;
        try {
            $this->db = new PDO($conn['dsn'], $conn['user'], $conn['pass'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));/*解决存入数据库时乱码问题*/
        } catch (PDOException $e) {
            die("数据库连接错误:" . $e->getMessage());
        }
        $this->tableColum = $this->db->query("SHOW COLUMNS FROM `$this->tableName` ")->fetchAll(PDO::FETCH_ASSOC);//获取列名
    }

    //查找最新一条，一般情况返回true，没有找到返回false；若指明返回找到的行，则返回行(一维)
    function find($returnFind = false, $dissql = false)
    {
        $this->sql = "SELECT $this->field FROM `$this->tableName` $this->where LIMIT 1";
        if ($dissql) echo $this->sql;
        $get = $this->db->query($this->sql)->fetchAll(PDO::FETCH_ASSOC);//获取多维数组，每维列名为索引
        $this->where = "";//为了安全
        if (count($get) == 0) return false;//返回空
        else if (!$returnFind) return true;
        return $get[0];
    }

    //select方法，与where  limit连贯使用（二维数组，数据行）
    function select($dissql = false)
    {
        $this->sql = "SELECT $this->field FROM `$this->tableName` $this->where $this->limit";
        if ($dissql) echo $this->sql;
        $get = $this->db->query($this->sql)->fetchAll(PDO::FETCH_ASSOC);//获取多维数组，每维列名为索引
        $this->where = "";//为了安全
        if (count($get) == 0) return null;//返回空
        return $get;
    }

    //insert方法，单独使用，传入要设置的键值索引数组，没传入的键默认空或者时间，默认返回最新自增id，否则如果插入失败返回false（根据影响行数）
    function insert($data, $dissql = false)
    {
        $colum = "";
        $value = "";
        $first = true;
        foreach ($this->tableColum as $arr) {
            if (!$first) {
                $colum .= ",";
                $value .= ",";
            }
            $colum .= "`" . $arr['Field'] . "`";

            if ($data[$arr['Field']] == null) { //对于默认值
                if ($arr['Type'] == "datetime") $value .= "'" . date('Y-m-d H:i:s') . "'";//默认时间类型
                else $value .= "''";//默认空
            } else  $value .= "'" . $data[$arr['Field']] . "'";//默认加引号
            $first = false;
        }
        $this->sql = "INSERT INTO `$this->tableName` ( $colum) VALUES ( $value )";
        if ($dissql) echo $this->sql;
        if ($this->db->query($this->sql)->rowCount() >= 1) return $this->db->lastInsertId();
        else return false;
    }

    //必须结合where使用，错误返回false，否则返回影响行数>=1
    function update($data, $dissql = false)
    {
        if ($this->where == "") return false;
        if (count($data) <= 0) return false;//默认改变值不能为空
        $set = "";
        $first = true;
        foreach ($data as $key => $v) {
            if (!$first) $set .= ",";

            $inColum = false;//验证是否在表列中
            foreach ($this->tableColum as $arr) {
                if ($key == $arr['Field']) $inColum = true;
            }
            if (!$inColum) return false;

            $set .= "`$key` = '$v'";
            $first = false;
        }
        $this->sql = "UPDATE `$this->tableName` SET $set $this->where";
        if ($dissql) echo $this->sql;
        $this->where = "";//为了安全
        $updateCount = $this->db->query($this->sql)->rowCount();
        if ($updateCount <= 0) return false;
        return $updateCount;
    }

    //必须配合where使用,返回删除条数，失败返回false
    function delete($dissql = false)
    {
        if ($this->where == "") return false;
        $this->sql = "DELETE FROM `$this->tableName` $this->where";
        if ($dissql) echo $this->sql;
        $this->where = "";//为了安全
        $deleteCount = $this->db->query($this->sql)->rowCount();
        if ($deleteCount <= 0) return false;
        return $deleteCount;

    }

    //在where方法中，每行 ['Field'],['Value'],['Type'](逻辑类型，如 =，<,默认=)，被构造后带引号
    //如下方法中：$w=array('Field'=>Value),$type=array('Field'=>Type)表示对应逻辑，,默认空时逻辑为=,Field在左;多条件连接默认AND
    function where($w = array(), $type = array('logic' => '='), $link = "AND")
    {
        if (count($w) == 0) {
            $this->where = "";
            return $this;
        }
        $where = "WHERE ";
        $first = true;
        foreach ($w as $key => $v) {
            if (!$first) $where .= "$link ";
            $logic = $type[$key] == null ? '=' : $type[$key];
            $where .= "`$key` $logic '$v' ";
            $first = false;
        }
        $this->where = $where;
        return $this;
    }

    //limit  当没有调用时默认一百条
    function limit($start, $length = 100)
    {
        if ($start < 0) $start = 0;
        $this->limit = "LIMIT $start,$length ";
        return $this;
    }

}

//M方法，表名，链接（为空时使用当前目录  database.php）
function M($tableName, $conn = array())
{
    return new SQL($tableName, $conn);
}
