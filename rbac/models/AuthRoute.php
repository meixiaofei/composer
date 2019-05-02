<?php

namespace meixiaofei\rbac\models;

/**
 * This is the model class for table "auth_route".
 *
 * @property int    $id
 * @property int    $pid
 * @property string $name
 * @property string $f_route
 * @property string $b_route
 * @property int    $status
 * @property int    $is_menu
 * @property int    $sort
 * @property string $icon
 * @property string $created_at
 * @property string $updated_at
 */
class AuthRoute extends _BaseCate
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_route}}';
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios           = parent::scenarios();
        $scenarios['create'] = ['pid', 'name', 'f_route', 'b_route', 'status', 'is_menu', 'sort', 'icon'];
        $scenarios['update'] = ['pid', 'name', 'f_route', 'b_route', 'status', 'is_menu', 'sort', 'icon'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'status', 'is_menu', 'sort'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['f_route', 'b_route', 'icon'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'pid'        => 'Pid',
            'name'       => 'Name',
            'f_route'    => 'F Route',
            'b_route'    => 'B Route',
            'status'     => 'Status',
            'is_menu'    => 'Is Menu',
            'sort'       => 'Sort',
            'icon'       => 'Icon',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return array
     */
    public static function getMenu()
    {
        $allRoute = AuthRoleRoute::getAllRouteByUid(1);

        return self::modReturn(1, '', self::getCate($allRoute));
    }

    public static function getDataList($param = [])
    {
        $param = self::prepareParam($param);
        $data  = self::find()->orderBy('id, sort desc')->asArray()->all();
        if (isset($param['role_id'])) {
            $roleRouteIds = AuthRoleRoute::getAllRouteIdByRoleIdsWithoutStatus([$param['role_id']]);
            foreach ($data as &$val) {
                if (in_array($val['id'], $roleRouteIds)) {
                    $val['checked'] = true;
                } else {
                    $val['checked'] = false;
                }
            }
        }

        return self::getCate($data);
    }

    private static function setRouteCache($uid, $routes)
    {
        $key = 'rbac_authority_uid' . $uid;
        self::cache()->set($key, $routes);
    }

    private static function getAuthCache($uid, $route)
    {
        $key = 'rbac_authority_uid' . $uid;
        if (self::cache()->exists($key)) {
            if (in_array($route, self::cache()->get($key))) {
                return true;
            }
        }

        return false;
    }

    public static function deleteAuthCache($uid)
    {
        $key = 'rbac_authority_uid' . $uid;
        self::cache()->delete($key);
    }

    public static function checkPermission($uid, $route)
    {
        $route = str_replace('//', '/', '/' . $route);

        if (in_array($route, self::app()->params['rbac_config']['except_routes'])) {
            return true;
        }
        while (($pos = strrpos($route, '/')) > 0) {
            $route = substr($route, 0, $pos);
            if (in_array($route . '/*', self::app()->params['rbac_config']['except_routes'])) {
                return true;
            }
        }

        if (self::getAuthCache($uid, $route)) {
            return true;
        }

        $roleIds = AuthUserRole::getRoleIdsByUid($uid);
        if (empty($roleIds)) {
            return false;
        }

        if (in_array(1, $roleIds)) {
            return true;
        }

        $authorityBRoutes = AuthRoleRoute::getAllRouteFieldByRoleIds($roleIds, 'route.b_route');
        self::setRouteCache($uid, $authorityBRoutes);
        if (in_array($route, $authorityBRoutes)) {
            return true;
        }

        return false;
    }
}
