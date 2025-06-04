<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $benutzername = $_POST["benutzername"];
    $passwort = password_hash($_POST["passwort"], PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO Benutzer (benutzername, passwort_hash) VALUES (?, ?)");
        $stmt->execute([$benutzername, $passwort]);
        echo "✅ Registrierung erfolgreich. <a href='index.php'>Zum Login</a>";
        exit;
    } catch (PDOException $e) {
        $error = "❌ Fehler: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Registrieren</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
  <div class="auth-box">
    <h2>Registrieren</h2>
    <?php if (!empty($error)) echo "<p>$error</p>"; ?>
    <form method="post">
      <input type="text" name="benutzername" placeholder="Benutzername" required>
      <input type="password" name="passwort" placeholder="Passwort" required>
      <button type="submit">Registrieren</button>
    </form>
    <div class="link">
      <a href="index.php">Zurück zum Login</a>
    </div>
  </div>
</body>
</html>
