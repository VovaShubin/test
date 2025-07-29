<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "link_clicks".
 *
 * @property int $id
 * @property int $short_link_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $referer
 * @property string $clicked_at
 *
 * @property ShortLink $shortLink
 */
class LinkClick extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%link_clicks}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['clicked_at'],
                ],
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['short_link_id', 'ip_address'], 'required'],
            [['short_link_id'], 'integer'],
            [['user_agent', 'referer'], 'string'],
            [['clicked_at'], 'safe'],
            [['ip_address'], 'string', 'max' => 45],
            [['short_link_id'], 'exist', 'skipOnError' => true, 'targetClass' => ShortLink::class, 'targetAttribute' => ['short_link_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'short_link_id' => 'ID короткой ссылки',
            'ip_address' => 'IP адрес',
            'user_agent' => 'User Agent',
            'referer' => 'Реферер',
            'clicked_at' => 'Время перехода',
        ];
    }

    /**
     * Gets query for [[ShortLink]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShortLink()
    {
        return $this->hasOne(ShortLink::class, ['id' => 'short_link_id']);
    }

    /**
     * Создает запись о переходе
     * @param int $shortLinkId
     * @return bool
     */
    public static function createClick($shortLinkId)
    {
        $click = new self();
        $click->short_link_id = $shortLinkId;
        $click->ip_address = Yii::$app->request->userIP;
        $click->user_agent = Yii::$app->request->userAgent;
        $click->referer = Yii::$app->request->referrer;
        
        return $click->save();
    }
} 