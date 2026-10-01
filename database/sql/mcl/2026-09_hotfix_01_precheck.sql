-- =====================================================================
-- Hotfix MCL Vale (set/2026) — script 01 de 4: PRÉ-CHECAGEM
-- SOMENTE LEITURA — não altera nada no banco.
-- =====================================================================
-- Objetivo: conferir, antes de aplicar o 02, que o banco está como o
--   hotfix espera: versão do servidor, tipo/charset de
--   atendimentos_relatorios.aten_rel_id (a tabela nova aponta para ela) e
--   que a tabela nova atendimentos_relatorios_dias ainda NÃO existe.
--
-- Ordem de execução: 01 -> 02 -> 03. O 99 é só para desfazer (rollback).
--
-- ANTES DE TUDO: faça BACKUP COMPLETO do banco
--   (phpMyAdmin > selecionar o banco > Exportar > Rápido > SQL).
--
-- Onde rodar: phpMyAdmin do Plesk, com o banco da MCL selecionado, aba
--   "SQL". Pode colar o arquivo inteiro e clicar em "Executar".
--
-- Aplicar os scripts ANTES de publicar o backend novo: o backend novo
--   consulta a tabela atendimentos_relatorios_dias ao abrir qualquer
--   relatório — se o backend subir antes da tabela existir, NENHUM
--   relatório abre (nem no app, nem na web, nem o PDF).
-- =====================================================================

-- 1) Versão do servidor (produção MCL: MariaDB 10.4.x)
SELECT VERSION() AS versao_servidor;

-- 2) Estrutura da tabela de relatórios. Conferir no resultado:
--    `aten_rel_id` int(11) NOT NULL AUTO_INCREMENT   (int SEM "unsigned")
--    ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
--    (no MySQL 8 o mesmo charset aparece como utf8mb3 / utf8mb3_unicode_ci)
SHOW CREATE TABLE atendimentos_relatorios;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'atendimentos_relatorios'
  AND COLUMN_NAME = 'aten_rel_id';
-- Esperado: COLUMN_TYPE = int(11) (ou "int" no MySQL 8), sem "unsigned".

-- 3) A tabela nova ainda não pode existir.
SELECT
    COUNT(*) AS tabela_dias_existe,
    CASE WHEN COUNT(*) = 0
         THEN 'OK - pode rodar o 02'
         ELSE 'ATENCAO - a tabela ja existe; o 02 nao recria nada, pule para o 03'
    END AS resultado
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'atendimentos_relatorios_dias';

-- 4) A tabela `migrations` do Laravel existe? (decide se o bloco final do
--    02 deve ser usado)
SELECT COUNT(*) AS tabela_migrations_existe
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'migrations';
