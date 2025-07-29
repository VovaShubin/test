<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\ShortLink;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Контроллер для генерации QR кодов
 */
class QrController extends Controller
{
    /**
     * Генерирует QR код для короткой ссылки
     * @param string $code
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex($code)
    {
        $shortLink = ShortLink::findByCode($code);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Ссылка не найдена');
        }

        $filepath = Yii::getAlias('@webroot/qr-codes/' . $shortLink->short_code . '.png');
        
        if (file_exists($filepath)) {
            // Используем сохраненный файл для лучшей производительности
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'image/png');
            Yii::$app->response->headers->set('Cache-Control', 'public, max-age=86400'); // Кэшируем на 24 часа
            return file_get_contents($filepath);
        } else {
            // Fallback: генерируем QR-код на лету если файл не найден
            return $this->generateAndServeQrCode($shortLink);
        }
    }

    /**
     * Генерирует QR-код и отдает его в браузер
     * @param ShortLink $shortLink
     * @return string
     */
    private function generateAndServeQrCode($shortLink)
    {
        $qrCode = new QrCode($shortLink->getShortUrl());
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'image/png');
        return $result->getString();
    }

    /**
     * Пересоздает все отсутствующие QR-коды
     * @return string
     */
    public function actionRegenerateAll()
    {
        $shortLinks = ShortLink::find()->all();
        $regenerated = 0;
        
        foreach ($shortLinks as $shortLink) {
            $filepath = Yii::getAlias('@webroot/qr-codes/' . $shortLink->short_code . '.png');
            
            if (!file_exists($filepath)) {
                $this->generateQrCodeFile($shortLink);
                $regenerated++;
            }
        }
        
        return "Пересоздано QR-кодов: $regenerated";
    }

    /**
     * Генерирует QR-код и сохраняет в файл
     * @param ShortLink $shortLink
     * @return bool
     */
    private function generateQrCodeFile($shortLink)
    {
        $qrCode = new QrCode($shortLink->getShortUrl());
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $qrDir = Yii::getAlias('@webroot/qr-codes');
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        $filename = $shortLink->short_code . '.png';
        $filepath = $qrDir . '/' . $filename;
        
        return file_put_contents($filepath, $result->getString()) !== false;
    }
} 