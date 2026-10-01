-- =====================================================================
-- Hotfix MCL Vale (set/2026) — script 03 de 4: VERIFICAÇÃO
-- SOMENTE LEITURA — não altera nada no banco.
-- =====================================================================
-- Objetivo: provar que o 02 criou atendimentos_relatorios_dias certa:
--   colunas, chave primária, UNIQUE (relatório, data), índice, FK com
--   CASCADE, engine e charset. A última consulta resume tudo numa linha:
--   todas as colunas devem vir 1 (= OK).
--
-- Ordem de execução: 01 -> 02 -> 03. O 99 é só para desfazer (rollback).
--
-- ANTES DE TUDO (antes do 02): faça BACKUP COMPLETO do banco
--   (phpMyAdmin > selecionar o banco > Exportar > Rápido > SQL).
--
-- Onde rodar: phpMyAdmin do Plesk, com o banco da MCL selecionado, aba
--   "SQL". Pode colar o arquivo inteiro e clicar em "Executar".
--
-- Aplicar os scripts ANTES de publicar o backend novo: só publique o
--   backend depois que o resumo final deste script vier todo com 1.
-- =====================================================================

SHOW CREATE TABLE atendimentos_relatorios_dias;

-- 1) Colunas — esperado, nesta ordem (11 linhas):
--    aten_rel_dia_id                     int(11)    NO   auto_increment
--    aten_rel_dia_relatorio_id           int(11)    NO
--    aten_rel_dia_data                   date       NO
--    aten_rel_dia_hora_entrada           time       YES
--    aten_rel_dia_hora_inicio_intervalo  time       YES
--    aten_rel_dia_hora_fim_intervalo     time       YES
--    aten_rel_dia_hora_saida             time       YES
--    aten_rel_dia_clima_manha            tinyint(4) YES
--    aten_rel_dia_clima_tarde            tinyint(4) YES
--    aten_rel_dia_clima_noite            tinyint(4) YES
--    aten_rel_dia_criado_em              datetime   NO   (default current_timestamp)
--    (no MySQL 8, int/tinyint aparecem sem o número entre parênteses)
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'atendimentos_relatorios_dias'
ORDER BY ORDINAL_POSITION;

-- 2) Índices — esperado:
--    PRIMARY                           único  aten_rel_dia_id
--    uq_aten_rel_dia_relatorio_data    único  aten_rel_dia_relatorio_id,aten_rel_dia_data
--    fk_aten_rel_dia_relatorio_id_idx  comum  aten_rel_dia_relatorio_id
SELECT INDEX_NAME,
       CASE WHEN NON_UNIQUE = 0 THEN 'unico' ELSE 'comum' END AS tipo,
       GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS colunas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'atendimentos_relatorios_dias'
GROUP BY INDEX_NAME, NON_UNIQUE
ORDER BY INDEX_NAME;

-- 3) Chave estrangeira — esperado: fk_aten_rel_dia_relatorio_id ->
--    atendimentos_relatorios.aten_rel_id, ON UPDATE CASCADE, ON DELETE CASCADE
SELECT rc.CONSTRAINT_NAME,
       kcu.COLUMN_NAME,
       rc.REFERENCED_TABLE_NAME,
       kcu.REFERENCED_COLUMN_NAME,
       rc.UPDATE_RULE,
       rc.DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS rc
JOIN information_schema.KEY_COLUMN_USAGE kcu
  ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
 AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
  AND rc.TABLE_NAME = 'atendimentos_relatorios_dias';

-- 4) RESUMO — tudo deve vir 1
SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias') = 1
        AS tabela_ok,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias') = 11
        AS colunas_ok,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias'
        AND COLUMN_NAME = 'aten_rel_dia_relatorio_id'
        AND DATA_TYPE = 'int' AND COLUMN_TYPE NOT LIKE '%unsigned%' AND IS_NULLABLE = 'NO') = 1
        AS relatorio_id_int_signed_ok,
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias'
        AND INDEX_NAME = 'uq_aten_rel_dia_relatorio_data' AND NON_UNIQUE = 0) = 2
        AS unique_relatorio_data_ok,
    (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias'
        AND CONSTRAINT_NAME = 'fk_aten_rel_dia_relatorio_id'
        AND REFERENCED_TABLE_NAME = 'atendimentos_relatorios'
        AND UPDATE_RULE = 'CASCADE' AND DELETE_RULE = 'CASCADE') = 1
        AS fk_cascade_ok,
    (SELECT ENGINE FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias') = 'InnoDB'
        AS engine_ok,
    (SELECT TABLE_COLLATION FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos_relatorios_dias')
        IN ('utf8_unicode_ci', 'utf8mb3_unicode_ci')
        AS collation_ok;

-- 5) Registro na tabela `migrations` (só se o bloco final do 02 foi usado)
--    Esperado: 1 linha. Fica por último de propósito: se a tabela
--    `migrations` não existir, esta consulta dá erro, mas o resumo acima
--    já foi mostrado.
SELECT id, migration, batch
FROM `migrations`
WHERE migration = '2026_09_30_120000_create_atendimentos_relatorios_dias_table';
