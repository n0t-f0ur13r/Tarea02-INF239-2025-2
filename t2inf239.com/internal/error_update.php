<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") != "POST") {
    header("Location: /main.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);

if (empty($id)) {
    header("Location: /main.php");
    exit;
}

$titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
$descripcion = filter_input(INPUT_POST, "descripcion", FILTER_SANITIZE_STRING);
$topico = filter_input(INPUT_POST, "topico", FILTER_SANITIZE_STRING);
$estado = filter_input(INPUT_POST, "estado", FILTER_SANITIZE_STRING);

if (empty($titulo)) {
    flash_warning("Debe ingresar un título.");
    header("Location: /main.php");
    exit;
}

if (empty($descripcion)) {
    flash_warning("Debe ingresar una descripción.");
    header("Location: /main.php");
    exit;
}

if (empty($topico)) {
    flash_warning("Debe seleccionar un tópico.");
    header("Location: /main.php");
    exit;
}

if (empty($estado)) {
    flash_warning("Debe seleccionar un estado.");
    header("Location: /main.php");
    exit;
}

if (mb_strlen($titulo) > 70) {
    flash_warning("Título muy grande. Máximo 70 caracteres.");
    header("Location: /sol_error.php?id={$id}");
    exit;
}

if (mb_strlen($descripcion) > 200) {
    flash_warning("Descripción muy grande. Máximo 200 caracteres.");
    header("Location: /sol_error.php?id={$id}");
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $query = "UPDATE solicitud_error SET titulo=:titulo, descripcion=:descripcion, topico=:topico, estado=:estado WHERE id=:id ;";
    $statement = $pdo->prepare($query);

    $statement->bindParam(":titulo", $titulo);
    $statement->bindParam(":descripcion", $descripcion);
    $statement->bindParam(":topico", $topico);
    $statement->bindParam(":estado", $estado);
    $statement->bindParam(":id", $id);

    $statement->execute();

    $pdo->commit();

    flash_success("Solicitud #{$id} actualizada correctamente.");
    header("Location: /sol_error.php?id={$id}");
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    require_once __DIR__ . "/../includes/pdoErrorInfoSnippet.php";
    checkPDOErrorInfo($e);
    
    error_log('PDOException - ' . $e->getMessage(), 0);

    flash_danger("Error fatal interno, la solicitud no pudo ser actualizada.");
    header("Location: /main.php");
    exit;
}