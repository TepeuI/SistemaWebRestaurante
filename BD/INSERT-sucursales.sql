-- Insertar sucursales de prueba
INSERT IGNORE INTO sucursales (direccion, horario_apertura, hora_cierre, capacidad_empleados, telefono, correo) 
VALUES 
('Zona 1, Ciudad', '08:00:00', '18:00:00', 20, '1234-5678', 'sucursal1@marea-roja.com'),
('Zona 10, Ciudad', '09:00:00', '19:00:00', 15, '8765-4321', 'sucursal2@marea-roja.com'),
('Centro Comercial Megaplex', '10:00:00', '22:00:00', 25, '5555-1234', 'sucursal3@marea-roja.com');