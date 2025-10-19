<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dbh.inc.php';

require_login();

if (filter_input(INPUT_SERVER, "REQUEST_METHOD") !== "POST") {
    header("Location: /main.php");
    exit;
}

$id      = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);
$titulo  = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
$resumen = filter_input(INPUT_POST, "resumen", FILTER_SANITIZE_STRING);
$topico  = filter_input(INPUT_POST, "topico", FILTER_SANITIZE_STRING);
$dev_env = filter_input(INPUT_POST, "dev_env", FILTER_SANITIZE_STRING);
$estado  = filter_input(INPUT_POST, "estado", FILTER_SANITIZE_STRING);

if (empty($id)) {
    header("Location: /main.php");
    exit;
}
if (empty($titulo)) {
    flash_warning("Debe ingresar un título.");
    header("Location: /main.php");
    exit;
}
if (empty($resumen)) {
    flash_warning("Debe ingresar un resumen.");
    header("Location: /main.php");
    exit;
}
if (empty($topico)) {
    flash_warning("Debe seleccionar un tópico.");
    header("Location: /main.php");
    exit;
}
if (empty($dev_env)) {
    flash_warning("Debe seleccionar un entorno.");
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
    header("Location: /sol_func.php?id={$id}");
    exit;
}
if (mb_strlen($resumen) > 150) {
    flash_warning("Resumen muy grande. Máximo 150 caracteres.");
    header("Location: /sol_func.php?id={$id}");
    exit;
}
if (mb_strlen($topico) > 25) {
    flash_warning("Tópico inválido (máximo 25 caracteres).");
    header("Location: /sol_func.php?id={$id}");
    exit;
}
if (mb_strlen($dev_env) > 25) {
    flash_warning("Entorno inválido (máximo 25 caracteres).");
    header("Location: /sol_func.php?id={$id}");
    exit;
}

$estados_validos = ['Abierto','En Progreso','Resuelto','Cerrado'];
if (!in_array($estado, $estados_validos, true)) {
    flash_warning("Estado inválido.");
    header("Location: /sol_func.php?id={$id}");
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $sql = "UPDATE solicitud_func
            SET titulo = :titulo,
                resumen = :resumen,
                topico  = :topico,
                dev_env = :dev_env,
                estado  = :estado
            WHERE id = :id";
    $st = $pdo->prepare($sql);
    $st->bindParam(":titulo", $titulo);
    $st->bindParam(":resumen", $resumen);
    $st->bindParam(":topico", $topico);
    $st->bindParam(":dev_env", $dev_env);
    $st->bindParam(":estado", $estado);
    $st->bindParam(":id", $id, PDO::PARAM_INT);
    $st->execute();

    $pdo->commit();

    flash_success("Solicitud #{$id} actualizada correctamente.");
    header("Location: /sol_func.php?id={$id}");
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (file_exists(__DIR__ . "/../includes/pdoErrorInfoSnippet.php")) {
        require_once __DIR__ . "/../includes/pdoErrorInfoSnippet.php";
        checkPDOErrorInfo($e);
    }
    error_log('PDOException - ' . $e->getMessage(), 0);
    flash_danger("Error fatal interno, la solicitud no pudo ser actualizada.");
    header("Location: /main.php");
    exit;
}
