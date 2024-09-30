<?php
// function logDebug($message) {
//     file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
// }
error_log("Request received: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . print_r(getallheaders(), true));
error_log("Raw input: " . file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log('Direct GET access to webhook.php detected');
    exit('Direct access not allowed');
}

// // GASからのリクエストを識別するための秘密のトークン
define('SECRET_TOKEN', 'mySuperSecretToken123!');
$headers = getallheaders();
if (!isset($headers['X-Secret-Token']) || $headers['X-Secret-Token'] !== SECRET_TOKEN) {
    logDebug('Invalid or missing secret token');
    http_response_code(403);
    exit('Access Denied');
}

// リクエストメソッドとContent-Typeをログに記録
logDebug("Request method: " . $_SERVER['REQUEST_METHOD']);
logDebug("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));


// リクエストヘッダーでトークンを確認
$headers = getallheaders();
logDebug('Received headers: ' . print_r($headers, true));

// POSTデータとraw inputをログに記録
logDebug('POST data: ' . print_r($_POST, true));
logDebug('Raw input: ' . file_get_contents('php://input'));

if (!isset($headers['X-Secret-Token']) || $headers['X-Secret-Token'] !== SECRET_TOKEN) {
    http_response_code(403);
    exit('Access Denied');
}

logDebug('Access Granted. Proceeding with script execution.');


// エラーメッセージを表示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function logDebug($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}



logDebug('Script started');
logDebug('Request method: ' . $_SERVER['REQUEST_METHOD']);
logDebug('Headers: ' . print_r(getallheaders(), true));
logDebug('GET data: ' . print_r($_GET, true));
logDebug('POST data: ' . print_r($_POST, true));
logDebug('Raw input: ' . file_get_contents('php://input'));

// リクエストの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_SERVER['CONTENT_TYPE']) || 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    logDetails("Invalid request received.");
    http_response_code(400);
    echo "無効なリクエストです。POSTメソッドとapplication/json Content-Typeが必要です。";
    exit;
}

// JSONデータの解析
$jsonData = json_decode(file_get_contents('php://input'), true);
logDebug('Decoded JSON data: ' . print_r($jsonData, true));

if ($jsonData === null) {
    logDebug('JSON decode error');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なJSONデータです。']);
    exit;
}

// リクエストの処理
$response = ['status' => 'success', 'message' => 'Data received'];
echo json_encode($response);

// CORS対応
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// ログファイルに書き込む関数
function logDetails($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message . "\n";
    if ($data !== null) {
        $logMessage .= print_r($data, true) . "\n";
    }
    $logMessage .= "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $logMessage .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set') . "\n";
    $logMessage .= "Referrer: " . ($_SERVER['HTTP_REFERER'] ?? 'Not set') . "\n";
    $logMessage .= "---\n";
    file_put_contents('webhook_log.txt', $logMessage, FILE_APPEND);
    error_log($logMessage); // サーバーのエラーログにも記録
}

// リクエストメソッドをログに記録
logDetails("Request method:", $_SERVER['REQUEST_METHOD']);

// OPTIONSリクエストを無視する処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    logDetails("OPTIONS request received and ignored.");
    exit(0);
}

logDetails("Request headers:", getallheaders());
logDetails("POST data:", $_POST);



// POSTリクエストのみ処理
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     logDetails("Non-POST request received, ignoring.");
//     echo "このエンドポイントはPOSTリクエストのみ対応しています。";
//     exit(0);
// }

// POSTリクエスト処理
$requestBody = file_get_contents('php://input');
logDetails("Raw request body:", $requestBody);

// JSONデータをデコード
$data = json_decode($requestBody, true);

// JSONデコードエラーの確認
if (json_last_error() !== JSON_ERROR_NONE) {
    logDetails("JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo "JSONデコードエラー: " . json_last_error_msg();
    exit;
}


logDetails("Decoded JSON data:", $data);


// `data` キーがあるか確認
if (!isset($data['data']) || !is_array($data['data']) || count($data['data']) !== 8) {
    logDetails("Invalid data structure in JSON.");
    http_response_code(400);
    echo "無効なデータ構造です。";
    exit;
}


// データベース接続設定
$db_name = "gsacademy2024_gs_db_class";
$db_id = "gsacademy2024";
$db_pw = "akagac21";
$db_host = "mysql651.db.sakura.ne.jp";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_id, $db_pw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("データベース接続失敗: " . $e->getMessage());
}

// スプレッドシートデータをデータベースに挿入
$rowData = $data['data'];
logDetails("Row data to insert:", $rowData);

try {
    $stmt = $pdo->prepare("INSERT INTO Gmail (Date, Name, Email, Model, Serial, Year, Price, Item_Condition) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$rowData[0], $rowData[1], $rowData[2], $rowData[3], $rowData[4], $rowData[5], $rowData[6], $rowData[7]]);
    echo "データが正常に保存されました。";
    logDetails("Data inserted successfully.");
} catch (PDOException $e) {
    logDetails("データ挿入エラー: " . $e->getMessage());
    echo "データ挿入エラー: " . $e->getMessage();
}

?>

