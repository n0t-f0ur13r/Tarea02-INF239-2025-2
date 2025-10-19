<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") !== "POST") {
    header("Location: /main.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);

if (empty($id)) {
    header("Location: /main.php");
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $sql = "DELETE FROM solicitud_func WHERE id = :id";
    $st  = $pdo->prepare($sql);
    $st->bindParam(":id", $id, PDO::PARAM_INT);
    $st->execute();

    $pdo->commit();

    flash_success("Solicitud #{$id} eliminada correctamente.");
    header("Location: /main.php");
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException - ' . $e->getMessage(), 0);
    flash_danger("Error fatal interno, la solicitud no pudo ser eliminada.");
    header("Location: /main.php");
    exit;
}
