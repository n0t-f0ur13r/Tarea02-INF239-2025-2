<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();
error_log("Comenzar delete", 0);

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") != "POST") {
    header("Location: /main.php");
    error_log("No es post");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);

if (empty($id)) {
    header("Location: /main.php");
    error_log("No hay id");
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $query = "DELETE FROM solicitud_error WHERE id = :id ;";
    $statement = $pdo->prepare($query);

    $statement->bindParam(":id", $id);

    $statement->execute();

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
}