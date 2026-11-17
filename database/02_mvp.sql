-- =============================================================
-- Acolhe+ | Ajustes do MVP (Sprint 1)
-- Controle de tentativas de login (US01: bloqueio após 5 tentativas)
-- =============================================================
USE acolhe_mais;
SET NAMES utf8mb4;

ALTER TABLE usuario
  ADD COLUMN tentativas_login TINYINT NOT NULL DEFAULT 0 AFTER senha_hash,
  ADD COLUMN bloqueado_ate    DATETIME NULL AFTER tentativas_login;
