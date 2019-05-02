<?php

namespace meixiaofei\rbac\models;

/**
 * This is the model class for table "auth_role".
 *
 * @property int    $id
 * @property string $name
 * @property int    $status
 * @property string $desc
 * @property string $created_at
 * @property string $updated_at
 */
class AuthRole extends _Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_role}}';
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios           = parent::scenarios();
        $scenarios['create'] = ['name', 'status', 'desc'];
        $scenarios['update'] = ['name', 'status', 'desc'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            ['name', 'unique', 'message' => '重复'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'name'       => 'Name',
            'status'     => 'Status',
            'desc'       => 'Desc',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getListData($param = [])
    {
        $param = self::prepareParam($param);

        $query = self::find();
        if (isset($param['id'])) {
            $query->where(['id' => $param['id']]);
        }
        $totalNum = $query->count();
        $lists    = $query->offset($param['offset'])->limit($param['limit'])->asArray()->all();

        return ['totalNum' => $totalNum, 'currentPage' => $param['page'], 'limit' => $param['limit'], 'lists' => $lists];
    }

    public static function getByCondition($where, $field = ['*'])
    {
        return self::find()->select($field)->where($where)->asArray()->all();
    }
}
