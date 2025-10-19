<?php
/**
 * Crea una reseña para solicitud de funcionalidad o error.
 * Requiere:
 *   POST: csrf, type ('func'|'err'), target (id de solicitud), mensaje (<=400)
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();

function redirect_back(): void {
    $to = $_SERVER['HTTP_REFERER'] ?? '/main.php';
    header('Location: ' . $to);
    exit;
}

if (auth_role() !== 'ingeniero') {
    http_response_code(403);
    flash_danger('Acceso denegado.');
    redirect_back();
}

if (!csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(400);
    flash_danger('CSRF inválido.');
    redirect_back();
}

$type   = ($_POST['type'] ?? '');
$target = (int)($_POST['target'] ?? 0);
$mensaje = trim((string)($_POST['mensaje'] ?? ''));

if (!in_array($type, ['func','err'], true) || $target <= 0) {
    http_response_code(400);
    flash_danger('Parámetros inválidos.');
    redirect_back();
}
if ($mensaje === '' || mb_strlen($mensaje) > 400) {
    http_response_code(400);
    flash_warning('El mensaje es obligatorio (máx. 400 caracteres).');
    redirect_back();
}

$pdo = db();
$pdo->beginTransaction();

try {
    $rut = auth_id();

    if ($type === 'func') {
        // Verifica asignación del ingeniero a la solicitud de funcionalidad
        $st = $pdo->prepare("
            SELECT 1
            FROM ingenieros_solicitud_func
            WHERE rut_ingeniero = :rut AND id_solicitud_func = :id
            LIMIT 1
        ");
        $st->execute([':rut'=>$rut, ':id'=>$target]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException('No está asignado a esta solicitud (Func).');
        }

        // Inserta reseña
        $ins = $pdo->prepare("
            INSERT INTO resena_func (id_solicitud_func, fecha, mensaje)
            VALUES (:id, CURRENT_DATE(), :msg)
        ");
        $ins->execute([':id'=>$target, ':msg'=>$mensaje]);

    } else {
        // Verifica asignación del ingeniero a la solicitud de error
        $st = $pdo->prepare("
            SELECT 1
            FROM ingenieros_solicitud_error
            WHERE rut_ingeniero = :rut AND id_solicitud_error = :id
            LIMIT 1
        ");
        $st->execute([':rut'=>$rut, ':id'=>$target]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException('No está asignado a esta solicitud (Error).');
        }

        // Inserta reseña
        $ins = $pdo->prepare("
            INSERT INTO resena_error (id_solicitud_error, fecha, mensaje)
            VALUES (:id, CURRENT_DATE(), :msg)
        ");
        $ins->execute([':id'=>$target, ':msg'=>$mensaje]);
    }

    $pdo->commit();
    flash_success('Reseña creada correctamente.');
    redirect_back();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    flash_danger('No se pudo crear la reseña: ' . $e->getMessage());
    redirect_back();
}
