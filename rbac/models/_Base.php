<?php

namespace meixiaofei\rbac\models;

use common\components\GeoHash;
use Yii;
use yii\db\ActiveRecord;

class _Base extends ActiveRecord
{
    static $unifiedUserField = 'user.id as user_id, user.username, user.avatar, user.gender';
    static $geoHashLevel     = 1;

    /**
     * @param string $code
     * @param string $msg
     * @param array  $data
     *
     * @return array
     */
    public static function modReturn($code = 1, $msg = '', $data = [])
    {
        return ['code' => $code, 'msg' => $msg ?: ($code ? '操作成功' : '操作失败'), 'data' => $data];
    }

    /**
     * @return \yii\console\Application|\yii\web\Application
     */
    public static function app()
    {
        return Yii::$app;
    }

    /**
     * @return mixed|\yii\web\User
     */
    public static function user()
    {
        return self::app()->user;
    }

    /**
     * @return \yii\caching\Cache|\yii\caching\CacheInterface
     */
    public static function cache()
    {
        return self::app()->cache;
    }

    /**
     * @return \yii\redis\Connection
     */
    public static function redis()
    {
        return self::app()->redis;
    }

    /**
     * @return \yii\db\Connection
     */
    public static function db()
    {
        return self::app()->db;
    }

    /**
     * @param array $dataArr
     *
     * @return int
     * @throws \yii\db\Exception
     */
    public static function addAll($dataArr = [])
    {
        if (!empty($dataArr)) {
            $fields = array_keys($dataArr[0]);
            $values = [];
            foreach ($dataArr as $dataVal) {
                $values[] = array_values($dataVal);
            }

            return static::db()->createCommand()->batchInsert(static::tableName(), $fields, $values)->execute();
        }
    }

    /**
     * 目前只处理页码 分页大小
     * 1.将完全匹配'true' or 'false'的值转化成 01
     * 2.当值为空时 unset此字段
     *
     * @param array $param
     * @param array $default
     *
     * @return array
     */
    public static function prepareParam($param = [], $default = [])
    {
        foreach (array_keys($param) as $keyName) {
            if (is_string($param[$keyName])) {
                $trimValue = trim($param[$keyName]);
                switch ($trimValue) {
                    case 'true':
                        $trimValue = 1;
                        break;
                    case 'false':
                        $trimValue = 0;
                        break;
                    case 'null':
                        $trimValue = null;
                        break;
                }
                $param[$keyName] = $trimValue;
                if ($trimValue === '') {
                    unset($param[$keyName]);
                }
            } elseif (is_bool($param[$keyName])) {
                $param[$keyName] = $param[$keyName] ? 1 : 0;
            }
        }

        $maxLimit        = 200;
        $limit           = $param['page_size'] ?? 20;
        $param['page']   = $param['page'] ?? 1;
        $param['limit']  = $limit < $maxLimit ? $limit : $maxLimit;
        $param['offset'] = $param['limit'] * ($param['page'] - 1);

        return array_merge($default, $param);
    }

    /**
     * @param     $timeString
     * 自动补成全时间格式 即 年-月-日 时:分:秒
     *
     * @return array
     * @throws \Exception
     */
    public static function explodeTimeRange($timeString)
    {
        $tmp = array_map('trim', explode(' - ', $timeString));
        if (count($tmp) != 2) {
            throw new \Exception('输入日期区间格式不对,应用 - 这样的东西分隔');
        }
        if (false !== strpos($timeString, ':')) {
            $range = $tmp;
        } else {
            $range = [$tmp[0] . ' 00:00:00', $tmp[1] . ' 23:59:59'];
        }

        return $range;
    }

    public static function getList($param = [])
    {
        $param = self::prepareParam($param);

        $query = self::find()->where(['status' => 1]);
        if (isset($param['cid'])) {
            $query->andWhere(['cid' => $param['cid']]);
        }

        $totalNum = $query->count();

        $lists = $query->offset($param['offset'])->limit($param['limit'])->orderBy('sort,id desc')->asArray()->all();

        return ['totalNum' => $totalNum, 'currentPage' => $param['page'], 'limit' => $param['limit'], 'lists' => $lists];
    }

    public static function softDelete($id)
    {
        return self::updateAll(['deleted_at' => null], ['id' => $id]);
    }

    public static function getGeo($hash = true)
    {
        if ($geo = self::cache()->get('geo:' . self::user()->id)) {
            $hash && $geo['geo_hash'] = GeoHash::encode($geo['lnt'], $geo['lat']);
        } else {
            $geo = ['lnt' => 114.39594, 'lat' => 30.51835];
            $hash && $geo['geo_hash'] = GeoHash::encode($geo['lnt'], $geo['lat']);
        }

        return $geo;
    }
}
