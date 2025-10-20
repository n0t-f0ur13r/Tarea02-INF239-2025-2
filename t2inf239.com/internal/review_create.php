<?php
/**
 * Crea una reseña para solicitud de funcionalidad o error.
 * Requiere:
 *   POST: type ('func'|'error'), target (id de solicitud), mensaje (<=400)
 * Seguridad:
 *   - Rol ingeniero
 *   - (Sin autor): NO se asocia a persona; sólo se valida que el ingeniero esté asignado a la solicitud.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();
require_role('ingeniero');

$type     = (string)($_POST['type']   ?? '');
$targetId = (int)   ($_POST['target'] ?? 0);
$mensaje  = trim((string)($_POST['mensaje'] ?? ''));

if (!in_array($type, ['func', 'error'], true) || $targetId <= 0) {
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
        // Validar asignación del ingeniero a la solicitud de funcionalidad
        $st = $pdo->prepare("
            SELECT 1
            FROM ingenieros_solicitud_func
            WHERE rut_ingeniero = :rut
              AND id_solicitud_func = :sid
            LIMIT 1
        ");
        $st->bindParam(':rut', $rut, PDO::PARAM_STR);
        $st->bindParam(':sid', $targetId, PDO::PARAM_INT);
        $st->execute();
        $asignado = (bool)$st->fetchColumn();
        $st->closeCursor();

        if (!$asignado) {
            $pdo->rollBack();
            flash_danger('No está asignado a esta solicitud (Func).');
            header('Location: /main.php');
            exit;
        }

        // Insertar reseña (sin autor, ligada sólo a la solicitud)
        $ins = $pdo->prepare("
            INSERT INTO resena_func (id_solicitud_func, fecha, mensaje)
            VALUES (:sid, CURRENT_DATE(), :msg)
        ");
        $ins->bindParam(':sid', $targetId, PDO::PARAM_INT);
        $ins->bindParam(':msg', $mensaje, PDO::PARAM_STR);
        $ins->execute();
        $newId = (int)$pdo->lastInsertId();
        $ins->closeCursor();

    } else {
        // Validar asignación del ingeniero a la solicitud de error
        $st = $pdo->prepare("
            SELECT 1
            FROM ingenieros_solicitud_error
            WHERE rut_ingeniero = :rut
              AND id_solicitud_error = :sid
            LIMIT 1
        ");
        $st->bindParam(':rut', $rut, PDO::PARAM_STR);
        $st->bindParam(':sid', $targetId, PDO::PARAM_INT);
        $st->execute();
        $asignado = (bool)$st->fetchColumn();
        $st->closeCursor();

        if (!$asignado) {
            $pdo->rollBack();
            flash_danger('No está asignado a esta solicitud (Error).');
            header('Location: /main.php');
            exit;
        }

        // Insertar reseña (sin autor, ligada sólo a la solicitud)
        $ins = $pdo->prepare("
            INSERT INTO resena_error (id_solicitud_error, fecha, mensaje)
            VALUES (:sid, CURRENT_DATE(), :msg)
        ");
        $ins->bindParam(':sid', $targetId, PDO::PARAM_INT);
        $ins->bindParam(':msg', $mensaje, PDO::PARAM_STR);
        $ins->execute();
        $newId = (int)$pdo->lastInsertId();
        $ins->closeCursor();
    }

    $pdo->commit();
    flash_success('Reseña creada correctamente.');
    header("Location: /resena.php?id={$newId}&kind={$type}");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_danger('Error interno, no se pudo crear la reseña.');
    error_log('PDOException (create review): ' . $e->getMessage());
    header('Location: /main.php');
    exit;
}