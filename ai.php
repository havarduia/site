<?php
$responseText = '';
$errorText = '';
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = trim($_POST['prompt'] ?? '');

    if ($prompt !== '') {
        $payload = json_encode([
            'model' => 'gemma4:e2b',
            'prompt' => $prompt,
            'stream' => false,
            'keep_alive' => '0',
            'options' => [
                'num_ctx' => 2048
            ]
        ]);

        $ch = curl_init(OLLAMA_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 180,
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $errorText = 'Request failed: ' . curl_error($ch);
        } else {
            $data = json_decode($raw, true);
            if (isset($data['response'])) {
                $responseText = $data['response'];
            } else {
                $errorText = 'Unexpected response: ' . $raw;
            }
        }

        curl_close($ch);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Private AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 900px;
      margin: 40px auto;
      padding: 0 16px;
      background: #111;
      color: #eee;
    }
    h1 { margin-bottom: 8px; }
    p { color: #bbb; }
    textarea {
      width: 100%;
      min-height: 180px;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #444;
      background: #1b1b1b;
      color: #eee;
      resize: vertical;
    }
    button {
      margin-top: 12px;
      padding: 10px 16px;
      border: 0;
      border-radius: 8px;
      cursor: pointer;
    }
    .out {
      white-space: pre-wrap;
      margin-top: 20px;
      padding: 16px;
      border-radius: 8px;
      background: #1b1b1b;
      border: 1px solid #444;
    }
    .err {
      color: #ff8f8f;
    }
  </style>
</head>
<body>
  <h1>Private AI</h1>
  <p>Ask Gemma something.</p>

  <form method="post">
    <textarea name="prompt" placeholder="Write your prompt here..."><?= htmlspecialchars($_POST['prompt'] ?? '') ?></textarea>
    <br>
    <button type="submit">Send</button>
  </form>

  <?php if ($responseText): ?>
    <div class="out"><?= htmlspecialchars($responseText) ?></div>
  <?php endif; ?>

  <?php if ($errorText): ?>
    <div class="out err"><?= htmlspecialchars($errorText) ?></div>
  <?php endif; ?>
</body>
</html>
