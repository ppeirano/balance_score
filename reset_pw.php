<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$email = 'pablo.peirano@temislostalo.com.ar';
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, $email]);

if ($stmt->rowCount() > 0) {
    echo "<h3>Contraseña reseteada correctamente</h3>";
    echo "<p>Email: <b>$email</b></p>";
    echo "<p>Nueva contraseña: <b>$newPassword</b></p>";
    echo "<p><a href='index.php'>Ir al login</a></p>";
} else {
    echo "<h3 style='color:red;'>No se encontró el usuario con email: $email</h3>";
    echo "<p>Verificá que el email sea correcto. Usuarios existentes:</p><ul>";
    $todos = $pdo->query("SELECT email FROM usuarios")->fetchAll();
    foreach ($todos as $u) {
        echo "<li>" . htmlspecialchars($u['email']) . "</li>";
    }
    echo "</ul>";
}
