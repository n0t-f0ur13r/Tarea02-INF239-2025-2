<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dbh.inc.php';

require_login();
require_role('ingeniero');

$id = empty(filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT)) ? null : filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);
$kind = empty(filter_input(INPUT_GET, "kind", FILTER_SANITIZE_STRING)) ? null : filter_input(INPUT_GET, "kind", FILTER_SANITIZE_STRING);

if (isset($id) && isset($kind)) {

    // SELECCIONAR LA RESENA
    if($kind == 'func'){
        $query = "SELECT fecha, mensaje FROM resena_func WHERE id =:id ;";
    }
    else if($kind == 'error'){
        $query = "SELECT fecha, mensaje FROM resena_error WHERE id =:id ;";
    }
    else{
        flash_warning("Clase de reseña inválido.");
        header("Location: /main.php");
        exit;
    }
    
    $pdo = db();

    $statement = $pdo->prepare($query);
    $statement->bindParam(":id", $id);

    $statement->execute();

    $resena_obtenida = $statement->fetch();
    $statement->closeCursor();

    if (empty($resena_obtenida)) {
        flash_info("No existe una reseña de {$kind} con tal id ({$id}).");
        session_write_close();
        header("Location: /main.php");
        exit;
    }


    flash_success("Reseña obtenida exitosamente.");
} else {
    $isView = false;
}

?>

<!doctype html>
<html lang="es">
<?php require_once __DIR__ . '/assets/head.php'; ?>

<body>
    <?php require_once __DIR__ . '/assets/navbar.php'; ?>

    <main class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-0">Reseña #<?= $id ?> - <?= $resena_obtenida['fecha']?></h1>
            </div>
            <a href="/main.php" class="btn btn-outline-secondary">← Volver</a>
        </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">
                    Detalles de la reseña</div>
                <div class="card-body">
                    <!-- Formulario de actualización -->
                    <form method="post" action="/internal/review_update.php">
                        <input type="hidden" name="review_id" value="<?= $id ?>">
                        <input type="hidden" name="type" value="<?= $kind ?>">

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion" name="mensaje" rows="6" maxlength="400" required><?= htmlspecialchars($resena_obtenida['mensaje']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success">Guardar cambios</button>
                            <a href="/main.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Formulario de eliminación -->
            <div class="card border-danger-subtle">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-semibold text-danger mb-1">Eliminar reseña</div>
                        <div class="small text-muted">Esta acción no se puede deshacer.</div>
                    </div>
                    <form method="post" action="/internal/review_delete.php" class="ms-3">
                        <input type="hidden" name="review_id" value="<?= $id ?>">
                        <input type="hidden" name="type" value="<?= $kind ?>">
                        <button type="submit" class="btn btn-outline-danger">Eliminar definitivamente</button>
                    </form>
                </div>
            </div>
    </main>

    <?php require_once __DIR__ . '/assets/toasts.php'; ?>
    <?php require_once __DIR__ . '/assets/footer.php'; ?>
</body>

</html>