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

$promptValue = $_POST['prompt'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Private AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --bg: #0b1220;
      --bg-elevated: rgba(17, 24, 39, 0.82);
      --bg-input: rgba(30, 41, 59, 0.72);
      --line: rgba(148, 163, 184, 0.3);
      --text: #e2e8f0;
      --text-muted: #94a3b8;
      --accent: #22d3ee;
      --accent-strong: #06b6d4;
      --danger: #f87171;
      --radius-lg: 18px;
      --radius-md: 12px;
      --shadow-lg: 0 24px 54px rgba(0, 0, 0, 0.45);
      --shadow-sm: 0 8px 20px rgba(0, 0, 0, 0.26);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      min-height: 100vh;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      background:
        radial-gradient(circle at 10% 10%, rgba(34, 211, 238, 0.14), transparent 38%),
        radial-gradient(circle at 88% 4%, rgba(59, 130, 246, 0.14), transparent 34%),
        linear-gradient(180deg, #020617, var(--bg));
      color: var(--text);
      padding: 28px 16px;
      display: grid;
      place-items: center;
    }

    .chat-shell {
      width: min(980px, 100%);
      background: var(--bg-elevated);
      border: 1px solid var(--line);
      border-radius: 24px;
      box-shadow: var(--shadow-lg);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      overflow: hidden;
    }

    .chat-head {
      padding: 20px 22px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      gap: 14px;
      align-items: center;
      flex-wrap: wrap;
    }

    .chat-head h1 {
      font-size: 1.1rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }

    .hint {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .chat-body {
      min-height: 320px;
      max-height: 58vh;
      overflow: auto;
      padding: 22px;
      display: grid;
      gap: 14px;
      align-content: start;
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.16), transparent 28%);
    }

    .empty-state {
      border: 1px dashed var(--line);
      border-radius: var(--radius-lg);
      padding: 20px;
      color: var(--text-muted);
      background: rgba(15, 23, 42, 0.26);
    }

    .bubble {
      max-width: min(90%, 760px);
      padding: 14px 16px;
      border-radius: var(--radius-md);
      border: 1px solid var(--line);
      box-shadow: var(--shadow-sm);
      white-space: pre-wrap;
      line-height: 1.55;
      word-break: break-word;
    }

    .bubble-label {
      display: inline-block;
      margin-bottom: 8px;
      font-weight: 700;
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
    }

    .bubble-user {
      justify-self: end;
      background: linear-gradient(145deg, #164e63, #0f3550);
      border-color: rgba(34, 211, 238, 0.44);
    }

    .bubble-ai {
      justify-self: start;
      background: rgba(30, 41, 59, 0.72);
    }

    .bubble-error {
      justify-self: start;
      border-color: rgba(248, 113, 113, 0.45);
      color: #fecaca;
      background: rgba(127, 29, 29, 0.26);
    }

    .chat-form-wrap {
      border-top: 1px solid var(--line);
      padding: 16px;
      background: rgba(2, 6, 23, 0.38);
    }

    .chat-form {
      display: grid;
      gap: 12px;
    }

    textarea {
      width: 100%;
      min-height: 96px;
      max-height: 40vh;
      padding: 14px;
      resize: vertical;
      border-radius: var(--radius-md);
      border: 1px solid var(--line);
      background: var(--bg-input);
      color: var(--text);
      font: inherit;
      line-height: 1.45;
      transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    textarea:focus {
      outline: none;
      border-color: rgba(34, 211, 238, 0.9);
      box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.16);
    }

    .form-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .kbd-hint {
      color: var(--text-muted);
      font-size: 0.82rem;
    }

    .kbd-hint kbd {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      background: rgba(148, 163, 184, 0.16);
      border: 1px solid var(--line);
      border-bottom-width: 2px;
      border-radius: 6px;
      padding: 1px 6px;
    }

    .actions {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-left: auto;
    }

    button {
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 0.92rem;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease, border-color 0.2s ease;
    }

    button:hover {
      transform: translateY(-1px);
    }

    .btn-primary {
      color: #06202b;
      background: linear-gradient(180deg, var(--accent), var(--accent-strong));
      box-shadow: 0 8px 18px rgba(6, 182, 212, 0.35);
    }

    .btn-secondary {
      color: var(--text);
      background: transparent;
      border-color: var(--line);
    }

    .btn-secondary:hover {
      background: rgba(148, 163, 184, 0.12);
    }

    @media (max-width: 720px) {
      .chat-head,
      .chat-body,
      .chat-form-wrap {
        padding: 14px;
      }

      .bubble {
        max-width: 100%;
      }

      .form-row {
        align-items: stretch;
      }

      .actions {
        width: 100%;
      }

      .actions button {
        flex: 1;
      }
    }
  </style>
</head>
<body>
  <main class="chat-shell">
    <header class="chat-head">
      <div>
        <h1>Private AI Chat</h1>
        <p class="hint">Fast local text chat with Gemma.</p>
      </div>
      <p class="hint">Simple flow: write → send → iterate.</p>
    </header>

    <section class="chat-body" id="chat-body">
      <?php if (!$responseText && !$errorText): ?>
        <div class="empty-state">Start by asking a question, drafting copy, or pasting text to improve.</div>
      <?php endif; ?>

      <?php if ($promptValue !== ''): ?>
        <article class="bubble bubble-user">
          <span class="bubble-label">You</span>
          <div><?= htmlspecialchars($promptValue) ?></div>
        </article>
      <?php endif; ?>

      <?php if ($responseText): ?>
        <article class="bubble bubble-ai" id="ai-response">
          <span class="bubble-label">Assistant</span>
          <div><?= htmlspecialchars($responseText) ?></div>
        </article>
      <?php endif; ?>

      <?php if ($errorText): ?>
        <article class="bubble bubble-error">
          <span class="bubble-label">Error</span>
          <div><?= htmlspecialchars($errorText) ?></div>
        </article>
      <?php endif; ?>
    </section>

    <div class="chat-form-wrap">
      <form class="chat-form" method="post" id="chat-form">
        <label for="prompt" class="hint">Message</label>
        <textarea id="prompt" name="prompt" placeholder="Ask anything…" required><?= htmlspecialchars($promptValue) ?></textarea>
        <div class="form-row">
          <p class="kbd-hint">Press <kbd>Ctrl</kbd> + <kbd>Enter</kbd> to send.</p>
          <div class="actions">
            <button class="btn-secondary" type="button" id="clear-btn">Clear</button>
            <button class="btn-secondary" type="button" id="copy-btn">Copy reply</button>
            <button class="btn-primary" type="submit" id="send-btn">Send</button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <script>
    const form = document.getElementById('chat-form');
    const prompt = document.getElementById('prompt');
    const sendBtn = document.getElementById('send-btn');
    const clearBtn = document.getElementById('clear-btn');
    const copyBtn = document.getElementById('copy-btn');
    const aiResponse = document.getElementById('ai-response');
    const chatBody = document.getElementById('chat-body');

    function focusPromptToEnd() {
      prompt.focus();
      const valueLength = prompt.value.length;
      prompt.setSelectionRange(valueLength, valueLength);
    }

    focusPromptToEnd();

    if (chatBody) {
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    prompt.addEventListener('keydown', function (event) {
      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        form.requestSubmit();
      }
    });

    form.addEventListener('submit', function () {
      sendBtn.textContent = 'Sending...';
      sendBtn.disabled = true;
    });

    clearBtn.addEventListener('click', function () {
      prompt.value = '';
      focusPromptToEnd();
    });

    copyBtn.addEventListener('click', async function () {
      if (!aiResponse) {
        copyBtn.textContent = 'No reply yet';
        setTimeout(() => {
          copyBtn.textContent = 'Copy reply';
        }, 1400);
        return;
      }

      const text = aiResponse.innerText.replace(/^Assistant\s*/i, '').trim();
      try {
        await navigator.clipboard.writeText(text);
        copyBtn.textContent = 'Copied';
      } catch (error) {
        copyBtn.textContent = 'Copy failed';
      }
      setTimeout(() => {
        copyBtn.textContent = 'Copy reply';
      }, 1400);
    });
  </script>
</body>
</html>
