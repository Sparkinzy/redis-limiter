# 使用方式

## 添加redis配置
```php
$redisConf = [
	'host' => '127.0.0.1',
	'port' => 16379,
	'auth' => ''
];
Limit::setRedisConf($redisConf);
```

## 配置限流规则
```php
Limit::getInstance()->addItem('default')->setMax('1r/s');
```
addItem() 配置的是限流的key

setMax()  配置的是限流规则，类似nginx限流规则
- 1r/s 表示每秒限一次
- 1r/m 每分钟一次
- 1r/h 每小时一次
- 1r/d 每天一次

## 3.实际项目中检查是否符合限流
```php
if (Limit::isAllow('default')){
    echo '成功',PHP_EOL;
}else{
    echo '失败',PHP_EOL;
}
```