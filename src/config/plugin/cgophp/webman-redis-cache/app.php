<?php
return [
	// 是否开启插件
    'enable' => true,
	
	// 主机
	'host' => '127.0.0.1',
	
	// 端口
	'port' => 6379,
	
	// 连接超时时间
	'timeout' => 30,
	
	// 密码
	'password' => '',
	
	// 数据库编号
	'database' => 0,
	
	// key前缀
	'prefix' => 'webman_redis_cache',
	
	// 默认缓存时间(秒)
	'default_expire' => 3600,
	
	// 最大缓存时间(秒)
	'max_expire' => 86400 * 100,
];
