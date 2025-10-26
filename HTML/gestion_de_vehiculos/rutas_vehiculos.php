<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear_ruta':
            crearRuta();
            break;
        case 'actualizar_ruta':
            actualizarRuta();
            break;
        case 'eliminar_ruta':
            eliminarRuta();
            break;
    }
}

function crearRuta() {
    global $conn;
    $conn = conectar();
    
    $descripcion_ruta = $_POST['descripcion_ruta'] ?? '';
    $inicio_ruta = $_POST['inicio_ruta'] ?? '';
    $fin_ruta = $_POST['fin_ruta'] ?? '';
    $gasolina_aproximada = $_POST['gasolina_aproximada'] ?? 0;
    
    $sql = "INSERT INTO rutas (descripcion_ruta, inicio_ruta, fin_ruta, gasolina_aproximada) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function actualizarRuta() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    $descripcion_ruta = $_POST['descripcion_ruta'] ?? '';
    $inicio_ruta = $_POST['inicio_ruta'] ?? '';
    $fin_ruta = $_POST['fin_ruta'] ?? '';
    $gasolina_aproximada = $_POST['gasolina_aproximada'] ?? 0;
    
    $sql = "UPDATE rutas SET descripcion_ruta = ?, inicio_ruta = ?, fin_ruta = ?, gasolina_aproximada = ? 
            WHERE id_ruta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada, $id_ruta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function eliminarRuta() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    
    $sql = "DELETE FROM rutas WHERE id_ruta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ruta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

// Obtener todas las rutas para mostrar en la tabla
function obtenerRutas() {
    $conn = conectar();
    
    $sql = "SELECT * FROM rutas ORDER BY descripcion_ruta";
    $resultado = $conn->query($sql);
    $rutas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $rutas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $rutas;
}

$rutas = obtenerRutas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutas - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
        .mensaje {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-action {
            margin: 2px;
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 12px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .gasolina {
            text-align: right;
            font-weight: bold;
        }
        .ruta-info {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .descripcion-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE RUTAS DE VEHÍCULOS</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <!-- Mostrar mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['mensaje']); 
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Debug info -->
        <div class="debug-info">
            <strong>Debug:</strong> 
            <?php 
            echo "Rutas registradas: " . count($rutas);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE RUTAS</h2>

            <form id="form-rutas" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_ruta">
                <input type="hidden" id="id_ruta" name="id_ruta" value="">
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_ruta">Descripción de la Ruta:</label>
                    <input type="text" class="form-control" id="descripcion_ruta" name="descripcion_ruta" 
                           required placeholder="Ej. Ruta de entregas zona centro">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="inicio_ruta">Punto de Inicio:</label>
                    <input type="text" class="form-control" id="inicio_ruta" name="inicio_ruta" 
                           placeholder="Ej. Bodega principal">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fin_ruta">Punto Final:</label>
                    <input type="text" class="form-control" id="fin_ruta" name="fin_ruta" 
                           placeholder="Ej. Última entrega">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="gasolina_aproximada">Gasolina Aproximada (Litros):</label>
                    <input type="number" step="0.01" class="form-control" id="gasolina_aproximada" name="gasolina_aproximada" 
                           min="0" placeholder="Ej. 15.50">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">RUTAS REGISTRADAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-rutas">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Gasolina (L)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rutas as $ruta): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ruta['id_ruta']); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>">
                                <?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($ruta['inicio_ruta'] ?? 'No especificado'); ?></td>
                            <td><?php echo htmlspecialchars($ruta['fin_ruta'] ?? 'No especificado'); ?></td>
                            <td class="gasolina">
                                <?php echo $ruta['gasolina_aproximada'] ? number_format($ruta['gasolina_aproximada'], 2) . ' L' : 'No especificado'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $ruta['id_ruta']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>"
                                        data-inicio="<?php echo htmlspecialchars($ruta['inicio_ruta'] ?? ''); ?>"
                                        data-fin="<?php echo htmlspecialchars($ruta['fin_ruta'] ?? ''); ?>"
                                        data-gasolina="<?php echo $ruta['gasolina_aproximada']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta ruta?')">
                                    <input type="hidden" name="operacion" value="eliminar_ruta">
                                    <input type="hidden" name="id_ruta" value="<?php echo $ruta['id_ruta']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rutas)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay rutas registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-rutas');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idRutaInput = document.getElementById('id_ruta');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear_ruta';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar_ruta';
                    form.submit();
                }
            });

            // Botón Cancelar
            btnCancelar.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Eventos para botones Editar
            document.querySelectorAll('.editar-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const descripcion = this.getAttribute('data-descripcion');
                    const inicio = this.getAttribute('data-inicio');
                    const fin = this.getAttribute('data-fin');
                    const gasolina = this.getAttribute('data-gasolina');

                    // Llenar formulario
                    idRutaInput.value = id;
                    document.getElementById('descripcion_ruta').value = descripcion;
                    document.getElementById('inicio_ruta').value = inicio;
                    document.getElementById('fin_ruta').value = fin;
                    document.getElementById('gasolina_aproximada').value = gasolina;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idRutaInput.value = '';
                operacionInput.value = 'crear_ruta';
            }

            function mostrarBotonesGuardar() {
                btnGuardar.style.display = 'inline-block';
                btnActualizar.style.display = 'none';
                btnCancelar.style.display = 'none';
            }

            function mostrarBotonesActualizar() {
                btnGuardar.style.display = 'none';
                btnActualizar.style.display = 'inline-block';
                btnCancelar.style.display = 'inline-block';
            }

            function validarFormulario() {
                const descripcion = document.getElementById('descripcion_ruta').value.trim();
                const gasolina = document.getElementById('gasolina_aproximada').value;

                if (!descripcion) {
                    alert('La descripción de la ruta es requerida');
                    return false;
                }
                if (gasolina && gasolina < 0) {
                    alert('La gasolina aproximada no puede ser negativa');
                    return false;
                }

                return true;
            }

            // Inicializar
            limpiarFormulario();
        });
    </script>
</body>
</html>