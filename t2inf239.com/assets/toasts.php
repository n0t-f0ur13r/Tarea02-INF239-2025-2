<?php
// assets/toasts.php
// Estructura esperada de cada flash: ['type' => 'success|info|warning|danger', 'msg' => 'texto']
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flashes = $_SESSION['flash'] ?? [];
// Limpia para que no se repitan al recargar
unset($_SESSION['flash']);

// Mapea tipo a clases Bootstrap
$map = [
    'success' => 'text-bg-success',
    'info' => 'text-bg-info',
    'warning' => 'text-bg-warning',
    'danger' => 'text-bg-danger',
];

if (!empty($flashes)):
    ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
        <?php
        foreach ($flashes as $f):
            $cls = $map[$f['type'] ?? 'info'] ?? 'text-bg-secondary';
            $msg = htmlspecialchars($f['msg'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
            <div class="toast align-items-center border-0 mb-2 <?php echo $cls; ?>" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body"> 
                        <?php echo $msg; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        // Inicializa y muestra todos los toasts encontrados
        document.addEventListener('DOMContentLoaded', () => {
            const toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.forEach(el => new bootstrap.Toast(el).show());
        });
    </script>
<?php endif; ?>
