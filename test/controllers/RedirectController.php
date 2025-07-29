<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\ShortLink;

/**
 * Контроллер для обработки переходов по коротким ссылкам
 */
class RedirectController extends Controller
{
    /**
     * Переход по короткой ссылке
     * @param string $code
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionIndex($code)
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
} 