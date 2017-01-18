<?php

/**
 * mysqli驱动器
 * @author 刘健 <59208859@qq.com>
 */
class MysqliDriver
{

    protected $mysqli = '';
    protected $host = 'localhost';
    protected $port = 3306;
    protected $username = 'root';
    protected $password = '';
    protected $database = '';
    protected $charset = 'utf8';
    protected $addslashes = true;

    public $affectedRows = 0;

    public function __construct($config = array())
    {
        $this->mysqli = mysqli_init();
        $this->connect($config);
    }

    public function __destruct()
    {
        $this->mysqli->close();
    }

    /**
     * 连接数据库
     * @param  array  $config [数据库配置参数]
     */
    public function connect($config = array())
    {
        // 配置参数
        foreach ($config as $key => $value) {
            if (isset($this->$key)) {
                $this->$key = $value;
            }
        }
        // 连接
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); // 设置超时时间
        $connect = @$this->mysqli->real_connect($this->host, $this->username, $this->password, $this->database, $this->port);
        if (!$connect) {
            throw new Exception(sprintf('Connect Error: [%s] %s', $this->mysqli->connect_errno, $this->mysqli->connect_error));
        }
        // 设置编码
        if (!$this->mysqli->set_charset($this->charset)) {
            throw new Exception(sprintf("Error loading character set utf8: [%s] %s", $this->mysqli->errno, $this->mysqli->error));
        }
    }

    /**
     * 执行查询
     */
    public function query()
    {
        // 获取参数
        $args = func_num_args();
        $argv = func_get_args();
        // 取出sql
        $sql = $argv[0];
        // 处理字符串参数
        unset($argv[0]);
        foreach ($argv as $key => $value) {
            if (!is_numeric($value)) {
                // 转义
                if ($this->addslashes) {
                    $argv[$key] = addslashes($argv[$key]);
                }
                // 加单引号
                $argv[$key] = "'{$argv[$key]}'";
            }
        }
        // 生成sql
        foreach ($argv as $key => $value) {
            if ($start = stripos($sql, '?')) {
                $sql = substr_replace($sql, $argv[$key], $start, 1);
            }
        }
        // 执行sql
        $resource = $this->mysqli->query($sql);
        // 设置影响的行数
        $this->affectedRows = $this->mysqli->affected_rows;
        // 返回数据
        if (is_bool($resource)) {
            if (!$resource) {
                throw new Exception(sprintf("Error SQL: [%s] %s", $this->mysqli->errno, $this->mysqli->error));
            }
            return $resource;
        } else {
            $this->affected_rows = 0;
            return new MysqliResult($resource);
        }
    }

}

/**
 * mysqli结果集
 * @author 刘健 <59208859@qq.com>
 */
class MysqliResult
{

    protected $resource = '';
    public $numRows = '';

    public function __construct($resource)
    {
        $this->resource = $resource;
        $this->numRows = $resource->num_rows;
    }

    /**
     * 返回全部结果集，对象
     * @return [type] [description]
     */
    public function allObject()
    {
        $tmp = array();
        while ($row = $this->rowObject()) {
            $tmp[] = $row;
        }
        return $tmp;
    }

    /**
     * 返回全部结果集，数组
     * @return [type] [description]
     */
    public function allArray()
    {
        $tmp = array();
        while ($row = $this->rowArray()) {
            $tmp[] = $row;
        }
        return $tmp;
    }

    /**
     * 返回结果集的一行，对象
     * @return [type] [description]
     */
    public function rowObject()
    {
        return $this->resource->fetch_object();
    }

    /**
     * 返回结果集的一行，数组
     * @return [type] [description]
     */
    public function rowArray()
    {
        return $this->resource->fetch_assoc();
    }

}
