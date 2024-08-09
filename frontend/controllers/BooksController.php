<?php

namespace frontend\controllers;

use common\models\AuthorsBooks;
use common\models\Follower;
use Yii;
use common\models\Books;
use common\models\Authors;
use frontend\models\BooksSearch;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * BooksController implements the CRUD actions for Books model.
 */
class BooksController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
			'access' => [
				'class' => AccessControl::class,
				'only' => ['сreate', 'update', 'delete', 'follow'],
				'rules' => [
					[
						'allow' => true,
						'actions' => ['сreate', 'update', 'delete'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['follow'],
						'roles' => ['?'],
					],
				],
			],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Books models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BooksSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays a single Books model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Books model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Books();
		$post = Yii::$app->request->post();

        if (!empty($post)) {
			$result = array_pop($post["Books"]);
			$model->image = UploadedFile::getInstance($model, 'image');
			$post["Books"]["img"]=$model->image->name;

			if ($model->load($post) && $model->save()) {
				if ($model->image){
					$model->upload($model->id);
				}
				if (!empty($result)){
					foreach ($result as $val):
						$model2 = new AuthorsBooks();
						$model2->author = $val;
						$model2->books = $model->id;
						$model2->save();
						unset($model2);

						$followers = Follower::findAll(['author' => $val]);
						foreach ($followers as $f){
							$phone[] = $f['phone'];
						}

					endforeach;
					//print_r($phone);
					// отправка уведомлений на телефоны из массива $phone
				}

				return $this->redirect(['view', 'id' => $model->id]);
			}
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Books model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

		$post = Yii::$app->request->post();

		if (!empty($post)) {
			$result = array_pop($post["Books"]);
			if ($model->load($post) && $model->save()) {
				AuthorsBooks::deleteAll(['books' => $id]);
				foreach ($result as $val):
					$model2 = new AuthorsBooks();
					$model2->author = $val;
					$model2->books = $model->id;
					$model2->save();
					unset($model2);
				endforeach;

				return $this->redirect(['view', 'id' => $model->id]);
			}
		}

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Books model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
		AuthorsBooks::deleteAll(['books' => $id]);

        return $this->redirect(['index']);
    }

	public function actionFollow($id)
	{

		$model = new Follower();

		$post = Yii::$app->request->post();

		if (!empty($post)) {
			$post['Follower']['author']=AuthorsBooks::findOne(['books' => $id])->author;
			if ($model->load($post) && $model->save()) {
				return $this->redirect(['index']);
			}
		}

		return $this->render('follow', [
			'model' => $model,
		]);
	}

	/**
	 * Displays top10 page.
	 *
	 * @return mixed
	 */
	public function actionTop10($year=2024)
	{
		$years = Yii::$app->request->get('years');

		$query = (new \yii\db\Query())
			->select(['authors.author', 'COUNT(*) as count'])
			->from('authors_books')
			->leftJoin('books', 'authors_books.books = books.id')
			->leftJoin('authors', 'authors.id = authors_books.author')
			->where(['books.years' => $years])
			->groupBy(['authors_books.author'])
			->having(['>','COUNT(*)', 0])
			->limit(10)
			;

		// add conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);

		return $this->render('top10', [
			'dataProvider' => $dataProvider,
		]);
	}


	/**
     * Finds the Books model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Books the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Books::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
