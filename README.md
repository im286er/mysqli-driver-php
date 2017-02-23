## 项目介绍

简易好用的mysqli驱动器与结果集，带自动事务，参数转义

### 说明文档

> example.php 里有范例代码

#### 1. 连接数据库

##### 方法1：使用构造方法连接

```php
$mysqli = new MysqliDriver(['username' => 'root', 'password' => '123456', 'database' => 'test']);
```

##### 方法2：使用connect连接

```php
$mysqli = new MysqliDriver();
$conf = ['username' => 'root', 'password' => '123456', 'database' => 'test'];
$mysqli->connect($conf);
```

##### 方法3: 使用类文件里的默认值连接

```php
$mysqli = new MysqliDriver();
$mysqli->connect();
```

#### 2. 执行查询

select

```php
$query = $mysqli->query('select * from `money`');
print_r($query->result());
```

update insert delete

```php
$status = $mysqli->query('update `money` set number = number - ? where uid = ?', 10, 100);
if($status){
    echo $mysqli->affectedRows;
}
```

#### 2. 事务

事物中的sql执行失败，会自动回滚

```php
$mysqli->transStart();
$mysqli->query('update `money` set number = number - ? where uid = ?', 10, 100);
$mysqli->query('insert `history`(uid, number) values(?, ?)', 100, -10);
$mysqli->transComplete();
echo 'Transaction execution status: ' . ($mysqli->transStatus() ? 'true' : 'false');
```
