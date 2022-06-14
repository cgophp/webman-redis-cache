<?php

/**
 * redis缓存
 * 基于php的redis扩展(使用时需先安装php的redis扩展)
 * https://github.com/phpredis/phpredis
 */

namespace Cgophp\WebmanRedisCache;

use support\Log;

class RedisCache
{
	// 连接redis
	public static function connect($action = null)
	{
		// redis对象
		static $redis = null;
		
		// 重连
		if ($action === 'reconnect') {
			$redis = null;
		}
		
		// 已连接，返回redis对象
		if ($redis) {
			return $redis;
		}
		
		// 配置
		$config = config('plugin.cgophp.webman-redis-cache.app');
		
		// 实例化
		$redis = new \Redis();
		
		// 连接
		$redis->connect($config['host'], $config['port'], $config['timeout']);
		
		// 验证密码
		if ($config['password']) {
			$redis->auth($config['password']);
		}
		
		// 选择数据库
		if ($config['database']) {
			$redis->select($config['database']);
		}
		
		// 返回redis对象
		return $redis;
	}
	
	// 获取redis缓存数据
	public static function get($key, $callback, $expire = 0)
	{
		// 缓存key
		$key = trim($key);
		
		// 缓存key无效
		if (strlen($key) < 1) {
			return;
		}
		
		// 配置
		$config = config('plugin.cgophp.webman-redis-cache.app');
		
		// key加前缀
		$key = implode('_', [
			$config['prefix'],
			$key,
		]);
		
		// 连接redis
		$redis = static::connect();
		
		try {
			// 获取数据
			$raw = $redis->get($key);
		} catch (\Throwable $error) {
			// 记录错误日志
			Log::error('[webman-redis-cache-error]' . $error->getMessage());
			
			// 发生异常时重连一次
			$redis = static::connect('reconnect');
			
			// 重连后再次获取数据
			$raw = $redis->get($key);
		}
		
		// 命中缓存，直接返回数据
		if ($raw) {
			// 将原始数据反序列化为数组
			$array = unserialize($raw);
			return $array['value'];
		}
		
		// 获取缓存value
		$value = $callback();
		
		if (is_null($value)) {
			return;
		}
		
		// 缓存时间
		$expire = intval($expire);
		$expire = $expire > 0 ? $expire : $config['default_expire'];
		$expire = $expire > $config['max_expire'] ? $config['max_expire'] : $expire;
		
		// 设置缓存
		$redis->setEx($key, $expire, serialize([
			'value' => $value,
		]));
		
		return $value;
	}
	
	// 删除缓存key
	public static function remove($key)
	{
		// 缓存key
		$key = trim($key);
		
		// 缓存key无效
		if (strlen($key) < 1) {
			return;
		}
		
		// 配置
		$config = config('plugin.cgophp.webman-redis-cache.app');
		
		// key加前缀
		$key = implode('_', [
			$config['prefix'],
			$key,
		]);
		
		// 连接redis
		$redis = static::connect();
		
		try {
			// 删除缓存key
			$result = $redis->del($key);
		} catch (\Throwable $error) {
			// 记录错误日志
			Log::error('[webman-redis-cache-error]' . $error->getMessage());
			
			// 发生异常时重连一次
			$redis = static::connect('reconnect');
			
			// 重连后再次删除缓存key
			$result = $redis->del($key);
		}
		
		return $result > 0;
	}
}
