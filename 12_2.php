<?php
// 文字コードをUTF-8に統一

header('Content-Type: text/html; charset=UTF-8');

// ここでDB接続済みと仮定（$link, $tablename が有効）
// 例：$link = mysqli_connect($host, $user, $pass, $db);
//     mysqli_set_charset($link, 'utf8mb4');

$post_data = $_POST ?? [];
if (!isset($post_data['user'])) {
    echo_error_page('ユーザーIDが送信されていません。', '');
    exit;
}

// ユーザーID
$user_id = trim((string)$post_data['user']);

// 質問1?7を収集
$responses = [];
for ($i = 1; $i <= 7; $i++) {
    $key = 'question' . $i;
    $responses[$key] = isset($post_data[$key]) ? trim((string)$post_data[$key]) : '';
}

// 必要なら必須チェック（例：全設問必須）
/*
for ($i = 1; $i <= 7; $i++) {
    if ($responses['question' . $i] === '') {
        echo_error_page("設問{$i}が未入力です。", $user_id);
        exit;
    }
}
*/

// INSERT（質問1?7まで）
$insert_sql = "
    INSERT INTO $tablename
    (`user_id`, `question1`, `question2`, `question3`, `question4`, `question5`, `question6`, `question7`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($link, $insert_sql);
if (!$stmt) {
    $error = mysqli_error($link);
    echo_error_page("SQL準備に失敗しました: " . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $user_id);
    mysqli_close($link);
    exit;
}

// すべて文字列としてバインド
$types = 'ssssssss'; // user_id + 7問 = 8個
$bind_ok = mysqli_stmt_bind_param(
    $stmt,
    $types,
    $user_id,
    $responses['question1'],
    $responses['question2'],
    $responses['question3'],
    $responses['question4'],
    $responses['question5'],
    $responses['question6'],
    $responses['question7']
);
if (!$bind_ok) {
    $error = mysqli_error($link);
    echo_error_page("パラメータのバインドに失敗しました: " . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $user_id);
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    exit;
}

// 実行
if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt) ?: mysqli_error($link);
    echo_error_page("データ登録中にエラーが発生しました: " . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $user_id);
    mysqli_stmt_close($stmt);
    mysqli_close($link);
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($link);

// 完了ページ表示
echo_rui_page($user_id);


// -----------------------------
// 出力関数
// -----------------------------
function echo_error_page($msg, $user)
{
    $safe_msg  = htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8');
    $safe_user = htmlspecialchars((string)$user, ENT_QUOTES, 'UTF-8');

    echo <<<EOT
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <title>ルーティン調査 - エラー</title>
    <style>
        body {
            font-family: Arial, "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
            margin: 20px;
        }
        .error {
            color: #c00;
            font-weight: bold;
        }
        .back-button {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <p class="error">{$safe_msg}</p>
    <form method="   <input type="hidden" name="transition" value="submit_survey">
        <input type="hidden" name="user" value="{$safe_user}">
        <button type="submit">アンケートへ戻る</button>
    </form>
</body>
</html>
EOT;
}

function echo_rui_page($who)
{
    $safe_who = htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8');

    echo <<<EOT
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <title>ルーティン調査 - 完了</title>
    <style>
        body {
            font-family: Arial, "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
            margin: 20px;
        }
        .thank-you {
            font-size: 1.2em;
        }
        .home-link {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <p class="thank-you"><b>{$safe_who}</b> さん、アンケートへのご協力ありがとうございました！</p>
    <div class="home-link">
        12_1.phpホームページへ戻る</a>
    </div>
</body>
</html>
EOT;
}
