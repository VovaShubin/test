<?php
namespace frontend\models;

use yii\base\Model;
use common\models\User;

/**
 * Signup form
 */
class TodoForm extends Model
{
    public $userid;
    public $todoitem;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

        ];
    }

}
