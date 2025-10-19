<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!doctype html>
<html lang="es">
    <?php require_once __DIR__ . '/assets/head.php'; ?>
    <body>
        <?php require_once __DIR__ . '/assets/navbar.php'; ?>
        <main class="container py-4">
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1>Búsqueda</h1>
                <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
            </div>

            <!-- Barra de búsqueda -->
            <form class="mb-3" action="search.php" method="get" novalidate>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" name="q"  placeholder="Título de la solicitud...">
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                </div>

                <!-- Filtros avanzados -->
                <div class="card card-filters shadow-sm mb-4">
                    <div class="card-body">

                        <?php
                        require_once __DIR__ . '/includes/dbh.inc.php';

                        try {
                            $pdo = db();

                            /* TÓPICOS desde ambas tablas (sin duplicados, sin null/'' y ordenados) */
                            $sql_topics = "
                            SELECT topico FROM (
                              SELECT DISTINCT TRIM(topico) AS topico
                              FROM solicitud_func
                              WHERE topico IS NOT NULL AND topico <> ''
                              UNION
                              SELECT DISTINCT TRIM(topico) AS topico
                              FROM solicitud_error
                              WHERE topico IS NOT NULL AND topico <> ''
                            ) AS t
                            ORDER BY topico
                            ";
                            $topics = $pdo->query($sql_topics)->fetchAll(PDO::FETCH_COLUMN);

                            /* AMBIENTES sólo desde solicitud_func (sin null/'' y ordenados) */
                            $sql_envs = "
                            SELECT DISTINCT TRIM(dev_env) AS dev_env
                            FROM solicitud_func
                            WHERE dev_env IS NOT NULL AND dev_env <> ''
                            ORDER BY dev_env
                            ";
                            $envs = $pdo->query($sql_envs)->fetchAll(PDO::FETCH_COLUMN);
                        } catch (Exception $ex) {
                            die();
                        }
                        ?>

                        <!-- FORMULARIO -->
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label">Fecha envío (desde)</label>
                                <input type="date" class="form-control" name="from">
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label class="form-label">Fecha envío (hasta)</label>
                                <input type="date" class="form-control" name="to">
                            </div>

                            <div class="col-sm-6 col-md-2">
                                <label for="topic" class="form-label">Tópico</label>
                                <select class="form-select" name="topic">
                                    <option value="todos">Todos</option>
                                    <?php foreach ($topics as $topic): ?>
                                        <option value="<?= htmlspecialchars($topic) ?>"><?= htmlspecialchars($topic) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label">Ambiente</label>
                                <select class="form-select" name="env">
                                    <option value="todos">Todos</option>
                                    <?php foreach ($envs as $env): ?>
                                        <option value="<?= htmlspecialchars($env) ?>"><?= htmlspecialchars($env) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="status">
                                    <option value="todos">Todos</option>
                                    <option value="Abierto">Abierto</option>
                                    <option value="En Progreso">En Progreso</option>
                                    <option value="Resuelto">Resuelto</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <a class="btn btn-outline-secondary" href="search.php">Limpiar</a>
                            </div>
                        </div>

                        <?php
                        $title = filter_input(INPUT_GET, "q", FILTER_SANITIZE_STRING);

                        $date_from = filter_input(INPUT_GET, "from", FILTER_SANITIZE_STRING);
                        $date_to = filter_input(INPUT_GET, "to", FILTER_SANITIZE_STRING);

                        $topic = filter_input(INPUT_GET, "topic", FILTER_SANITIZE_STRING);
                        $env = filter_input(INPUT_GET, "env", FILTER_SANITIZE_STRING);

                        $status = filter_input(INPUT_GET, "status", FILTER_SANITIZE_STRING);

                        $base_query_func = "SELECT titulo, resumen, topico, dev_env, estado "
                                . "FROM solicitud_func "
                                . "WHERE titulo LIKE :titulo ";

                        $base_query_error = "SELECT titulo, descripcion, topico, estado "
                                . "FROM solicitud_error "
                                . "WHERE titulo LIKE :titulo ";
                        if (filter_input(INPUT_SERVER, "REQUEST_METHOD") == "GET" && isset($title)){
                            
                            // Obtener solicitudes de funcionalidad primero
                            try {
                                if($title == ""){
                                    $title = "*";
                                }
                                // Filtrar topico, dev_env y estado
                                $bool_topic = false;
                                if ($topic != "todos") {
                                    $base_query_func = $base_query_func . "AND topico = :topico ";
                                    $bool_topic = true;
                                }

                                $bool_env = false;
                                if ($env != "todos") {
                                    $base_query_func = $base_query_func . "AND dev_env = :env ";
                                    $bool_env = true;
                                }

                                $bool_status = false;
                                if ($status != "todos") {
                                    $base_query_func = $base_query_func . "AND estado = :status ";
                                    $bool_status = true;
                                }



                                // Sesgar para todos los títulos si es que se busca por "*"
                                if ($title == "*") {
                                    $title_search_term = "%";
                                } else {
                                    $title_search_term = "%{$title}%";
                                }

                                // Filtrar la fecha segun cada caso
                                $bool_date_to = false;
                                $bool_date_from = false;

                                if (!empty($date_to) && !empty($date_from)) {
                                    $base_query_func = $base_query_func . "AND pub_date BETWEEN :date_from AND :date_to";
                                    $bool_date_from = true;
                                    $bool_date_to = true;
                                } else if (!empty($date_to)) {
                                    $base_query_func = $base_query_func . "AND pub_date <= :date_to ";
                                    $bool_date_to = true;
                                } else if (!empty($date_from)) {
                                    $base_query_func = $base_query_func . "AND pub_date >= :date_from ";
                                    $bool_date_from = true;
                                }



                                // Ejecutar busqueda
                                $base_query_func = $base_query_func . ";";
                                $statement_func = $pdo->prepare($base_query_func);

                                $statement_func->bindParam(":titulo", $title_search_term, PDO::PARAM_STR);

                                if ($bool_topic) {
                                    $statement_func->bindParam(":topico", $topic);
                                }
                                if ($bool_env) {
                                    $statement_func->bindParam(":env", $env);
                                }
                                if ($bool_status) {
                                    $statement_func->bindParam(":status", $status);
                                }

                                if ($bool_date_from) {
                                    $statement_func->bindParam(":date_from", $date_from);
                                }
                                if ($bool_date_to) {
                                    $statement_func->bindParam(":date_to", $date_to);
                                }

                                $statement_func->execute();

                                $func_results = $statement_func->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                echo "Error solicitudes de funcionalidad: " . $e->getMessage();
                                unset($pdo);
                                die();
                            }

                            // Obtener solicitudes de error
                            try {
                                // Filtrar topico, dev_env y estado
                                $bool_topic = false;
                                if ($topic != "todos") {
                                    $base_query_error = $base_query_error . "AND topico = :topico ";
                                    $bool_topic = true;
                                }

                                $bool_status = false;
                                if ($status != "todos") {
                                    $base_query_error = $base_query_error . "AND estado = :status ";
                                    $bool_status = true;
                                }

                                // Sesgar para todos los títulos si es que se busca por "*"
                                if ($title == "*") {
                                    $title_search_term = "%";
                                } else {
                                    $title_search_term = "%{$title}%";
                                }

                                // Filtrar la fecha segun cada caso
                                $bool_date_to = false;
                                $bool_date_from = false;

                                if (!empty($date_to) && !empty($date_from)) {
                                    $base_query_error = $base_query_error . "AND pub_date BETWEEN :date_from AND :date_to";
                                    $bool_date_from = true;
                                    $bool_date_to = true;
                                } else if (!empty($date_to)) {
                                    $base_query_error = $base_query_error . "AND pub_date <= :date_to ";
                                    $bool_date_to = true;
                                } else if (!empty($date_from)) {
                                    $base_query_error = $base_query_error . "AND pub_date >= :date_from ";
                                    $bool_date_from = true;
                                }

                                // Ejecutar busqueda
                                $base_query_error = $base_query_error . ";";
                                $statement_error = $pdo->prepare($base_query_error);

                                $statement_error->bindParam(":titulo", $title_search_term, PDO::PARAM_STR);

                                if ($bool_topic) {
                                    $statement_error->bindParam(":topico", $topic);
                                }

                                if ($bool_date_from) {
                                    $statement_error->bindParam(":date_from", $date_from);
                                }
                                if ($bool_date_to) {
                                    $statement_error->bindParam(":date_to", $date_to);
                                }

                                $statement_error->execute();

                                $error_results = $statement_error->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                echo "Error solicitudes de error: " . $e->getMessage();
                                unset($pdo);
                                die();
                            }
                        }
                        ?>

                    </div>
                </div>

            </form>
            <!-- Resultados (placeholder frontend) -->
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre/Título</th>
                                <th>Resumen/Descripción</th>
                                <th>Tópico</th>
                                <th>Ambiente</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Repite filas con PHP según resultados -->
                            <!-- Funcionalidad-->

                            <?php foreach ($func_results as $solicitud): ?>

                                <tr>
                                    <td><span class="badge text-bg-info">Fun.</span></td>
                                    <td><?= htmlspecialchars($solicitud["titulo"]) ?></td>
                                    <td><?= htmlspecialchars($solicitud["resumen"]) ?></td>
                                    <td><?= htmlspecialchars($solicitud["topico"]) ?></td>
                                    <td><?= htmlspecialchars($solicitud["dev_env"]) ?></td>

                                    <td>
                                        <?php
                                        $estado = (string) $solicitud["estado"];
                                        $badge = match ($estado) {
                                            "Abierto" => "text-bg-info",
                                            "En Progreso" => "text-bg-warning",
                                            "Resuelto" => "text-bg-success",
                                            "Cerrado" => "text-bg-danger",
                                            default => "text-bg-secondary",
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span>
                                    </td>


                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="func_solis.php">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Error -->

                            <?php foreach ($error_results as $solicitud): ?>

                                <tr>
                                    <td><span class="badge text-bg-danger">Err.</span></td>
                                    <td><?= htmlspecialchars($solicitud["titulo"]) ?></td>
                                    <td><?= htmlspecialchars($solicitud["descripcion"]) ?></td>
                                    <td><?= htmlspecialchars($solicitud["topico"]) ?></td>
                                    <td>-</td>

                                    <td>
                                        <?php
                                        $estado = (string) $solicitud["estado"];
                                        $badge = match ($estado) {
                                            "Abierto" => "text-bg-info",
                                            "En Progreso" => "text-bg-warning",
                                            "Resuelto" => "text-bg-success",
                                            "Cerrado" => "text-bg-danger",
                                            default => "text-bg-secondary",
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span>
                                    </td>


                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="func_solis.php">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>


                            <!-- ... -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/assets/footer.php'; ?>
    </body>
</html>