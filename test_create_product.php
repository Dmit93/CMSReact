<?php
// Устанавливаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Заголовок для вывода текста
header('Content-Type: text/html; charset=utf-8');

// Функция для отправки cURL запроса
function sendRequest($url, $method = 'GET', $data = null, $headers = []) {
    $curl = curl_init();
    
    // Базовые опции для всех запросов
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    // Для POST/PUT запросов добавляем данные
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    
    // Включаем получение заголовков ответа
    curl_setopt($curl, CURLOPT_HEADER, true);
    
    // Выполняем запрос
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Проверка на ошибки
    $error = curl_error($curl);
    
    curl_close($curl);
    
    return [
        'status' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'error' => $error
    ];
}

// Функция для прямого добавления товара через модель (обходя API)
function createProductDirect() {
    try {
        // Подключаем необходимые файлы
        require_once __DIR__ . '/backend/api/Database.php';
        require_once __DIR__ . '/backend/modules/Shop/models/ProductModel.php';
        
        // Создаём модель товара
        $productModel = new \Modules\Shop\Models\ProductModel();
        
        // Тестовые данные товара
        $data = [
            'title' => 'Тестовый товар ' . time(),
            'price' => 1000,
            'stock' => 50,
            'status' => 'published',
            'slug' => 'test-product-' . time(),
            'sku' => 'TEST' . rand(1000, 9999),
            'category_id' => 1,
            'description' => 'Описание тестового товара',
            'author_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Создаём товар
        $result = $productModel->create($data);
        
        return [
            'success' => true,
            'result' => $result,
            'data' => $data
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Функция для проверки структуры таблицы products
function checkProductsTable() {
    try {
        require_once __DIR__ . '/backend/api/Database.php';
        
        $db = \API\Database::getInstance();
        
        // Получаем структуру таблицы
        $structure = $db->fetchAll("SHOW COLUMNS FROM products");
        
        // Получаем CREATE TABLE запрос
        $tableInfo = $db->fetch("SHOW CREATE TABLE products");
        
        // Проверяем первые 10 записей
        $records = $db->fetchAll("SELECT * FROM products LIMIT 10");
        
        return [
            'success' => true,
            'structure' => $structure,
            'table_info' => $tableInfo,
            'sample_records' => $records
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Выводим HTML страницу с формой и результатами тестов
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тест добавления товаров Shop</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        .tabs { display: flex; margin-bottom: 15px; }
        .tab { padding: 10px 15px; cursor: pointer; border: 1px solid #ddd; }
        .tab.active { background: #f5f5f5; border-bottom: none; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Тест добавления товаров в модуле Shop</h1>';

// Выполняем проверку структуры таблицы
echo '<div class="section">
    <h2>Проверка структуры таблицы products</h2>';

$tableCheck = checkProductsTable();
if ($tableCheck['success']) {
    echo '<p class="success">Структура таблицы получена успешно</p>';
    
    echo '<h3>Структура таблицы:</h3>';
    echo '<pre>' . print_r($tableCheck['structure'], true) . '</pre>';
    
    echo '<h3>CREATE TABLE:</h3>';
    echo '<pre>' . print_r($tableCheck['table_info'], true) . '</pre>';
    
    echo '<h3>Примеры записей:</h3>';
    echo '<pre>' . print_r($tableCheck['sample_records'], true) . '</pre>';
} else {
    echo '<p class="error">Ошибка при проверке структуры таблицы:</p>';
    echo '<pre>' . $tableCheck['error'] . '</pre>';
    echo '<p>Трассировка:</p>';
    echo '<pre>' . $tableCheck['trace'] . '</pre>';
}

echo '</div>';

// Тест 1: Прямое добавление через модель
echo '<div class="section">
    <h2>Тест 1: Прямое добавление товара (через модель)</h2>';

$directResult = createProductDirect();
if ($directResult['success']) {
    echo '<p class="success">Товар успешно добавлен напрямую через модель</p>';
    echo '<pre>' . print_r($directResult['result'], true) . '</pre>';
    echo '<p>Использованные данные:</p>';
    echo '<pre>' . print_r($directResult['data'], true) . '</pre>';
} else {
    echo '<p class="error">Ошибка при добавлении товара напрямую:</p>';
    echo '<pre>' . $directResult['error'] . '</pre>';
    echo '<p>Трассировка:</p>';
    echo '<pre>' . $directResult['trace'] . '</pre>';
}

echo '</div>';

// Тест 2: Добавление через API
echo '<div class="section">
    <h2>Тест 2: Добавление товара через API</h2>';

// Генерируем данные для нового товара
$productData = [
    'title' => 'API Тестовый товар ' . time(),
    'price' => 1500,
    'stock' => 25,
    'status' => 'published',
    'slug' => 'api-test-product-' . time(),
    'sku' => 'API' . rand(1000, 9999),
    'category_id' => 1,
    'description' => 'Описание API тестового товара',
    'author_id' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Отправляем запрос к API
$apiResponse = sendRequest(
    'http://localhost/cms/backend/api/shop/products',
    'POST',
    json_encode($productData),
    [
        'Content-Type: application/json',
        'Accept: application/json'
    ]
);

echo '<h3>Запрос:</h3>';
echo '<pre>POST http://localhost/cms/backend/api/shop/products</pre>';
echo '<pre>' . json_encode($productData, JSON_PRETTY_PRINT) . '</pre>';

echo '<h3>Ответ:</h3>';
echo '<p>Статус код: ' . $apiResponse['status'] . '</p>';
echo '<h4>Заголовки:</h4>';
echo '<pre>' . $apiResponse['headers'] . '</pre>';
echo '<h4>Тело ответа:</h4>';
echo '<pre>' . htmlspecialchars($apiResponse['body']) . '</pre>';

if ($apiResponse['error']) {
    echo '<p class="error">Ошибка cURL: ' . $apiResponse['error'] . '</p>';
}

echo '</div>';

// Тест 3: Форма для ручного добавления товара
echo '<div class="section">
    <h2>Тест 3: Ручное добавление товара</h2>
    <form id="productForm">
        <div class="form-group">
            <label for="title">Название товара:</label>
            <input type="text" id="title" name="title" value="Ручной тестовый товар ' . time() . '" required>
        </div>
        <div class="form-group">
            <label for="price">Цена:</label>
            <input type="number" id="price" name="price" value="2000" required>
        </div>
        <div class="form-group">
            <label for="stock">Количество на складе:</label>
            <input type="number" id="stock" name="stock" value="10" required>
        </div>
        <div class="form-group">
            <label for="sku">Артикул:</label>
            <input type="text" id="sku" name="sku" value="MAN' . rand(1000, 9999) . '">
        </div>
        <div class="form-group">
            <label for="slug">Slug:</label>
            <input type="text" id="slug" name="slug" value="manual-test-product-' . time() . '">
        </div>
        <div class="form-group">
            <label for="category_id">ID категории:</label>
            <input type="number" id="category_id" name="category_id" value="1">
        </div>
        <div class="form-group">
            <label for="status">Статус:</label>
            <select id="status" name="status">
                <option value="published" selected>Опубликован</option>
                <option value="draft">Черновик</option>
                <option value="hidden">Скрыт</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Описание:</label>
            <textarea id="description" name="description">Описание ручного тестового товара</textarea>
        </div>
        <button type="button" id="submitButton">Добавить товар</button>
    </form>
    <div id="result" style="margin-top: 20px;"></div>

    <script>
        document.getElementById("submitButton").addEventListener("click", function() {
            const form = document.getElementById("productForm");
            const resultDiv = document.getElementById("result");
            
            // Собираем данные формы
            const formData = {
                title: document.getElementById("title").value,
                price: parseFloat(document.getElementById("price").value),
                stock: parseInt(document.getElementById("stock").value),
                sku: document.getElementById("sku").value,
                slug: document.getElementById("slug").value,
                category_id: parseInt(document.getElementById("category_id").value),
                status: document.getElementById("status").value,
                description: document.getElementById("description").value,
                author_id: 1,
                created_at: new Date().toISOString().slice(0, 19).replace("T", " "),
                updated_at: new Date().toISOString().slice(0, 19).replace("T", " ")
            };
            
            // Отображаем информацию о запросе
            resultDiv.innerHTML = `<h3>Отправка запроса...</h3>
                <pre>POST /cms/backend/api/shop/products</pre>
                <pre>${JSON.stringify(formData, null, 2)}</pre>`;
            
            // Отправляем запрос
            fetch("/cms/backend/api/shop/products", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                // Получаем статус и заголовки ответа
                const status = response.status;
                const headers = {};
                response.headers.forEach((value, key) => {
                    headers[key] = value;
                });
                
                // Возвращаем и статус/заголовки, и тело ответа
                return response.text().then(text => {
                    return {
                        status,
                        headers,
                        body: text
                    };
                });
            })
            .then(data => {
                // Форматируем заголовки для отображения
                const headersList = Object.entries(data.headers)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join("\\n");
                
                // Проверяем, является ли тело ответа JSON
                let bodyDisplay = data.body;
                try {
                    const jsonBody = JSON.parse(data.body);
                    bodyDisplay = JSON.stringify(jsonBody, null, 2);
                } catch (e) {
                    // Если не JSON, оставляем как есть
                }
                
                // Отображаем результат
                resultDiv.innerHTML = `
                    <h3>Ответ:</h3>
                    <p>Статус код: ${data.status}</p>
                    <h4>Заголовки:</h4>
                    <pre>${headersList}</pre>
                    <h4>Тело ответа:</h4>
                    <pre>${bodyDisplay}</pre>
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <h3>Ошибка:</h3>
                    <pre class="error">${error.message}</pre>
                `;
            });
        });
    </script>
</div>';

// Тест 4: Диагностика ошибок PHP
echo '<div class="section">
    <h2>Тест 4: Диагностика ошибок PHP</h2>';

echo '<p>Проверка PHP error log:</p>';
$errorLog = '';
$logFile = ini_get('error_log');

if (file_exists($logFile)) {
    // Получаем последние 50 строк лога ошибок
    $logContent = file($logFile);
    if (count($logContent) > 50) {
        $logContent = array_slice($logContent, -50);
    }
    $errorLog = implode('', $logContent);
    
    echo '<pre>' . htmlspecialchars($errorLog) . '</pre>';
} else {
    echo '<p>Файл лога ошибок не найден: ' . htmlspecialchars($logFile) . '</p>';
    
    // Проверяем лог Apache
    $apacheLogPaths = [
        'D:/xampp-8/apache/logs/error.log',
        'D:/xampp/apache/logs/error.log',
        '/var/log/apache2/error.log'
    ];
    
    foreach ($apacheLogPaths as $path) {
        if (file_exists($path)) {
            echo '<p>Найден лог Apache: ' . htmlspecialchars($path) . '</p>';
            $apacheLog = file($path);
            if (count($apacheLog) > 50) {
                $apacheLog = array_slice($apacheLog, -50);
            }
            echo '<pre>' . htmlspecialchars(implode('', $apacheLog)) . '</pre>';
            break;
        }
    }
}

echo '</div>';

// Завершаем HTML страницу
echo '</div>
</body>
</html>';
?> 