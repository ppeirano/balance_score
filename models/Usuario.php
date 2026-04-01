<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Usuario {

    static function login($pdo, $email, $password) {
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre AS responsable_nombre, r.cargo AS responsable_cargo
            FROM usuarios u
            LEFT JOIN responsables r ON r.id = u.responsable_id
            WHERE u.email = ? AND u.activo = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_perfil'] = $user['perfil'];
        $_SESSION['user_nombre'] = $user['responsable_nombre'] ?? $user['email'];
        $_SESSION['user_responsable_id'] = $user['responsable_id'];

        return $user;
    }

    static function logout() {
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_perfil'],
              $_SESSION['user_nombre'], $_SESSION['user_responsable_id']);
    }

    static function getAll($pdo) {
        return $pdo->query("
            SELECT u.*, r.nombre AS responsable_nombre, r.cargo AS responsable_cargo
            FROM usuarios u
            LEFT JOIN responsables r ON r.id = u.responsable_id
            ORDER BY u.email
        ")->fetchAll();
    }

    static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre AS responsable_nombre
            FROM usuarios u
            LEFT JOIN responsables r ON r.id = u.responsable_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    static function guardar($pdo, $data) {
        if (!empty($data['id'])) {
            if (!empty($data['password'])) {
                $stmt = $pdo->prepare("
                    UPDATE usuarios SET email = ?, password_hash = ?, responsable_id = ?, perfil = ?, activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['email'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['responsable_id'] ?: null,
                    $data['perfil'] ?? 'consultor',
                    $data['activo'] ?? 1,
                    $data['id']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios SET email = ?, responsable_id = ?, perfil = ?, activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['email'],
                    $data['responsable_id'] ?: null,
                    $data['perfil'] ?? 'consultor',
                    $data['activo'] ?? 1,
                    $data['id']
                ]);
            }
            flash('success', 'Usuario actualizado.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (email, password_hash, responsable_id, perfil, activo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['responsable_id'] ?: null,
                $data['perfil'] ?? 'consultor',
                $data['activo'] ?? 1
            ]);
            flash('success', 'Usuario creado.');
        }
        redirect('index.php?page=admin_usuarios');
    }
}
