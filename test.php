<?php
class OCRSpaceSimple {
    private $apiKey;
    private $baseUrl = 'https://api.ocr.space/parse/imageurl';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function recognizeFromUrl($imageUrl) {
        // Кодируем URL для безопасной передачи в GET-параметре
        $encodedUrl = urlencode($imageUrl);
        
        // Формируем полный URL запроса
        $apiUrl = $this->baseUrl . '?' . http_build_query([
            'apikey' => $this->apiKey,
            'language' => 'rus',
            'url' => $imageUrl
        ]);
        
        return $this->makeGetRequest($apiUrl);
    }
    
    private function makeGetRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            return [
                'error' => true,
                'httpCode' => $httpCode,
                'message' => $error,
                'response' => $response
            ];
        }
    }
}

// Конфигурация
$apiKey = 'K81126633088957';
$githubBaseUrl = 'https://github.com/ffg53/library/blob/main/photos/';
$localPhotosDir = 'photos/';

$ocr = new OCRSpaceSimple($apiKey);

// Получаем список файлов в папке
$files = scandir($localPhotosDir);
$imageFiles = array_filter($files, function($file) {
    return $file !== '.' && $file !== '..' && 
           in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
});

echo "Найдено изображений: " . count($imageFiles) . "\n\n";

// Обрабатываем каждое изображение
foreach ($imageFiles as $filename) {
    echo "=== Обрабатывается: $filename ===\n";
    
    // Формируем прямую ссылку на GitHub с raw=true
    $imageUrl = $githubBaseUrl . $filename . '?raw=true';
    
    // Альтернативный вариант через raw.githubusercontent.com (может работать быстрее)
    // $imageUrl = "https://raw.githubusercontent.com/ffg53/library/main/photos/" . $filename;
    
    echo "URL: $imageUrl\n";
    
    $result = $ocr->recognizeFromUrl($imageUrl);
    
    if (isset($result['error'])) {
        echo "❌ Ошибка: " . $result['message'] . " (HTTP: " . $result['httpCode'] . ")\n";
    } else {
        if (isset($result['ParsedResults'][0]['ParsedText'])) {
            $text = trim($result['ParsedResults'][0]['ParsedText']);
            if (!empty($text)) {
                echo "✅ Распознанный текст:\n";
                echo $text . "\n";
                
                // Сохраняем результат в файл
                $outputFilename = 'results/' . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
                file_put_contents($outputFilename, $text);
                echo "💾 Сохранено в: $outputFilename\n";
            } else {
                echo "⚠️ Текст не распознан\n";
            }
        } else {
            echo "❌ Неожиданная структура ответа\n";
            if (isset($result['ErrorMessage']) && !empty($result['ErrorMessage'])) {
                echo "Ошибка API: " . $result['ErrorMessage'] . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // Пауза между запросами чтобы не превысить лимиты API
    sleep(1);
}

echo "Обработка завершена!\n";
?>
