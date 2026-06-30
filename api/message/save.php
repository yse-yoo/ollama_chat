<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed.']);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond(400, ['error' => 'Invalid JSON body.']);
}

$conversationId = normalizeNullableInt($input['conversation_id'] ?? null);
$title = normalizeNullableString($input['title'] ?? null);
$messages = normalizeMessages($input);

if ($messages === []) {
    respond(400, ['error' => 'At least one message is required.']);
}

try {
    $pdo = createPdo();
    $pdo->beginTransaction();

    if ($conversationId === null) {
        $conversationId = findConversationIdByMessageUuids(
            $pdo,
            array_column($messages, 'uuid')
        );

        if ($conversationId === null) {
            $stmt = $pdo->prepare('INSERT INTO conversations (title) VALUES (:title)');
            $stmt->execute(['title' => $title]);
            $conversationId = (int) $pdo->lastInsertId();
        } elseif ($title !== null) {
            $stmt = $pdo->prepare('UPDATE conversations SET title = :title WHERE id = :id');
            $stmt->execute([
                'id' => $conversationId,
                'title' => $title,
            ]);
        }
    } else {
        $stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = :id');
        $stmt->execute(['id' => $conversationId]);

        if ($stmt->fetchColumn() === false) {
            throw new RuntimeException('Conversation not found.', 404);
        }

        if ($title !== null) {
            $stmt = $pdo->prepare('UPDATE conversations SET title = :title WHERE id = :id');
            $stmt->execute([
                'id' => $conversationId,
                'title' => $title,
            ]);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO messages (uuid, conversation_id, role, content)
         VALUES (:uuid, :conversation_id, :role, :content)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );

    $messageIds = [];
    $messageUuids = [];

    foreach ($messages as $message) {
        $stmt->execute([
            'uuid' => $message['uuid'],
            'conversation_id' => $conversationId,
            'role' => $message['role'],
            'content' => $message['content'],
        ]);

        $messageIds[] = (int) $pdo->lastInsertId();
        $messageUuids[] = $message['uuid'];
    }

    $pdo->commit();

    respond(200, [
        'conversation_id' => $conversationId,
        'message_ids' => $messageIds,
        'message_uuids' => $messageUuids,
    ]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status = $error->getCode();
    if (!is_int($status) || $status < 400 || $status > 599) {
        $status = 500;
    }

    respond($status, ['error' => $error->getMessage()]);
}

function findConversationIdByMessageUuids(PDO $pdo, array $uuids): ?int
{
    if ($uuids === []) {
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($uuids), '?'));
    $stmt = $pdo->prepare(
        "SELECT conversation_id FROM messages WHERE uuid IN ($placeholders) ORDER BY id LIMIT 1"
    );
    $stmt->execute($uuids);

    $conversationId = $stmt->fetchColumn();
    return $conversationId === false ? null : (int) $conversationId;
}

function normalizeMessages(array $input): array
{
    $rawMessages = $input['messages'] ?? null;

    if (is_array($rawMessages)) {
        $messages = $rawMessages;
    } else {
        $messages = [[
            'role' => $input['role'] ?? null,
            'content' => $input['content'] ?? null,
        ]];
    }

    $normalized = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            throw new RuntimeException('Message must be an object.', 400);
        }

        $role = normalizeString($message['role'] ?? null);
        $content = normalizeString($message['content'] ?? null);
        $uuid = normalizeUuid($message['uuid'] ?? null);

        if (!in_array($role, ['user', 'assistant', 'system'], true)) {
            throw new RuntimeException('Invalid message role.', 400);
        }

        if ($content === '') {
            throw new RuntimeException('Message content is required.', 400);
        }

        $normalized[] = [
            'uuid' => $uuid,
            'role' => $role,
            'content' => $content,
        ];
    }

    return $normalized;
}

function normalizeString(mixed $value): string
{
    return is_string($value) ? trim($value) : '';
}

function normalizeNullableString(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    return $value === '' ? null : $value;
}

function normalizeNullableInt(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        throw new RuntimeException('Invalid conversation_id.', 400);
    }

    return (int) $value;
}

function normalizeUuid(mixed $value): string
{
    if ($value === null || $value === '') {
        throw new RuntimeException('Message uuid is required.', 400);
    }

    if (!is_string($value)) {
        throw new RuntimeException('Invalid message uuid.', 400);
    }

    $value = strtolower(trim($value));

    if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $value) !== 1) {
        throw new RuntimeException('Invalid message uuid.', 400);
    }

    return $value;
}

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
