<?php
/**
 * Elimina una reseña existente.
 * Requiere:
 *   POST: csrf, type ('func'|'error'), review_id (id reseña)
 * Seguridad:
 *   - Rol ingeniero
 *   - (Sin autor): NO se valida autoría; sólo que el ingeniero esté asignado a la solicitud a la que pertenece la reseña.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();
require_role('ingeniero');

$type = (string)($_POST['type'] ?? '');
$rid  = (int)   ($_POST['review_id'] ?? 0);

if (!in_array($type, ['func', 'error'], true) || $rid <= 0) {
    flash_danger('Parámetros inválidos.');
    header('Location: /main.php');
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $rut = auth_id();

    if ($type === 'func') {
        // Verificar que la reseña exista y que el ingeniero esté ASIGNADO a la solicitud
        $st = $pdo->prepare("
            SELECT rf.id
            FROM resena_func rf
            INNER JOIN ingenieros_solicitud_func isf
                ON isf.id_solicitud_func = rf.id_solicitud_func
               AND isf.rut_ingeniero = :rut
            WHERE rf.id = :rid
            LIMIT 1
        ");
        $st->bindParam(':rut', $rut, PDO::PARAM_STR);
        $st->bindParam(':rid', $rid, PDO::PARAM_INT);
        $st->execute();
        $exists = (bool)$st->fetchColumn();
        $st->closeCursor();

        if (!$exists) {
            $pdo->rollBack();
            flash_danger('Reseña no encontrada o sin permisos (Func).');
            header('Location: /main.php');
            exit;
        }

        $del = $pdo->prepare("DELETE FROM resena_func WHERE id = :rid");
        $del->bindParam(':rid', $rid, PDO::PARAM_INT);
        $del->execute();
        $del->closeCursor();

    } else {
        // Verificar que la reseña exista y que el ingeniero esté ASIGNADO a la solicitud
        $st = $pdo->prepare("
            SELECT re.id
            FROM resena_error re
            INNER JOIN ingenieros_solicitud_error ise
                ON ise.id_solicitud_error = re.id_solicitud_error
               AND ise.rut_ingeniero = :rut
            WHERE re.id = :rid
            LIMIT 1
        ");
        $st->bindParam(':rut', $rut, PDO::PARAM_STR);
        $st->bindParam(':rid', $rid, PDO::PARAM_INT);
        $st->execute();
        $exists = (bool)$st->fetchColumn();
        $st->closeCursor();

        if (!$exists) {
            $pdo->rollBack();
            flash_danger('Reseña no encontrada o sin permisos (Error).');
            header('Location: /main.php');
            exit;
        }

        $del = $pdo->prepare("DELETE FROM resena_error WHERE id = :rid");
        $del->bindParam(':rid', $rid, PDO::PARAM_INT);
        $del->execute();
        $del->closeCursor();
    }

    $pdo->commit();
    flash_success('Reseña eliminada correctamente.');
    header('Location: /main.php');
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_danger('Error interno, no se pudo eliminar la reseña.');
    error_log('PDOException (delete review): ' . $e->getMessage());
    header('Location: /main.php');
    exit;
}