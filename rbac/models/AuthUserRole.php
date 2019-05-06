<?php

namespace meixiaofei\rbac\models;

use Yii;

/**
 * This is the model class for table "auth_user_role".
 *
 * @property int    $uid
 * @property int    $role_id
 * @property string $created_at
 * @property string $updated_at
 */
class AuthUserRole extends _Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_user_role}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'role_id'], 'required'],
            [['uid', 'role_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['uid', 'role_id'], 'unique', 'targetAttribute' => ['uid', 'role_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'uid'        => 'Uid',
            'role_id'    => 'Role ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getRoleByUid($uid = '', $field = 'role.id, role.name')
    {
        return self::find()->alias('user_role')
            ->select($field)
            ->innerJoin(AuthRole::tableName() . ' AS role', 'role.id = user_role.role_id')
            ->where(['user_role.uid' => $uid ?: self::user()->id])
            ->asArray()->all();
    }

    public static function getRoleIdsByUid($uid = '', $field = 'role.id')
    {
        $defaultRoleIds = self::app()->params['rbac_config']['default_role_ids'];
        $extraRoleIds   = self::find()->alias('user_role')
            ->select($field)
            ->innerJoin(AuthRole::tableName() . ' AS role', 'role.id = user_role.role_id')
            ->where(['user_role.uid' => $uid ?: self::user()->id])
            ->asArray()->column();

        return array_merge($defaultRoleIds, $extraRoleIds);
    }

    public static function setUserRoles($uid = '', $roleIds = [])
    {
        if (!is_numeric($uid)) {
            return self::modReturn(0, '需要指定用户id');
        }
        self::deleteAll(['uid' => $uid]);
        foreach ($roleIds as &$roleId) {
            $roleId = ['uid' => $uid, 'role_id' => $roleId];
        }
        self::addAll($roleIds);

        return self::modReturn();
    }

    public static function addRoles($roleIds = [], $uid = '')
    {
        $uid        = $uid ?: self::user()->id;
        $nowRoleIds = self::find()->select('role_id')->where(['uid' => $uid])->column();
        $add        = [];
        foreach (array_unique($roleIds) as $roleId) {
            if (!in_array($roleId, $nowRoleIds)) {
                $add[] = ['uid' => $uid, 'role_id' => $roleId];
            }
        }
        self::addAll($add);

        return self::modReturn();
    }
}
