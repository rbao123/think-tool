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

    /**
     * @package redis\Redis
     * RedisTool constructor.
     */
    public function __construct()
    {
        $this->options = config('cache.stores')[config('cache.default')];
        if ($this->options ['type'] != 'redis') {
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
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }


    /**
     * 获取标签对应的缓存
     * @param $tag
     * @return mixed
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
     * @return mixed
     */
    public function getTag($tag)
    {
        return Cache::getTagItems(Cache::getTagKey($tag));
    }

    /**
     * 删除标签数组缓存并删除标签组关联的key
     * @param $tag
     * @return mixed
     */
    public function delTag($tag)
    {
        Cache::tag($tag)->clear();
        Cache::delete(Cache::getTagKey($tag));
    }

    /**
     * 获取set集合数据
     * @param $key
     * @return mixed
     */
    public function getSet($key)
    {
        return $this->handler->sMembers($this->options['prefix'] . $key);
    }

    /**
     * 删除set集合数据
     * @param $key
     * @param $value
     * @return mixed
     */
    public function sRem($key, $value)
    {
        if (is_array($value)) {
            return $this->handler->sRem($this->options['prefix'] . $key, ...$value);
        } else {
            return $this->handler->sRem($this->options['prefix'] . $key, $value);
        }
    }


    /**
     * 获取有序集合 指定范围的
     * @param $key
     * @param $start
     * @param $end
     * @param array $options 限制项
     *  - withscores => TRUE, 返回排序值
     *  - and limit => array($offset, $count) 限制数量
     * @return mixed
     */
    public function zRangeByScore($key, $start, $end, array $options = [])
    {
        return $this->handler->zRangeByScore($this->options['prefix'] . $key, $start, $end, $options);
    }

    /**
     * 删除有序集合 指定范围的
     * @param $key
     * @param $start
     * @param $end
     * @return mixed
     */
    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->handler->zRemRangeByScore($this->options['prefix'] . $key, $start, $end);
    }

    /**
     * 写入有序集合
     * @param $key
     * @param $arr array|string  ['k'=>v]|分数
     * @param null $value
     * @return mixed
     */
    public function zAdd($key, $arr, $value = null)
    {
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                $this->handler->zAdd($this->options['prefix'] . $key, ['CH'], $k, $v);
            }
        } else {
            $this->handler->zAdd($this->options['prefix'] . $key, ['CH'], $arr, $value);
        }
    }

    /**
     * 有序集合删除指定值
     * @param $key
     * @param $arr array|string
     * @return mixed
     */
    public function zRem($key, $arr)
    {
        $this->handler->zRem($this->options['prefix'] . $key, $arr);
    }
}