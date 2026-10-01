-- =====================================================================
-- Hotfix MCL Vale (set/2026) — script 02 de 4: APLICAÇÃO (altera o banco)
-- =====================================================================
-- Objetivo: criar a tabela atendimentos_relatorios_dias (horário e clima
--   lançados por dia no relatório — uma linha por relatório + data).
--   Este hotfix NÃO altera nenhuma tabela existente e não mexe em nenhum
--   dado: relatórios com horário/clima no formato antigo continuam como
--   estão (o sistema passa a mostrá-los somente leitura).
--
-- Ordem de execução: 01 -> 02 -> 03. O 99 é só para desfazer (rollback).
--
-- ANTES DE TUDO: faça BACKUP COMPLETO do banco
--   (phpMyAdmin > selecionar o banco > Exportar > Rápido > SQL).
--
-- Onde rodar: phpMyAdmin do Plesk, com o banco da MCL selecionado, aba
--   "SQL". Pode colar o arquivo inteiro e clicar em "Executar".
--
-- Aplicar ANTES de publicar o backend novo: o backend novo consulta esta
--   tabela ao abrir qualquer relatório — se o backend subir antes dela
--   existir, NENHUM relatório abre (nem no app, nem na web, nem o PDF).
--
-- Idempotente: pode rodar mais de uma vez sem erro e sem duplicar nada
--   (CREATE TABLE IF NOT EXISTS; o INSERT do final só insere se faltar).
--
-- Equivalente à migration Laravel
--   database/migrations/2026_09_30_120000_create_atendimentos_relatorios_dias_table.php
--   (SHOW CREATE TABLE idêntico ao gerado por ela).
-- =====================================================================

-- Charset/collation iguais às tabelas vizinhas: "utf8" é o nome usado pela
-- produção (MariaDB 10.4); no MySQL 8 o mesmo charset se chama utf8mb3.
-- aten_rel_dia_relatorio_id é int SIGNED, igual a atendimentos_relatorios.aten_rel_id
-- (FK exige o mesmo tipo dos dois lados).
-- Clima: 1 = ensolarado, 2 = nublado, 3 = chuvoso, NULL = não informado.
CREATE TABLE IF NOT EXISTS `atendimentos_relatorios_dias` (
  `aten_rel_dia_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_dia_relatorio_id` int NOT NULL,
  `aten_rel_dia_data` date NOT NULL,
  `aten_rel_dia_hora_entrada` time DEFAULT NULL,
  `aten_rel_dia_hora_inicio_intervalo` time DEFAULT NULL,
  `aten_rel_dia_hora_fim_intervalo` time DEFAULT NULL,
  `aten_rel_dia_hora_saida` time DEFAULT NULL,
  `aten_rel_dia_clima_manha` tinyint DEFAULT NULL,
  `aten_rel_dia_clima_tarde` tinyint DEFAULT NULL,
  `aten_rel_dia_clima_noite` tinyint DEFAULT NULL,
  `aten_rel_dia_criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`aten_rel_dia_id`),
  UNIQUE KEY `uq_aten_rel_dia_relatorio_data` (`aten_rel_dia_relatorio_id`, `aten_rel_dia_data`),
  KEY `fk_aten_rel_dia_relatorio_id_idx` (`aten_rel_dia_relatorio_id`),
  CONSTRAINT `fk_aten_rel_dia_relatorio_id`
    FOREIGN KEY (`aten_rel_dia_relatorio_id`)
    REFERENCES `atendimentos_relatorios` (`aten_rel_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ---------------------------------------------------------------------
-- Registro na tabela `migrations` do Laravel
-- ---------------------------------------------------------------------
-- Use SOMENTE se a tabela `migrations` existir (o script 01, item 4,
-- mostra isso). Na produção MCL ela EXISTE (confirmado no dump de
-- 03/09/2026 e pelo `php artisan migrate` rodado naquela data), então:
-- DESCOMENTE e rode as linhas abaixo.
--
-- Por quê: sem este registro, um `php artisan migrate` futuro tentaria
-- criar a tabela de novo e falharia ("table already exists").
-- Só insere se ainda não houver o registro (pode rodar mais de uma vez):
-- o HAVING descarta a linha quando o registro já existe.
--
-- INSERT INTO `migrations` (`migration`, `batch`)
-- SELECT '2026_09_30_120000_create_atendimentos_relatorios_dias_table',
--        COALESCE(MAX(`batch`), 0) + 1
-- FROM `migrations`
-- HAVING COALESCE(SUM(`migration` = '2026_09_30_120000_create_atendimentos_relatorios_dias_table'), 0) = 0;
