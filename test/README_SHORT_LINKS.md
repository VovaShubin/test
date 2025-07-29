# Подробная документация: Сервис коротких ссылок + QR

## 📋 Содержание

1. [Описание проекта](#описание-проекта)
2. [Функциональность](#функциональность)
3. [Технический стек](#технический-стек)
4. [Структура базы данных](#структура-базы-данных)
5. [Установка](#установка)
6. [Конфигурация](#конфигурация)
7. [Использование](#использование)
8. [API](#api)
9. [Структура проекта](#структура-проекта)
10. [Безопасность](#безопасность)
11. [Производительность](#производительность)
12. [Мониторинг](#мониторинг)
13. [Устранение неполадок](#устранение-неполадок)

## 📖 Описание проекта

Сервис коротких ссылок с QR-кодами - это веб-приложение, которое позволяет пользователям создавать короткие ссылки из длинных URL и автоматически генерирует QR-коды для каждой ссылки. Приложение построено на базе Yii2 Framework и использует современные технологии для обеспечения надежности и производительности.

### Основные возможности:
- Создание коротких ссылок из любых URL
- Автоматическая генерация QR-кодов
- Валидация и проверка доступности URL
- Отслеживание статистики переходов
- Логирование всех действий пользователей
- Современный AJAX интерфейс

## 🚀 Функциональность

### Создание коротких ссылок
- Ввод URL через веб-интерфейс
- Валидация формата URL
- Проверка доступности ресурса через cURL
- Генерация уникального короткого кода
- Сохранение в базе данных
- Создание QR-кода

### Управление ссылками
- Уникальные короткие коды (6 символов)
- Защита от дублирования URL
- Автоматическое обновление счетчика переходов
- Логирование всех переходов

### QR-коды
- Автоматическая генерация для каждой ссылки
- Сохранение в формате PNG
- Прямой доступ через URL
- Оптимизированный размер для мобильных устройств

### Статистика и аналитика
- Счетчик переходов по каждой ссылке
- Логирование IP адресов
- Запись User Agent браузера
- Отслеживание рефереров
- Временные метки всех действий

## 🛠 Технический стек

### Backend
- **PHP 7.4+** - основной язык программирования
- **Yii2 Framework** - веб-фреймворк
- **MySQL 5.7+** - система управления базами данных
- **Composer** - менеджер зависимостей

### Frontend
- **jQuery** - JavaScript библиотека
- **Bootstrap 5** - CSS фреймворк
- **AJAX** - асинхронные запросы
- **Clipboard API** - копирование в буфер обмена

### Дополнительные библиотеки
- **Endroid QR Code** - генерация QR-кодов
- **cURL** - проверка доступности URL

## 🗄 Структура базы данных

### Таблица `short_links`
```sql
CREATE TABLE `short_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `original_url` text NOT NULL COMMENT 'Оригинальная ссылка',
    `short_code` varchar(10) NOT NULL COMMENT 'Короткий код',
    `qr_code_path` varchar(255) DEFAULT NULL COMMENT 'Путь к QR коду',
    `clicks_count` int(11) DEFAULT 0 COMMENT 'Количество переходов',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_short_links_short_code` (`short_code`),
    UNIQUE KEY `idx_short_links_original_url_unique` (`original_url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Таблица `link_clicks`
```sql
CREATE TABLE `link_clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `short_link_id` int(11) NOT NULL COMMENT 'ID короткой ссылки',
    `ip_address` varchar(45) NOT NULL COMMENT 'IP адрес',
    `user_agent` text DEFAULT NULL COMMENT 'User Agent',
    `referer` text DEFAULT NULL COMMENT 'Реферер',
    `clicked_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Время перехода',
    PRIMARY KEY (`id`),
    KEY `idx_link_clicks_short_link_id` (`short_link_id`),
    KEY `idx_link_clicks_ip_address` (`ip_address`),
    KEY `idx_link_clicks_clicked_at` (`clicked_at`),
    CONSTRAINT `fk_link_clicks_short_link_id` FOREIGN KEY (`short_link_id`) REFERENCES `short_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Индексы и оптимизация
- Уникальный индекс на `short_code` для быстрого поиска
- Уникальный индекс на `original_url(255)` для предотвращения дублей
- Индекс на `short_link_id` для быстрого поиска логов
- Индекс на `ip_address` для аналитики
- Индекс на `clicked_at` для временных запросов

## ⚙️ Установка

### Системные требования
- PHP 7.4 или выше
- MySQL 5.7 или выше
- Composer
- Веб-сервер (Apache/Nginx) или PHP встроенный сервер
- Расширения PHP: curl, gd, mbstring, pdo_mysql

### Пошаговая установка

#### 1. Подготовка окружения
```bash
# Проверка версии PHP
php -v

# Проверка расширений
php -m | grep -E "(curl|gd|mbstring|pdo_mysql)"

# Установка Composer (если не установлен)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### 2. Клонирование проекта
```bash
git clone <repository-url>
cd test
```

#### 3. Установка зависимостей
```bash
composer install --no-dev --optimize-autoloader
```

#### 4. Настройка базы данных

**Вариант A: Через SQL скрипт**
```bash
mysql -u root -p < database.sql
```

**Вариант B: Через миграции Yii2**
```bash
php yii migrate
```

#### 5. Настройка конфигурации

**База данных (`config/db.php`)**
```php
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8',
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
```

**Основные настройки (`config/web.php`)**
```php
'request' => [
    'cookieValidationKey' => 'your-secret-key-here',
],
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        's/<code:\w+>' => 'redirect/index',
        'qr/<code:\w+>' => 'qr/index',
    ],
],
```

#### 6. Настройка веб-сервера

**Apache (.htaccess уже настроен)**
```apache
DocumentRoot "/path/to/your/project/web"
<Directory "/path/to/your/project/web">
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx**
```nginx
server {
    charset utf-8;
    client_max_body_size 128M;

    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
}
```

**PHP встроенный сервер (для разработки)**
```bash
php -S localhost:8081 -t web
```

#### 7. Настройка прав доступа
```bash
chmod 755 runtime/
chmod 755 web/assets/
chmod 777 web/qr-codes/
chown -R www-data:www-data runtime/
chown -R www-data:www-data web/assets/
chown -R www-data:www-data web/qr-codes/
```

## 📖 Использование

### Создание короткой ссылки
1. Откройте главную страницу приложения
2. Введите URL в поле ввода
3. Нажмите кнопку "Создать"
4. Получите короткую ссылку и QR-код

### Переход по короткой ссылке
- Используйте формат: `http://your-domain.com/s/ABC123`
- Система автоматически перенаправит на оригинальный URL
- Счетчик переходов увеличится
- Информация о переходе будет записана в лог

### Доступ к QR-коду
- Прямой доступ: `http://your-domain.com/qr/ABC123`
- Сканирование камерой телефона
- Скачивание изображения

## 🔌 API

### Создание короткой ссылки
```http
POST /site/create-short-link
Content-Type: application/x-www-form-urlencoded

url=https://example.com&_csrf=token
```

**Ответ:**
```json
{
    "success": true,
    "shortUrl": "http://your-domain.com/s/ABC123",
    "qrCodeUrl": "http://your-domain.com/qr/ABC123",
    "shortCode": "ABC123"
}
```

### Переход по ссылке
```http
GET /s/{code}
```

**Ответ:** HTTP 302 Redirect на оригинальный URL

### Получение QR-кода
```http
GET /qr/{code}
```

**Ответ:** PNG изображение QR-кода

## 📁 Структура проекта

```
test/
├── config/                          # Конфигурация
│   ├── db.php                      # Настройки БД
│   ├── web.php                     # Основная конфигурация
│   └── params.php                  # Параметры приложения
├── controllers/                     # Контроллеры
│   ├── SiteController.php          # Главный контроллер
│   ├── RedirectController.php      # Обработка переходов
│   └── QrController.php            # Генерация QR-кодов
├── models/                         # Модели
│   ├── ShortLink.php               # Модель коротких ссылок
│   └── LinkClick.php               # Модель логов
├── views/                          # Представления
│   ├── layouts/
│   │   └── main.php                # Основной шаблон
│   └── site/
│       └── index.php               # Главная страница
├── migrations/                     # Миграции БД
│   └── m240728_210000_create_short_link_tables.php
├── web/                           # Веб-корень
│   ├── qr-codes/                  # QR-коды
│   ├── assets/                    # Ресурсы
│   ├── .htaccess                  # Правила Apache
│   └── index.php                  # Точка входа
├── runtime/                       # Временные файлы
├── vendor/                        # Зависимости Composer
├── composer.json                  # Зависимости
├── database.sql                   # SQL скрипт
└── README_ru.md                   # Документация
```

## 🔒 Безопасность

### Защита от атак
- **CSRF защита** - все формы защищены токенами
- **Валидация входных данных** - проверка всех пользовательских данных
- **SQL инъекции** - использование подготовленных запросов
- **XSS защита** - экранирование вывода
- **Уникальные индексы** - предотвращение дублирования данных

### Проверка URL
- Валидация формата URL
- Проверка доступности через cURL
- Таймаут запросов (10 секунд)
- Ограничение размера ответа

### Логирование
- Запись всех переходов
- IP адреса посетителей
- User Agent браузеров
- Временные метки

## ⚡ Производительность

### Оптимизация базы данных
- Индексы на часто используемых полях
- Внешние ключи для целостности данных
- Кэширование схемы БД
- Оптимизированные запросы

### Кэширование
- Кэширование схемы базы данных
- Кэширование QR-кодов в файловой системе
- HTTP кэширование QR-кодов (24 часа)
- Оптимизация автозагрузки Composer

### Оптимизация QR-кодов
- QR-коды сохраняются в файлы при создании ссылки
- При запросе QR-кода сначала проверяется наличие файла
- Fallback генерация на лету если файл отсутствует
- HTTP заголовки кэширования для QR-кодов
- Возможность пересоздания всех QR-кодов через `/qr/regenerate-all`

### Оптимизация фронтенда
- Минификация CSS и JS
- Сжатие изображений
- Кэширование браузера
- AJAX для асинхронных операций


## 🐛 Устранение неполадок

### Частые проблемы

#### Ошибка подключения к БД
```bash
# Проверка статуса MySQL
sudo systemctl status mysql

# Проверка подключения
mysql -u root -p -e "SHOW DATABASES;"

# Проверка настроек в config/db.php
```

#### Ошибка генерации QR-кодов
```bash
# Проверка прав доступа
ls -la web/qr-codes/

# Установка прав
chmod 777 web/qr-codes/

# Проверка расширения GD
php -m | grep gd
```

#### Ошибка "Method Not Allowed"
```bash
# Проверка mod_rewrite
apache2ctl -M | grep rewrite

# Включение mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Ошибка 500
```bash
# Проверка логов
tail -f runtime/logs/app.log

# Проверка прав доступа
chmod 755 runtime/
chmod 755 web/assets/
```

### Отладка

#### Включение режима отладки
```php
// config/web.php
'components' => [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'warning'],
            ],
        ],
    ],
],
```

#### Проверка конфигурации
```bash
php yii help
php yii migrate/history
```

## 📝 Лицензия

Проект создан для демонстрационных целей.

---

**Версия документации**: 1.0  
**Дата обновления**: 28.07.2024  
**Автор**: Shubin VA


