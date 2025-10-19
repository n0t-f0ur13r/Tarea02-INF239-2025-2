<?php

// /internal/auth_signup.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/dbh.inc.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php?e=method');
    exit;
}


// Campos esperados desde register.php
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$rut = trim(filter_input(INPUT_POST, 'rut', FILTER_SANITIZE_STRING) ?? '');
$account = trim(filter_input(INPUT_POST, 'account', FILTER_SANITIZE_STRING) ?? 'usuario'); // 'user' | 'engineer' (elige en el formulario)
$pass = (string) (filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING) ?? '');
$pass2 = (string) (filter_input(INPUT_POST, 'password2', FILTER_SANITIZE_STRING) ?? '');

// Validaciones básicas
if ($name === '' || $email === '' || $rut === '' || $pass === '' || $pass2 === '') {
    header('Location: /register.php?e=empty');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /register.php?e=email');
    exit;
}
if ($pass !== $pass2) {
    header('Location: /register.php?e=nomatch');
    exit;
}
if (strlen($pass) < 8) {
    header('Location: /register.php?e=weak');
    exit;
}

$account = ($account === 'usuario') ? 'usuario' : 'ingeniero';
$table = ($account === 'usuario') ? 'usuarios' : 'ingenieros';

$pdo = db();

/**
 * Chequea si email o rut existen en usuarios o ingenieros.
 */
function account_exists_any(PDO $pdo, string $email, string $rut): bool {
    // Usuarios
    $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :email OR rut = :rut LIMIT 1");
    $st->execute([':email' => $email, ':rut' => $rut]);
    if ($st->fetch())
        return true;

    // Ingenieros
    $st = $pdo->prepare("SELECT 1 FROM ingenieros WHERE email = :email OR rut = :rut LIMIT 1");
    $st->execute([':email' => $email, ':rut' => $rut]);
    if ($st->fetch())
        return true;

    return false;
}

try {
    if (account_exists_any($pdo, $email, $rut)) {
        $_SESSION['flash'][] = ['type' => 'warning', 'msg' => 'Ya existe una cuenta con ese RUT o email.'];
        header('Location: /register.php');
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Ambas tablas se asumen con las mismas columnas: rut, nombre, email, hash
    $sql = "INSERT INTO {$table} (rut, nombre, email, hash)
          VALUES (:rut, :nombre, :email, :hash)";
    $params = [
        ':rut' => $rut,
        ':nombre' => $name,
        ':email' => $email,
        ':hash' => $hash,
    ];
    $pdo->prepare($sql)->execute($params);

    // Autologin tras registro
    login([
        'rut' => $rut,
        'name' => $name,
        'email' => $email,
        'role' => $account, // 'usuario' o 'ingeniero'
    ]);

    header('Location: /main.php?ok=signup');
    exit;
} catch (Throwable $e) {
    header('Location: /register.php?e=db');
    exit;
}
