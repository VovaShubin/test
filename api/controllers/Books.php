<?php
namespace api\controllers;

use common\models\Book;
use yii\rest\ActiveController;
use yii\filters\auth\QueryParamAuth;

class Books extends ActiveController
{
	public $modelClass = 'common\models\Book';

	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['authenticator']['class'] = QueryParamAuth::className();
		$behaviors['authenticator']['tokenParam'] = 'access_token';

		$behaviors['contentNegotiator'] = [
			'class' => '\yii\filters\ContentNegotiator',
			'formatParam' => '_format',
			'formats' => [
				'application/json' => \yii\web\Response::FORMAT_JSON,
			],
		];
		return $behaviors;
	}
	public function actionItem($userid)
	{
		return Book::findAll(['userid' => $userid]);
	}
}
