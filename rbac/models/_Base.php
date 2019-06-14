<?php

namespace meixiaofei\rbac\models;

use common\components\GeoHash;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class _Base extends ActiveRecord
{
    static $unifiedUserField = 'user.id as user_id, user.username, user.avatar, user.gender';
    static $geoHashLevel = 1;
    static $isSuperUser = null;

    /**
     * @return false|string
     */
    public static function getNow()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @param int    $code
     * @param string $msg
     * @param array  $data
     *
     * @return array
     */
    public static function modReturn($code = 1, $msg = '', $data = [])
    {
        return ['code' => (int)$code, 'msg' => $msg ?: ($code ? '操作成功' : '操作失败'), 'data' => $data];
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
     * @param null $name
     * @param null $defaultValue
     *
     * @return array|mixed
     */
    public static function get($name = null, $defaultValue = null)
    {
        return self::app()->request->get($name, $defaultValue);
    }

    /**
     * @param null $name
     * @param null $defaultValue
     *
     * @return array
     */
    public static function input($name = null, $defaultValue = null)
    {
        if ($name) {
            return self::get($name, $defaultValue) ?: self::post($name, $defaultValue);
        }

        return array_merge(self::post($name, $defaultValue), self::get($name, $defaultValue));
    }

    /**
     * @param null $name
     * @param null $defaultValue
     *
     * @return array|mixed
     */
    public static function post($name = null, $defaultValue = null)
    {
        return self::app()->request->post($name, $defaultValue);
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
        $default['like'] = [];
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

    /**
     * @param array         $param
     * @param callable|null $whereFunc
     * @param string        $orderBy
     *
     * @return array
     * @throws \Exception
     */
    public static function getList($param = [], callable $whereFunc = null, $orderBy = 'id desc,sort desc')
    {
        $param['default'] = $param['default'] ?? [];
        $param            = self::prepareParam($param, array_merge(['status' => 1], $param['default']));
        $query            = static::find();

        /**
         *  执行回调
         *  function($query){
         *      $query->andWhere()
         *      $query->andOrderBy()
         *      ...
         *  }
         */
        if (is_callable($whereFunc)) {
            $whereFunc($query, $param);
        }
        $alreadyUsedFields = self::getWhereField((array)$query->where);
        $validateField     = array_diff(array_keys(array_intersect_key($param, (new static())->getAttributes())), $alreadyUsedFields);
        foreach ($validateField as $field) {
            $param[$field] !== 998 && $query->andWhere([$field => $param[$field]]);
        }

        foreach ($param['like'] as $likeField) {
            if (isset($param[$likeField])) {
                $query->andWhere(['like', $likeField, "$param[$likeField]%", false]);
            }
        }

        if (isset($param['created_at_range'])) {
            $range = self::explodeTimeRange($param['created_at_range']);
            $query->andWhere(['between', 'created_at', $range[0], $range[1]]);
        }

        $totalNum = $query->count();

        $lists = $query->offset($param['offset'])->limit($param['limit'])->orderBy($orderBy)->asArray()->all();

        return ['totalNum' => $totalNum, 'currentPage' => $param['page'], 'limit' => $param['limit'], 'lists' => $lists];
    }

    /**
     * @param $conditions
     *
     * @return array
     */
    public static function getWhereField($conditions)
    {
        $fields = [];
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                if (isset($condition[1])) {
                    array_push($fields, $condition[1]);
                } else {
                    foreach (array_keys($condition) as $field) {
                        array_push($fields, $field);
                    }
                }
            } else {
                list($field) = explode(' = ', $condition);
                array_push($fields, $field);
            }
        }

        return array_unique($fields);
    }

    public static function softDelete($id)
    {
        return static::updateAll(['deleted_at' => null], ['id' => $id]);
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

    public static function getAuthScalar($field, $userId = null)
    {
        return static::find()->select($field)->where(['uid' => self::input('uid', $userId ?: self::user()->id)])->scalar();
    }

    public static function getAuthColumn($field, $userId = null)
    {
        return static::find()->select($field)->where(['uid' => self::input('uid', $userId ?: self::user()->id)])->column();
    }

    /**
     * 角色是否为: 超管
     *
     * @param null $userId
     *
     * @return bool
     */
    public static function isSuperUser($userId = null)
    {
        if (self::$isSuperUser !== null) {
            return self::$isSuperUser;
        }

        return self::$isSuperUser = self::isTheRoleById(1, $userId);
    }

    /**
     *
     * $whenArr = [
     * ['key' => 1, 'value' => '待初审'],
     * ['key' => 2, 'value' => '待复审'],
     * ['key' => 3, 'value' => '待打款'],
     * ['key' => 4, 'value' => '审核不通过'],
     * ['key' => 5, 'value' => '确认打款'],
     * ['key' => 6, 'value' => '打款失败'],
     * ]
     *
     * 最终生成的select
     * CASE filed WHEN 0 THEN '00' WHEN 1 THEN '11' ELSE 'OTHERS' END AS alias
     *
     * @param      $filed
     * @param      $whenArr
     * @param null $else
     * @param null $alias
     *
     * @return string
     */
    public static function buildCaseSelect($filed, $whenArr, $else = null, $alias = null)
    {
        $caseSelect = "CASE $filed ";
        foreach ($whenArr as $when) {
            $caseSelect .= 'WHEN ' . self::db()->quoteValue($when['key']) . ' THEN ' . self::db()->quoteValue($when['value']) . ' ';
        }
        if ($else) {
            $caseSelect .= 'ELSE ' . $else . ' ';
        }
        $caseSelect .= 'END';
        if ($alias) {
            $caseSelect .= ' AS ' . $alias;
        }

        return new Expression($caseSelect);
    }

    /**
     * @param array $roleId
     * @param null  $userId
     *
     * @return bool
     */
    private static function isTheRoleById($roleId = [], $userId = null)
    {
        $allRoleId = AuthUserRole::getAuthColumn('role_id', $userId);
        if (is_numeric($roleId)) {
            $roleId = [$roleId];
        }
        if (array_intersect($roleId, $allRoleId)) {
            return true;
        }

        return false;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public static function generateApiDoc()
    {
        $doc = '';
        foreach (static::getTableSchema()->columns as $column) {
            $type    = ucfirst($column->phpType);
            $comment = $column->comment ?: $column->name;
            if ($column->defaultValue) {
                $doc .= "* @apiParam {{$type}} [{$column->name}={$column->defaultValue}] {$comment}<br/>";
            } else {
                $doc .= "* @apiParam {{$type}} [{$column->name}] {$comment}<br/>";
            }
        }
        die($doc);
    }
}
