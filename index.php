<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $benutzername = $_POST["benutzername"];
    $passwort = $_POST["passwort"];

    try {
        $stmt = $conn->prepare("SELECT * FROM Benutzer WHERE benutzername = ?");
        $stmt->execute([$benutzername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($passwort, $user["passwort_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            header("Location: todo.php");
            exit;
        } else {
            $error = "❌ Login fehlgeschlagen.";
        }
    } catch (PDOException $e) {
        $error = "Fehler beim Login: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <div class="auth-box">
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<p>$error</p>"; ?>
    <form method="post">
      <input type="text" name="benutzername" placeholder="Benutzername" required>
      <input type="password" name="passwort" placeholder="Passwort" required>
      <button type="submit">Login</button>
    </form>
    <div class="link">
      <a href="register.php">Noch kein Konto? Jetzt registrieren</a>
    </div>
  </div>
</body>
</html>
