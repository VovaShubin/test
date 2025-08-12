<?php
/**
 * Скрипт для нормализации данных сотрудников в Битрикс24 
 */

// Проверка аргументов командной строки
$is_web_mode = !isset($argv[1]);
$webhook_url = '';

if (!$is_web_mode) {
    // Парсинг аргументов командной строки
    foreach ($argv as $arg) {
        if (strpos($arg, '--webhook=') === 0) {
            $webhook_url = substr($arg, 10);
            break;
        }
    }
    
    if (empty($webhook_url)) {
        echo "Использование: php normalize_users.php --webhook=URL\n";
        echo "Пример: php normalize_users.php --webhook=https://domain.bitrix24.ru/rest/1/key/\n";
        exit(1);
    }
}

// Класс для нормализации пользователей
class Bitrix24UserNormalizer {
    private $webhook_url;
    private $ch;
    
    public function __construct($webhook_url) {
        $this->webhook_url = rtrim($webhook_url, '/');
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Bitrix24-User-Normalizer/1.0'
        ]);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
    }
    
    public function __destruct() {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
    
    /**
     * Получение списка всех пользователей
     */
    public function getUsers() {
        $params = [
            'ACTIVE' => true,
            'FIELDS' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'EMAIL']
        ];
        
        $response = $this->callMethod('user.get', $params);
        return isset($response['result']) ? $response['result'] : [];
    }
    
    /**
     * Обновление данных пользователя
     */
    public function updateUser($userId, $fields) {
        $params = [
            'ID' => $userId,
            'FIELDS' => $fields
        ];
        
        $response = $this->callMethod('user.update', $params);
        return isset($response['result']) ? $response['result'] : false;
    }
    
    /**
     * Вызов метода API Битрикс24
     */
    private function callMethod($method, $params) {
        $url = $this->webhook_url . '/' . $method;
        
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        
        $response = curl_exec($this->ch);
        $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($this->ch);
            throw new Exception("CURL ошибка: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP ошибка: " . $httpCode . " - " . $response);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка JSON: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Нормализация имени и отчества
     */
    public function normalizeName($name) {
        if (empty($name) || !is_string($name)) {
            return ['', ''];
        }
        
        // Убираем лишние пробелы
        $name = trim($name);
        
        // Разделяем по пробелам
        $parts = preg_split('/\s+/', $name);
        
        if (count($parts) === 1) {
            // Только имя
            return [$parts[0], ''];
        } elseif (count($parts) === 2) {
            // Имя и отчество
            return [$parts[0], $parts[1]];
        } else {
            // Больше двух частей - берем первую как имя, остальное как отчество
            return [$parts[0], implode(' ', array_slice($parts, 1))];
        }
    }
    
    /**
     * Проверка, нормализованы ли данные пользователя
     */
    public function isNormalized($user) {
        $name = $user['NAME'] ?? '';
        $secondName = $user['SECOND_NAME'] ?? '';
        
        // Если в SECOND_NAME уже есть данные, считаем нормализованным
        if (!empty($secondName)) {
            return true;
        }
        
        // Если в NAME нет пробелов, считаем нормализованным
        if (empty($name) || strpos($name, ' ') === false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Нормализация всех пользователей
     */
    public function normalizeAllUsers() {
        $output = [];
        $output[] = "Получение списка пользователей...";
        
        try {
            $users = $this->getUsers();
            
            if (empty($users)) {
                $output[] = "Пользователи не найдены";
                return [
                    'total' => 0,
                    'normalized' => 0,
                    'skipped' => 0,
                    'errors' => 0,
                    'output' => $output
                ];
            }
            
            $output[] = "Найдено пользователей: " . count($users);
            
            $stats = [
                'total' => count($users),
                'normalized' => 0,
                'skipped' => 0,
                'errors' => 0
            ];
            
            foreach ($users as $user) {
                $userId = $user['ID'];
                $name = $user['NAME'] ?? '';
                
                $output[] = "";
                $output[] = "Обработка пользователя ID={$userId}: {$name}";
                
                if ($this->isNormalized($user)) {
                    $output[] = "  - Пропущен (уже нормализован)";
                    $stats['skipped']++;
                    continue;
                }
                
                try {
                    // Нормализуем имя
                    list($normalizedName, $patronymic) = $this->normalizeName($name);
                    
                    if (empty($normalizedName)) {
                        $output[] = "  - Ошибка: не удалось извлечь имя";
                        $stats['errors']++;
                        continue;
                    }
                    
                    // Подготавливаем поля для обновления
                    $updateFields = [
                        'NAME' => $normalizedName
                    ];
                    
                    if (!empty($patronymic)) {
                        $updateFields['SECOND_NAME'] = $patronymic;
                    }
                    
                    // Обновляем пользователя
                    $success = $this->updateUser($userId, $updateFields);
                    
                    if ($success) {
                        $output[] = "  - Успешно нормализован:";
                        $output[] = "    Имя: '{$normalizedName}'";
                        if (!empty($patronymic)) {
                            $output[] = "    Отчество: '{$patronymic}'";
                        }
                        $stats['normalized']++;
                    } else {
                        $output[] = "  - Ошибка обновления";
                        $stats['errors']++;
                    }
                    
                } catch (Exception $e) {
                    $output[] = "  - Ошибка: " . $e->getMessage();
                    $stats['errors']++;
                }
            }
            
            $stats['output'] = $output;
            return $stats;
            
        } catch (Exception $e) {
            $output[] = "Критическая ошибка: " . $e->getMessage();
            return [
                'total' => 0,
                'normalized' => 0,
                'skipped' => 0,
                'errors' => 1,
                'output' => $output
            ];
        }
    }
    
    /**
     * Вывод статистики нормализации
     */
    public function printStats($stats) {
        $output = $stats['output'];
        $output[] = "";
        $output[] = str_repeat("=", 50);
        $output[] = "СТАТИСТИКА НОРМАЛИЗАЦИИ";
        $output[] = str_repeat("=", 50);
        $output[] = "Всего пользователей: " . $stats['total'];
        $output[] = "Нормализовано: " . $stats['normalized'];
        $output[] = "Пропущено: " . $stats['skipped'];
        $output[] = "Ошибок: " . $stats['errors'];
        $output[] = str_repeat("=", 50);
        
        return $output;
    }
}

// Веб-интерфейс
if ($is_web_mode) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Нормализатор данных сотрудников Битрикс24</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; text-align: center; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
            button { background: #3498db; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
            button:hover { background: #2980b9; }
            .results { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; white-space: pre-wrap; font-family: monospace; }
            .error { color: #e74c3c; }
            .success { color: #27ae60; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Нормализатор данных сотрудников Битрикс24</h1>
            
            <form method="post">
                <div class="form-group">
                    <label for="webhook_url">URL вебхука Битрикс24:</label>
                    <input type="text" id="webhook_url" name="webhook_url" 
                           placeholder="https://your-domain.bitrix24.ru/rest/1/webhook_key/" 
                           value="<?= htmlspecialchars($_POST['webhook_url'] ?? '') ?>" required>
                </div>
                
                <button type="submit">🚀 Запустить нормализацию</button>
            </form>
            
            <?php
            if ($_POST && !empty($_POST['webhook_url'])) {
                try {
                    $webhook_url = $_POST['webhook_url'];
                    $normalizer = new Bitrix24UserNormalizer($webhook_url);
                    
                    echo "<div class='results'>";
                    echo "<h3>Результаты нормализации:</h3>";
                    
                    $stats = $normalizer->normalizeAllUsers();
                    $output = $normalizer->printStats($stats);
                    
                    foreach ($output as $line) {
                        echo htmlspecialchars($line) . "\n";
                    }
                    
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='results error'>";
                    echo "Ошибка: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
            }
            ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Консольный режим
echo "Нормализатор данных сотрудников Битрикс24\n";
echo str_repeat("=", 50) . "\n";

try {
    $normalizer = new Bitrix24UserNormalizer($webhook_url);
    
    echo "Будет выполнена нормализация данных сотрудников.\n";
    echo "URL: {$webhook_url}\n\n";
    
    echo "Продолжить? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $confirm = trim(fgets($handle));
    fclose($handle);
    
    if (!in_array(strtolower($confirm), ['y', 'yes', 'да'])) {
        echo "Операция отменена\n";
        exit(0);
    }
    
    // Выполнение нормализации
    $stats = $normalizer->normalizeAllUsers();
    
    // Вывод результатов
    foreach ($stats['output'] as $line) {
        echo $line . "\n";
    }
    
    // Вывод статистики
    $finalOutput = $normalizer->printStats($stats);
    foreach ($finalOutput as $line) {
        echo $line . "\n";
    }
    
} catch (Exception $e) {
    echo "Критическая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?>
