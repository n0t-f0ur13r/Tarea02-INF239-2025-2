<!DOCTYPE html>

<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>


<html>
    <?php require_once __DIR__ . '/assets/head.php'; ?>
    <body>
        <?php require_once __DIR__ . '/assets/toasts.php'; ?>
        <?php require_once __DIR__ . '/assets/navbar.php'; ?>

        <?php
        require_once __DIR__ . '/includes/dbh.inc.php';
        $pdo = db();
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

        $topicos = $pdo->query($sql_topics)->fetchAll(PDO::FETCH_COLUMN);

        $sql_envs = "
            SELECT DISTINCT TRIM(dev_env) AS dev_env
            FROM solicitud_func
            WHERE dev_env IS NOT NULL AND dev_env <> ''
            ORDER BY dev_env
        ";
        $envs = $pdo->query($sql_envs)->fetchAll(PDO::FETCH_COLUMN);

        /* === Datos para reseñas y asignaciones (solo si es ingeniero) === */
        $me_rut = auth_id();
        $mis_err_ids = [];
        $mis_func_ids = [];
        $asignadas_error = [];
        $asignadas_func  = [];

        if (auth_role()==='ingeniero' && $me_rut) {
            /* IDs asignados al ingeniero (errores y funcionalidades) */
            $qIdsErr  = $pdo->prepare("SELECT id_solicitud_error FROM ingenieros_solicitud_error WHERE rut_ingeniero = :rut");
            $qIdsFunc = $pdo->prepare("SELECT id_solicitud_func  FROM ingenieros_solicitud_func  WHERE rut_ingeniero = :rut");
            $qIdsErr->execute([':rut'=>$me_rut]);
            $qIdsFunc->execute([':rut'=>$me_rut]);
            $mis_err_ids  = $qIdsErr->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $mis_func_ids = $qIdsFunc->fetchAll(PDO::FETCH_COLUMN) ?: [];

            /* Listas “Asignadas a mí” */
            $qAsignErr = $pdo->prepare("
                SELECT se.id, se.titulo, se.descripcion, se.topico, se.estado, se.pub_date
                FROM solicitud_error se
                INNER JOIN ingenieros_solicitud_error ise ON ise.id_solicitud_error = se.id
                WHERE ise.rut_ingeniero = :rut
                ORDER BY se.pub_date DESC
            ");
            $qAsignErr->execute([':rut'=>$me_rut]);
            $asignadas_error = $qAsignErr->fetchAll() ?: [];

            $qAsignFunc = $pdo->prepare("
                SELECT sf.id, sf.titulo, sf.resumen, sf.topico, sf.dev_env, sf.estado, sf.pub_date
                FROM solicitud_func sf
                INNER JOIN ingenieros_solicitud_func isf ON isf.id_solicitud_func = sf.id
                WHERE isf.rut_ingeniero = :rut
                ORDER BY sf.pub_date DESC
            ");
            $qAsignFunc->execute([':rut'=>$me_rut]);
            $asignadas_func = $qAsignFunc->fetchAll() ?: [];
        }

        /* Listas del sistema (completas) */
        $base_query_error = "CALL sp_solicitudes_error_sistema()";
        $statement = $pdo->prepare($base_query_error);
        $statement->execute();
        $solicitudes_error_ing = $statement->fetchAll();
        
        $statement->closeCursor();

        $base_query_func = "CALL sp_solicitudes_funcionalidad_sistema()";
        $stf = $pdo->prepare($base_query_func);
        $stf->execute();
        $solicitudes_func_ing = $stf->fetchAll();
        
        $statement->closeCursor();
        ?>
        
        <main class="container py-4">

            <!-- Búsqueda global -->
            <section class="mb-4">
                <div class="page-header mb-3">
                    <h1 class="h3 mb-1">Buscar solicitudes</h1>
                    <p class="text-muted mb-0">Busca por título (errores o funcionalidades)</p>
                </div>

                <form action="main.php" method="get" class="mb-3">
                    <div class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control" placeholder="Ej: 'Error al guardar', 'Nueva exportación PDF', etc.">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                </form>

                <?php
                    $q = filter_input(INPUT_GET, "q", FILTER_SANITIZE_STRING);
                    $showResults = false;
                    if(!empty($q)){
                        
                        if(empty($pdo)){
                            $pdo = db();
                        }
                        $showResults = true;
                        
                        $titulo = "%{$q}%";
                        
                        $query_error = "SELECT id, titulo, topico, estado FROM solicitud_error WHERE titulo LIKE :titulo ORDER BY pub_date DESC;";
                        $query_func = "SELECT id, titulo, topico, estado FROM solicitud_func WHERE titulo LIKE :titulo ORDER BY pub_date DESC;";
                        
                        // Solicitudes de error
                        $statement_error = $pdo->prepare($query_error);
                        
                        $statement_error->bindParam(":titulo", $titulo, PDO::PARAM_STR);
                        
                        try{
                            $statement_error->execute();
                            $searchResults_error = $statement_error->fetchAll();
                        } catch (PDOException $e) {
                            error_log("PDOException at main.php - " . $e->getMessage());
                            flash_danger("Error fatal. No se pudieron obtener solicitudes en su búsqueda.");
                            header("Location: /main.php");
                            exit;
                        }
                        
                        // Solicitudes de funcionalidad
                        $statement_func = $pdo->prepare($query_func);
                        $statement_func->bindParam(":titulo", $titulo, PDO::PARAM_STR);
                        
                        try{
                            $statement_func->execute();
                            $searchResults_func = $statement_func->fetchAll();
                            $statement->closeCursor();
                        } catch (PDOException $e) {
                            error_log("PDOException at main.php - " . $e->getMessage());
                            flash_danger("Error fatal. No se pudieron obtener solicitudes en su búsqueda.");
                            header("Location: /main.php");
                            exit;
                        }
                        
                        if(empty($searchResults_error) && empty($searchResults_func)){
                            $showResults = false;
                            flash_info("No se encontraron solicitudes con tal título.");
                        }
                        
                    }
                ?>
                
                <?php if($showResults): ?>
                
                <!-- Resultados de la búsqueda -->
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Resultados</div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <div class="border rounded scroll-list scroll-320">
                                <ul class="list-group list-group-flush">
                                    
                                    <?php foreach($searchResults_func as $result): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="me-3">
                                            <div class="fw-semibold">[FUNC] <?= htmlspecialchars($result['titulo']) ?></div>
                                            
                                            <?php
                                            $estado = (string) $result["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                            ?>
                                            
                                            <small class="text-muted">Tópico: <?= htmlspecialchars($result['topico'])?> · Estado: <span class="badge <?= $badge ?>"><?= $estado ?></span></small>
                                        </div>
                                        <a href="/sol_func.php?id=<?=$result['id']?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                    </li>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach($searchResults_error as $result): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="me-3">
                                            <div class="fw-semibold">[ERR] <?= htmlspecialchars($result['titulo'])?></div>
                                            
                                            <?php
                                            $estado = (string) $result["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                            ?>
                                            
                                            <small class="text-muted">Tópico: <?= htmlspecialchars($result['topico']) ?>· Estado: <span class="badge <?= $badge ?>"><?= $result['estado'] ?></span></small>
                                        </div>
                                        <a href="/sol_error.php?id=<?= $result['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </section>

            <!-- Creación de solicitudes -->
            <section class="mb-5">
                <div class="page-header mb-3">
                    <h2 class="h4 mb-1">Crear nuevas solicitudes</h2>
                    <p class="text-muted mb-0">Completa el formulario correspondiente</p>
                </div>

                <div class="row g-3">
                    <!-- Formulario: Solicitud de Error -->
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Nueva solicitud de error</div>
                            <div class="card-body">
                                <form action="/internal/error_create.php" method="post" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label">Título</label>
                                        <input type="text" class="form-control" name="titulo" placeholder="Ej: 'Error 500 al guardar reseña'">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descripción</label>
                                        <textarea class="form-control" name="descripcion" rows="4" placeholder="Describe el comportamiento, pasos para reproducir, logs, etc."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="form-label">Tópico</label>
                                            <select class="form-select" name="topico">
                                                <?php foreach ($topicos as $topico): ?>
                                                <option value="<?= $topico ?>">
                                                    <?= $topico ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-danger" type="submit">Crear Error</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario: Solicitud de Funcionalidad -->
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Nueva solicitud de funcionalidad</div>
                            <div class="card-body">
                                <form action="/internal/func_create.php" method="post" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label">Título</label>
                                        <input type="text" class="form-control" name="titulo" placeholder="Ej: 'Exportar reportes a PDF'">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Resumen</label>
                                        <textarea class="form-control" name="resumen" rows="4" placeholder="Describe brevemente el objetivo y el valor de la funcionalidad."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="form-label">Tópico</label>
                                            <select class="form-select" name="topico">
                                                <?php foreach ($topicos as $topico): ?>
                                                <option value="<?= $topico ?>">
                                                    <?= $topico ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="form-label">Entorno de desarrollo</label>
                                            <select class="form-select" name="dev_env">
                                                <?php foreach($envs as $env): ?>
                                                <option value="<?= $env ?>">
                                                    <?= $env ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-primary" type="submit">Crear Funcionalidad</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Sección Ingeniero -->
            <?php if (auth_role()=='ingeniero'): ?>
            <section class="mb-5">
                <div class="page-header mb-3">
                    <h2 class="h4 mb-1">Panel del Ingeniero</h2>
                    <p class="text-muted mb-0">Solicitudes asignadas y vistas del sistema</p>
                </div>

                <!-- Asignadas al ingeniero -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Asignadas a mí</div>
                    <div class="card-body">
                        <div class="border rounded scroll-list scroll-260">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($asignadas_error as $se): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <div class="fw-semibold">[ERR] <?= htmlspecialchars($se['titulo']) ?></div>
                                        <div class="text-muted"><?= htmlspecialchars($se['descripcion']) ?></div>
                                        <?php
                                            $estado = (string) $se["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                        ?>
                                        <small class="text-muted"><b>Tópico:</b> <?= htmlspecialchars($se['topico'])?> · <b>Estado:</b> <span class="badge <?= $badge ?>"><?=htmlspecialchars($estado)?></span> · <?= htmlspecialchars($se['pub_date'])?></small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="sol_error.php?id=<?= (int)$se['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rev-err-<?= (int)$se['id'] ?>">Reseñar</button>
                                    </div>
                                </li>
                                <li class="list-group-item collapse" id="rev-err-<?= (int)$se['id'] ?>">
                                    <form action="/internal/review_create.php" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="type" value="err">
                                        <input type="hidden" name="target" value="<?= (int)$se['id'] ?>">
                                        <div class="input-group">
                                            <textarea name="mensaje" class="form-control" rows="2" maxlength="400" placeholder="Escribe tu reseña…" required></textarea>
                                            <button class="btn btn-primary btn-sm" type="submit">Enviar</button>
                                        </div>
                                    </form>
                                </li>
                                <?php endforeach; ?>

                                <?php foreach ($asignadas_func as $sf): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <div class="fw-semibold">[FUNC] <?= htmlspecialchars($sf['titulo']) ?></div>
                                        <div class="text-muted"><?= htmlspecialchars($sf['resumen']) ?></div>
                                        <?php
                                            $estado = (string) $sf["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                        ?>
                                        <small class="text-muted"><b>Tópico:</b> <?= htmlspecialchars($sf['topico'])?> · <b>Entorno:</b> <?= htmlspecialchars($sf['dev_env'])?> · <b>Estado:</b> <span class="badge <?= $badge ?>"><?=htmlspecialchars($sf['estado'])?></span> · <?= htmlspecialchars($sf['pub_date'])?></small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="sol_func.php?id=<?= (int)$sf['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rev-func-<?= (int)$sf['id'] ?>">Reseñar</button>
                                    </div>
                                </li>
                                <li class="list-group-item collapse" id="rev-func-<?= (int)$sf['id'] ?>">
                                    <form action="/internal/review_create.php" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="type" value="func">
                                        <input type="hidden" name="target" value="<?= (int)$sf['id'] ?>">
                                        <div class="input-group">
                                            <textarea name="mensaje" class="form-control" rows="2" maxlength="400" placeholder="Escribe tu reseña…" required></textarea>
                                            <button class="btn btn-primary btn-sm" type="submit">Enviar</button>
                                        </div>
                                    </form>
                                </li>
                                <?php endforeach; ?>

                                <?php if (empty($asignadas_error) && empty($asignadas_func)): ?>
                                <li class="list-group-item text-muted">No tienes solicitudes asignadas.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Listas del sistema: errores y funcionalidades lado a lado -->
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Solicitudes de error (sistema)</div>
                            <div class="card-body">
                                <div class="border rounded scroll-list scroll-300">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($solicitudes_error_ing as $solicitud): ?>
                                        <?php
                                            $estado = (string) $solicitud["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                            $idErr = (int)$solicitud['id'];
                                            $yoAsignadoErr = in_array($idErr, $mis_err_ids, true);
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="me-3">
                                                <div class="fw-semibold"><?= htmlspecialchars($solicitud['titulo']) ?></div>
                                                <div class="text-muted"><?= htmlspecialchars($solicitud['descripcion']) ?></div>
                                                <small class="text-muted"><b>Tópico:</b> <?= htmlspecialchars($solicitud['topico'])?> · <b>Estado:</b> <span class="badge <?= $badge ?>"><?=htmlspecialchars($estado)?></span> · <b>Autor:</b> <?= htmlspecialchars($solicitud['autor']) ?> · <?= htmlspecialchars($solicitud['pub_date'])?></small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="sol_error.php?id=<?= $idErr ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                <?php if ($yoAsignadoErr): ?>
                                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rev-err-sys-<?= $idErr ?>">Reseñar</button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                        <?php if ($yoAsignadoErr): ?>
                                        <li class="list-group-item collapse" id="rev-err-sys-<?= $idErr ?>">
                                            <form action="/internal/review_create.php" method="post">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                <input type="hidden" name="type" value="err">
                                                <input type="hidden" name="target" value="<?= $idErr ?>">
                                                <div class="input-group">
                                                    <textarea name="mensaje" class="form-control" rows="2" maxlength="400" placeholder="Escribe tu reseña…" required></textarea>
                                                    <button class="btn btn-primary btn-sm" type="submit">Enviar</button>
                                                </div>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Solicitudes de funcionalidad (sistema)</div>
                            <div class="card-body">
                                <div class="border rounded scroll-list scroll-300">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($solicitudes_func_ing as $solicitud): ?>
                                        <?php
                                            $estado = (string) $solicitud["estado"];
                                            $badge = match ($estado) {
                                                "Abierto" => "text-bg-info",
                                                "En Progreso" => "text-bg-warning",
                                                "Resuelto" => "text-bg-success",
                                                "Cerrado" => "text-bg-danger",
                                                default => "text-bg-secondary",
                                            };
                                            $idFunc = (int)$solicitud['id'];
                                            $yoAsignadoFunc = in_array($idFunc, $mis_func_ids, true);
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="me-3">
                                                <div class="fw-semibold"><?= htmlspecialchars($solicitud['titulo']) ?></div>
                                                <div class="text-muted"><?= htmlspecialchars($solicitud['resumen']) ?></div>
                                                <small class="text-muted"><b>Tópico:</b> <?= htmlspecialchars($solicitud['topico'])?> · <b>Entorno:</b> <?= htmlspecialchars($solicitud['dev_env'])?> · <b>Estado:</b> <span class="badge <?= $badge ?>"><?=htmlspecialchars($estado)?></span> · <b>Autor:</b> <?= htmlspecialchars($solicitud['autor']) ?> · <?= htmlspecialchars($solicitud['pub_date'])?></small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="sol_func.php?id=<?= $idFunc ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                <?php if ($yoAsignadoFunc): ?>
                                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rev-func-sys-<?= $idFunc ?>">Reseñar</button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                        <?php if ($yoAsignadoFunc): ?>
                                        <li class="list-group-item collapse" id="rev-func-sys-<?= $idFunc ?>">
                                            <form action="/internal/review_create.php" method="post">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                <input type="hidden" name="type" value="func">
                                                <input type="hidden" name="target" value="<?= $idFunc ?>">
                                                <div class="input-group">
                                                    <textarea name="mensaje" class="form-control" rows="2" maxlength="400" placeholder="Escribe tu reseña…" required></textarea>
                                                    <button class="btn btn-primary btn-sm" type="submit">Enviar</button>
                                                </div>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Sección Usuario -->
            <?php if (auth_role()=='usuario'): ?>
            <?php
                // Carga de mis solicitudes (funcionalidades y errores) del usuario actual
                $rut_actual = auth_id(); // viene de includes/auth.php
                $pdo = $pdo ?? db();

                // Mis funcionalidades
                $sql_mis_func = "
                    SELECT id, titulo, resumen, topico, dev_env, estado, pub_date
                    FROM solicitud_func
                    WHERE rut_autor = :rut
                    ORDER BY pub_date DESC, id DESC
                ";
                $st_mis_func = $pdo->prepare($sql_mis_func);
                $st_mis_func->bindParam(':rut', $rut_actual, PDO::PARAM_STR);
                $st_mis_func->execute();
                $mis_func = $st_mis_func->fetchAll(PDO::FETCH_ASSOC);
                $st_mis_func->closeCursor();

                // Mis errores
                $sql_mis_err = "
                    SELECT id, titulo, descripcion, topico, estado, pub_date
                    FROM solicitud_error
                    WHERE rut_autor = :rut
                    ORDER BY pub_date DESC, id DESC
                ";
                $st_mis_err = $pdo->prepare($sql_mis_err);
                $st_mis_err->bindParam(':rut', $rut_actual, PDO::PARAM_STR);
                $st_mis_err->execute();
                $mis_err = $st_mis_err->fetchAll(PDO::FETCH_ASSOC);
                $st_mis_err->closeCursor();
            ?>
            <section class="mb-5">
                <div class="page-header mb-3">
                    <h2 class="h4 mb-1">Panel del Usuario</h2>
                    <p class="text-muted mb-0">Tus solicitudes como autor</p>
                </div>

                <div class="row g-3">
                    <!-- Funcionalidades del usuario -->
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Mis funcionalidades</div>
                            <div class="card-body">
                                <div class="border rounded scroll-list scroll-300">
                                    <ul class="list-group list-group-flush">
                                        <?php if (empty($mis_func)): ?>
                                            <li class="list-group-item text-muted">No tienes funcionalidades creadas.</li>
                                        <?php else: ?>
                                            <?php foreach ($mis_func as $f): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div class="me-3">
                                                        <div class="fw-semibold"><?= htmlspecialchars($f['titulo']) ?></div>
                                                        <div class="text-muted"><?= htmlspecialchars($f['resumen']) ?></div>
                                                        <?php
                                                            $estado = (string) $f["estado"];
                                                            $badge = match ($estado) {
                                                                "Abierto" => "text-bg-info",
                                                                "En Progreso" => "text-bg-warning",
                                                                "Resuelto" => "text-bg-success",
                                                                "Cerrado" => "text-bg-danger",
                                                                default => "text-bg-secondary",
                                                            };
                                                        ?>
                                                        <small class="text-muted">
                                                            <b>Tópico:</b> <?= htmlspecialchars($f['topico']) ?> ·
                                                            <b>Entorno:</b> <?= htmlspecialchars($f['dev_env']) ?> ·
                                                            <b>Estado:</b> <span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span> ·
                                                            <?= htmlspecialchars($f['pub_date']) ?>
                                                        </small>
                                                    </div>
                                                    <a href="sol_func.php?id=<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Errores del usuario -->
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header fw-semibold">Mis errores reportados</div>
                            <div class="card-body">
                                <div class="border rounded scroll-list scroll-300">
                                    <ul class="list-group list-group-flush">
                                        <?php if (empty($mis_err)): ?>
                                            <li class="list-group-item text-muted">No tienes errores reportados.</li>
                                        <?php else: ?>
                                            <?php foreach ($mis_err as $e): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div class="me-3">
                                                        <div class="fw-semibold"><?= htmlspecialchars($e['titulo']) ?></div>
                                                        <div class="text-muted"><?= htmlspecialchars($e['descripcion']) ?></div>
                                                        <?php
                                                            $estado = (string) $e["estado"];
                                                            $badge = match ($estado) {
                                                                "Abierto" => "text-bg-info",
                                                                "En Progreso" => "text-bg-warning",
                                                                "Resuelto" => "text-bg-success",
                                                                "Cerrado" => "text-bg-danger",
                                                                default => "text-bg-secondary",
                                                            };
                                                        ?>
                                                        <small class="text-muted">
                                                            <b>Tópico:</b> <?= htmlspecialchars($e['topico']) ?> ·
                                                            <b>Estado:</b> <span class="badge <?= $badge ?>"><?= htmlspecialchars($estado) ?></span> ·
                                                            <?= htmlspecialchars($e['pub_date']) ?>
                                                        </small>
                                                    </div>
                                                    <a href="sol_error.php?id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
            <?php endif; ?>

            
        </main>


        <?php require_once __DIR__ . '/assets/footer.php'; ?>
    </body>
</html>
