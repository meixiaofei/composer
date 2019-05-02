<?php

namespace meixiaofei\rbac;

/**
 * rbac module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'meixiaofei\rbac\controllers';

    /**
     * {@inheritdoc}
     */
    public $defaultRoute = 'auth';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
