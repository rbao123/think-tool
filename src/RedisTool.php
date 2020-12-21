<?php


namespace bao\tool;


use think\App;
use think\facade\Cache;


/**
 * Class RedisTool redis扩展工具
 * @package app\common\tool
 */
class RedisTool
{
    private $handler;
    private $options = [];

    public function __construct()
    {
        $options = config('cache.stores')[config('cache.default')];
        if ($options['type'] != 'redis') {
            abort(422, '当前缓存不是redis');
        }
        $this->handler = Cache::handler();
    }

    /**
     * 反序列化数据
     * @access protected
     * @param string $data 缓存数据
     * @return mixed
     */
    public function unserialize(string $data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->options['serialize'][1] ?? "unserialize";

        return $unserialize($data);
    }


    /**
     * 获取连接完成的 redis 实例
     * @return
     */
    public function getHandler()
    {
        return $this->handler;
    }


    /**
     * 获取标签对应的缓存
     * @param $tag
     * @return array
     */
    public function getTagArr($tag)
    {
        $names = Cache::getTagItems(Cache::getTagKey($tag));
        $res = [];
        foreach ($names as $name) {
            $res[] = $this->unserialize($this->handler->get($name));
        }
        return $res;
    }

    /**
     * 获取标签数组缓存
     * @param $tag
     * @return array
     */
    public function getTag($tag)
    {
        return Cache::getTagItems(Cache::getTagKey($tag));
    }
}