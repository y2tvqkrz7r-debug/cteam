<?php
// デバッグ用: エラーメッセージの表示（本番環境では無効にしてください）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// HTTPヘッダーで文字セットを明示的に指定
header('Content-Type: text/html; charset=UTF-8');

// データベース接続情報の統合
$hostname = "127.0.0.1";       // データベースサーバーのホスト名
$db_username = "root";          // データベースのユーザー名
$db_password = "dbpass";        // データベースのパスワード
$dbname = "kadai12_1";          // 使用するデータベース名
$tablename = "survey";          // 使用するテーブル名

// ユーザー認証情報
// 実際のアプリケーションでは、ユーザー情報はデータベースで管理することを推奨します
$passlist = array('2342025' => '0625'); // ユーザーID => パスワード

// セッションの開始（セキュリティ強化のため）
session_start();

// POSTデータが存在する場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['transition'])) {
        switch ($_POST['transition']) {
            case "submit_survey":
                // 認証済みか確認
                if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
                    echo_auth_page("認証が必要です");
                    exit;
                }
                process_survey($_POST);
                exit;
        }
    }
}

// ユーザー認証ページの表示
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user'])) {
    echo_auth_page("<b>アンケート調査</b>");
    exit;
}

// 'pass'キーが存在する場合のみ取得
$user = $_POST['user'];
$pass = isset($_POST['pass']) ? $_POST['pass'] : '';

// パスワードが送信された場合、認証を行う
if ($pass !== '') {
    if (!array_key_exists($user, $passlist) || $passlist[$user] !== $pass) {
        echo_auth_page("パスワードが違います");
        exit;
    }
    // 認証成功、セッションを設定
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = $user;
    // アンケートフォームを表示
    echo_survey_page($user);
    exit;
}

// パスワードが送信されていない場合、ルーティンフォームを表示
// ※セキュリティ上、パスワード未送信でルーティンフォームを表示しないようにすることを推奨
echo_auth_page("パスワードを入力してください。");
exit;

///////////////////////////////////////////////////////////////////////
function echo_auth_page($msg)
{
echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>ルーティン認証</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .auth-container {
                width: 100%;
                max-width: 400px;
                padding: 30px;
                background-color: #ffffff;
                border: 1px solid #ddd;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .auth-container h2 {
                text-align: center;
                color: #333;
                margin-bottom: 20px;
            }
            .auth-container label {
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
            .auth-container input[type="text"],
            .auth-container input[type="password"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-sizing: border-box;
            }
            .auth-container button {
                width: 100%;
                padding: 10px;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            .auth-container button:hover {
                background-color: #218838;
            }
            .error-message {
                color: red;
                text-align: center;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <h2>$msg</h2>
            <form method="POST" action="12_1.php">
                <label for="user">ユーザーID:</label>
                <input type="text" id="user" name="user" required>
                
                <label for="pass">パスワード:</label>
                <input type="password" id="pass" name="pass" required>
                
                <button type="submit">ログイン</button>
            </form>
        </div>
    </body>
</html>
EOT;
}
///////////////////////////////////////////////////////////////////////
function echo_survey_page($who)
{
echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>ルーティン調査</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .survey-container {
                width: 100%;
                max-width: 800px;
                padding: 30px;
                background-color: #ffffff;
                border: 1px solid #ddd;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .survey-container h2 {
                text-align: center;
                color: #333;
                margin-bottom: 20px;
            }
            .survey-container p {
                text-align: center;
                color: #555;
                margin-bottom: 30px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                padding: 15px;
                text-align: center;
                border: 1px solid #ddd;
            }
            th {
                background-color: #f2f2f2;
                color: #333;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .submit-button {
                text-align: center;
                margin-top: 20px;
            }
            .submit-button button {
                padding: 12px 25px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            .submit-button button:hover {
                background-color: #0069d9;
            }
            @media (max-width: 768px) {
                .survey-container {
                    padding: 20px;
                }
                table, th, td {
                    font-size: 14px;
                }
                .submit-button button {
                    width: 100%;
                    padding: 10px;
                    font-size: 14px;
                }
            }
        </style>
    </head>
    <body>
        <div class="survey-container">
            <h2>こんにちは <b>$who</b> さん</h2>
            <p>日々のルーティン調査を開始します。以下の設問にご回答ください。</p>
            
            <form method="POST" action="12_1.php" onsubmit="return validateForm();">
                <table>
                    <thead>
                        <tr>
                            <th>質問</th>
                            <th>1. できた</th>
                            <th>2. できなかった</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>洗濯ができた？</td>
                            <td><input type="radio" name="question1" value="1" required></td>
                            <td><input type="radio" name="question1" value="2"></td>
                            
                        </tr>
                        <tr>
                            <td>筋トレはできた？</td>
                            <td><input type="radio" name="question2" value="1" required></td>
                            <td><input type="radio" name="question2" value="2"></td>
                            
                        </tr>
                        <tr>
                            <td>課題ができた？</td>
                            <td><input type="radio" name="question3" value="1" required></td>
                            <td><input type="radio" name="question3" value="2"></td>
                            
                        </tr>
                        <tr>
                            <td>ご飯を作れた？</td>
                            <td><input type="radio" name="question4" value="1" required></td>
                            <td><input type="radio" name="question4" value="2"></td>
                            
                        </tr>
                        <tr>
                            <td>自主学習ができた？</td>
                            <td><input type="radio" name="question5" value="1" required></td>
                            <td><input type="radio" name="question5" value="2"></td>
                           
                        </tr>
                        <tr>
                            <td>掃除ができた？</td>
                            <td><input type="radio" name="question6" value="1" required></td>
                            <td><input type="radio" name="question6" value="2"></td>
                            
                        <tr>
                            <td>瞑想ができた？</td>
                            <td><input type="radio" name="question7" value="1" required></td>
                            <td><input type="radio" name="question7" value="2"></td>
                            
                       
                            
                        </tr>
                    </tbody>
                </table>
                <div class="submit-button">
                    <button type="submit" name="submit" value="submit">送信</button>
                    <input type="hidden" name="transition" value="submit_survey">
                    <input type="hidden" name="user" value="$who">
                </div>
            </form>
            
            <script>
            function validateForm() {
                // クライアントサイドでも全ての質問が回答されているか確認
                for (let i = 1; i <= 7; i++) {
                    let radios = document.getElementsByName('question' + i);
                    let checked = false;
                    for (let radio of radios) {
                        if (radio.checked) {
                            checked = true;
                            break;
                        }
                    }
                    if (!checked) {
                        alert('すべての質問に回答してください。');
                        return false;
                    }
                }
                return true;
            }
            </script>
            
        </div>
    </body>
</html>
EOT;
}
///////////////////////////////////////////////////////////////////////
function process_survey($post_data)
{
    // データベース接続情報の統合
    $hostname = "127.0.0.1";       // データベースサーバーのホスト名
    $db_username = "root";          // データベースのユーザー名
    $db_password = "dbpass";        // データベースのパスワード
    $dbname = "kadai12_1";          // 使用するデータベース名
    $tablename = "survey";          // 使用するテーブル名

    // サーバーサイドで全ての質問が回答されているか確認
    for ($i = 1; $i <= 7; $i++) {
        if (!isset($post_data['question' . $i])) {
            echo_error_page("すべての質問に回答してください。", $post_data['user']);
            exit;
        }
    }

    // データベースに接続
    $link = mysqli_connect($hostname, $db_username, $db_password, $dbname);
    if (!$link) {
        echo_error_page("データベースへの接続に失敗しました。", $post_data['user']);
        exit;
    }

    // 文字セットの設定
    if (!mysqli_set_charset($link, 'utf8mb4')) {
        echo_error_page("文字セットの設定に失敗しました: " . mysqli_error($link), $post_data['user']);
        mysqli_close($link);
        exit;
    }

    // テーブルが存在しない場合は作成
    $create_table_query = "CREATE TABLE IF NOT EXISTS `$tablename` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `question1` INT NOT NULL,
        `question2` INT NOT NULL,
        `question3` INT NOT NULL,
        `question4` INT NOT NULL,
        `question5` INT NOT NULL,
        `question6` INT NOT NULL,
        `question7` INT NOT NULL,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($link, $create_table_query)) {
        $error = mysqli_error($link);
        echo_error_page("テーブルの作成に失敗しました。原因: " . $error, $post_data['user']);
        mysqli_close($link);
        exit;
    }

    // エスケープ処理とデータの整形
    $user_id = mysqli_real_escape_string($link, $post_data['user']);
    $responses = [];
    for ($i = 1; $i <= 7; $i++) {
        $q = 'question' . $i;
        $responses[$q] = intval($post_data[$q]); // 数値に変換
    }

    // 挿入クエリの作成（プリペアドステートメントを使用）
    $stmt = mysqli_prepare($link, "INSERT INTO `$tablename` (`user_id`, `question1`, `question2`, `question3`, `question4`, `question5`, `question6`, `question7`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $error = mysqli_error($link);
        echo_error_page("準備されたステートメントの作成に失敗しました。原因: " . $error, $post_data['user']);
        mysqli_close($link);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'siiiiiii',
        $user_id,
        $responses['question1'],
        $responses['question2'],
        $responses['question3'],
        $responses['question4'],
        $responses['question5'],
        $responses['question6'],
        $responses['question7']
        
    );

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        echo_error_page("データの挿入に失敗しました。原因: " . $error, $post_data['user']);
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        exit;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($link);

    echo_thank_you_page($post_data['user']);
}
///////////////////////////////////////////////////////////////////////
function echo_error_page($msg, $user)
{
    echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>エラー</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .error-container {
                width: 100%;
                max-width: 600px;
                padding: 30px;
                background-color: #ffe6e6;
                border: 1px solid #ffcccc;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
            }
            .error-container h2 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-container p {
                color: #555;
                margin-bottom: 30px;
            }
            .error-container button {
                padding: 12px 25px;
                background-color: #dc3545;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            .error-container button:hover {
                background-color: #c82333;
            }
            @media (max-width: 768px) {
                .error-container {
                    padding: 20px;
                }
                .error-container button {
                    width: 100%;
                    padding: 10px;
                    font-size: 14px;
                }
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h2>エラーが発生しました</h2>
            <p>$msg</p>
            <form method="POST" action="12_1.php">
                <input type="hidden" name="transition" value="submit_survey">
                <input type="hidden" name="user" value="$user">
                <button type="submit">ルーティン内容に戻る</button>
            </form>
        </div>
    </body>
</html>
EOT;
}
///////////////////////////////////////////////////////////////////////
function echo_thank_you_page($who)
{
echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>ありがとうございました</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .thank-you-container {
                width: 100%;
                max-width: 600px;
                padding: 30px;
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
            }
            .thank-you-container h2 {
                color: #155724;
                margin-bottom: 20px;
            }
            .thank-you-container a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 25px;
                background-color: #28a745;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            .thank-you-container a:hover {
                background-color: #218838;
            }
            @media (max-width: 768px) {
                .thank-you-container {
                    padding: 20px;
                }
                .thank-you-container a {
                    width: 100%;
                    padding: 10px;
                    font-size: 14px;
                }
            }
        </style>
    </head>
    <body>
        <div class="thank-you-container">
            <h2><b>$who</b> さん、今日も一日お疲れさまでした！</h2>
            <a href="12_1.php">最初のページに戻る</a>
        </div>
    </body>
</html>
EOT;
}
?>


