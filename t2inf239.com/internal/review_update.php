<?php
/**
 * Actualiza el mensaje de una reseña existente.
 * Requiere:
 *   POST: csrf, type ('func'|'error'), review_id (id reseña), mensaje (<=400)
 * Seguridad:
 *   - Rol ingeniero
 *   - (Sin autor): NO se valida autoría de la reseña; sólo que el ingeniero esté asignado a la solicitud a la que pertenece.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();
require_role('ingeniero');

$type    = (string)($_POST['type'] ?? '');
$rid     = (int)   ($_POST['review_id'] ?? 0);
$mensaje = trim((string)($_POST['mensaje'] ?? ''));

if (!in_array($type, ['func', 'error'], true) || $rid <= 0) {
    flash_danger('Parámetros inválidos.');
    header('Location: /main.php');
    exit;
}

if ($mensaje === '' || mb_strlen($mensaje) > 400) {
    flash_warning('El mensaje es obligatorio (máx. 400 caracteres).');
    header('Location: /main.php');
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $rut = auth_id();

    if ($type === 'func') {
        // Verificar que la reseña exista y que el ingeniero esté ASIGNADO a la solicitud de esa reseña
        $st = $pdo->prepare("
            SELECT rf.id, rf.id_solicitud_func
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
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $st->closeCursor();

        if (!$row) {
            $pdo->rollBack();
            flash_danger('Reseña no encontrada o sin permisos (Func).');
            header('Location: /main.php');
            exit;
        }

        $up = $pdo->prepare("
            UPDATE resena_func
               SET mensaje = :msg, fecha = CURRENT_DATE()
             WHERE id = :rid
        ");
        $up->bindParam(':msg', $mensaje, PDO::PARAM_STR);
        $up->bindParam(':rid', $rid, PDO::PARAM_INT);
        $up->execute();
        $up->closeCursor();

    } else {
        // Verificar que la reseña exista y que el ingeniero esté ASIGNADO a la solicitud de esa reseña
        $st = $pdo->prepare("
            SELECT re.id, re.id_solicitud_error
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
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $st->closeCursor();

        if (!$row) {
            $pdo->rollBack();
            flash_danger('Reseña no encontrada o sin permisos (Error).');
            header('Location: /main.php');
            exit;
        }

        $up = $pdo->prepare("
            UPDATE resena_error
               SET mensaje = :msg, fecha = CURRENT_DATE()
             WHERE id = :rid
        ");
        $up->bindParam(':msg', $mensaje, PDO::PARAM_STR);
        $up->bindParam(':rid', $rid, PDO::PARAM_INT);
        $up->execute();
        $up->closeCursor();
    }

    $pdo->commit();
    flash_success('Reseña actualizada correctamente.');
    header("Location: /resena.php?id={$rid}&kind={$type}");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_danger('Error interno, no se pudo actualizar la reseña.');
    error_log('PDOException (update review): ' . $e->getMessage());
    header('Location: /main.php');
    exit;
}