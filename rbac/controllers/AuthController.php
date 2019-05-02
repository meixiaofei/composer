<?php


namespace meixiaofei\rbac\controllers;

use common\controllers\TopController;
use meixiaofei\components\Validate;
use meixiaofei\rbac\models\AuthRole;
use meixiaofei\rbac\models\AuthRoleRoute;
use meixiaofei\rbac\models\AuthRoute;
use meixiaofei\rbac\models\AuthUserRole;
use meixiaofei\rbac\models\User;

class AuthController extends TopController
{
    /**
     * @var string
     */
    public $defaultAction = 'menu';

    /**SUNLANDS
     *
     * @url          /auth/menu
     * @method       get
     * @tags         权限模块
     * @description  获取菜单
     * @summary      获取菜单
     *
     * @return \yii\web\Response
     */
    public function actionMenu()
    {
        return $this->asJson(AuthRoute::getMenu());
    }

    /**SUNLANDS
     *
     * @url          /auth/role-create
     * @method       post
     * @tags         权限模块
     * @description  添加角色
     * @summary      添加角色
     *
     * @param string name / formData 名称 true
     * @param integer status 1 formData 是否启用 false
     * @param string desc / formData 描述 false
     *
     * @return \yii\web\Response
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

    /**SUNLANDS
     *
     * @url          /auth/role-delete
     * @method       post
     * @tags         权限模块
     * @description  删除角色
     * @summary      删除角色
     *
     * @param array ids / formData 角色id true
     *
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
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

    /**SUNLANDS
     *
     * @url          /auth/role-update
     * @method       post
     * @tags         权限模块
     * @description  编辑角色
     * @summary      编辑角色
     *
     * @param string name / formData 名称 true
     * @param integer status 1 formData 是否启用 false
     * @param string desc / formData 描述 false
     *
     * @return \yii\web\Response
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

    /**SUNLANDS
     *
     * @url          /auth/role-list
     * @method       get
     * @tags         权限模块
     * @description  获取角色列表
     * @summary      获取角色列表
     *
     * @param integer id / query 角色id false
     * @param integer page 1 query 当前页码 false
     * @param integer page_size 20 query 分页大小 false
     *
     * @return \yii\web\Response
     */
    public function actionRoleList()
    {
        return $this->asJson(1, '', AuthRole::getListData($this->get()));
    }

    /**SUNLANDS
     *
     * @url          /auth/route-create
     * @method       post
     * @tags         权限模块
     * @description  添加路由
     * @summary      添加路由
     *
     * @param string name / formData 名称 true
     * @param integer pid 0 formData 父级id false
     * @param string f_route / formData 前端路由 false
     * @param string b_route / formData 后端路由 false
     * @param integer status 1 formData 状态 false
     * @param integer is_menu 0 formData 是否菜单 false
     * @param integer sort / formData 排序(越大越靠前) false
     * @param string icon / formData icon-class false
     *
     * @return \yii\web\Response
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

    /**SUNLANDS
     *
     * @url          /auth/route-delete
     * @method       post
     * @tags         权限模块
     * @description  删除路由
     * @summary      删除路由
     *
     * @param array ids / formData ids true
     *
     * @return \yii\web\Response
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

    /**SUNLANDS
     *
     * @url          /auth/route-update
     * @method       post
     * @tags         权限模块
     * @description  修改路由
     * @summary      修改路由
     *
     * @param integer id / formData id true
     * @param string name / formData 名称 true
     * @param integer pid 0 formData 父级id false
     * @param string f_route / formData 前端路由 false
     * @param string b_route / formData 后端路由 false
     * @param integer status 1 formData 状态 false
     * @param integer is_menu 0 formData 是否菜单 false
     * @param integer sort / formData 排序(越大越靠前) false
     * @param string icon / formData icon-class false
     *
     * @return \yii\web\Response
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

    /**SUNLANDS
     *
     * @url          /auth/route-detail
     * @method       get
     * @tags         权限模块
     * @description  路由详情
     * @summary      路由详情
     *
     * @param integer id / query 路由id true
     *
     * @return \yii\web\Response
     */
    public function actionRouteDetail()
    {
        return $this->asJson(1, '', AuthRoute::find()->where(['id' => $this->get('id', 1)])->asArray()->one());
    }

    /**SUNLANDS
     *
     * @url          /auth/route-list
     * @method       get
     * @tags         权限模块
     * @description  获取路由列表
     * @summary      获取路由列表
     *
     * @param integer role_id / query 角色id,查询角色被赋予的路由 false
     *
     * @return \yii\web\Response
     */
    public function actionRouteList()
    {
        return $this->asJson(1, '', AuthRoute::getDataList($this->get()));
    }

    /**SUNLANDS
     *
     * @url          /auth/role-assign-route
     * @method       post
     * @tags         权限模块
     * @description  角色赋予路由
     * @summary      角色赋予路由
     *
     * @param integer role_id / formData 角色id true
     * @param array route_ids / formData 路由id true
     *
     * @return \yii\web\Response
     * @throws \yii\db\Exception
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

    /**SUNLANDS
     *
     * @url          /auth/user-assign-role
     * @method       post
     * @tags         权限模块
     * @description  用户赋予角色
     * @summary      用户赋予角色
     *
     * @param integer uid / formData 用户id true
     * @param array role_ids / formData 角色id true
     *
     * @return \yii\web\Response
     * @throws \yii\db\Exception
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

    /**SUNLANDS
     *
     * @url          /auth/user-list
     * @method       get
     * @tags         权限模块
     * @description  用户列表
     * @summary      用户列表
     *
     * @param string username / query 263账号 false
     * @param string real_name / query 真实名 false
     * @param integer role_id / query 角色id false
     * @param integer page 1 query 当前页码 false
     * @param integer page_size 20 query 分页大小 false
     *
     * @return \yii\web\Response
     */
    public function actionUserList()
    {
        return $this->asJson(1, '', User::getDataList($this->get()));
    }
}
