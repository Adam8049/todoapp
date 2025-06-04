<?php
header('Content-Type: application/json');
require_once 'db.php'; // Stellt $conn (PDO) bereit

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// === Hilfsfunktionen ===
function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// === Benutzer-APIs ===

// Registrierung: POST /api.php?action=register
if ($method === 'POST' && ($_GET['action'] ?? '') === 'register') {
    $benutzername = $input['benutzername'] ?? '';
    $passwort = $input['passwort'] ?? '';
    if (!$benutzername || !$passwort) response(['error' => 'Felder fehlen!'], 400);

    // Prüfe, ob Benutzer existiert
    $stmt = $conn->prepare("SELECT id FROM Benutzer WHERE benutzername = ?");
    $stmt->execute([$benutzername]);
    if ($stmt->fetch()) response(['error' => 'Benutzer existiert!'], 409);

    $hash = password_hash($passwort, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Benutzer (benutzername, passwort_hash) VALUES (?, ?)");
    $stmt->execute([$benutzername, $hash]);
    response(['success' => true, 'id' => $conn->lastInsertId()]);
}

// Login: POST /api.php?action=login
if ($method === 'POST' && ($_GET['action'] ?? '') === 'login') {
    $benutzername = $input['benutzername'] ?? '';
    $passwort = $input['passwort'] ?? '';
    if (!$benutzername || !$passwort) response(['error' => 'Felder fehlen!'], 400);

    $stmt = $conn->prepare("SELECT id, passwort_hash FROM Benutzer WHERE benutzername = ?");
    $stmt->execute([$benutzername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($passwort, $user['passwort_hash'])) {
        response(['error' => 'Login fehlgeschlagen!'], 401);
    }
    response(['success' => true, 'user_id' => $user['id']]);
}

// Benutzer-Info: GET /api.php?action=user&id=1
if ($method === 'GET' && ($_GET['action'] ?? '') === 'user' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT id, benutzername FROM Benutzer WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) response(['error' => 'Nicht gefunden'], 404);
    response($user);
}

// === Todo-APIs ===

// Alle Todos eines Users: GET /api.php?benutzer_id=1
if ($method === 'GET' && isset($_GET['benutzer_id'])) {
    $stmt = $conn->prepare("SELECT * FROM Todos WHERE benutzer_id = ?");
    $stmt->execute([$_GET['benutzer_id']]);
    response($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Einzelnes Todo: GET /api.php?todo_id=123
if ($method === 'GET' && isset($_GET['todo_id'])) {
    $stmt = $conn->prepare("SELECT * FROM Todos WHERE id = ?");
    $stmt->execute([$_GET['todo_id']]);
    $todo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$todo) response(['error' => 'Nicht gefunden'], 404);
    response($todo);
}

// Neues Todo: POST /api.php
if ($method === 'POST' && !isset($_GET['action'])) {
    $benutzer_id = $input['benutzer_id'] ?? null;
    $text = $input['text'] ?? null;
    $deadline = $input['deadline'] ?? null;
    if (!$benutzer_id || !$text) response(['error' => 'Felder fehlen!'], 400);

    $stmt = $conn->prepare("INSERT INTO Todos (benutzer_id, text, deadline) VALUES (?, ?, ?)");
    $stmt->execute([$benutzer_id, $text, $deadline]);
    response(['success' => true, 'id' => $conn->lastInsertId()]);
}

// Todo bearbeiten: PUT /api.php?id=123
if ($method === 'PUT' && isset($_GET['id'])) {
    $fields = [];
    $params = [];
    foreach (['text', 'erledigt', 'deadline'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f=?"; $params[] = $input[$f]; }
    }
    if (!$fields) response(['error' => 'Keine Felder'], 400);
    $params[] = $_GET['id'];
    $sql = "UPDATE Todos SET ".implode(', ', $fields)." WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    response(['success' => true]);
}

// Todo löschen: DELETE /api.php?id=123
if ($method === 'DELETE' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM Todos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    response(['success' => true]);
}

response(['error' => 'Ungültige Anfrage'], 400);
?>
