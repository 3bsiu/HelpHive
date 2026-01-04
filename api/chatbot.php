<?php
header('Content-Type: application/json');
require_once '../database/connection.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

session_start();

$userMessage = '';
$chatHistory = [];
$GEMINI_API_KEY = 'AIzaSDgJw';
$GEMINI_MODELS = [
    ['model' => 'gemini-2.5-flash', 'version' => 'v1beta'],
    ['model' => 'gemini-1.5-flash', 'version' => 'v1beta'],
    ['model' => 'gemini-1.5-pro', 'version' => 'v1beta'],
    ['model' => 'gemini-1.0-pro', 'version' => 'v1beta'],
    ['model' => 'gemini-pro', 'version' => 'v1beta']
];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message']) || empty(trim($data['message']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }

    $userMessage = trim($data['message']);
    $chatHistory = $data['history'] ?? [];

    $context = "You are HelpHive Assistant, a helpful AI assistant for a university help desk system. ";
    $context .= "أنت مساعد HelpHive، مساعد ذكي مفيد لنظام مكتب المساعدة في الجامعة.\n\n";
    
    $context .= "Your task is to help students and staff answer their questions about: ";
    $context .= "مهمتك هي مساعدة الطلاب والموظفين في الإجابة على أسئلتهم حول:\n\n";
    
    $context .= "Available Categories in the System / الفئات المتاحة في النظام:\n";
    $context .= "1. IT Support - الدعم التقني: Password reset, WiFi issues, Email problems, Network issues / إعادة تعيين كلمة المرور، مشاكل الواي فاي، مشاكل البريد الإلكتروني، مشاكل الشبكة\n";
    $context .= "2. Finance - الشؤون المالية: Tuition fees, Scholarships, Payment deadlines / الرسوم الدراسية، المنح الدراسية، المواعيد النهائية للدفع\n";
    $context .= "3. Registration - التسجيل: Course registration, Schedules, Academic records / التسجيل في المواد، الجداول الدراسية، السجلات الأكاديمية\n";
    $context .= "4. Academic - أكاديمي: Courses, Exams, Grades / المواد الدراسية، الامتحانات، الدرجات\n";
    $context .= "5. Library - المكتبة: Book borrowing, Resources, Working hours / استعارة الكتب، الموارد، ساعات العمل\n";
    $context .= "6. Housing - السكن الجامعي: Rooms, Facilities, Rules / الغرف، المرافق، القواعد\n";
    $context .= "7. Transportation - المواصلات: University transport, Parking / المواصلات الجامعية، مواقف السيارات\n";
    $context .= "8. Account Issue - مشاكل الحساب: Login issues, Account activation / تسجيل الدخول، تفعيل الحساب\n";
    $context .= "9. Email - البريد الإلكتروني: Email setup, Technical issues / إعداد البريد، المشاكل التقنية\n";
    $context .= "10. Network - الشبكة: WiFi, Internet connection / الواي فاي، الاتصال بالإنترنت\n";
    $context .= "11. Software - البرامج: Applications, Required software / التطبيقات، البرامج المطلوبة\n";
    $context .= "12. Hardware - الأجهزة: Computers, Printers / أجهزة الكمبيوتر، الطابعات\n";
    $context .= "13. General Inquiry - استفسار عام: Any general inquiry / أي استفسار عام\n";
    $context .= "14. Other - أخرى: Any other issue / أي مشكلة أخرى\n\n";
    
    $context .= "Important Instructions / تعليمات مهمة:\n";
    $context .= "1. Always respond in Arabic (العربية) - إذا كتب المستخدم بالعربية، أجب بالعربية. إذا كتب بالإنجليزية، يمكنك الإجابة بالعربية أو الإنجليزية (يفضل العربية)\n";
    $context .= "2. Be friendly, polite, and helpful / كن ودوداً ومهذباً ومفيداً\n";
    $context .= "3. Answer questions directly and clearly (2-4 sentences) / أجب على الأسئلة مباشرة وبوضوح (2-4 جمل)\n";
    $context .= "4. If the question is simple and you can answer it, answer directly / إذا كان السؤال بسيطاً ويمكنك الإجابة عليه، أجب مباشرة\n";
    $context .= "5. If the question needs specific system information or complex procedures, suggest creating a support ticket with the appropriate category / إذا كان السؤال يحتاج معلومات محددة من النظام أو إجراءات معقدة، اقترح إنشاء تذكرة دعم مع الفئة المناسبة\n";
    $context .= "6. Don't say 'contact support' unless the question is truly complex or needs human intervention / لا تقل 'احكي مع الدعم' إلا إذا كان السؤال معقداً حقاً أو يحتاج تدخل بشري\n";
    $context .= "7. Be helpful and cooperative - try to answer as many questions as possible / كن مفيداً ومتعاوناً - حاول الإجابة على أكبر قدر ممكن من الأسئلة\n";
    $context .= "8. If you suggest creating a ticket, mention the appropriate category from the list above / إذا اقترحت إنشاء تذكرة، اذكر الفئة المناسبة من القائمة أعلاه\n\n";
    
    $context .= "Examples of questions you can answer directly / أمثلة على الأسئلة التي يمكنك الإجابة عليها مباشرة:\n";
    $context .= "- 'How do I reset my password?' / 'كيف أعيد تعيين كلمة المرور؟' - Explain general steps / اشرح الخطوات العامة\n";
    $context .= "- 'When is the payment deadline?' / 'متى موعد دفع الرسوم؟' - Mention general deadlines (e.g., beginning of each semester) / اذكر المواعيد العامة (مثلاً: بداية كل فصل دراسي)\n";
    $context .= "- 'How do I connect to WiFi?' / 'كيف أتصل بالواي فاي؟' - Explain general method (select network, enter credentials) / اشرح الطريقة العامة (اختر الشبكة، أدخل بيانات الدخول)\n";
    $context .= "- 'Hello' or 'Thanks' / 'مرحبا' أو 'شكرا' - Respond with appropriate greeting / رد بتحية مناسبة\n";
    $context .= "- 'What categories are available?' / 'ما هي الفئات المتاحة؟' - List the categories from above / اذكر الفئات من القائمة أعلاه\n\n";

    $conversationText = $context;
    foreach ($chatHistory as $msg) {
        $role = $msg['role'] ?? 'user';
        $content = $msg['content'] ?? '';
        if ($role === 'user') {
            $conversationText .= "User: " . $content . "\n";
        } else {
            $conversationText .= "Assistant: " . $content . "\n";
        }
    }
    $conversationText .= "User: " . $userMessage . "\nAssistant: ";

    $requestData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $conversationText]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 500,
        ]
    ];

    function callGeminiAPI($apiUrl, $requestData) {
        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception("cURL Error: " . $curlError);
            }

            return ['code' => $httpCode, 'body' => $response];
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($requestData),
                'timeout' => 30
            ]
        ]);

        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to connect to API');
        }

        $httpCode = 200;
        if (isset($http_response_header)) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            if (isset($matches[1])) {
                $httpCode = (int)$matches[1];
            }
        }

        return ['code' => $httpCode, 'body' => $response];
    }

    $aiResponse = null;
    $lastError = null;
    $attemptedModels = [];
    
    foreach ($GEMINI_MODELS as $modelConfig) {
        $model = $modelConfig['model'];
        $version = $modelConfig['version'];
        $apiUrl = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key=" . $GEMINI_API_KEY;
        $attemptedModels[] = "{$model} ({$version})";
        
        try {
            $result = callGeminiAPI($apiUrl, $requestData);
            $httpCode = $result['code'];
            $response = $result['body'];
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : "HTTP $httpCode";
                $lastError = "API Error with {$model} ({$version}): " . $errorMsg;
                error_log($lastError . " - Full response: " . substr($response, 0, 1000));
                
                if ($httpCode === 401 || $httpCode === 403) {
                    throw new Exception('API authentication failed. Please check API key. HTTP ' . $httpCode);
                }
                
                if ($httpCode === 429) {
                    throw new Exception('Rate limit exceeded. You have reached the daily/monthly request limit. Please try again later or set up billing to increase limits.');
                }
                
                if ($httpCode === 404) {
                    error_log("Model {$model} ({$version}) not found, trying next...");
                    continue;
                }
                
                error_log("Error with {$model} ({$version}): HTTP {$httpCode}");
                continue;
            }

            $responseData = json_decode($response, true);

            if (!$responseData) {
                $lastError = "Invalid JSON from {$model} ({$version})";
                error_log($lastError . " - Response: " . substr($response, 0, 500));
                continue;
            }

            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                if (isset($responseData['candidates'][0]['finishReason'])) {
                    $finishReason = $responseData['candidates'][0]['finishReason'];
                    if ($finishReason !== 'STOP') {
                        $lastError = "Response blocked by {$model}: " . $finishReason;
                        error_log($lastError);
                        continue;
                    }
                }
                
                $lastError = "Unexpected response format from {$model}";
                error_log($lastError . " - Response: " . json_encode($responseData));
                continue;
            }

            $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
            error_log("Successfully used model: {$model} ({$version})");
            break;
            
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            error_log("Exception with {$model} ({$version}): " . $lastError);
            
            if (strpos($lastError, 'authentication') !== false) {
                throw $e;
            }
            
            continue;
        }
    }
    
    if (!$aiResponse) {
        $errorDetails = "All models failed. Attempted: " . implode(', ', $attemptedModels);
        if ($lastError) {
            $errorDetails .= " | Last error: " . $lastError;
        }
        error_log("Chatbot: " . $errorDetails);
        throw new Exception($lastError ?: 'All models failed. Please check API key and available models. Attempted: ' . implode(', ', $attemptedModels));
    }

    echo json_encode([
        'success' => true,
        'response' => trim($aiResponse)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    
    $logMessage = "Chatbot Error: " . $errorMessage;
    $logMessage .= "\nRequest: " . json_encode([
        'message' => substr($userMessage ?? 'N/A', 0, 100),
        'history_count' => count($chatHistory ?? [])
    ]);
    error_log($logMessage);
    
    $isAuthError = strpos($errorMessage, 'authentication') !== false || 
                   strpos($errorMessage, '401') !== false || 
                   strpos($errorMessage, '403') !== false;
    $isConnectionError = strpos($errorMessage, 'connect') !== false || 
                         strpos($errorMessage, 'cURL') !== false ||
                         strpos($errorMessage, 'timeout') !== false;
    $isRateLimit = strpos($errorMessage, 'Rate limit') !== false || 
                   strpos($errorMessage, '429') !== false ||
                   strpos($errorMessage, 'limit exceeded') !== false;
    
    $userMessage = 'أعتذر، لكنني أواجه مشكلة في الاتصال الآن. يرجى المحاولة مرة أخرى بعد قليل أو إنشاء تذكرة دعم للحصول على المساعدة.';
    
    if ($isRateLimit) {
        $userMessage = 'تم الوصول إلى الحد الأقصى لعدد الطلبات اليومية. يرجى المحاولة مرة أخرى غداً أو إنشاء تذكرة دعم للحصول على المساعدة.';
    } elseif ($isAuthError) {
        $userMessage = 'مشكلة في مصادقة API. يرجى التحقق من إعدادات النظام.';
    } elseif ($isConnectionError) {
        $userMessage = 'مشكلة في الاتصال بالإنترنت. يرجى التحقق من اتصالك والمحاولة مرة أخرى.';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $userMessage,
        'error' => $errorMessage,
        'debug' => [
            'is_auth_error' => $isAuthError,
            'is_connection_error' => $isConnectionError,
            'is_rate_limit' => $isRateLimit
        ]
    ]);
}
?>


