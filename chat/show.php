<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$conversationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$error = null;
$conversation = null;
$messages = [];

if ($conversationId === false || $conversationId === null) {
    $error = 'Invalid conversation id.';
} else {
    try {
        $pdo = createPdo();

        $stmt = $pdo->prepare(
            'SELECT id, title, created_at, updated_at
             FROM conversations
             WHERE id = :id'
        );
        $stmt->execute(['id' => $conversationId]);
        $conversation = $stmt->fetch();

        if ($conversation === false) {
            $error = 'Conversation not found.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, uuid, role, content, created_at
                 FROM messages
                 WHERE conversation_id = :conversation_id
                 ORDER BY id ASC'
            );
            $stmt->execute(['conversation_id' => $conversationId]);
            $messages = $stmt->fetchAll();
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function messageClass(string $role): string
{
    return $role === 'user'
        ? 'ml-auto bg-emerald-700 text-white'
        : 'mr-auto bg-white text-zinc-900 border border-zinc-200';
}

function labelClass(string $role): string
{
    return $role === 'user' ? 'text-emerald-100' : 'text-zinc-500';
}
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chat Messages - Ollama Chat</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-zinc-100 text-zinc-900">
  <header class="border-b border-zinc-300 px-4 py-3">
    <div class="mx-auto flex w-full max-w-5xl items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">
          <?= h($conversation['title'] ?? 'Chat Messages') ?>
        </h1>
        <?php if ($conversation !== null): ?>
          <p class="text-sm text-zinc-600">
            ID <?= h((string) $conversation['id']) ?> / Updated <?= h($conversation['updated_at']) ?>
          </p>
        <?php endif; ?>
      </div>
      <div class="flex gap-2">
        <a class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50" href="./">List</a>
        <a class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" href="../index.php">Back</a>
      </div>
    </div>
  </header>

  <main class="mx-auto w-full max-w-5xl px-4 py-5 sm:px-6">
    <?php if ($error !== null): ?>
      <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= h($error) ?>
      </div>
    <?php elseif ($messages === []): ?>
      <div class="rounded-md border border-zinc-300 bg-white px-4 py-8 text-center text-sm text-zinc-500">
        No messages saved in this chat.
      </div>
    <?php else: ?>
      <section class="space-y-3">
        <?php foreach ($messages as $message): ?>
          <article class="flex">
            <div class="max-w-[85%] rounded-md px-4 py-3 text-sm leading-6 shadow-sm <?= h(messageClass($message['role'])) ?>">
              <div class="mb-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold <?= h(labelClass($message['role'])) ?>">
                <span><?= h($message['role']) ?></span>
                <span class="font-normal opacity-80"><?= h($message['created_at']) ?></span>
              </div>
              <div class="whitespace-pre-wrap"><?= h($message['content']) ?></div>
              <div class="mt-2 break-all text-xs opacity-60"><?= h($message['uuid']) ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
</body>

</html>
