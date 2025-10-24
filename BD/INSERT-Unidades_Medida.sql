use marea_roja_db;
ALTER TABLE ingredientes 
ADD COLUMN descripcion VARCHAR(200) NULL AFTER nombre_ingrediente;


INSERT INTO unidades_medida (unidad, abreviatura) VALUES
-- Unidades de peso
('Gramos', 'g'),
('Kilogramos', 'kg'),
('Libras', 'lb'),
('Onzas', 'oz'),

-- Unidades de volumen
('Mililitros', 'ml'),
('Litros', 'l'),
('Tazas', 'taza'),
('Cucharadas', 'cda'),
('Cucharaditas', 'cdta'),
('Pizca', 'pizca'),

-- Unidades de cantidad
('Unidades', 'ud'),
('Docenas', 'doc'),
('Paquetes', 'pqt'),
('Latas', 'lata'),
('Botellas', 'bot'),
('Sobres', 'sobre'),

-- Unidades de longitud
('Centímetros', 'cm'),
('Metros', 'm'),

-- Otras unidades
('Raciones', 'rac'),
('Porciones', 'por'),
('Filetes', 'filete'),
('Rodajas', 'rod'),
('Dientes', 'dte'),
('Manojos', 'manojo'),
('Ramos', 'ramo');