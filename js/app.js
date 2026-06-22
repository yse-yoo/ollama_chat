const DEFAULT_SERVER_URL = "http://172.16.3.6:11434";
const MODELS = ["qwen3:8b", "qwen2.5-coder:7b", "gemma3:4b", "qwen3:4b"];
const DEFAULT_MODEL = MODELS[0];

const settingsForm = document.querySelector("#settingsForm");
const serverUrlInput = document.querySelector("#serverUrl");
const modelNameInput = document.querySelector("#modelName");
const chatForm = document.querySelector("#chatForm");
const messageInput = document.querySelector("#messageInput");
const sendButton = document.querySelector("#sendButton");
const messagesEl = document.querySelector("#messages");

const state = {
  serverUrl: localStorage.getItem("ollamaChat.serverUrl") || DEFAULT_SERVER_URL,
  model: getSavedModel(),
  messages: [],
  loading: false,
};

serverUrlInput.value = state.serverUrl;
modelNameInput.value = state.model;

renderEmptyState();

settingsForm.addEventListener("submit", (event) => {
  event.preventDefault();

  state.serverUrl = normalizeServerUrl(serverUrlInput.value);
  state.model = MODELS.includes(modelNameInput.value) ? modelNameInput.value : DEFAULT_MODEL;

  serverUrlInput.value = state.serverUrl;
  modelNameInput.value = state.model;
  localStorage.setItem("ollamaChat.serverUrl", state.serverUrl);
  localStorage.setItem("ollamaChat.model", state.model);

  showNotice("Settings saved.");
});

chatForm.addEventListener("submit", async (event) => {
  event.preventDefault();

  const content = messageInput.value.trim();
  if (!content || state.loading) return;

  appendMessage("user", content);
  messageInput.value = "";
  setLoading(true);

  const assistantMessage = appendMessage("assistant", "");

  try {
    await streamChatResponse(assistantMessage);
  } catch (error) {
    assistantMessage.content = `Error: ${error.message}`;
    assistantMessage.element.querySelector("[data-content]").textContent = assistantMessage.content;
  } finally {
    setLoading(false);
    messageInput.focus();
  }
});

messageInput.addEventListener("keydown", (event) => {
  if (event.key === "Enter" && (event.ctrlKey || event.metaKey)) {
    chatForm.requestSubmit();
  }
});

async function streamChatResponse(assistantMessage) {
  const chatHistory = state.messages
    .filter((message) => message !== assistantMessage)
    .map(({ role, content }) => ({ role, content }));

  const response = await fetch(`${state.serverUrl}/api/chat`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      model: state.model,
      messages: chatHistory,
      stream: true,
    }),
  });

  if (!response.ok) {
    const detail = await response.text();
    throw new Error(`${response.status} ${response.statusText}${detail ? ` - ${detail}` : ""}`);
  }

  if (!response.body) {
    throw new Error("Response body is not readable.");
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = "";

  while (true) {
    const { value, done } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });
    const lines = buffer.split("\n");
    buffer = lines.pop() || "";

    for (const line of lines) {
      if (!line.trim()) continue;

      const chunk = JSON.parse(line);
      const token = chunk.message?.content || "";
      if (token) updateAssistantMessage(assistantMessage, token);
      if (chunk.done) return;
    }
  }
}

function appendMessage(role, content, options = {}) {
  clearEmptyState();

  const message = { role, content };
  if (options.persist !== false) {
    state.messages.push(message);
  }

  const wrapper = document.createElement("article");
  wrapper.className = role === "user" ? "flex justify-end" : "flex justify-start";

  const bubble = document.createElement("div");
  bubble.className =
    role === "user"
      ? "max-w-[85%] whitespace-pre-wrap rounded-md bg-emerald-700 px-4 py-3 text-sm leading-6 text-white"
      : "max-w-[85%] whitespace-pre-wrap rounded-md bg-zinc-100 px-4 py-3 text-sm leading-6 text-zinc-900";

  const label = document.createElement("div");
  label.className =
    role === "user"
      ? "mb-1 text-xs font-semibold text-emerald-100"
      : "mb-1 text-xs font-semibold text-zinc-500";
  label.textContent = role === "user" ? "You" : "Ollama";

  const body = document.createElement("div");
  body.dataset.content = "true";
  body.textContent = content || "Thinking...";

  bubble.append(label, body);
  wrapper.appendChild(bubble);
  messagesEl.appendChild(wrapper);
  messagesEl.scrollTop = messagesEl.scrollHeight;

  message.element = wrapper;
  return message;
}

function updateAssistantMessage(message, token) {
  message.content += token;
  message.element.querySelector("[data-content]").textContent = message.content;
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function setLoading(isLoading) {
  state.loading = isLoading;
  sendButton.disabled = isLoading;
  sendButton.textContent = isLoading ? "Sending" : "Send";
}

function showNotice(text) {
  const notice = appendMessage("assistant", text, { persist: false });
  notice.element.querySelector("[data-content]").classList.add("text-zinc-600");
}

function renderEmptyState() {
  messagesEl.innerHTML = `
    <div data-empty-state class="flex h-full min-h-64 items-center justify-center text-center text-sm text-zinc-500">
      <p>Send a message to start chatting.</p>
    </div>
  `;
}

function clearEmptyState() {
  const emptyState = messagesEl.querySelector("[data-empty-state]");
  if (emptyState) emptyState.remove();
}

function normalizeServerUrl(value) {
  return (value.trim() || DEFAULT_SERVER_URL).replace(/\/+$/, "");
}

function getSavedModel() {
  const savedModel = localStorage.getItem("ollamaChat.model");
  return MODELS.includes(savedModel) ? savedModel : DEFAULT_MODEL;
}
