-- ============================================================================
--  SIGAMETEC — Intentos extra por alumno (examen)
-- ============================================================================
--  Permite que el docente otorgue intentos adicionales a un estudiante
--  concreto, SIN modificar examenes.intentos_permitidos (que aplica a todo
--  el curso) y SIN borrar el historial de intentos del alumno.
--
--  El limite efectivo pasa a ser:
--      examenes.intentos_permitidos + COALESCE(examen_intentos_extra.intentos_extra, 0)
--
--  Esta tabla es ademas la bitacora de la accion: guarda quien otorgado el
--  intento, cuando y por que, para poder responder reclamos.
--
--  El intento adicional es de ULTIMA INSTANCIA: como maximo 1 por alumno. Un
--  segundo otorgamiento se rechaza en vez de acumular, y por eso la fila es
--  siempre la bitacora completa (nunca se pisa un motivo anterior).
--
--  IMPORTANTE: el historial NUNCA se borra. Un alumno que repite conserva
--  todos sus intentos anteriores, y la nota que se muestra es la mejor
--  (MAX(nota)), no la ultima.
--
--  Es idempotente: se puede ejecutar varias veces sin duplicar nada.
-- ============================================================================

CREATE TABLE IF NOT EXISTS examen_intentos_extra (
  id             INT          NOT NULL AUTO_INCREMENT,
  examen_id      INT          NOT NULL,
  estudiante_id  INT          NOT NULL,
  intentos_extra INT          NOT NULL DEFAULT 1,
  motivo         VARCHAR(255) NULL,
  otorgado_por   INT          NULL,
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_examen_estudiante (examen_id, estudiante_id),
  KEY idx_extra_examen (examen_id),
  KEY idx_extra_estudiante (estudiante_id),
  CONSTRAINT fk_extra_examen     FOREIGN KEY (examen_id)     REFERENCES examenes (id) ON DELETE CASCADE,
  CONSTRAINT fk_extra_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id)  ON DELETE CASCADE,
  CONSTRAINT fk_extra_otorgante  FOREIGN KEY (otorgado_por)  REFERENCES usuarios (id)  ON DELETE SET NULL,
  CONSTRAINT chk_extra_tope      CHECK (intentos_extra BETWEEN 0 AND 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existia con un CHECK mas laxo, se agrega el tope de 1.
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE examen_intentos_extra
       ADD CONSTRAINT chk_extra_tope CHECK (intentos_extra BETWEEN 0 AND 1)',
    'DO 0')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'examen_intentos_extra'
    AND CONSTRAINT_NAME = 'chk_extra_tope'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
