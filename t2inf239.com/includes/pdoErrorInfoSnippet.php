<?php

function checkPDOErrorInfo(PDOException $e) {
    if (!empty($e)) {
        [$sqlstate, $errno, $driver_message] = $e->errorInfo();

        if ($sqlstate === '22001' || $errno === 1406) {
            flash_danger("Uno o mas campos exceden su tamaño (titulo o descripción).");
            header("Location: /main.php");
            exit;
        } elseif ($sqlstate === '23000' && $errno === 1062) {
            flash_danger("Ya existe un registro con esos datos (constraint UNIQUE).");
            header("Location: /main.php");
            exit;
        } elseif ($sqlstate === '23000' && $errno === 1452) {
            flash_danger("Referencia no válida.");
            header("Location: /main.php");
            exit;
        }
    }
}
