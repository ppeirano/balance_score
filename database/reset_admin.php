<?php
/**
 * Script para resetear la password del usuario admin.
 * Ejecutar una vez desde el navegador y luego BORRAR este archivo.
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = 'admin@temislostalo.com'");
$stmt->execute([$hash]);

if ($stmt->rowCount() > 0) {
    echo "<h3>Password reseteada correctamente.</h3>";
    echo "<p>Email: <strong>admin@temislostalo.com</strong></p>";
    echo "<p>Password: <strong>$newPassword</strong></p>";
    echo "<p><a href='../index.php?page=login'>Ir al login</a></p>";
    echo "<p style='color:red;'><strong>IMPORTANTE: Borrar este archivo despues de usar.</strong></p>";
} else {
    echo "<h3>No se encontro el usuario admin@temislostalo.com</h3>";
    echo "<p>Asegurate de haber ejecutado la migracion add_usuarios.sql primero.</p>";
}
