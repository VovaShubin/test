<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "follower".
 *
 * @property int $id
 * @property int $author
 * @property string $phone
 */
class Follower extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'follower';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['author'], 'integer'],
			[['phone'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author' => 'Author',
            'phone' => 'Phone',
        ];
    }
}
