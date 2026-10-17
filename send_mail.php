<?php
// お問い合わせフォームの内容を Mailpit (localhost:1025) へ SMTP で送信する
const SMTP_HOST = '127.0.0.1';
const SMTP_PORT = 1025;
const MAIL_TO = 'info@example.com';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function smtp_read($fp): string
{
    $res = '';
    while (($line = fgets($fp, 512)) !== false) {
        $res .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    return $res;
}

function smtp_cmd($fp, string $cmd, string $expect): void
{
    fwrite($fp, $cmd . "\r\n");
    $res = smtp_read($fp);
    if (strpos($res, $expect) !== 0) {
        throw new RuntimeException('SMTPエラー: ' . trim($res));
    }
}

function send_mail(string $from, string $fromName, string $to, string $subject, string $body): void
{
    $fp = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 5);
    if (!$fp) {
        throw new RuntimeException("Mailpitに接続できません ($errstr)");
    }
    try {
        smtp_read($fp);
        smtp_cmd($fp, 'HELO localhost', '250');
        smtp_cmd($fp, "MAIL FROM:<$from>", '250');
        smtp_cmd($fp, "RCPT TO:<$to>", '250');
        smtp_cmd($fp, 'DATA', '354');

        $headers = [
            'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <$from>",
            "To: <$to>",
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . date('r'),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
        smtp_cmd($fp, $data . "\r\n.", '250');
        smtp_cmd($fp, 'QUIT', '221');
    } finally {
        fclose($fp);
    }
}

$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $error = '不正なアクセスです。';
} else {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $message === '') {
        $error = '未入力の項目があります。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレスの形式が正しくありません。';
    } else {
        $body = "お名前: $name\r\nメールアドレス: $email\r\n\r\nお問い合わせ内容:\r\n$message\r\n";
        try {
            send_mail($email, $name, MAIL_TO, 'お問い合わせ（' . $name . '様）', $body);
            $ok = true;
        } catch (RuntimeException $e) {
            $error = '送信に失敗しました。' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>お問い合わせ送信｜ダルマちゃんめだか</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-shop">
  <main class="container" style="text-align:center;">
    <?php if ($ok): ?>
      <h2 class="page-title">送信完了</h2>
      <p>お問い合わせを送信しました。ありがとうございました。</p>
    <?php else: ?>
      <h2 class="page-title">送信できませんでした</h2>
      <p><?= h($error) ?></p>
    <?php endif; ?>
    <p><a href="contact.html">お問い合わせページに戻る</a></p>
  </main>
</body>
</html>
