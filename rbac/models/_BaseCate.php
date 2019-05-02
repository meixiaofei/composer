<?php

namespace meixiaofei\rbac\models;

class _BaseCate extends _Base
{
    /**
     * 获取层级分类
     *
     * @param array  $categories
     * @param string $name
     * @param int    $pid
     *
     * @return array
     */
    public static function getCate($categories = [], $name = 'child', $pid = 0)
    {
        $arr = [];
        foreach ($categories as $category) {
            if ($category['pid'] == $pid) {
                $category[$name] = self::getCate($categories, $name, $category['id']);
                $arr[]           = $category;
            }
        }

        return $arr;
    }

    /**
     * 获取目录分类
     *
     * @param array $categories
     * @param int   $pid
     * @param int   $level
     *
     * @return array
     */
    public static function getCateMenu($categories = [], $pid = 0, $level = 0)
    {
        $arr = [];
        foreach ($categories as $k => $category) {
            if ($category['pid'] == $pid) {
                $category['level'] = $level + 1;
                $arr[]             = $category;
                $arr               = array_merge($arr, self::getCateMenu($categories, $category['id'], $level + 1));
            }
        }

        return $arr;
    }

    /**
     * 获取父级
     *
     * @param array $categories
     * @param       $pid
     *
     * @return array
     */
    public static function getParents($categories = [], $pid)
    {
        $arr = [];
        foreach ($categories as $category) {
            if ($category['id'] == $pid) {
                $arr[] = $category;
                $arr   = array_merge(self::getParents($categories, $category['pid'], $arr));
            }
        }

        return $arr;
    }

    /**
     * 获取子级
     *
     * @param array $categories
     * @param       $id
     *
     * @return array
     */
    public static function getChildren($categories = [], $id)
    {
        $arr = [];
        foreach ($categories as $category) {
            if ($category['pid'] == $id) {
                $arr[] = $category;
                $arr   = array_merge($arr, self::getChildren($categories, $category['id']));
            }
        }

        return $arr;
    }

    /**
     * 获取子级id
     *
     * @param array   $categories
     * @param         $id
     * @param bool    $needArray
     * @param bool    $self
     * @param string  $keyName
     *
     * @return array|string
     */
    public static function getChildrenIds($categories = [], $id, $needArray = true, $self = false , $keyName = 'id')
    {
        $arr = [];
        foreach ($categories as $category) {
            if ($category['pid'] == $id) {
                $arr[] = $category[$keyName];
                $arr   = array_merge($arr, self::getChildrenIds($categories, $category[$keyName],$needArray,$self,$keyName));
            }
        }

        if ($self) {
            array_push($arr, $id);
        }

        return $needArray ? $arr : implode(',', $arr);
    }

    public static function getList($param = [])
    {
        return static::find()->where(['status' => 1])->orderBy('sort,id desc')->asArray()->all();
    }

    public static function getCateList()
    {
        return self::getCate(self::getList());
    }
}
