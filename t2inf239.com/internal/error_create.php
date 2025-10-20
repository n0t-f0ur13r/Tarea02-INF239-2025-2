<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';


if (filter_input(INPUT_SERVER, "REQUEST_METHOD") != "post") {
    if (empty($_SESSION['user'])) {
        header("Location: /index.php");
    } else {
        header("Location: /main.php");
    }
}


require_login();
require_role("usuario");


$titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
$descripcion = filter_input(INPUT_POST, "descripcion", FILTER_SANITIZE_STRING);
$topico = filter_input(INPUT_POST, "topico", FILTER_SANITIZE_STRING);

if (empty($titulo)) {
    flash_warning("Debe ingresar un título.");
    header("Location: /main.php");
    exit;
}

if (empty($descripcion)) {
    flash_warning("Debe ingresar descripción.");
    header("Location: /main.php");
    exit;
}

if (empty($topico)) {
    flash_warning("Debe seleccionar un tópico.");
    header("Location: /main.php");
    exit;
}

$rut = $_SESSION['user']['rut'];
$pub_date = date('Y-m-d');
$estado = "Abierto";

try {

    $query = "INSERT INTO solicitud_error (titulo, descripcion, pub_date, topico, estado, rut_autor) "
            . "VALUES (:titulo, :descripcion, :pub_date, :topico, :estado, :rut_autor);";

    $pdo = db();

    $pdo->beginTransaction();

    $statement = $pdo->prepare($query);
    $statement->bindParam(":titulo", $titulo);
    $statement->bindParam(":descripcion", $descripcion);
    $statement->bindParam(":pub_date", $pub_date);
    $statement->bindParam(":topico", $topico);
    $statement->bindParam(":estado", $estado);
    $statement->bindParam(":rut_autor", $rut);
    $statement->execute();

    $last_id = $pdo->lastInsertId();

    $pdo->commit();

    flash_success("Solicitud de error #{$last_id}. creada exitosamente!");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    require_once __DIR__ . "/../includes/pdoErrorInfoSnippet.php";
    checkPDOErrorInfo($pdo);

    error_log('PDOException - ' . $e->getMessage(), 0);

    flash_danger("Error fatal interno, la solicitud no pudo ser creada.");
    header("Location: /main.php");
}
