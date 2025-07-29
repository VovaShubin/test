<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\ShortLink;
use yii\web\NotFoundHttpException;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'create-short-link' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Создает короткую ссылку через AJAX
     * @return array
     */
    public function actionCreateShortLink()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $url = Yii::$app->request->post('url');
        
        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'URL не может быть пустым'
            ];
        }

        // Валидация URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Некорректный URL'
            ];
        }

        // Проверка доступности URL
        if (!$this->isUrlAccessible($url)) {
            return [
                'success' => false,
                'message' => 'Данный URL не доступен'
            ];
        }

        // Проверяем, существует ли уже короткая ссылка для этого URL
        $existingShortLink = ShortLink::findOne(['original_url' => $url]);
        if ($existingShortLink) {
            return [
                'success' => true,
                'shortUrl' => $existingShortLink->getShortUrl(),
                'qrCodeUrl' => $existingShortLink->getQrCodeUrl(),
                'shortCode' => $existingShortLink->short_code,
                'message' => 'Короткая ссылка для этого URL уже существует'
            ];
        }

        // Создание короткой ссылки
        $shortLink = new ShortLink();
        $shortLink->original_url = $url;
        $shortLink->short_code = ShortLink::generateShortCode();

        if ($shortLink->save()) {
            // Генерация QR кода
            $qrCodePath = $this->generateQrCode($shortLink);
            $shortLink->qr_code_path = $qrCodePath;
            $shortLink->save(false);

            return [
                'success' => true,
                'shortUrl' => $shortLink->getShortUrl(),
                'qrCodeUrl' => $shortLink->getQrCodeUrl(),
                'shortCode' => $shortLink->short_code
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при создании короткой ссылки'
        ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Переход по короткой ссылке
     * @param string $code
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRedirect($code)
    {
        $shortLink = ShortLink::findByCode($code);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Ссылка не найдена');
        }

        // Увеличиваем счетчик переходов
        $shortLink->incrementClicks();
        
        // Создаем запись о переходе
        \app\models\LinkClick::createClick($shortLink->id);

        return $this->redirect($shortLink->original_url);
    }

    /**
     * Проверяет доступность URL
     * @param string $url
     * @return bool
     */
    private function isUrlAccessible($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result && $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * Генерирует QR код и сохраняет его
     * @param ShortLink $shortLink
     * @return string
     */
    private function generateQrCode($shortLink)
    {
        $qrCode = new \Endroid\QrCode\QrCode($shortLink->getShortUrl());
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        $qrDir = Yii::getAlias('@webroot/qr-codes');
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        $filename = $shortLink->short_code . '.png';
        $filepath = $qrDir . '/' . $filename;
        
        file_put_contents($filepath, $result->getString());
        
        return $filename; // Возвращаем имя файла для сохранения в БД
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
