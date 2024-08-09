<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $form yii\widgets\ActiveForm */
/* @var $this yii\web\View */

$this->title = 'Top 10';
$this->params['breadcrumbs'][] = ['label' => 'Books', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="Top10-view">

    <h1><?= Html::encode($this->title) ?></h1>

	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'columns' => [
			['class' => 'yii\grid\SerialColumn'],
			'author',
			'count',
		],
	]); ?>
</div>
