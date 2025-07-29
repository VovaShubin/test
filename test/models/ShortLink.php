<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "short_links".
 *
 * @property int $id
 * @property string $original_url
 * @property string $short_code
 * @property string|null $qr_code_path
 * @property int $clicks_count
 * @property string $created_at
 * @property string $updated_at
 */
class ShortLink extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%short_links}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
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
            [['original_url', 'short_code'], 'required'],
            [['original_url'], 'string'],
            [['clicks_count'], 'integer'],
            [['clicks_count'], 'default', 'value' => 0],
            [['short_code'], 'string', 'max' => 10],
            [['short_code'], 'unique'],
            [['original_url'], 'unique'],
            [['qr_code_path'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'original_url' => 'Оригинальная ссылка',
            'short_code' => 'Короткий код',
            'qr_code_path' => 'Путь к QR коду',
            'clicks_count' => 'Количество переходов',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Генерирует уникальный короткий код
     * @return string
     */
    public static function generateShortCode()
    {
        do {
            $code = self::generateRandomString(6);
        } while (self::findOne(['short_code' => $code]));

        return $code;
    }

    /**
     * Генерирует случайную строку
     * @param int $length
     * @return string
     */
    private static function generateRandomString($length = 6)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Находит ссылку по короткому коду
     * @param string $code
     * @return ShortLink|null
     */
    public static function findByCode($code)
    {
        return self::findOne(['short_code' => $code]);
    }

    /**
     * Увеличивает счетчик переходов
     */
    public function incrementClicks()
    {
        $this->clicks_count++;
        $this->save(false);
    }

    /**
     * Получает полный URL для короткой ссылки
     * @return string
     */
    public function getShortUrl()
    {
        return Yii::$app->request->hostInfo . '/s/' . $this->short_code;
    }

    /**
     * Получает URL для QR кода
     * @return string
     */
    public function getQrCodeUrl()
    {
        return Yii::$app->request->hostInfo . '/qr/' . $this->short_code;
    }

    /**
     * Связь с логами переходов
     * @return \yii\db\ActiveQuery
     */
    public function getClicks()
    {
        return $this->hasMany(LinkClick::class, ['short_link_id' => 'id']);
    }
} 