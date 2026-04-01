<?php

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'perfil' => $_SESSION['user_perfil'],
        'nombre' => $_SESSION['user_nombre'],
        'responsable_id' => $_SESSION['user_responsable_id'] ?? null
    ];
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_perfil'] ?? '') === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php?page=login');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        flash('warning', 'No tenés permiso para acceder a esta sección.');
        redirect('index.php?page=dashboard');
    }
}
