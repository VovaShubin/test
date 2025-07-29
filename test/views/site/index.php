<?php

/** @var yii\web\View $this */

$this->title = 'Сервис коротких ссылок';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-transparent mt-5 mb-5">
        <h1 class="display-4">Сервис коротких ссылок</h1>
        <p class="lead">Создавайте короткие ссылки и QR-коды для ваших URL</p>
    </div>

    <div class="body-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Создать короткую ссылку</h3>
                    </div>
                    <div class="card-body">
                        <form id="shortLinkForm">
                            <div class="input-group mb-3">
                                <input type="url" class="form-control" id="urlInput" 
                                       placeholder="Введите URL (например: https://www.google.com)" 
                                       required>
                                <button class="btn btn-primary" type="submit" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    Создать
                                </button>
                            </div>
                        </form>

                        <div id="result" class="mt-4" style="display: none;">
                            <div class="alert alert-success">
                                <h5>Короткая ссылка создана!</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Короткая ссылка:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="shortUrl" readonly>
                                                <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#shortUrl">
                                                    Копировать
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">QR-код:</label>
                                            <div class="text-center">
                                                <img id="qrCode" class="img-fluid" style="max-width: 200px;" alt="QR Code">
                                                <br>
                                                <small class="text-muted">Наведите камеру телефона для сканирования</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="error" class="mt-4" style="display: none;">
                            <div class="alert alert-danger">
                                <h5>Ошибка!</h5>
                                <p id="errorMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Как это работает</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <i class="fas fa-link fa-3x text-primary"></i>
                                </div>
                                <h5>1. Введите URL</h5>
                                <p>Вставьте любую ссылку в поле выше</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <i class="fas fa-check-circle fa-3x text-success"></i>
                                </div>
                                <h5>2. Проверка</h5>
                                <p>Система проверит валидность и доступность ссылки</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <i class="fas fa-qrcode fa-3x text-info"></i>
                                </div>
                                <h5>3. Получите результат</h5>
                                <p>Короткая ссылка и QR-код готовы к использованию</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$createUrl = \yii\helpers\Url::to(['site/create-short-link']);
$csrfToken = Yii::$app->request->csrfToken;

$js = <<<JS
// Глобальные функции для копирования
function copyToClipboard(elementId) {
    var element = document.getElementById(elementId);
    
    if (!element) {
        return;
    }
    
    var text = element.value;
    
    // Используем современный Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess(elementId);
        }).catch(function(err) {
            fallbackCopyTextToClipboard(text, elementId);
        });
    } else {
        fallbackCopyTextToClipboard(text, elementId);
    }
}

function fallbackCopyTextToClipboard(text, elementId) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(elementId);
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(elementId) {
    // Находим кнопку рядом с полем ввода
    var element = document.getElementById(elementId);
    var button = element.parentNode.querySelector('button');
    
    if (button) {
        var originalText = button.textContent;
        button.textContent = 'Скопировано!';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }
}

function showResult(data) {
    $('#shortUrl').val(data.shortUrl);
    $('#qrCode').attr('src', data.qrCodeUrl);
    $('#result').show();
    $('#error').hide();
}

function showError(message) {
    $('#errorMessage').text(message);
    $('#error').show();
    $('#result').hide();
}

$(document).ready(function() {
    // Обработчик для кнопки копирования
    $(document).on('click', '.copy-btn', function() {
        var targetId = $(this).data('clipboard-target').replace('#', '');
        copyToClipboard(targetId);
    });
    
    $('#shortLinkForm').on('submit', function(e) {
        e.preventDefault();
        
        var url = $('#urlInput').val();
        if (!url) {
            showError('Пожалуйста, введите URL');
            return;
        }
        
        // Показываем спиннер
        $('#submitBtn .spinner-border').removeClass('d-none');
        $('#submitBtn').prop('disabled', true);
        
        // Скрываем предыдущие результаты
        $('#result, #error').hide();
        
        $.ajax({
            url: '$createUrl',
            type: 'POST',
            data: {
                url: url,
                _csrf: '$csrfToken'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showResult(response);
                } else {
                    showError(response.message);
                }
            },
            error: function() {
                showError('Произошла ошибка при обработке запроса');
            },
            complete: function() {
                // Скрываем спиннер
                $('#submitBtn .spinner-border').addClass('d-none');
                $('#submitBtn').prop('disabled', false);
            }
        });
    });
});
JS;

$this->registerJs($js);
?>
