<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Empleado</title>
    <link rel="stylesheet" href="../css/login_empleado.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Ingreso de Empleados</h1>
            
            <!-- Mostrar error si existe -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- El formulario ahora apunta a procesar_login.php -->
            <form action="procesar_login.php" method="post">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" placeholder="Ingresa tu contraseña" required>
                </div>
                <button type="submit" class="btn">Ingresar</button>
                <div class="create-user-section">
                  
                </div>
                <p class="link">¿Olvidaste tu contraseña? <a href="#">Recupérala aquí</a></p>
            </form>
        </div>
    </div>
</body>
</html>