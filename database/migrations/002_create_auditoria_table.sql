CREATE TABLE IF NOT EXISTS auditoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED DEFAULT NULL,
    accion VARCHAR(50) NOT NULL,
    entidad VARCHAR(50) DEFAULT NULL,
    entidad_id INT UNSIGNED DEFAULT NULL,
    detalle TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_accion (accion),
    INDEX idx_auditoria_entidad (entidad, entidad_id),
    INDEX idx_auditoria_usuario (usuario_id),
    INDEX idx_auditoria_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
