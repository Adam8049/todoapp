<?php
require 'db.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 1200) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT benutzername FROM Benutzer WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $user["benutzername"] ?? "Unbekannt";

// CRUD
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["text"])) {
    $text = trim($_POST["text"]);
    $deadline = !empty($_POST["deadline"]) ? $_POST["deadline"] : null;
    $id = $_POST["id"] ?? null;

    if (strlen($text) > 0 && strlen($text) <= 255) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE Todos SET text = ?, deadline = ? WHERE id = ? AND benutzer_id = ?");
            $stmt->execute([$text, $deadline, $id, $user_id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO Todos (benutzer_id, text, deadline) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $text, $deadline]);
        }
    }
}

foreach (["erledigt", "offen", "delete"] as $action) {
    if (isset($_GET[$action])) {
        $id = $_GET[$action];
        switch ($action) {
            case "erledigt":
                $stmt = $conn->prepare("UPDATE Todos SET erledigt = 1 WHERE id = ? AND benutzer_id = ?");
                break;
            case "offen":
                $stmt = $conn->prepare("UPDATE Todos SET erledigt = 0 WHERE id = ? AND benutzer_id = ?");
                break;
            case "delete":
                $stmt = $conn->prepare("DELETE FROM Todos WHERE id = ? AND benutzer_id = ?");
                break;
        }
        $stmt->execute([$id, $user_id]);
    }
}

$editTask = null;
if (isset($_GET["edit"])) {
    $stmt = $conn->prepare("SELECT * FROM Todos WHERE id = ? AND benutzer_id = ?");
    $stmt->execute([$_GET["edit"], $user_id]);
    $editTask = $stmt->fetch(PDO::FETCH_ASSOC);
}

$filter = $_GET["filter"] ?? "alle";
$sql = match ($filter) {
    "offen" => "SELECT * FROM Todos WHERE benutzer_id = ? AND erledigt = 0",
    "erledigt" => "SELECT * FROM Todos WHERE benutzer_id = ? AND erledigt = 1",
    default => "SELECT * FROM Todos WHERE benutzer_id = ?",
};

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($todos);
$done = count(array_filter($todos, fn($t) => $t["erledigt"]));
$open = $total - $done;
$percent = $total > 0 ? round($done / $total * 100) : 0;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>ToDo App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<a class="logout-button" href="logout.php">Abmelden</a>

<div class="wrapper">
    <div class="panel form-panel">
        <h2>Aufgabe</h2>
        <form method="post">
            <input type="text" name="text" placeholder="Aufgabe" required maxlength="255"
                   value="<?= $editTask ? htmlspecialchars($editTask["text"]) : "" ?>">
            <input type="date" name="deadline" value="<?= $editTask["deadline"] ?? "" ?>">
            <?php if ($editTask): ?>
                <input type="hidden" name="id" value="<?= $editTask["id"] ?>">
                <button>Speichern</button>
                <a class="cancel-btn" href="todo.php">Abbrechen</a>
            <?php else: ?>
                <button>Hinzufügen</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel list-panel">
        <h2>Hallo <?= htmlspecialchars($username) ?></h2>
        <div class="stats">
            <span><?= $total ?> Aufgaben</span>
            <span><?= $done ?> erledigt</span>
            <span><?= $open ?> offen</span>
        </div>
        <div class="progress-bar">
        <div class="bar" style="width: <?= $percent ?>%"></div>
        </div>
        <div class="filters">
            <a href="?filter=alle" class="<?= $filter === 'alle' ? 'active' : '' ?>">Alle</a>
            <a href="?filter=offen" class="<?= $filter === 'offen' ? 'active' : '' ?>">Offene</a>
            <a href="?filter=erledigt" class="<?= $filter === 'erledigt' ? 'active' : '' ?>">Erledigte</a>
        </div>

        <input type="text" id="search" placeholder="Suche..." onkeyup="filterTasks()">

        <ul id="taskList">
            <?php foreach ($todos as $row): ?>
                <li class="<?= $row["erledigt"] ? 'done' : '' ?>">
                    <div>
                        <?= $row["erledigt"] ? "<s>" : "" ?>
                        <?= htmlspecialchars($row["text"]) ?>
                        <?php if ($row["deadline"]): ?>
                            <small>(Fällig: <?= $row["deadline"] ?>)</small>
                        <?php endif; ?>
                        <?= $row["erledigt"] ? "</s>" : "" ?>
                    </div>
                    <div class="btn-group">
                        <?php if (!$row["erledigt"]): ?>
                            <a href="?erledigt=<?= $row["id"] ?>"><img src="icons/check.png" alt="Erledigt" /></a>
                        <?php else: ?>
                            <a href="?offen=<?= $row["id"] ?>"><img src="icons/undo.png" alt="Offen" /></a>
                        <?php endif; ?>
                        <a href="?edit=<?= $row["id"] ?>"><img src="icons/edit.png" alt="Bearbeiten" /></a>
                        <a href="?delete=<?= $row["id"] ?>"><img src="icons/delete.png" alt="Löschen" /></a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
function filterTasks() {
    const input = document.getElementById("search").value.toLowerCase();
    const items = document.querySelectorAll("#taskList li");
    items.forEach(li => {
        li.style.display = li.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>
<script>
function filterTasks() {
    const input = document.getElementById("search").value.toLowerCase();
    const items = document.querySelectorAll("#taskList li");
    items.forEach(li => {
        li.style.display = li.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

// Fortschrittsbalken animieren
window.addEventListener('DOMContentLoaded', () => {
    const bar = document.querySelector('.progress-bar .bar');
    const percent = document.querySelector('.progress-bar').dataset.percent;
    setTimeout(() => {
        bar.style.transition = 'width 1s ease';
        bar.style.width = percent + '%';
    }, 100); // kleiner Delay für sanfte Animation
});
</script>
</body>
</html>
