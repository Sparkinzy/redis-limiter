<?php
/**
 * Created by PhpStorm.
 * User: mu
 * Date: 2019-05-06
 * Time: 23:50
 */

/**
 * @deprecated
 * Class Limit_req
 */
class Limit_req {
	
	# 有效的频率周期单位
	private $valid_rate_cycles = ['s' => 1, 'm' => '60', 'h' => '3600', 'd' => '86400'];
	# 保存错误信息内容
	private $error = [];
	
	# redis实例
	private $redis = NULL;
	# redis实例链接超时时间
	private $redis_timeout = 10;
	
	private $rate_count = 1;
	
	private $rate_cycle = 's';
	
	private $lua_limiter = <<<LUA
local key        = KEYS[1] -- 限流key
local limit      = tonumber(ARGV[1] or "1") -- 限流大小
local limit_time = tonumber(ARGV[2] or "1") -- 限流频率
local current    = tonumber(redis.call('get',key) or "0")
if current + 1 > limit then
	return 0
else -- 请求书+1 并设置过期时间
	redis.call("INCRBY",key,"1")
	redis.call("expire",key,limit_time)
	return 1
end
LUA;
	
	public function __construct($redis_host = '127.0.0.1', $redis_port = '6379', $redis_auth = '')
	{
		$this->init_redis($redis_host, $redis_port, $redis_auth);
	}
	
	/**
	 * 初始化redis实例
	 *
	 * @param string $redis_host
	 * @param string $redis_port
	 * @param string $redis_auth
	 *
	 */
	private function init_redis($redis_host = '127.0.0.1', $redis_port = '6379', $redis_auth = '')
	{
		$this->check_redis_extension();
		$this->redis = new Redis();
		try
		{
			$this->redis->connect($redis_host, $redis_port, $this->redis_timeout);
			if ( ! empty($redis_auth))
			{
				$this->redis->auth($redis_auth);
			}
		} catch (RedisException $e)
		{
			$msg = $e->getMessage();
			var_dump($msg);
		}
	}
	
	/**
	 * 检查当前环境是否包含redis扩展
	 * @return bool
	 */
	private function check_redis_extension()
	{
		if (extension_loaded('redis'))
		{
			return TRUE;
		} else
		{
			$this->error[] = 'Redis扩展未加载';
			return FALSE;
		}
	}
	
	/**
	 * 解析频率限制
	 *
	 * @param string $rate
	 *
	 * @return mixed
	 */
	private function parse_rate($rate = '1r/s')
	{
		$arr        = explode('/', trim($rate));
		$rate_count = $arr[0];
		$rate_cycle = $arr[1];
		if ( ! array_key_exists($rate_cycle, $this->valid_rate_cycles))
		{
			$this->error[] = '限流规则格式错误';
			return FALSE;
		}
		$rate_count = (int)str_replace('r', '', $rate_count);
		return [
			$rate_count,
			$this->valid_rate_cycles[$rate_cycle]
		];
	}
	
	public function burst($key = 'uri', $rate = '1r/s')
	{
		list($limit, $limit_time) = $this->parse_rate($rate);
		$is_passed = $this->redis->eval($this->lua_limiter, [$key, $limit, $limit_time], 1);
		return $is_passed;
	}
}

if (php_sapi_name() === 'cli')
{
	# 命令行模式访问
	$limiter = new Limit_req('127.0.0.1', '9101', 'Tyfo028');
	$is_pass = $limiter->burst('/dashboard', '3r/s');
	var_dump(date('Y-m-d H:i:s'), $is_pass);
}