<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$error = null;
$conversations = [];

try {
    $pdo = createPdo();
    $stmt = $pdo->query(
        'SELECT
            c.id,
            c.title,
            c.created_at,
            c.updated_at,
            COUNT(m.id) AS message_count,
            MAX(m.created_at) AS last_message_at
         FROM conversations c
         LEFT JOIN messages m ON m.conversation_id = c.id
         GROUP BY c.id, c.title, c.created_at, c.updated_at
         ORDER BY c.updated_at DESC, c.id DESC'
    );
    $conversations = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chat List - Ollama Chat</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-zinc-100 text-zinc-900">
  <header class="border-b border-zinc-300 px-4 py-3">
    <div class="mx-auto flex w-full max-w-5xl items-end justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Chat List</h1>
        <p class="text-sm text-zinc-600">Saved conversations</p>
      </div>
      <a class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" href="../index.php">Back</a>
    </div>
  </header>

  <main class="mx-auto w-full max-w-5xl px-4 py-5 sm:px-6">
    <?php if ($error !== null): ?>
      <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= h($error) ?>
      </div>
    <?php elseif ($conversations === []): ?>
      <div class="rounded-md border border-zinc-300 bg-white px-4 py-8 text-center text-sm text-zinc-500">
        Saved chats will appear here.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto rounded-md border border-zinc-300 bg-white shadow-sm">
        <table class="w-full border-collapse text-left text-sm">
          <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500">
            <tr>
              <th class="px-4 py-3 font-semibold">ID</th>
              <th class="px-4 py-3 font-semibold">Title</th>
              <th class="px-4 py-3 font-semibold">Messages</th>
              <th class="px-4 py-3 font-semibold">Created</th>
              <th class="px-4 py-3 font-semibold">Updated</th>
              <th class="px-4 py-3 font-semibold"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($conversations as $conversation): ?>
              <tr class="border-b border-zinc-100 last:border-0">
                <td class="px-4 py-3 text-zinc-500"><?= h((string) $conversation['id']) ?></td>
                <td class="max-w-md px-4 py-3 font-medium">
                  <?= h($conversation['title'] ?: 'Untitled Chat') ?>
                </td>
                <td class="px-4 py-3 text-zinc-600"><?= h((string) $conversation['message_count']) ?></td>
                <td class="px-4 py-3 text-zinc-600"><?= h($conversation['created_at']) ?></td>
                <td class="px-4 py-3 text-zinc-600"><?= h($conversation['updated_at']) ?></td>
                <td class="px-4 py-3 text-right">
                  <a class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50" href="show.php?id=<?= h((string) $conversation['id']) ?>">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
