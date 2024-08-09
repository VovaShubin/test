<?php

namespace common\models;

use Yii;
use yii\web\UploadedFile;

/**
 * This is the model class for table "books".
 *
 * @property int $id
 * @property string $name
 * @property int $years
 * @property string $description
 * @property string $isbn
 * @property string $img
 * @property array $authors
 */
class Books extends \yii\db\ActiveRecord
{
	public $image;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'books';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['years'], 'integer'],
            [['name', 'description', 'isbn', 'img'], 'string', 'max' => 50],
			[['image'], 'file', 'extensions' => 'png, jpg'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'years' => 'Years',
            'description' => 'Description',
            'isbn' => 'Isbn',
			'authors' => 'Authors',
			'image' => 'Image'
        ];
    }

	public function getAuthors()
	{
		return $this->hasMany(Authors::className(), ['id' => 'author'])
			->viaTable('authors_books', ['books' => 'id']);
	}

	public function upload($id){
		if ($this->validate()){
			mkdir('upload/' . $id,0777);
			$path = 'upload/' . $id . '/' . $this->image->baseName . '.' . $this->image->extension;
			$this->image->saveAs($path);
			@unlink($path);
			return true;
		}
		else{
			return false;
		}
	}

}
