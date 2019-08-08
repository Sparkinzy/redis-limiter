<?php

require_once __DIR__.'/src/Limit.php';
require_once __DIR__.'/src/Parser.php';
require_once __DIR__.'/src/RedisLimter.php';

use Mu\Juyuan\Limit;

$redisConf = [
	'host' => '127.0.0.1',
	'port' => 16379,
	'auth' => ''
];
Limit::setRedisConf($redisConf);
Limit::getInstance()->addItem('default')->setMax('1r/s');
for ($i=0;$i<100;$i++){
	usleep(500000);
	echo '['.date('Y-m-d H:i:s').']';
	if (Limit::isAllow('default')){
		echo '成功',PHP_EOL;
	}else{
		echo '失败',PHP_EOL;
	}
}

