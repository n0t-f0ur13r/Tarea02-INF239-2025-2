<?php
/**
 * Elimina una reseña existente.
 * Requiere:
 *   POST: csrf, type ('func'|'err'), review_id (id reseña)
 * Seguridad:
 *   - Rol ingeniero
 *   - El ingeniero debe estar asignado a la solicitud a la que pertenece la reseña.
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
$rid    = (int)($_POST['review_id'] ?? 0);

if (!in_array($type, ['func','err'], true) || $rid <= 0) {
    http_response_code(400);
    flash_danger('Parámetros inválidos.');
    redirect_back();
}

$pdo = db();
$pdo->beginTransaction();

try {
    $rut = auth_id();

    if ($type === 'func') {
        // Verifica que la reseña pertenece a una solicitud asignada al ingeniero
        $st = $pdo->prepare("
            SELECT rf.id
            FROM resena_func rf
            INNER JOIN ingenieros_solicitud_func isf
                ON isf.id_solicitud_func = rf.id_solicitud_func
               AND isf.rut_ingeniero = :rut
            WHERE rf.id = :rid
            LIMIT 1
        ");
        $st->execute([':rut'=>$rut, ':rid'=>$rid]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException('Reseña no encontrada o sin permisos (Func).');
        }

        $del = $pdo->prepare("DELETE FROM resena_func WHERE id = :rid");
        $del->execute([':rid'=>$rid]);

    } else {
        // Verifica que la reseña pertenece a una solicitud asignada al ingeniero
        $st = $pdo->prepare("
            SELECT re.id
            FROM resena_error re
            INNER JOIN ingenieros_solicitud_error ise
                ON ise.id_solicitud_error = re.id_solicitud_error
               AND ise.rut_ingeniero = :rut
            WHERE re.id = :rid
            LIMIT 1
        ");
        $st->execute([':rut'=>$rut, ':rid'=>$rid]);
        if (!$st->fetchColumn()) {
            throw new RuntimeException('Reseña no encontrada o sin permisos (Error).');
        }

        $del = $pdo->prepare("DELETE FROM resena_error WHERE id = :rid");
        $del->execute([':rid'=>$rid]);
    }

    $pdo->commit();
    flash_success('Reseña eliminada correctamente.');
    redirect_back();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    flash_danger('No se pudo eliminar la reseña: ' . $e->getMessage());
    redirect_back();
}
