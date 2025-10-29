<?php
session_start();
require_once '../conexion.php';

// proteger acceso
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

function obtenerDetalleCompras(): array {
    $conn = conectar();
    $sql = "
        SELECT 
            d.id_compra_insumo,
            i.id_insumo,
            i.insumo AS nombre_insumo,
            p.nombre_proveedor AS proveedor,
            d.costo_unitario,
            d.costo_total,
            c.fecha_compra
        FROM detalle_compra_insumo d
        INNER JOIN compras_insumos c ON c.id_compra_insumo = d.id_compra_insumo
        INNER JOIN inventario_insumos i ON i.id_insumo = d.id_insumo
        INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
        ORDER BY c.fecha_compra DESC, d.id_compra_insumo DESC, i.insumo ASC
    ";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

$detalles = obtenerDetalleCompras();

// construir lista de IDs de compra únicos para el combo
$idsCompra = [];
foreach ($detalles as $r) {
    $idsCompra[$r['id_compra_insumo']] = true;
}
$idsCompra = array_keys($idsCompra);
sort($idsCompra);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Detalle de Compras de Insumos - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; }
        .toolbar { margin-bottom: 12px; display:flex; gap:8px; align-items:center; flex-wrap: wrap; }
        .table-responsive { max-height: 60vh; overflow-y: auto; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">DETALLE COMPRAS INSUMOS</h1>
        <a href="../menu_empleados_vista.php" class="btn btn-outline-dark">Regresar</a>
    </div>
</header>

<main class="container my-4">
    <section class="card shadow p-4">
        <div class="toolbar">
            <div class="d-flex align-items-center gap-2">
                <label for="filtro_compra" class="form-label mb-0">ID Compra:</label>
                <select id="filtro_compra" class="form-select form-select-sm" style="width:170px;">
                    <option value="">— Todas —</option>
                    <?php foreach ($idsCompra as $idc): ?>
                        <option value="<?= (int)$idc; ?>"><?= (int)$idc; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input id="filtro" type="search" class="form-control form-control-sm" style="min-width:280px" placeholder="Buscar por insumo, proveedor, fecha o ID de compra...">
            <div class="text-muted ms-auto">Registros: <span id="count"><?= count($detalles); ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="tabla-detalle-compras">
                <thead class="table-dark">
                    <tr>
                        <th style="width:110px">ID Compra</th>
                        
                        <th>Insumo</th>
                        <th>Proveedor</th>
                        <th style="width:140px" class="text-end">Costo Unitario</th>
                        <th style="width:140px" class="text-end">Costo Total</th>
                        <th style="width:140px">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($detalles): ?>
                    <?php foreach ($detalles as $r): ?>
                        <tr data-compra-id="<?= (int)$r['id_compra_insumo']; ?>">
                            <td class="mono"><?= (int)$r['id_compra_insumo']; ?></td>
                            
                            <td><?= htmlspecialchars($r['nombre_insumo']); ?></td>
                            <td><?= htmlspecialchars($r['proveedor']); ?></td>
                            <td class="text-end"><?= number_format((float)$r['costo_unitario'], 2, '.', ''); ?></td>
                            <td class="text-end"><?= number_format((float)$r['costo_total'], 2, '.', ''); ?></td>
                            <td><?= htmlspecialchars($r['fecha_compra']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No hay registros de compras</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filtroTexto = document.getElementById('filtro');
    const filtroCompra = document.getElementById('filtro_compra');
    const tbody = document.querySelector('#tabla-detalle-compras tbody');
    const countEl = document.getElementById('count');

    function coincideTexto(tr, q) {
        if (!q) return true;
        const t = (tr.textContent || '').toLowerCase();
        return t.indexOf(q) !== -1;
    }

    function coincideCompra(tr, idCompra) {
        if (!idCompra) return true;
        return tr.getAttribute('data-compra-id') === idCompra;
    }

    function aplicarFiltros() {
        const q = (filtroTexto.value || '').trim().toLowerCase();
        const idCompra = (filtroCompra.value || '').trim();

        let visibles = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const show = coincideTexto(tr, q) && coincideCompra(tr, idCompra);
            tr.style.display = show ? '' : 'none';
            if (show) visibles++;
        });
        countEl.textContent = visibles;
    }

    filtroTexto.addEventListener('input', aplicarFiltros);
    filtroCompra.addEventListener('change', aplicarFiltros);

    // Atajo: clic en la celda de "ID Compra" fija el filtro a ese ID
    tbody.addEventListener('click', function(e){
        const cell = e.target.closest('td');
        const row = e.target.closest('tr');
        if (!cell || !row) return;
        const colIndex = Array.prototype.indexOf.call(row.children, cell);
        if (colIndex === 0) { // primera columna = ID Compra
            const id = row.getAttribute('data-compra-id');
            if (id) {
                filtroCompra.value = id;
                aplicarFiltros();
            }
        }
    });

    // ESC borra filtro de texto
    filtroTexto.addEventListener('keydown', function(e){
        if (e.key === 'Escape') { this.value = ''; aplicarFiltros(); }
    });
});
</script>
</body>
</html>
