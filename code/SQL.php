<?php
/**
 * Class SQL
 * @see SQL操作类：基于PDO,根据表名初始化,默认持久化和utf8
 * @Time 2017-2-8   admin@ksust.com
 * @version 1.0.4
 * @property tableName  数据表名
 * @property tableColum 表列名数组
 * @property db PDO对象
 * @property sql 最后执行的sql语句（执行时使用prepare）
 * @property where 构造过程中的where部分，被构造后带引号
 * @property limit 构造过程limit部分
 * @property order 构造过程order部分
 * @property field 构造过程field部分，默认*
 * @see     约定 每个sql与句段结束后空格
 */

Class SQL
{
    public $tableName;
    public $tableColum = [];
    public $db = null;
    public $sql;
    public $where = '';
    public $limit = null;
    public $order='';
    public $field = '*';

    /**
     * SQL constructor
     * @param null $tableName 操作表名，按表操作
     * @param array $conn 数据库连接信息，默认配置文件dataabse.php
     */
    function __construct($tableName=null, $conn = array())
    {
        //构造方法默认使用配置数据库连接
        if (count($conn) == 0) $conn = include("database.php");//连接信息，来自文件的返回数组
        $this->tableName = $tableName;
        try {
            $this->db = new PDO($conn['dsn'], $conn['user'], $conn['pass'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';",PDO::ATTR_PERSISTENT => true));/*解决存入数据库时乱码问题，并持久化*/
        } catch (PDOException $e) {
            die("数据库连接错误:" . $e->getMessage());
        }
       if($tableName!=null) $this->tableColum = $this->db->query("SHOW COLUMNS FROM `$this->tableName` ")->fetchAll(PDO::FETCH_ASSOC);//获取列名
    }


    /**
     * @param bool $returnFind 是否返回查找到的最新一条数据
     * @param bool $dissql
     * @return bool 是否查找到 或者 一维数组（最新一条）
     */
    function find($returnFind = false, $dissql = false)
    {
        $this->sql = "SELECT $this->field FROM `$this->tableName` $this->where LIMIT 1";
        $this->sql=$this->db->prepare($this->sql);//预处理，如转义、防注入等等
        if ($dissql) echo $this->sql->queryString;
        $this->sql->execute();
        $get=$this->sql->fetchAll(PDO::FETCH_ASSOC);
        $this->where = "";//为了安全
        if (count($get) == 0) return false;//返回空
        else if (!$returnFind) return true;
        return $get[0];
    }

    /**
     * @see 与where  limit连贯使用（二维数组，数据行）
     * @param bool $dissql
     * @return array|null 二维数组或者false
     */
    function select($dissql = false)
    {
        $this->sql = "SELECT $this->field FROM `$this->tableName` $this->where $this->order $this->limit";
        $this->sql=$this->db->prepare($this->sql);//预处理，如转义、防注入等等
        if ($dissql) echo $this->sql->queryString;
        $this->sql->execute();
        $get=$this->sql->fetchAll(PDO::FETCH_ASSOC);
        $this->where = "";//为了安全
        if (count($get) == 0) return null;//返回空
        return $get;
    }

    /**
     * @see 单独使用，传入要设置的键值索引数组，没传入的键默认空或者时间
     * @param $data=[field=>value,...]
     * @param bool $dissql
     * @return bool|string 默认返回最新自增id，否则如果插入失败返回false
     */
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
                else $value .= "NULL";//默认空 NULL
            } else  $value .= "'" . $data[$arr['Field']] . "'";//默认加引号
            $first = false;
        }
        $this->sql = "INSERT INTO `$this->tableName` ( $colum) VALUES ( $value )";
        $this->sql=$this->db->prepare($this->sql);//预处理，如转义、防注入等等
        if ($dissql) echo $this->sql->queryString;
        $this->sql->execute();
        if($this->sql->rowCount()>=1) return $this->db->lastInsertId();
        else return false;
    }

    /**
     * @see 必须结合where使用，错误返回false，否则返回影响行数>=1
     * @param array $data
     * @param bool $dissql
     * @return bool|int
     */
    function update($data=array(), $dissql = false)
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
        $this->sql=$this->db->prepare($this->sql);//预处理，如转义、防注入等等
        if ($dissql) echo $this->sql->queryString;
        $this->sql->execute();
        $this->where = "";//为了安全
        $updateCount = $this->sql->rowCount();
        if ($updateCount <= 0) return false;
        return $updateCount;
    }

    //必须配合where使用,返回删除条数，失败返回false

    /**
     * @see 可能需要配合回滚
     * @param bool $dissql
     * @return bool|int
     */
    function delete($dissql = false)
    {
        if ($this->where == "") return false;
        $this->sql = "DELETE FROM `$this->tableName` $this->where";
        $this->sql=$this->db->prepare($this->sql);//预处理，如转义、防注入等等
        if ($dissql) echo $this->sql->queryString;
        $this->sql->execute();
        $this->where = "";//为了安全
        $deleteCount = $this->sql->rowCount();
        //$this->db->rollBack();//可能需要回滚
        if ($deleteCount <= 0) return false;
        return $deleteCount;

    }

    /**
     * @param array $w =[field=>value,...]
     * @param array $type=array('Field'=>Value),$type=array('Field'=>Type)表示对应逻辑，,默认空时逻辑为=,Field在左;
     * @param string $link 多条件连接默认AND
     * @return $this
     */
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

    /**
     * @param $start
     * @param int $length
     * @return $this
     */
    function limit($start, $length = 100)
    {
        if ($start < 0) $start = 0;
        $this->limit = "LIMIT $start,$length ";
        return $this;
    }

    /**
     * @see 排序，自写字符串
     * @param string $orderby
     * @return $this
     */
    function order($orderby='order by id desc'){
        $this->order=$orderby;
        return $this;
    }

    /**
     * $see 设置查询field，默认为*
     * @param array $field
     * @return $this
     */
    function setField($field=['*']){
        if(count($field)<=0) return $this;
        $this->field='';
        $count=0;
        //这里不用验证列名，在执行prepare时会验证
        foreach ($field as $item){
            if($count>0) $this->field.=',';
            $this->field.='`'.$item.'`';
        }
        return $this;
    }

}

//M方法，表名，链接（为空时使用当前目录  database.php）
function M($tableName=null, $conn = array())
{
    return new SQL($tableName, $conn);
}
