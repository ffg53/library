<?php
class OCRSpace {
    private $apiKey;
    private $endpoint = 'https://api.ocr.space/parse/image';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function recognizeFromUrl($imageUrl, $options = []) {
        $defaultOptions = [
            'language' => 'rus',
            'isOverlayRequired' => 'false',
//            'OCREngine' => '2'
        ];
        
        $postData = array_merge($defaultOptions, $options, ['url' => $imageUrl]);
        
        return $this->makeRequest($postData);
    }
    
    public function recognizeFromFile($filePath, $options = []) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: " . $filePath);
        }
        
        $defaultOptions = [
            'language' => 'rus',
            'isOverlayRequired' => 'false',
//            'OCREngine' => '2'
        ];
        
        $postData = array_merge($defaultOptions, $options);
        $postData['file'] = new CURLFile($filePath);
        
        return $this->makeRequest($postData);
    }
    
    private function makeRequest($postData) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 30, // Reduced timeout
            CURLOPT_CONNECTTIMEOUT => 10,
 //           CURLOPT_SSL_VERIFYPEER => false, // Try disabling SSL verification temporarily
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        // Debug information
        if ($error || $httpCode !== 200) {
            echo "\nCURL Error: " . $error;
            echo "\nHTTP Code: " . $httpCode;
            echo "\n";
        }
        
        curl_close($curl);
        
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

// Usage with better error handling
$apiKey = 'K81126633088957';
$ocr = new OCRSpace($apiKey);

// Test with a single file first
$testFile = 'photos/IMG_20251106_122126.jpg';

if (!file_exists($testFile)) {
    die("Test file not found: $testFile\n");
}

echo "Testing with file: $testFile\n";
echo "File size: " . filesize($testFile) . " bytes\n";

$result = $ocr->recognizeFromFile($testFile);

if (!isset($result['error'])) {
    if (isset($result['ParsedResults'][0]['ParsedText'])) {
        $text = $result['ParsedResults'][0]['ParsedText'];
        echo "OCR Result:\n" . $text . "\n";
    } else {
        echo "Unexpected response structure:\n";
        print_r($result);
    }
} else {
    echo "Error occurred:\n";
    print_r($result);
}
?>
