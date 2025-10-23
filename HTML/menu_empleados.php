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
        header('Location: login_empleado.php');
        exit();
    }
    ?>
    
    <header>
        <h1>
            <img src="../image/Logo.png" width="60" height="60" alt="Marea Roja"> 
            Marea Roja
        </h1>
        <div class="user-info">
            Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>
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
                <a href="#" class="submenu-toggle"><span>🪑</span> Gestión de Mobiliario</a>
                <ul class="submenu">
                    <li><a href="../HTML/gestion_de_mobiliario/gestion_mobiliario.php">Control del Mobiliario</a></li>
                    <li><a href="../HTML/gestion_de_mobiliario/compras_mobiliario.php">Gestion de Compras</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>🚗</span> Gestión de Vehiculos</a>
                <ul class="submenu">
                    <li><a href="../HTML/gestion_de_vehiculos/gestion_vehiculos.php">Gestion de Vehiculos</a></li>
                    <li><a href="gestion_de_vehiculos/mantenimiento_vehiculos.php">mantenimiento</a></li>
                    <li><a href="gestion_de_vehiculos/viajes_vehiculos.php">Viajes</a></li>
                    <li><a href="rutas_vehiculos.html">Rutas</a></li>
                </ul>
            </li>

            <li>
                <a href="#" class="submenu-toggle"><span>⚙️</span> Taller de vechiculos</a>
                <ul class="submenu">
                    <li><a href="../HTML/taller_de_vehiculos/taller_vehiculos.php">Taller</a></li>
                </ul>
            </li>
            
            <li>
                <a href="#" class="submenu-toggle"><span>🥘</span> Inventario Ingredientes</a>
                <ul class="submenu">
                    <li><a href="Gestion_Inventario_Ingredientes.html">Gestion de Ingrediente</a></li>
                    <li><a href="Gestion_Compras_Inventarios_Ingredientes.html">Compra de Ingredientes</a></li>
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
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['usuario']); ?></p>
        </div>
    </div>

    <footer>
        &copy; 2025 Marea Roja - Sistema de Gestión
    </footer>
    <script src="../javascript/submenu.js"></script>
</body>
</html>