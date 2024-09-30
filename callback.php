<?php
session_start();
require_once 'vendor/autoload.php';

$client = new Google\Client();
$client->setAuthConfig('/Applications/XAMPP/xamppfiles/htdocs/Info_App/credentials/client_secret_628142568752-j3lpnni6hkjqd36dpud0qsm6cviddf3d.apps.googleusercontent.com.json');
$client->setRedirectUri('http://localhost/callback.php');
$client->setScopes(Google\Service\Sheets::SPREADSHEETS_READONLY);
$client->setAccessType('offline');

if (!isset($_GET['code'])) {
    echo "エラー：認証コードがありません。";
    exit;
}

$accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (array_key_exists('error', $accessToken)) {
    echo "エラー：" . $accessToken['error'];
    exit;
}

// トークンを保存
file_put_contents('token.json', json_encode($accessToken));
echo "認証成功！トークンを保存しました。";

// callback.phpにリダイレクトされた時のGETパラメータを表示
echo "<pre>";
print_r($_GET);
echo "</pre>";

// echo "Callback file is working!";
?>
