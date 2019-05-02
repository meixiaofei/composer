<?php

namespace meixiaofei\rbac\models;

use Yii;

/**
 * This is the model class for table "auth_role_route".
 *
 * @property int    $role_id
 * @property int    $route_id
 * @property string $created_at
 * @property string $updated_at
 */
class AuthRoleRoute extends _Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_role_route}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['role_id', 'route_id'], 'required'],
            [['role_id', 'route_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['role_id', 'route_id'], 'unique', 'targetAttribute' => ['role_id', 'route_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'role_id'    => 'Role ID',
            'route_id'   => 'Route ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getAllRouteByUid($isMenu = 0)
    {
        $query = self::find()->alias('role_route')
            ->select('route.*')
            ->innerJoin(AuthRoute::tableName() . ' AS route', 'route.id = role_route.route_id and route.status = 1')
            ->innerJoin(AuthUserRole::tableName() . ' AS user_role', 'user_role.role_id = role_route.role_id')
            ->innerJoin(AuthRole::tableName() . ' AS role', 'role.id = user_role.role_id and role.status = 1')
            ->where(['user_role.uid' => self::user()->id]);
        if ($isMenu) {
            $query->andWhere(['route.is_menu' => $isMenu]);
        }

        return $query->orderBy('route.sort desc,route.id asc')->asArray()->all();
    }

    public static function getAllRouteFieldByRoleIds($roleIds = [], $field = 'route.*')
    {
        if (empty($roleIds)) {
            return [];
        }

        return self::find()->alias('role_route')
            ->select($field)
            ->innerJoin(AuthRoute::tableName() . ' AS route', 'route.id = role_route.route_id and route.status = 1')
            ->innerJoin(AuthRole::tableName() . ' AS role', 'role.id = role_route.role_id and role.status = 1')
            ->where(['role_route.role_id' => $roleIds])
            ->asArray()->column();
    }

    public static function getAllRouteIdByRoleIdsWithoutStatus($roleIds = [])
    {
        return self::find()->select('route_id')->where(['role_id' => $roleIds])->asArray()->column();
    }
}
