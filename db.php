<?php
$server   = "server-zrh2.database.windows.net,1433";
$database = "ToDo-Liste";
$username = "serveradmin";
$password = 'Pa$$w0rd'; // ← dein echtes Passwort

try {
    // SQLSRV statt ODBC
    $conn = new PDO(
        "sqlsrv:Server=$server;Database=$database",
        $username,
        $password
    );

    // Fehlerausgabe aktivieren
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Verbindung erfolgreich!";
} catch (PDOException $e) {
    die("❌ Verbindung fehlgeschlagen: " . $e->getMessage());
}
?>
