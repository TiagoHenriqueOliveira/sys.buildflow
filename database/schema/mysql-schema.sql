/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `atendimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos` (
  `aten_id` int NOT NULL AUTO_INCREMENT,
  `aten_natureza_id` int NOT NULL,
  `aten_cliente_id` int NOT NULL,
  `aten_usuario_id` int NOT NULL,
  `aten_status` int NOT NULL DEFAULT '0' COMMENT '0 - Não iniciada\n             1 - Paralisada\n             2 - Em andamento\n             3 - Concluída',
  `aten_nr_proposta` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `aten_responsavel` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `aten_telefone` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `aten_endereco` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `aten_entrega_tecnica` tinyint(1) NOT NULL DEFAULT '0',
  `aten_dt_inicio` date NOT NULL,
  `aten_dt_fim` date NOT NULL,
  `aten_obs_tecnica` longtext COLLATE utf8mb3_unicode_ci,
  `aten_obs_cliente` longtext COLLATE utf8mb3_unicode_ci,
  `aten_obs_manutencao` text COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`aten_id`),
  KEY `fk_aten_natureza_id_idx` (`aten_natureza_id`),
  KEY `fk_aten_cliente_id_idx` (`aten_cliente_id`),
  KEY `fk_aten_usuario_id_idx` (`aten_usuario_id`),
  CONSTRAINT `fk_aten_cliente_id` FOREIGN KEY (`aten_cliente_id`) REFERENCES `clientes` (`cli_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aten_natureza_id` FOREIGN KEY (`aten_natureza_id`) REFERENCES `naturezas_atendimentos` (`nat_aten_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_aten_usuario_id` FOREIGN KEY (`aten_usuario_id`) REFERENCES `usuarios` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_anexos` (
  `aten_anexo_id` int unsigned NOT NULL AUTO_INCREMENT,
  `aten_anexo_atendimento_id` int unsigned NOT NULL,
  `aten_anexo_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aten_anexo_nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aten_anexo_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`aten_anexo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_equipamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_equipamentos` (
  `aten_equip_id` int NOT NULL AUTO_INCREMENT,
  `aten_equip_atendimento_id` int NOT NULL,
  `aten_equip_descricao` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`aten_equip_id`),
  KEY `fk_aten_equip_atendimento_id_idx` (`aten_equip_atendimento_id`),
  CONSTRAINT `fk_aten_equip_atendimento_id` FOREIGN KEY (`aten_equip_atendimento_id`) REFERENCES `atendimentos` (`aten_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios` (
  `aten_rel_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_atendimento_id` int NOT NULL,
  `aten_rel_modelo_relatorio_id` int NOT NULL,
  `aten_rel_status` int NOT NULL DEFAULT '0' COMMENT '0 - preenchendo | 1 - revisar | 2 - aprovado',
  `aten_rel_descricao` longtext COLLATE utf8mb3_unicode_ci,
  `aten_rel_informacoes_adicionais` longtext COLLATE utf8mb3_unicode_ci,
  `aten_rel_dt_fim` date DEFAULT NULL,
  `aten_rel_data` date NOT NULL,
  PRIMARY KEY (`aten_rel_id`),
  KEY `fk_aten_rel_atendimento_id` (`aten_rel_atendimento_id`),
  KEY `fk_aten_rel_modelo_relatorio_id` (`aten_rel_modelo_relatorio_id`),
  KEY `idx_aten_rel_atendimento` (`aten_rel_atendimento_id`),
  CONSTRAINT `fk_aten_rel_atendimento_id` FOREIGN KEY (`aten_rel_atendimento_id`) REFERENCES `atendimentos` (`aten_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aten_rel_modelo_relatorio_id` FOREIGN KEY (`aten_rel_modelo_relatorio_id`) REFERENCES `modelos_relatorios` (`mod_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_anexos` (
  `aten_rel_anexo_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_anexo_relatorio_id` int NOT NULL,
  `aten_rel_anexo_path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`aten_rel_anexo_id`),
  KEY `fk_aten_rel_anexo_relatorio_id` (`aten_rel_anexo_relatorio_id`),
  CONSTRAINT `fk_aten_rel_anexo_relatorio_id` FOREIGN KEY (`aten_rel_anexo_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_assinaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_assinaturas` (
  `aten_rel_ass_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_ass_relatorio_id` int NOT NULL,
  `aten_rel_ass_path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `aten_rel_ass_tipo` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `aten_rel_ass_assinado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`aten_rel_ass_id`),
  KEY `fk_aten_rel_ass_relatorio_id_idx` (`aten_rel_ass_relatorio_id`),
  CONSTRAINT `fk_aten_rel_ass_relatorio_id` FOREIGN KEY (`aten_rel_ass_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_condicoes_climaticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_condicoes_climaticas` (
  `aten_rel_clima_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_clima_relatorio_id` int NOT NULL,
  `aten_rel_clima_periodo` int NOT NULL COMMENT '0 - Manhã | 1 - Tarde | 2 - Noite',
  `aten_rel_clima_condicao` int NOT NULL COMMENT '0 - Claro | 1 - Nublado | 2 - Chuvoso',
  PRIMARY KEY (`aten_rel_clima_id`),
  UNIQUE KEY `uq_aten_rel_clima_relatorio_periodo` (`aten_rel_clima_relatorio_id`,`aten_rel_clima_periodo`),
  KEY `fk_aten_rel_clima_relatorio_id` (`aten_rel_clima_relatorio_id`),
  CONSTRAINT `fk_aten_rel_clima_relatorio_id` FOREIGN KEY (`aten_rel_clima_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_fotos` (
  `aten_rel_foto_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_foto_relatorio_id` int NOT NULL,
  `aten_rel_foto_path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `aten_rel_foto_legenda` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`aten_rel_foto_id`),
  KEY `fk_aten_rel_foto_relatorio_id` (`aten_rel_foto_relatorio_id`),
  CONSTRAINT `fk_aten_rel_foto_relatorio_id` FOREIGN KEY (`aten_rel_foto_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_horarios` (
  `aten_rel_hora_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_hora_relatorio_id` int NOT NULL,
  `aten_rel_hora_entrada` time NOT NULL,
  `aten_rel_hora_inicio_intervalo` time DEFAULT NULL,
  `aten_rel_hora_fim_intervalo` time DEFAULT NULL,
  `aten_rel_hora_saida` time NOT NULL,
  PRIMARY KEY (`aten_rel_hora_id`),
  UNIQUE KEY `uq_aten_rel_hora_relatorio` (`aten_rel_hora_relatorio_id`),
  KEY `fk_aten_rel_hora_relatorio_id` (`aten_rel_hora_relatorio_id`),
  CONSTRAINT `fk_aten_rel_hora_relatorio_id` FOREIGN KEY (`aten_rel_hora_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_ocorrencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_ocorrencias` (
  `aten_rel_ocor_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_ocor_relatorio_id` int NOT NULL,
  `aten_rel_ocor_ocorrencia_id` int NOT NULL,
  `aten_rel_ocor_observacao` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`aten_rel_ocor_id`),
  UNIQUE KEY `uq_aten_rel_ocor_relatorio_ocorrencia` (`aten_rel_ocor_relatorio_id`,`aten_rel_ocor_ocorrencia_id`),
  KEY `fk_aten_rel_ocor_relatorio_id` (`aten_rel_ocor_relatorio_id`),
  KEY `fk_aten_rel_ocor_ocorrencia_id` (`aten_rel_ocor_ocorrencia_id`),
  CONSTRAINT `fk_aten_rel_ocor_ocorrencia_id` FOREIGN KEY (`aten_rel_ocor_ocorrencia_id`) REFERENCES `ocorrencias` (`ocor_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_aten_rel_ocor_relatorio_id` FOREIGN KEY (`aten_rel_ocor_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_pecas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_pecas` (
  `aten_rel_peca_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_peca_relatorio_id` int NOT NULL,
  `aten_rel_peca_descricao` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`aten_rel_peca_id`),
  KEY `aten_rel_peca_relatorio_id` (`aten_rel_peca_relatorio_id`),
  CONSTRAINT `atendimentos_relatorios_pecas_ibfk_1` FOREIGN KEY (`aten_rel_peca_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_servicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_servicos` (
  `aten_rel_serv_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_serv_relatorio_id` int NOT NULL,
  `aten_rel_serv_descricao` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`aten_rel_serv_id`),
  KEY `aten_rel_serv_relatorio_id` (`aten_rel_serv_relatorio_id`),
  CONSTRAINT `atendimentos_relatorios_servicos_ibfk_1` FOREIGN KEY (`aten_rel_serv_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `atendimentos_relatorios_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `atendimentos_relatorios_videos` (
  `aten_rel_vid_id` int NOT NULL AUTO_INCREMENT,
  `aten_rel_vid_relatorio_id` int NOT NULL,
  `aten_rel_vid_path` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`aten_rel_vid_id`),
  KEY `fk_aten_rel_vid_relatorio_id` (`aten_rel_vid_relatorio_id`),
  CONSTRAINT `fk_aten_rel_vid_relatorio_id` FOREIGN KEY (`aten_rel_vid_relatorio_id`) REFERENCES `atendimentos_relatorios` (`aten_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `cli_id` int NOT NULL AUTO_INCREMENT,
  `cli_nome` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cli_cnpj` varchar(14) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cli_cidade` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cli_uf` varchar(2) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cli_telefone` varchar(11) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cli_email` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cli_ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`cli_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_auditoria` (
  `log_aud_id` int unsigned NOT NULL AUTO_INCREMENT,
  `log_aud_modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_aud_acao` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_aud_registro_id` int DEFAULT NULL,
  `log_aud_usuario_id` int DEFAULT NULL,
  `log_aud_dados_anteriores` json DEFAULT NULL,
  `log_aud_dados_novos` json DEFAULT NULL,
  `log_aud_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_aud_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_erros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_erros` (
  `log_err_id` int unsigned NOT NULL AUTO_INCREMENT,
  `log_err_modulo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `log_err_nivel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'error',
  `log_err_mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_err_contexto` json DEFAULT NULL,
  `log_err_usuario_id` int DEFAULT NULL,
  `log_err_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_err_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modelos_relatorios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelos_relatorios` (
  `mod_rel_id` int NOT NULL AUTO_INCREMENT,
  `mod_rel_tp_data` int NOT NULL DEFAULT '0' COMMENT '0 - relatório diário\\n1 - relatório período',
  `mod_rel_descricao` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `mod_rel_ativo` tinyint NOT NULL DEFAULT '1',
  `mod_rel_descricao_secao` tinyint NOT NULL DEFAULT '0',
  `mod_rel_servicos_prestados` tinyint NOT NULL DEFAULT '0',
  `mod_rel_pecas_substituidas` tinyint NOT NULL DEFAULT '0',
  `mod_rel_informacoes_adicionais` tinyint NOT NULL DEFAULT '0',
  `mod_rel_horarios` tinyint NOT NULL DEFAULT '1',
  `mod_rel_cond_clima` tinyint NOT NULL DEFAULT '1',
  `mod_rel_ocorrencia` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`mod_rel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `naturezas_atendimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `naturezas_atendimentos` (
  `nat_aten_id` int NOT NULL AUTO_INCREMENT,
  `nat_aten_mod_relatorio_id` int NOT NULL,
  `nat_aten_descricao` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `nat_aten_ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`nat_aten_id`),
  KEY `fk_nat_aten_mod_relatorio_id_idx` (`nat_aten_mod_relatorio_id`),
  CONSTRAINT `fk_nat_aten_mod_relatorio_id` FOREIGN KEY (`nat_aten_mod_relatorio_id`) REFERENCES `modelos_relatorios` (`mod_rel_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ocorrencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias` (
  `ocor_id` int NOT NULL AUTO_INCREMENT,
  `ocor_descricao` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `ocor_ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`ocor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_atendimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_atendimentos` (
  `tp_aten_id` int NOT NULL AUTO_INCREMENT,
  `tp_aten_descricao` varchar(30) COLLATE utf8mb3_unicode_ci NOT NULL,
  `tp_aten_ativo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`tp_aten_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_nivel_acesso` int NOT NULL COMMENT '0 - Administrador\n1 - Técnico',
  `user_nome` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_email` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_senha` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_ativo` tinyint NOT NULL DEFAULT '1',
  `user_protegido` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `usuarios_user_email_unique` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

