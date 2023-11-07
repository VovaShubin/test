<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * TodoList model
 *
 * @property integer $id
 * @property integer $userid
 * @property string $item
 *
 */

class TodoList extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%todolist}}';
    }

}
