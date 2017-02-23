<?php

namespace Library;

/**
 * mysqli驱动器
 * @author 刘健 <code.liu@qq.com>
 */
class MysqliDriver
{

    /* 内部参数 */
    protected $mysqli = '';
    protected $connectTimeout = 2;
    protected $transStartStatus = false;
    protected $transQueryStatus = false;

    /* 用户配置参数 */
    protected $host = 'localhost';
    protected $port = 3306;
    protected $username = 'root';
    protected $password = '123456';
    protected $database = 'test';
    protected $charset = 'utf8';
    protected $addslashes = true;

    /* 公共参数 */
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
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->connectTimeout); // 设置超时时间
        $connect = @$this->mysqli->real_connect($this->host, $this->username, $this->password, $this->database, $this->port);
        if (!$connect) {
            throw new \Exception(sprintf('Connect Error: [%s] %s', $this->mysqli->connect_errno, $this->mysqli->connect_error));
        }
        // 设置编码
        if (!$this->mysqli->set_charset($this->charset)) {
            throw new \Exception(sprintf("Error loading character set utf8: [%s] %s", $this->mysqli->errno, $this->mysqli->error));
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
        unset($argv[0]);
        // 处理字符串参数        
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
                // sql执行失败
                if ($this->transStartStatus) {
                    $this->transQueryStatus = false;
                } else {
                    throw new \Exception(sprintf("Error SQL: [%s] %s", $this->mysqli->errno, $this->mysqli->error));
                }
            }
            return $resource;
        } else {
            return new MysqliResult($resource);
        }
    }

    /**
     * 自动事务开始
     */
    public function transStart()
    {
        $this->transStartStatus = true;
        $this->transQueryStatus = true;
    }

    /**
     * 自动事务完成 (自动提交或回滚)
     */
    public function transComplete()
    {
        if ($this->transQueryStatus) {
            $this->transCommit();
        } else {
            $this->transRollback();
        }
        $this->transStartStatus = false;
        $this->transQueryStatus = false;
    }

    /**
     * 事务执行状态
     */
    public function transStatus()
    {
        return $this->transQueryStatus;
    }

    /**
     * 事务开始
     */
    public function transBegin()
    {
        $this->mysqli->autocommit(false); // 关闭自动提交
    }

    /**
     * 事务提交
     */
    public function transCommit()
    {
        $this->mysqli->commit();
        $this->mysqli->autocommit(true); // 重新开启自动提交
    }

    /**
     * 事务回滚
     */
    public function transRollback()
    {
        $this->mysqli->rollback();
        $this->mysqli->autocommit(true); // 重新开启自动提交
    }

}
