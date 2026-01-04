<?php
$GEMINI_API_KEY = 'AIzaSyBQaGJbeUjuIBNDgJw';

$modelsToTry = [
    ['model' => 'gemini-2.5-flash', 'version' => 'v1beta', 'name' => 'Gemini 2.5 Flash (v1beta) - Latest'],
    ['model' => 'gemini-1.5-flash', 'version' => 'v1beta', 'name' => 'Gemini 1.5 Flash (v1beta)'],
    ['model' => 'gemini-1.5-pro', 'version' => 'v1beta', 'name' => 'Gemini 1.5 Pro (v1beta)'],
    ['model' => 'gemini-1.0-pro', 'version' => 'v1beta', 'name' => 'Gemini 1.0 Pro (v1beta)'],
    ['model' => 'gemini-pro', 'version' => 'v1beta', 'name' => 'Gemini Pro (v1beta)']
];

$currentModel = $modelsToTry[0];
$apiUrl = "https://generativelanguage.googleapis.com/{$currentModel['version']}/models/{$currentModel['model']}:generateContent?key=" . $GEMINI_API_KEY;

$requestData = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Hello! Say hi in one sentence.']
            ]
        ]
    ]
];

echo "<h2>Testing Gemini API</h2>";
echo "<p>Testing Model: <strong>{$currentModel['name']}</strong></p>";
echo "<p>API URL: <code>$apiUrl</code></p>";
echo "<hr>";

if (!function_exists('curl_init')) {
    echo "<p style='color: red;'>❌ cURL is not available!</p>";
    echo "<p>Please enable cURL extension in PHP.</p>";
    exit;
}

echo "<p style='color: green;'>✅ cURL is available</p>";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h3>Response:</h3>";
echo "<p>HTTP Code: <strong>$httpCode</strong></p>";

if ($curlError) {
    echo "<p style='color: red;'>❌ cURL Error: <strong>$curlError</strong></p>";
} else {
    echo "<p style='color: green;'>✅ No cURL errors</p>";
}

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
        echo "<p style='color: green;'>✅ <strong>SUCCESS!</strong></p>";
        echo "<p><strong>Working Model:</strong> {$currentModel['name']}</p>";
        echo "<p><strong>AI Response:</strong> " . htmlspecialchars($aiResponse) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Response received but format unexpected</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>FAILED!</strong> HTTP $httpCode</p>";
    $errorData = json_decode($response, true);
    if (isset($errorData['error'])) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($errorData['error']['message'] ?? 'Unknown error') . "</p>";
    }
    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    
    echo "<hr><h3>Trying Other Models:</h3>";
    foreach (array_slice($modelsToTry, 1) as $model) {
        $testUrl = "https://generativelanguage.googleapis.com/{$model['version']}/models/{$model['model']}:generateContent?key=" . $GEMINI_API_KEY;
        echo "<p>Trying: <strong>{$model['name']}</strong>...</p>";
        
        $ch2 = curl_init($testUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
        
        $response2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($httpCode2 === 200) {
            $data2 = json_decode($response2, true);
            if (isset($data2['candidates'][0]['content']['parts'][0]['text'])) {
                echo "<p style='color: green;'>✅ <strong>SUCCESS with {$model['name']}!</strong></p>";
                echo "<p>" . htmlspecialchars($data2['candidates'][0]['content']['parts'][0]['text']) . "</p>";
                break;
            }
        } else {
            echo "<p style='color: red;'>❌ Failed (HTTP $httpCode2)</p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='../index.php'>Back to Home</a></p>";
?>


