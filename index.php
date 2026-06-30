<!doctype html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ollama Chat</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-zinc-100 text-zinc-900">
  <header class="mb-4 px-4 py-3 flex flex-col gap-3 border-b border-zinc-300 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Ollama Chat</h1>
      <a class="text-sm text-zinc-600 underline hover:text-zinc-900" href="chat/">Chat List</a>
    </div>
    <form id="settingsForm" class="grid gap-2 sm:grid-cols-[minmax(220px,1fr)_minmax(160px,220px)_auto] md:w-[680px]">
      <label class="block text-sm">
        <span class="mr-2 font-medium text-zinc-700">Server</span>
        <span id="server-url" class="text-xs text-zinc-500"></span>
        <span id="connection-status" class="inline-block text-xs text-red-500">NG</span>
      </label>
      <select id="modelName"
        class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm">
      </select>
      <button
        class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:bg-zinc-400"
        type="submit">
        Save
      </button>
    </form>
  </header>

  <main class="mx-auto flex w-full max-w-5xl flex-col px-4 py-4 sm:px-6">
    <section id="messages"
      class="flex-1 space-y-3 overflow-y-auto rounded-md border border-zinc-300 bg-white p-4 shadow-sm"></section>

    <form id="chatForm" class="mt-4 flex gap-2">
      <textarea id="messageInput"
        class="min-h-12 flex-1 resize-y rounded-md border border-zinc-300 bg-white px-3 py-3 text-sm outline-none focus:border-zinc-700"
        rows="2" placeholder="Type a message..."></textarea>
      <button id="saveChatButton"
        class="h-12 rounded-md bg-zinc-800 px-5 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:bg-zinc-400"
        type="button" disabled>
        Save Chat
      </button>
      <button id="sendButton"
        class="h-12 rounded-md bg-emerald-700 px-5 text-sm font-medium text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-zinc-400"
        type="submit">
        Send
      </button>
    </form>
  </main>

  <script src="js/env.js?<?= time() ?>"></script>
  <script src="js/app.js?<?= time() ?>"></script>
</body>

</html>
