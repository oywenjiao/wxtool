<?php
/**
 * Created by : PhpStorm
 * User: OuYangWenJiao
 * Date: 2019/12/30
 * Time: 14:49
 */

namespace Wj\WxTool\Unit\CacheManage;


class RedisManage
{
    // 主机IP
    private $host = '127.0.0.1';

    // 服务端口
    private $port = '6739';

    // 连接时长 默认0表示不限时长
    private $timeout = 0;

    // 连接类型 1普通连接 2长连接
    private $redisType = 1;

    public $_REDIS = null;

    /**
     * 初始化
     * RedisManager constructor.
     * @param null $config
     */
    public function __construct($config = null)
    {
        if (!isset($this->_REDIS)) {
            $this->_REDIS = new \Redis();
            if ($config === null) {
                $this->connect($this->host, $this->port, $this->timeout, $this->redisType);
            } else {
                $this->connect($config['host'], $config['port'], $config['timeout'], $config['redis_type']);
            }
        }
    }

    /**
     * 连接redis服务器
     * @param $host
     * @param $port
     * @param $timeout
     * @param $type
     */
    private function connect($host, $port, $timeout, $type)
    {
        switch ($type) {
            case 1:
                $this->_REDIS->connect($host, $port, $timeout);
                break;
            case 2:
                $this->_REDIS->pconnect($host, $port, $timeout);
                break;
            default:
                break;
        }
    }

    /**
     * 获取 redis 实例
     * @return \Redis|null
     */
    public function getRedis()
    {
        return $this->_REDIS;
    }

    /**
     * 设置redis锁
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool
     */
    public function lock($key, $value, $ttl)
    {
        return $this->_REDIS->set($key, $value, array('nx', 'ex' => $ttl));
    }

    /**
     * 解除redis锁
     * @param $key
     */
    public function unlock($key)
    {
        $this->_REDIS->del($key);
    }

    /**
     * 对key值进行自增操作
     * @param $key
     * @param int $num 自增值，默认为1
     * @return int
     */
    public function setInc($key, $num = 1)
    {
        if ($num > 1) {
            return $this->_REDIS->incrBy($key, $num);
        }
        return $this->_REDIS->incr($key);
    }

    /**
     * 对key值进行自减操作
     * @param $key
     * @param int $num 自减值，默认为1
     * @return int
     */
    public function setDec($key, $num = 1)
    {
        if ($num > 1) {
            return $this->_REDIS->decrBy($key, $num);
        }
        return $this->_REDIS->decr($key);
    }
}