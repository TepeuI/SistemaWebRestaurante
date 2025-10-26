<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marea Roja | Ventas</title>
    <link rel="stylesheet" href="../css/diseñoModulos.css">
    <link rel="stylesheet" href="../css/diseñoMenuEmpleados.css">
</head>
<body id="body-empleados">
    <?php
    session_start();
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: login.php');
        exit();
    }
    ?>
    
    <header>
        <h1>
            <img src="../image/Logo.png" width="60" height="60" alt="Marea Roja"> 
            Marea Roja
        </h1>
        <div class="user-info">
            Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'No disponible'); ?>
        </div>
    </header>
    
    <div class="sidebar-empleados">
        <ul>
            <li>
                <a href="#" class="submenu-toggle"><span>📅</span> Reservaciones</a>
                <ul class="submenu">
                    <li><a href="Reservaciones.html">Nueva Reservación</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>👥</span> Gestión de Empleados</a>
                <ul class="submenu">
                    <li><a href="gestion_empleados/Empleados.php">Empleados</a></li>
                    <li><a href="gestion_empleados/Telefono_empleados.php">Teléfonos</a></li>
                    <li><a href="gestion_empleados/Correo_empleados.php">Correos</a></li>

                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>📍</span> Gestión Departamental</a>
                <ul class="submenu">
                    <li><a href="Departamental/Sucursales.php">Sucursales</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🪑</span> Gestión de Mobiliario</a>
                <ul class="submenu">
                    <li><a href="../HTML/gestion_de_mobiliario/gestion_mobiliario.php">Control del Mobiliario</a></li>
                    <li><a href="../HTML/gestion_de_mobiliario/compras_mobiliario.php">Gestion de Compras</a></li>
                    <li><a href="../HTML/gestion_de_mobiliario/detalle_compras_mobiliario.php">detalle de Compras</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🛠️</span> mantenimiento de Mobiliario</a>
                <ul class="submenu">
                    <li><a href="../HTML/mantenimiento_de_Mobiliario/mantenimiento_muebles.php">mantenimiento de muebles</a></li>
                    <li><a href="../HTML/mantenimiento_de_Mobiliario/mantenimiento_electrodomesticos.php">mantenimiento de electrónica</a></li>
                    
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🚗</span> Gestión de Vehiculos</a>
                <ul class="submenu">
                    <li><a href="../HTML/gestion_de_vehiculos/gestion_vehiculos.php">Gestion de Vehiculos</a></li>
                    <li><a href="../HTML/gestion_de_vehiculos/mantenimiento_vehiculos.php">mantenimiento</a></li>
                    <li><a href="../HTML/gestion_de_vehiculos/viajes_vehiculos.php">Viajes</a></li>
                    <li><a href="../HTML/gestion_de_vehiculos/rutas_vehiculos.php">Rutas</a></li>
                    <li><a href="../HTML/gestion_de_vehiculos/reportes_accidentes.php">Accidentes</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>⚙️</span> Taller de vechiculos</a>
                <ul class="submenu">
                    <li><a href="../HTML/taller_de_vehiculos/taller_vehiculos.php">Taller</a></li>
                </ul>
            </li>
            
             <li>
                <a href="#" class="submenu-toggle"><span>🍺🍽️</span> Platos Y Bebidas</a>
                <ul class="submenu">
                    <li><a href="../HTML/Recetas_platos_Bebida_Orden/platos.php">Platos</a></li>
                    <li><a href="../HTML/Recetas_platos_Bebida_Orden/bebidas.php">Bebidas</a></li>
                    <li><a href="../HTML/Recetas_platos_Bebida_Orden/recetas.php">Recetas</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🦞</span> Inventario Ingredientes</a>
                <ul class="submenu">
                    <li><a href="../HTML/inventario_ingredientes/Gestion_Inventario_Ingredientes.php">Gestion de Ingredientes</a></li>
                    <li><a href="../HTML/inventario_ingredientes/Control_Ingrediente.php">Control de Ingredientes</a></li>
                    <li><a href="../HTML/inventario_ingredientes/Perdida_Ingrediente.php">Perdida de Ingredientes</a></li>
                </ul>
            </li>
            
             <li>
                <a href="#" class="submenu-toggle"><span>🦀🛒</span> Compra de Ingredientes</a>
                <ul class="submenu">
                    <li><a href="../HTML/inventario_ingredientes/Gestion_Compras_Inventarios_Ingredientes.php">Gestion de Compra</a></li>
                    <li><a href="../HTML/inventario_ingredientes/Detalle_Compras_Ingredientes.php">Detalle de Compras</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>👤</span> Proveedores</a>
                <ul class="submenu">
                    <li><a href="../HTML/proveedores/gestion_proveedores.php">Gestion de Proveedores</a></li>
 
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>💰</span> Facturaciones</a>
                <ul class="submenu">
                    <li><a href="Facturacion_Ventas.html">Nueva Factura</a></li>
                </ul>
            </li>
            
            <li><a href="login.php"><span>🚪</span> Cerrar Sesión</a></li>
        </ul>
    </div>

    <div class="container">
        <h2>🏢 Panel de Control - Empleados</h2>
        <div id="tabla-reservaciones">
            <h3>¡Bienvenido al Sistema de Gestión Marea Roja!</h3>
            <p>Selecciona una opción del menú lateral para comenzar a gestionar el restaurante</p>
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'No disponible'); ?></p>
        </div>
    </div>

    <footer>
        &copy; 2025 Marea Roja - Sistema de Gestión
    </footer>
    <script src="../javascript/submenu.js"></script>
</body>
</html>