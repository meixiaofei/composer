<?php


namespace meixiaofei\rbac\controllers;

use common\controllers\TopController;
use common\models\User;
use meixiaofei\components\Validate;
use meixiaofei\rbac\models\AuthRole;
use meixiaofei\rbac\models\AuthRoleRoute;
use meixiaofei\rbac\models\AuthRoute;
use meixiaofei\rbac\models\AuthUserRole;

class AuthController extends TopController
{
    /**
     * @var string
     */
    public $defaultAction = 'menu';

    /**
     * @api      {get} /rbac/auth/menu 获取菜单
     * @apiGroup 权限模块
     */
    public function actionMenu()
    {
        return $this->asJson(AuthRoute::getMenu());
    }

    /**
     * @api      {post} /rbac/auth/role-create 创建角色
     * @apiGroup 权限模块
     *
     * @apiParam {String} name 名称
     * @apiParam {Boolean} [status=true] 是否启用
     * @apiParam {String} [desc] 角色id
     */
    public function actionRoleCreate()
    {
        $mod = new AuthRole();
        $mod->setAttributes($this->post());
        $mod->setScenario('create');
        if ($mod->save()) {
            return $this->asJson();
        } else {
            return $this->asJson(0, $mod->getFirstErrors());
        }
    }

    /**
     * @api      {post} /rbac/auth/role-delete 删除角色
     * @apiGroup 权限模块
     *
     * @apiParam {Array} ids 角色id
     */
    public function actionRoleDelete()
    {
        $ids = $this->post('ids');
        if (in_array(1, $ids)) {
            return $this->asJson(0, '包含超管角色 非法删除~');
        }
        if (AuthRole::deleteAll(['id' => $ids])) {
            return $this->asJson();
        } else {
            return $this->asJson(0, '删除失败~');
        }
    }

    /**
     * @api      {post} /rbac/auth/role-update 编辑角色
     * @apiGroup 权限模块
     *
     * @apiParam {String} [name] 名称
     * @apiParam {Boolean} [status] 是否启用
     * @apiParam {String} [desc] 描述
     */
    public function actionRoleUpdate()
    {
        $post = $this->post();
        if (AuthRole::find()->where(['name' => $post['name']])->andWhere(['!=', 'id', $post['id']])->exists()) {
            return $this->asJson(0, '同名角色已经存在~');
        }
        $mod = AuthRole::findOne($post['id']);
        if (!$mod) {
            return $this->asJson(0, '该条目不存在~');
        }
        $mod->setAttributes($post);
        $mod->setScenario('update');
        if ($mod->save()) {
            return $this->asJson();
        } else {
            return $this->asJson(0, $mod->getFirstErrors());
        }
    }

    /**
     * @api      {get} /rbac/auth/role-list 获取角色列表
     * @apiGroup 权限模块
     *
     * @apiUse   page
     */
    public function actionRoleList()
    {
        return $this->asJson(1, '', AuthRole::getListData($this->get()));
    }

    /**
     * @api      {post} /rbac/auth/route-create 添加路由
     * @apiGroup 权限模块
     *
     * @apiParam {String} name 名称
     * @apiParam {Number} [pid=0] 父级id
     * @apiParam {String} [f_route] 前端路由
     * @apiParam {String} [b_route] 后端路由
     * @apiParam {Boolean} [status] 状态
     * @apiParam {Boolean} [is_menu] 是否菜单
     * @apiParam {Number} [sort=0] 排序(越大越靠前)
     * @apiParam {String} [icon] 图标
     */
    public function actionRouteCreate()
    {
        $post = $this->post();
        $mod  = new AuthRoute();
        $mod->setAttributes($post);
        $mod->setScenario('create');
        if ($mod->save()) {
            return $this->asJson();
        } else {
            return $this->asJson(0, $mod->getFirstErrors());
        }
    }

    /**
     * @api      {post} /rbac/auth/route-delete 删除路由
     * @apiGroup 权限模块
     *
     * @apiParam {Array} [ids] ids
     */
    public function actionRouteDelete()
    {
        $deleteRouteIds = $this->post('ids');
        if (empty($deleteRouteIds)) {
            return $this->asJson(0, '没有要删除的条目~');
        }
        if (AuthRoute::deleteAll(['id' => $deleteRouteIds])) {
            return $this->asJson();
        } else {
            return $this->asJson(0, '删除失败~');
        }
    }

    /**
     * @api      {post} /rbac/auth/route-update 修改路由
     * @apiGroup 权限模块
     *
     * @apiParam {Number} id id
     * @apiParam {String} name 名称
     * @apiParam {Number} [pid=0] 父级id
     * @apiParam {String} [f_route] 前端路由
     * @apiParam {String} [b_route] 后端路由
     * @apiParam {Boolean} [status] 状态
     * @apiParam {Boolean} [is_menu] 是否菜单
     * @apiParam {Number} [sort=0] 排序(越大越靠前)
     * @apiParam {String} [icon] 图标
     */
    public function actionRouteUpdate()
    {
        $post = $this->post();

        $mod = AuthRoute::findOne($post['id']);
        if (!$mod) {
            return $this->asJson(0, '该条目不存在~');
        }
        $mod->setAttributes($post);
        $mod->setScenario('update');
        if ($mod->save()) {
            return $this->asJson();
        } else {
            return $this->asJson(0, $mod->getFirstErrors());
        }
    }

    /**
     * @api      {get} /rbac/auth/route-detail 路由详情
     * @apiGroup 权限模块
     *
     * @apiParam {Number} id id
     */
    public function actionRouteDetail()
    {
        return $this->asJson(1, '', AuthRoute::find()->where(['id' => $this->get('id', 1)])->asArray()->one());
    }

    /**
     * @api      {get} /rbac/auth/route-list 获取路由列表
     * @apiGroup 权限模块
     *
     * @apiParam {Number} [role_id] 角色id,查询角色被赋予的路由
     */
    public function actionRouteList()
    {
        return $this->asJson(1, '', AuthRoute::getDataList($this->get()));
    }

    /**
     * @api      {post} /rbac/auth/role-assign-route 角色赋予路由
     * @apiGroup 权限模块
     *
     * @apiParam {Number} role_id 角色id
     * @apiParam {Array} route_ids 角色路由ids
     */
    public function actionRoleAssignRoute()
    {
        $post     = $this->post();
        $validate = new Validate();
        $validate->rule([
            'role_id'   => 'require|integer',
            'route_ids' => 'require|array',
        ]);
        if (!$validate->check($post)) {
            return $this->asJson(0, $validate->getError());
        }
        foreach ($post['route_ids'] as &$val) {
            $val = ['role_id' => $post['role_id'], 'route_id' => $val];
        }
        AuthRoleRoute::deleteAll(['role_id' => $post['role_id']]);
        AuthRoleRoute::addAll($post['route_ids']);

        return $this->asJson();
    }

    /**
     * @api      {post} /rbac/auth/user-assign-role 用户赋予角色
     * @apiGroup 权限模块
     *
     * @apiParam {Number} uid 用户id
     * @apiParam {Array} [role_ids] 角色id
     */
    public function actionUserAssignRole()
    {
        $post     = $this->post();
        $validate = new Validate();
        $validate->rule([
            'uid'      => 'require|integer',
            'role_ids' => 'require|array',
        ]);
        if (!$validate->check($post)) {
            return $this->asJson(0, $validate->getError());
        }
        foreach ($post['role_ids'] as &$val) {
            $val = ['uid' => $post['uid'], 'role_id' => $val];
        }
        AuthUserRole::deleteAll(['uid' => $post['uid']]);
        AuthUserRole::addAll($post['role_ids']);
        AuthRoute::deleteAuthCache($post['uid']);

        return $this->asJson();
    }

    /**
     * @api      {get} /rbac/auth/user-list 后台用户列表
     * @apiGroup 权限模块
     *
     * @apiParam {String} [username] 用户名
     * @apiParam {String} [real_name] 真名
     * @apiParam {Array} [role_id] 角色id
     * @apiUse   page
     */
    public function actionUserList()
    {
        return $this->asJson(1, '', User::getDataList($this->get()));
    }
}
