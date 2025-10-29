<?php
session_start();
require_once '../conexion.php';

// proteger acceso
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

function obtenerInsumos(): array {
    $conn = conectar();
    $sql = "SELECT id_insumo, insumo, descripcion, stock FROM inventario_insumos ORDER BY insumo ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

$insumos = obtenerInsumos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de Insumos - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; }
        .descripcion-cell { max-width: 360px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .toolbar { margin-bottom: 12px; display:flex; gap:8px; align-items:center; }
        .table-responsive { max-height: 60vh; overflow-y: auto; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">LISTADO DE INSUMOS</h1>
        <a href="../menu_empleados_vista.php" class="btn btn-outline-dark">Regresar</a>
    </div>
</header>

<main class="container my-4">
    <section class="card shadow p-4">
        <div class="toolbar">
            <input id="filtro" type="search" class="form-control form-control-sm w-50" placeholder="Buscar por nombre o descripción...">
            <div class="text-muted ms-auto">Registros: <span id="count"><?= count($insumos); ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="tabla-insumos-readonly">
                <thead class="table-dark">
                    <tr>
                        <th style="width:80px">ID</th>
                        <th>Insumo</th>
                        <th>Descripción</th>
                        <th style="width:120px">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($insumos): ?>
                        <?php foreach ($insumos as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['id_insumo']); ?></td>
                                <td><?= htmlspecialchars($r['insumo']); ?></td>
                                <td class="descripcion-cell" title="<?= htmlspecialchars($r['descripcion']); ?>">
                                    <?= htmlspecialchars($r['descripcion'] ?: 'Sin descripción'); ?>
                                </td>
                                <td class="text-end"><?= (int)$r['stock']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No hay insumos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filtro = document.getElementById('filtro');
    const tabla = document.getElementById('tabla-insumos-readonly');
    const tbody = tabla.querySelector('tbody');
    const countEl = document.getElementById('count');

    function actualizarConteo() {
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.style.display !== 'none');
        countEl.textContent = rows.length;
    }

    filtro.addEventListener('input', function () {
        const q = (this.value || '').trim().toLowerCase();
        tbody.querySelectorAll('tr').forEach(tr => {
            const text = (tr.textContent || '').toLowerCase();
            const mostrar = q === '' || text.indexOf(q) !== -1;
            tr.style.display = mostrar ? '' : 'none';
        });
        actualizarConteo();
    });

    filtro.addEventListener('keydown', function(e){
        if (e.key === 'Escape') { this.value = ''; this.dispatchEvent(new Event('input')); }
    });
});
</script>
</body>
</html>
