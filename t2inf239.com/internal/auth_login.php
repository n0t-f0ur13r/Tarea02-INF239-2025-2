<?php

// /internal/auth_login.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/dbh.inc.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php?e=method');
    exit;
}

// === Datos del formulario ===
$rut = filter_input(INPUT_POST, 'rut', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$pass = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

if (!$rut || !$pass) {
    header('Location: /index.php');
    exit;
}

$pdo = db();

/**
 * Busca un registro por RUT en usuarios o ingenieros.
 * Devuelve [array $row, string $role] o [null, null] si no encuentra.
 */
function find_account_by_rut(PDO $pdo, string $rut): array {
    // Buscar en usuarios
    $stmt = $pdo->prepare("SELECT rut, nombre, email, hash FROM usuarios WHERE rut = :rut LIMIT 1");
    $stmt->bindParam(':rut', $rut);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user)
        return [$user, 'usuario'];

    // Buscar en ingenieros
    $stmt = $pdo->prepare("SELECT rut, nombre, email, hash FROM ingenieros WHERE rut = :rut LIMIT 1");
    $stmt->bindParam(':rut', $rut);
    $stmt->execute();
    $eng = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($eng)
        return [$eng, 'ingeniero'];

    return [null, null];
}

try {
    [$acc, $role] = find_account_by_rut($pdo, $rut);

    if (!$acc) {
        $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'No se encontró una cuenta con ese RUT.'];
        header('Location: /index.php');
        exit;
    }

    $hash = (string) ($acc['hash'] ?? '');
    if ($hash === '' || !password_verify($pass, $hash)) {
        $_SESSION['flash'][] = ['type' => 'warning', 'msg' => 'Usuario o contraseña incorrectos.'];
        header('Location: /index.php');
        exit;
    }

    // Inicia sesión
    login([
        'rut' => (string) $acc['rut'],
        'name' => (string) ($acc['nombre'] ?? ''),
        'email' => (string) ($acc['email'] ?? ''),
        'role' => $role, // 'usuario' o 'ingeniero'
    ]);

    $string = "Bienvenid@ " . $role . " " . (string) ($acc['nombre']);

    $_SESSION['flash'][] = ['type' => 'success', 'msg' => $string];
    // Redirige al dashboard principal
    header('Location: /main.php');
    exit;
} catch (Throwable $e) {
    header('Location: /index.php');
    exit;
}
