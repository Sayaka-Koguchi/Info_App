<?php
// namespace宣言を最初に
namespace MyApp;

// namespaceの後にセッションを開始
session_start();

use Google\Client;
use Google\Service\Sheets;
use PDO;
use PDOException;

require '/Applications/XAMPP/xamppfiles/htdocs/Info_App/vendor/autoload.php';


const SPREADSHEET_ID = 'Sheets';
const SPREADSHEET_RANGE = 'シート1!A1:h100';

/**
 * Google Sheetsからデータを取得する
 * 
 * @return array
 * @throws \Google\Exception
 */
function getGoogleSheetData() {
    try {
        /** @var Client $client */
        $client = new Client();
        $client->setAuthConfig('/Applications/XAMPP/xamppfiles/htdocs/Info_App/credentials/client_secret_628142568752-j3lpnni6hkjqd36dpud0qsm6cviddf3d.apps.googleusercontent.com.json');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAccessType('offline');
        $client->setRedirectUri('http://localhost/Info_App/callback.php'); // リダイレクトURIを設定

        // トークンの処理（認証）
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            // 既存のトークンを使用
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        } else {
            // 認証コードがリダイレクトURLに渡されたか確認
            if (!isset($_GET['code'])) {
                // 認証URLを生成してブラウザで認証させる
                $authUrl = $client->createAuthUrl();
                echo "以下のURLを開いて認証してください: <a href='$authUrl'>$authUrl</a>";
                exit;
            } else {
              // 認証コードがリダイレクトURLに渡された場合、トークンを取得
                $authCode = $_GET['code'];
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                file_put_contents('token.json', json_encode($accessToken));
                $client->setAccessToken($accessToken);
            }
        }

        // Google Sheetsからデータを取得
        $service = new Sheets($client);
        $response = $service->spreadsheets_values->get(SPREADSHEET_ID, SPREADSHEET_RANGE);
        $values = $response->getValues();

        if (empty($values)) {
            echo "データが見つかりませんでした。\n";
            return [];
        } else {
            return $values;
        }
    } catch (\Exception $e) {
        echo "エラーが発生しました: " . $e->getMessage() . "\n";
        return [];
    }
}


// MySQLデータベースに接続する関数
function connectToDatabase() {
    $host = 'localhost'; // ローカル環境の場合は通常localhost
    $dbname = 'gs_db_class'; // 使用するデータベース名
    $username = 'root'; // データベースのユーザー名
    $password = ''; // データベースのパスワード

    try {
        // PDOでデータベースに接続
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続失敗: " . $e->getMessage());
    }
}

// データをデータベースに挿入する関数
function insertDataToDatabase($data) {
    $pdo = connectToDatabase();

    // データ挿入のためのSQLクエリを作成
    $sql = "INSERT INTO gs_db_class (Date, Name, Email, Model, Serial, Year, Price, Item_Condition) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    foreach ($data as $row) {
        try {
            $stmt = $pdo->prepare($sql);

            // 日付フォーマットを変換
            $date = DateTime::createFromFormat('Y/m/d H:i:s', $row[0]);
            $formattedDate = $date->format('Y-m-d H:i:s');

            $stmt->execute([
                $formattedDate, // date
                $row[1], // name
                $row[2], // email
                $row[3], // model
                $row[4], // serial
                $row[5], // year
                $row[6], // price
                $row[7], // item_condition
            ]);
        } catch (PDOException $e) {
            echo "データ挿入エラー: " . $e->getMessage();
        }
    }

    echo "データベースにデータを挿入しました。\n";
}


// Google Sheetsからデータを取得してデータベースに挿入
$data = getGoogleSheetData();
if (!empty($data)) {
    insertDataToDatabase($data);
}


