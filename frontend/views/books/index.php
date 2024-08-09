<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\BooksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Books';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="books-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Books', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'name',
            'years',
            'description',
            'isbn',
			[
				'label' => 'authors',
				'value' => function($model) {
                    return implode(' | ', \yii\helpers\ArrayHelper::map($model->authors, 'id', 'author'));
				},
			],
			[
				'value' => function ($model) {
					return Html::img('http://yii2.loc/frontend/upload/'.$model->id . '/' . $model->img, ['width' => 100, 'alt' => $model->name]);
				},
				'label' => 'Image',
				'format' => 'raw'
			],
            //'photo',

            ['class' => 'yii\grid\ActionColumn',
	            'template' => '{view} {update} {delete} {follow}',
                'buttons' => [
                'follow' => function($url, $model, $key) {
                    return Html::a('follow', $url);
                }
            ]
            ],
        ],
    ]); ?>
</div>
