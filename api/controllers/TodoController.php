<?php
namespace api\controllers;

use common\models\TodoList;
use yii\rest\ActiveController;
use yii\filters\auth\QueryParamAuth;

class TodoController extends ActiveController
{
	public $modelClass = 'common\models\TodoList';

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
		return TodoList::findAll(['userid' => $userid]);
	}
}
