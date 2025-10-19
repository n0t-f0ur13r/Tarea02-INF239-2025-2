<?php

/**
 * Manejo simple de sesión y roles.
 * Provee helpers: is_logged_in(), auth_user(), auth_id(), auth_role(),
 * require_login(), require_role($role), require_any_role([...]),
 * login($userArr), logout().
 *
 * Convención de sesión:
 *   $_SESSION['user'] = [
 *     'rut'   => string,
 *     'name'  => string,
 *     'email' => string,
 *     'role'  => 'usuario' | 'ingeniero'
 *   ]
 */
if (!defined('AUTH_INC_PHP')) {
    define('AUTH_INC_PHP', 1);

    // === Sesión con parámetros básicos seguros ===
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Ajustes razonables; adapta secure/httponly según tu entorno (HTTPS recomendado)
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    // ===== Helpers de estado de autenticación =====
    function is_logged_in(): bool {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    function auth_user(): ?array {
        return is_logged_in() ? $_SESSION['user'] : null;
    }

    function auth_id(): ?string {
        return is_logged_in() ? ($_SESSION['user']['rut'] ?? null) : null;
    }

    function auth_role(): ?string {
        return is_logged_in() ? ($_SESSION['user']['role'] ?? null) : null;
    }

    // ===== Enforcers (redirecciones simples) =====
    function require_login(string $redirectIfNot = '/index.php?e=auth'): void {
        if (!is_logged_in()) {
            header('Location: ' . $redirectIfNot);
            exit;
        }
    }

    function require_role(string $role, string $redirectIfNot = '/main.php?e=forbidden'): void {
        require_login();
        if (auth_role() !== $role) {
            header('Location: ' . $redirectIfNot);
            exit;
        }
    }

    function require_any_role(array $roles, string $redirectIfNot = '/main.php?e=forbidden'): void {
        require_login();
        if (!in_array(auth_role(), $roles, true)) {
            header('Location: ' . $redirectIfNot);
            exit;
        }
    }

    // ===== Login / Logout (para usar desde /internal/auth_login.php, etc.) =====

    /**
     * @param array $user Estructura mínima: ['rut'=>..., 'name'=>..., 'email'=>..., 'role'=>...]
     */
    function login(array $user): void {
        // Regenera ID para mitigar session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'rut' => (string) ($user['rut'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? 'user'),
        ];
    }

    function logout(string $redirectTo = '/index.php?bye=1'): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: ' . $redirectTo);
        exit;
    }

    // ===== Flash messages muy simples (opcional) =====
    function flash(string $type, string $message): void {
        // 1. Asegurarse de que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Crear el array con el formato correcto
        $flashMessage = [
            'type' => $type,
            'msg' => $message
        ];

        // 3. Añadirlo al array de sesión (apilarlo)
        $_SESSION['flash'][] = $flashMessage;
    }

    function flash_success(string $message): void {
        flash('success', $message);
    }

    function flash_danger(string $message): void {
        flash('danger', $message);
    }

    function flash_warning(string $message): void {
        flash('warning', $message);
    }

    function flash_info(string $message): void {
        flash('info', $message);
    }

    // ===== CSRF mínimo (opcional pero recomendado) =====
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrf_check(?string $token): bool {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

}
