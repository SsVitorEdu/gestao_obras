-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/12/2025 às 05:07
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_gestao`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_imob`
--

CREATE TABLE `clientes_imob` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes_imob`
--

INSERT INTO `clientes_imob` (`id`, `nome`, `cpf`, `telefone`) VALUES
(98, 'VINICIUS APARECIDO SOARES DA SILVA', '45006876883', NULL),
(99, 'KHALYANDRA DURANS DE FREITAS', '48202441838', NULL),
(100, 'SANDRA REGINA POSSOBON', '13965850873', NULL),
(101, 'JOSÉ PAULO ROCHA PEREIRA PINTO', '35977562802', NULL),
(102, 'MARIA CLARA VIEIRA', '48974111810', NULL),
(103, 'RAFAEL PORTES DE ALMEIDA', '37927186850', NULL),
(104, 'CAROLINA FERNANDA ZAMBONI FLOR DA ROSA', '41831666839', NULL),
(105, 'MATHEUS BEZERRA DE SOUZA', '48048927888', NULL),
(106, 'VALDINEIA LIMA DELANEZA', '13947953860', NULL),
(107, 'FERNANDA MILITELLO ANTONELLI', '03347289595', NULL),
(108, 'MARCELA MILITELLO ANTONELLI', '03347295560', NULL),
(109, 'EDMILSON FERREIRA MARQUES', '42733364804', NULL),
(110, 'HENRIQUE ROCHA DA MOTA', '05251179545', NULL),
(111, 'PRISCILA DE SOUZA PAGANI', '38672988825', NULL),
(112, 'WAGNER WASHINGTON DE SOUZA MARTINS', '48939289889', NULL),
(113, 'LEONE ALBERTO PEREIRA', '01836296630', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `compradores`
--

CREATE TABLE `compradores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos`
--

CREATE TABLE `contratos` (
  `id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `valor` decimal(15,2) DEFAULT NULL,
  `data_contrato` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `nome`, `codigo`) VALUES
(1, 'EMPRESA GERAL', '000');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cnpj_cpf` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores_resumo`
--

CREATE TABLE `fornecedores_resumo` (
  `id` int(11) NOT NULL,
  `nome_fornecedor` varchar(255) DEFAULT NULL,
  `tipo_material` varchar(100) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `valor_contrato` decimal(15,2) DEFAULT NULL,
  `consumo_acumulado` decimal(15,2) DEFAULT NULL,
  `saldo` decimal(15,2) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `materiais`
--

CREATE TABLE `materiais` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `unidade` varchar(20) DEFAULT 'un'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_detalhe`
--

CREATE TABLE `movimentacoes_detalhe` (
  `id` int(11) NOT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `material` text DEFAULT NULL,
  `qtd_pedido` decimal(10,2) DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_bruto` decimal(15,2) DEFAULT NULL,
  `qtd_recebida` decimal(10,2) DEFAULT NULL,
  `saldo_pendente` decimal(15,2) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obras`
--

CREATE TABLE `obras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcelas_imob`
--

CREATE TABLE `parcelas_imob` (
  `id` int(11) NOT NULL,
  `venda_id` int(11) NOT NULL,
  `numero_parcela` int(11) DEFAULT NULL,
  `valor_parcela` decimal(15,2) DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `valor_pago` decimal(15,2) DEFAULT 0.00,
  `obs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcelas_imob`
--

INSERT INTO `parcelas_imob` (`id`, `venda_id`, `numero_parcela`, `valor_parcela`, `data_vencimento`, `data_pagamento`, `valor_pago`, `obs`) VALUES
(1168, 123, 0, 1580.00, '2025-09-10', '2025-09-09', 1580.00, NULL),
(1169, 123, 0, 1580.00, '2025-10-10', '2025-10-18', 1587.37, NULL),
(1170, 123, 0, 1580.00, '2025-11-10', '2025-11-14', 1583.16, NULL),
(1171, 123, 0, 1580.00, '2025-12-10', NULL, 0.00, NULL),
(1172, 123, 0, 1580.00, '2026-01-12', NULL, 0.00, NULL),
(1173, 123, 0, 1580.00, '2026-02-10', NULL, 0.00, NULL),
(1174, 123, 0, 1580.00, '2026-03-10', NULL, 0.00, NULL),
(1175, 123, 0, 1580.00, '2026-04-10', NULL, 0.00, NULL),
(1176, 123, 0, 1580.00, '2026-05-11', NULL, 0.00, NULL),
(1177, 123, 0, 1580.00, '2026-06-10', NULL, 0.00, NULL),
(1178, 123, 0, 1580.00, '2026-07-10', NULL, 0.00, NULL),
(1179, 123, 0, 1580.00, '2026-08-10', NULL, 0.00, NULL),
(1180, 123, 0, 1580.00, '2026-09-10', NULL, 0.00, NULL),
(1181, 123, 0, 1580.00, '2026-10-12', NULL, 0.00, NULL),
(1182, 123, 0, 1580.00, '2026-11-10', NULL, 0.00, NULL),
(1183, 123, 0, 1580.00, '2026-12-10', NULL, 0.00, NULL),
(1184, 123, 0, 1580.00, '2027-01-11', NULL, 0.00, NULL),
(1185, 123, 0, 1580.00, '2027-02-10', NULL, 0.00, NULL),
(1186, 123, 0, 1580.00, '2027-03-10', NULL, 0.00, NULL),
(1187, 123, 0, 1580.00, '2027-04-12', NULL, 0.00, NULL),
(1188, 123, 0, 5000.00, '2025-09-10', '2025-09-10', 5000.00, NULL),
(1189, 124, 0, 10000.00, '2025-10-20', '2025-08-25', 10000.00, NULL),
(1190, 123, 0, 5000.00, '2025-12-10', NULL, 0.00, NULL),
(1191, 124, 0, 3215.00, '2025-10-20', '2025-10-21', 3215.00, NULL),
(1192, 124, 0, 3215.00, '2025-11-20', '2025-11-24', 3215.15, NULL),
(1193, 124, 0, 3215.00, '2025-12-22', NULL, 0.00, NULL),
(1194, 124, 0, 3215.00, '2026-01-20', NULL, 0.00, NULL),
(1195, 124, 0, 3215.00, '2026-02-20', NULL, 0.00, NULL),
(1196, 124, 0, 3215.00, '2026-03-20', NULL, 0.00, NULL),
(1197, 124, 0, 3215.00, '2026-04-20', NULL, 0.00, NULL),
(1198, 124, 0, 3215.00, '2026-05-20', NULL, 0.00, NULL),
(1199, 124, 0, 3215.00, '2026-06-22', NULL, 0.00, NULL),
(1200, 124, 0, 3215.00, '2026-07-20', NULL, 0.00, NULL),
(1201, 125, 0, 7342.57, '2025-08-25', '2025-08-25', 7342.57, NULL),
(1202, 125, 0, 7342.57, '2025-09-25', '2025-09-25', 7342.57, NULL),
(1203, 125, 0, 7342.57, '2025-10-27', '2025-10-27', 7342.57, NULL),
(1204, 125, 0, 7342.57, '2025-11-25', NULL, 0.00, NULL),
(1205, 125, 0, 7342.57, '2025-12-25', NULL, 0.00, NULL),
(1206, 125, 0, 7342.57, '2026-01-26', NULL, 0.00, NULL),
(1207, 125, 0, 7342.57, '2026-02-25', NULL, 0.00, NULL),
(1208, 125, 0, 7342.57, '2026-03-25', NULL, 0.00, NULL),
(1209, 125, 0, 7342.57, '2026-04-27', NULL, 0.00, NULL),
(1210, 125, 0, 7342.57, '2026-05-25', NULL, 0.00, NULL),
(1211, 125, 0, 7342.57, '2026-06-25', NULL, 0.00, NULL),
(1212, 125, 0, 7342.57, '2026-07-27', NULL, 0.00, NULL),
(1213, 125, 0, 75000.00, '2025-07-25', '2025-07-25', 75000.00, NULL),
(1214, 126, 0, 4644.88, '2025-09-25', '2025-10-06', 4696.71, NULL),
(1215, 126, 0, 4644.88, '2025-10-27', '2025-10-24', 4664.88, NULL),
(1216, 126, 0, 4644.88, '2025-11-25', '2025-11-25', 4664.88, NULL),
(1217, 126, 0, 4644.88, '2025-12-25', NULL, 0.00, NULL),
(1218, 126, 0, 4644.88, '2026-01-26', NULL, 0.00, NULL),
(1219, 126, 0, 4644.88, '2026-02-25', NULL, 0.00, NULL),
(1220, 126, 0, 4644.88, '2026-03-25', NULL, 0.00, NULL),
(1221, 126, 0, 4644.88, '2026-04-27', NULL, 0.00, NULL),
(1222, 126, 0, 4644.88, '2026-05-25', NULL, 0.00, NULL),
(1223, 126, 0, 4644.88, '2026-06-25', NULL, 0.00, NULL),
(1224, 126, 0, 4644.88, '2026-07-27', NULL, 0.00, NULL),
(1225, 126, 0, 4644.88, '2026-08-25', NULL, 0.00, NULL),
(1226, 126, 0, 4644.88, '2026-09-25', NULL, 0.00, NULL),
(1227, 126, 0, 4644.88, '2026-10-26', NULL, 0.00, NULL),
(1228, 126, 0, 4644.88, '2026-11-25', NULL, 0.00, NULL),
(1229, 126, 0, 8050.00, '2025-08-28', '2025-08-28', 8050.00, NULL),
(1230, 126, 0, 8050.00, '2025-08-29', '2025-08-29', 8050.00, NULL),
(1231, 127, 0, 3580.89, '2025-08-29', '2025-08-29', 3580.89, NULL),
(1232, 127, 0, 757.38, '2025-10-10', '2025-09-09', 757.38, NULL),
(1233, 127, 0, 757.38, '2025-11-10', '2025-11-03', 757.38, NULL),
(1234, 127, 0, 757.38, '2025-12-10', '2025-12-01', 757.38, NULL),
(1235, 127, 0, 757.38, '2026-01-12', NULL, 0.00, NULL),
(1236, 127, 0, 757.38, '2026-02-10', NULL, 0.00, NULL),
(1237, 127, 0, 757.38, '2026-03-10', NULL, 0.00, NULL),
(1238, 127, 0, 757.38, '2026-04-10', NULL, 0.00, NULL),
(1239, 127, 0, 757.38, '2026-05-11', NULL, 0.00, NULL),
(1240, 127, 0, 757.38, '2026-06-10', NULL, 0.00, NULL),
(1241, 127, 0, 757.38, '2026-07-10', NULL, 0.00, NULL),
(1242, 127, 0, 757.38, '2026-08-10', NULL, 0.00, NULL),
(1243, 127, 0, 757.38, '2026-09-10', NULL, 0.00, NULL),
(1244, 127, 0, 757.38, '2026-10-12', NULL, 0.00, NULL),
(1245, 127, 0, 757.38, '2026-11-10', NULL, 0.00, NULL),
(1246, 127, 0, 757.38, '2026-12-10', NULL, 0.00, NULL),
(1247, 127, 0, 757.38, '2027-01-11', NULL, 0.00, NULL),
(1248, 127, 0, 757.38, '2027-02-10', NULL, 0.00, NULL),
(1249, 127, 0, 757.38, '2027-03-10', NULL, 0.00, NULL),
(1250, 127, 0, 757.38, '2027-04-12', NULL, 0.00, NULL),
(1251, 127, 0, 757.38, '2027-05-10', NULL, 0.00, NULL),
(1252, 127, 0, 757.38, '2027-06-10', NULL, 0.00, NULL),
(1253, 127, 0, 757.38, '2027-07-12', NULL, 0.00, NULL),
(1254, 127, 0, 757.38, '2027-08-10', NULL, 0.00, NULL),
(1255, 127, 0, 757.38, '2027-09-10', NULL, 0.00, NULL),
(1256, 128, 0, 2000.00, '2025-09-20', '2025-09-17', 2000.00, NULL),
(1257, 128, 0, 2000.00, '2025-10-20', '2025-10-14', 2000.00, NULL),
(1258, 128, 0, 2000.00, '2025-11-20', '2025-11-24', 2000.00, NULL),
(1259, 128, 0, 2000.00, '2025-12-20', NULL, 0.00, NULL),
(1260, 128, 0, 2000.00, '2026-01-20', NULL, 0.00, NULL),
(1261, 128, 0, 2000.00, '2026-02-20', NULL, 0.00, NULL),
(1262, 128, 0, 2000.00, '2026-03-20', NULL, 0.00, NULL),
(1263, 128, 0, 2000.00, '2026-04-20', NULL, 0.00, NULL),
(1264, 128, 0, 2000.00, '2026-05-20', NULL, 0.00, NULL),
(1265, 128, 0, 2000.00, '2026-07-20', NULL, 0.00, NULL),
(1266, 128, 0, 2000.00, '2026-08-20', NULL, 0.00, NULL),
(1267, 128, 0, 2000.00, '2026-09-20', NULL, 0.00, NULL),
(1268, 128, 0, 2000.00, '2026-10-20', NULL, 0.00, NULL),
(1269, 128, 0, 2000.00, '2026-11-20', NULL, 0.00, NULL),
(1270, 128, 0, 2000.00, '2026-12-20', NULL, 0.00, NULL),
(1271, 128, 0, 2000.00, '2027-01-20', NULL, 0.00, NULL),
(1272, 128, 0, 2000.00, '2027-02-20', NULL, 0.00, NULL),
(1273, 129, 0, 3000.00, '2025-09-22', '2025-09-22', 3000.00, NULL),
(1274, 129, 0, 2890.00, '2025-10-20', '2025-10-18', 2890.00, NULL),
(1275, 129, 0, 2890.00, '2025-11-20', '2025-11-24', 2890.00, NULL),
(1276, 129, 0, 2890.00, '2025-12-22', NULL, 0.00, NULL),
(1277, 129, 0, 2890.00, '2026-01-20', NULL, 0.00, NULL),
(1278, 129, 0, 2890.00, '2026-02-20', NULL, 0.00, NULL),
(1279, 129, 0, 2890.00, '2026-03-20', NULL, 0.00, NULL),
(1280, 129, 0, 2890.00, '2026-04-20', NULL, 0.00, NULL),
(1281, 129, 0, 2890.00, '2026-05-20', NULL, 0.00, NULL),
(1282, 129, 0, 2890.00, '2026-06-22', NULL, 0.00, NULL),
(1283, 129, 0, 2890.00, '2026-07-20', NULL, 0.00, NULL),
(1284, 129, 0, 2890.00, '2026-08-20', NULL, 0.00, NULL),
(1285, 129, 0, 2890.00, '2026-09-21', NULL, 0.00, NULL),
(1286, 129, 0, 2890.00, '2026-10-20', NULL, 0.00, NULL),
(1287, 129, 0, 2890.00, '2026-11-20', NULL, 0.00, NULL),
(1288, 129, 0, 2890.00, '2026-12-21', NULL, 0.00, NULL),
(1289, 129, 0, 2890.00, '2027-01-20', NULL, 0.00, NULL),
(1290, 129, 0, 2890.00, '2027-02-22', NULL, 0.00, NULL),
(1291, 129, 0, 2890.00, '2027-03-22', NULL, 0.00, NULL),
(1292, 129, 0, 2890.00, '2027-04-20', NULL, 0.00, NULL),
(1293, 129, 0, 2890.00, '2027-05-20', NULL, 0.00, NULL),
(1294, 129, 0, 2890.00, '2027-06-21', NULL, 0.00, NULL),
(1295, 129, 0, 2890.00, '2027-07-20', NULL, 0.00, NULL),
(1296, 129, 0, 2890.00, '2027-08-20', NULL, 0.00, NULL),
(1297, 129, 0, 2890.00, '2027-09-20', NULL, 0.00, NULL),
(1298, 130, 0, 3201.90, '2025-10-10', '2025-10-10', 3201.90, NULL),
(1299, 130, 0, 3201.90, '2025-11-10', '2025-11-10', 3201.90, NULL),
(1300, 130, 0, 3201.90, '2025-12-10', NULL, 0.00, NULL),
(1301, 130, 0, 3201.90, '2026-01-12', NULL, 0.00, NULL),
(1302, 130, 0, 3201.90, '2026-02-10', NULL, 0.00, NULL),
(1303, 130, 0, 3201.90, '2026-03-10', NULL, 0.00, NULL),
(1304, 130, 0, 2819.82, '2026-04-10', NULL, 0.00, NULL),
(1305, 130, 0, 2819.82, '2026-05-11', NULL, 0.00, NULL),
(1306, 130, 0, 2819.82, '2026-06-10', NULL, 0.00, NULL),
(1307, 130, 0, 2819.82, '2026-07-10', NULL, 0.00, NULL),
(1308, 130, 0, 2819.82, '2026-08-10', NULL, 0.00, NULL),
(1309, 130, 0, 2819.82, '2026-09-10', NULL, 0.00, NULL),
(1310, 130, 0, 2819.82, '2026-10-12', NULL, 0.00, NULL),
(1311, 130, 0, 2819.82, '2026-11-10', NULL, 0.00, NULL),
(1312, 130, 0, 2819.82, '2026-12-10', NULL, 0.00, NULL),
(1313, 130, 0, 2819.82, '2027-01-11', NULL, 0.00, NULL),
(1314, 130, 0, 2819.82, '2027-02-10', NULL, 0.00, NULL),
(1315, 130, 0, 2819.82, '2027-03-10', NULL, 0.00, NULL),
(1316, 130, 0, 2819.82, '2027-04-12', NULL, 0.00, NULL),
(1317, 130, 0, 2819.82, '2027-05-10', NULL, 0.00, NULL),
(1318, 130, 0, 2819.82, '2027-06-10', NULL, 0.00, NULL),
(1319, 130, 0, 2819.82, '2027-07-12', NULL, 0.00, NULL),
(1320, 130, 0, 2819.82, '2027-08-10', NULL, 0.00, NULL),
(1321, 130, 0, 2819.82, '2027-09-10', NULL, 0.00, NULL),
(1322, 130, 0, 2819.82, '2027-10-11', NULL, 0.00, NULL),
(1323, 130, 0, 2819.82, '2027-11-10', NULL, 0.00, NULL),
(1324, 130, 0, 2819.82, '2027-12-10', NULL, 0.00, NULL),
(1325, 130, 0, 2819.82, '2028-01-10', NULL, 0.00, NULL),
(1326, 130, 0, 2819.82, '2028-02-10', NULL, 0.00, NULL),
(1327, 130, 0, 2819.82, '2028-03-10', NULL, 0.00, NULL),
(1328, 130, 0, 2819.82, '2028-04-10', NULL, 0.00, NULL),
(1329, 130, 0, 2819.82, '2028-05-10', NULL, 0.00, NULL),
(1330, 130, 0, 2819.82, '2028-06-12', NULL, 0.00, NULL),
(1331, 130, 0, 2819.82, '2028-07-10', NULL, 0.00, NULL),
(1332, 130, 0, 2819.82, '2028-08-10', NULL, 0.00, NULL),
(1333, 130, 0, 2819.82, '2028-09-11', NULL, 0.00, NULL),
(1334, 130, 0, 2819.82, '2028-10-10', NULL, 0.00, NULL),
(1335, 130, 0, 2819.82, '2028-11-10', NULL, 0.00, NULL),
(1336, 130, 0, 2819.82, '2028-12-11', NULL, 0.00, NULL),
(1337, 130, 0, 2819.82, '2029-01-10', NULL, 0.00, NULL),
(1338, 130, 0, 2819.82, '2029-02-12', NULL, 0.00, NULL),
(1339, 130, 0, 2819.82, '2029-03-12', NULL, 0.00, NULL),
(1340, 131, 0, 1468.00, '2025-10-08', '2025-10-08', 1468.00, NULL),
(1341, 131, 0, 1468.00, '2025-11-30', '2025-12-08', 1472.89, NULL),
(1342, 131, 0, 1468.00, '2025-12-10', NULL, 0.00, NULL),
(1343, 131, 0, 1468.00, '2026-01-12', NULL, 0.00, NULL),
(1344, 131, 0, 1468.00, '2026-02-10', NULL, 0.00, NULL),
(1345, 131, 0, 1468.00, '2026-03-10', NULL, 0.00, NULL),
(1346, 131, 0, 1468.00, '2026-04-10', NULL, 0.00, NULL),
(1347, 131, 0, 1468.00, '2026-05-11', NULL, 0.00, NULL),
(1348, 131, 0, 1468.00, '2026-06-10', NULL, 0.00, NULL),
(1349, 131, 0, 1468.00, '2026-07-10', NULL, 0.00, NULL),
(1350, 131, 0, 1468.00, '2026-08-10', NULL, 0.00, NULL),
(1351, 131, 0, 1468.00, '2026-09-10', NULL, 0.00, NULL),
(1352, 131, 0, 1468.00, '2026-10-12', NULL, 0.00, NULL),
(1353, 131, 0, 1468.00, '2026-11-10', NULL, 0.00, NULL),
(1354, 131, 0, 1468.00, '2026-12-10', NULL, 0.00, NULL),
(1355, 131, 0, 1468.00, '2027-01-11', NULL, 0.00, NULL),
(1356, 131, 0, 1468.00, '2027-02-10', NULL, 0.00, NULL),
(1357, 131, 0, 1468.00, '2027-03-10', NULL, 0.00, NULL),
(1358, 131, 0, 1468.00, '2027-04-12', NULL, 0.00, NULL),
(1359, 131, 0, 1468.00, '2027-05-10', NULL, 0.00, NULL),
(1360, 131, 0, 1468.00, '2027-06-10', NULL, 0.00, NULL),
(1361, 131, 0, 1468.00, '2027-07-12', NULL, 0.00, NULL),
(1362, 131, 0, 1468.00, '2027-08-10', NULL, 0.00, NULL),
(1363, 131, 0, 1468.00, '2027-09-10', NULL, 0.00, NULL),
(1364, 131, 0, 1468.00, '2027-10-11', NULL, 0.00, NULL),
(1365, 131, 0, 1468.00, '2027-11-10', NULL, 0.00, NULL),
(1366, 131, 0, 1468.00, '2027-12-10', NULL, 0.00, NULL),
(1367, 131, 0, 1468.00, '2028-01-10', NULL, 0.00, NULL),
(1368, 131, 0, 1468.00, '2028-02-10', NULL, 0.00, NULL),
(1369, 131, 0, 1468.00, '2028-03-10', NULL, 0.00, NULL),
(1370, 131, 0, 1468.00, '2028-04-10', NULL, 0.00, NULL),
(1371, 131, 0, 1468.00, '2028-05-10', NULL, 0.00, NULL),
(1372, 131, 0, 1468.00, '2028-06-12', NULL, 0.00, NULL),
(1373, 131, 0, 1468.00, '2028-07-10', NULL, 0.00, NULL),
(1374, 131, 0, 1468.00, '2028-08-10', NULL, 0.00, NULL),
(1375, 131, 0, 1468.00, '2028-09-11', NULL, 0.00, NULL),
(1376, 132, 0, 2058.90, '2025-08-29', '2025-09-02', 2058.90, NULL),
(1377, 132, 0, 1682.86, '2025-09-25', '2025-09-25', 1682.86, NULL),
(1378, 132, 0, 1682.86, '2025-10-27', '2025-10-27', 1682.86, NULL),
(1379, 132, 0, 1682.86, '2025-11-25', '2025-11-26', 1682.86, NULL),
(1380, 132, 0, 1682.86, '2025-12-25', NULL, 0.00, NULL),
(1381, 132, 0, 1682.86, '2026-01-26', NULL, 0.00, NULL),
(1382, 133, 0, 3000.00, '2025-08-29', '2025-08-30', 3000.00, NULL),
(1383, 133, 0, 2225.50, '2025-10-14', '2025-10-13', 2225.50, NULL),
(1384, 134, 0, 1000.00, '2025-08-22', '2025-08-15', 1000.00, NULL),
(1385, 134, 0, 1000.00, '2025-09-22', '2025-09-22', 1000.00, NULL),
(1386, 134, 0, 1000.00, '2025-10-22', '2025-10-22', 1000.00, NULL),
(1387, 135, 0, 2333.00, '2025-10-20', '2025-10-13', 2333.00, NULL),
(1388, 135, 0, 2333.00, '2025-11-20', '2025-11-11', 2333.00, NULL),
(1389, 135, 0, 2333.00, '2025-12-22', NULL, 0.00, NULL),
(1390, 135, 0, 2333.00, '2026-01-20', NULL, 0.00, NULL),
(1391, 135, 0, 2333.00, '2026-02-20', NULL, 0.00, NULL),
(1392, 135, 0, 2333.00, '2026-03-20', NULL, 0.00, NULL),
(1393, 135, 0, 2333.00, '2026-04-20', NULL, 0.00, NULL),
(1394, 135, 0, 2333.00, '2026-05-20', NULL, 0.00, NULL),
(1395, 135, 0, 2333.00, '2026-06-22', NULL, 0.00, NULL),
(1396, 135, 0, 2333.00, '2026-07-20', NULL, 0.00, NULL),
(1397, 135, 0, 2333.00, '2026-08-20', NULL, 0.00, NULL),
(1398, 135, 0, 2333.00, '2026-09-21', NULL, 0.00, NULL),
(1399, 135, 0, 2333.00, '2026-10-20', NULL, 0.00, NULL),
(1400, 135, 0, 2333.00, '2026-11-20', NULL, 0.00, NULL),
(1401, 135, 0, 2333.00, '2026-12-21', NULL, 0.00, NULL),
(1402, 135, 0, 2333.00, '2027-01-20', NULL, 0.00, NULL),
(1403, 135, 0, 2333.00, '2027-02-22', NULL, 0.00, NULL),
(1404, 135, 0, 2333.00, '2027-03-22', NULL, 0.00, NULL),
(1405, 135, 0, 2333.00, '2027-04-20', NULL, 0.00, NULL),
(1406, 135, 0, 2333.00, '2027-05-20', NULL, 0.00, NULL),
(1407, 135, 0, 2333.00, '2027-06-21', NULL, 0.00, NULL),
(1408, 135, 0, 2333.00, '2027-07-20', NULL, 0.00, NULL),
(1409, 135, 0, 2333.00, '2027-08-20', NULL, 0.00, NULL),
(1410, 135, 0, 2333.00, '2027-09-20', NULL, 0.00, NULL),
(1411, 128, 0, 2500.00, '2025-10-20', '2025-10-14', 2500.00, NULL),
(1412, 128, 0, 2500.00, '2025-11-20', '2025-11-03', 2500.00, NULL),
(1413, 130, 0, 500.00, '2025-05-12', '2025-05-12', 500.00, NULL),
(1414, 130, 0, 500.00, '2025-06-12', '2025-06-12', 500.00, NULL),
(1415, 130, 0, 500.00, '2025-07-12', '2025-07-12', 500.00, NULL),
(1416, 130, 0, 500.00, '2025-08-12', '2025-08-12', 500.00, NULL),
(1417, 130, 0, 500.00, '2025-09-12', '2025-09-08', 500.00, NULL),
(1418, 130, 0, 500.00, '2025-10-12', '2025-10-14', 500.00, NULL),
(1419, 136, 0, 1000.00, '2025-11-20', '2025-11-24', 1000.00, NULL),
(1420, 136, 0, 1000.00, '2025-12-22', NULL, 0.00, NULL),
(1421, 136, 0, 1000.00, '2026-01-20', NULL, 0.00, NULL),
(1422, 136, 0, 1000.00, '2026-02-20', NULL, 0.00, NULL),
(1423, 136, 0, 50000.00, '2026-03-20', NULL, 0.00, NULL),
(1424, 136, 0, 1004.00, '2026-04-20', NULL, 0.00, NULL),
(1425, 136, 0, 1004.00, '2026-05-20', NULL, 0.00, NULL),
(1426, 136, 0, 1004.00, '2026-06-22', NULL, 0.00, NULL),
(1427, 136, 0, 1004.00, '2026-07-20', NULL, 0.00, NULL),
(1428, 136, 0, 1004.00, '2026-08-20', NULL, 0.00, NULL),
(1429, 136, 0, 1004.00, '2026-09-21', NULL, 0.00, NULL),
(1430, 136, 0, 1004.00, '2026-10-20', NULL, 0.00, NULL),
(1431, 136, 0, 1004.00, '2026-11-20', NULL, 0.00, NULL),
(1432, 136, 0, 1004.00, '2026-12-21', NULL, 0.00, NULL),
(1433, 136, 0, 1004.00, '2027-01-20', NULL, 0.00, NULL),
(1434, 136, 0, 1004.00, '2027-02-22', NULL, 0.00, NULL),
(1435, 136, 0, 1004.00, '2027-03-22', NULL, 0.00, NULL),
(1436, 136, 0, 1004.00, '2027-04-20', NULL, 0.00, NULL),
(1437, 136, 0, 1004.00, '2027-05-20', NULL, 0.00, NULL),
(1438, 136, 0, 1004.00, '2027-06-21', NULL, 0.00, NULL),
(1439, 136, 0, 1004.00, '2027-07-20', NULL, 0.00, NULL),
(1440, 136, 0, 1004.00, '2027-08-20', NULL, 0.00, NULL),
(1441, 136, 0, 1004.00, '2027-09-20', NULL, 0.00, NULL),
(1442, 136, 0, 1004.00, '2027-10-20', NULL, 0.00, NULL),
(1443, 136, 0, 1004.00, '2027-11-22', NULL, 0.00, NULL),
(1444, 136, 0, 1004.00, '2027-12-20', NULL, 0.00, NULL),
(1445, 136, 0, 1004.00, '2028-01-20', NULL, 0.00, NULL),
(1446, 136, 0, 1004.00, '2028-02-21', NULL, 0.00, NULL),
(1447, 136, 0, 1004.00, '2028-03-20', NULL, 0.00, NULL),
(1448, 137, 0, 1034.89, '2025-12-22', NULL, 0.00, NULL),
(1449, 137, 0, 1034.89, '2026-01-20', NULL, 0.00, NULL),
(1450, 137, 0, 1034.89, '2026-02-20', NULL, 0.00, NULL),
(1451, 137, 0, 1034.89, '2026-03-20', NULL, 0.00, NULL),
(1452, 137, 0, 1034.89, '2026-04-20', NULL, 0.00, NULL),
(1453, 137, 0, 1034.89, '2026-05-20', NULL, 0.00, NULL),
(1454, 137, 0, 1034.89, '2026-06-22', NULL, 0.00, NULL),
(1455, 137, 0, 1034.89, '2026-07-20', NULL, 0.00, NULL),
(1456, 137, 0, 1034.89, '2026-08-20', NULL, 0.00, NULL),
(1457, 137, 0, 1034.89, '2026-09-21', NULL, 0.00, NULL),
(1458, 137, 0, 1034.89, '2026-10-20', NULL, 0.00, NULL),
(1459, 137, 0, 1034.89, '2026-11-20', NULL, 0.00, NULL),
(1460, 137, 0, 1034.89, '2026-12-21', NULL, 0.00, NULL),
(1461, 137, 0, 1034.89, '2027-01-20', NULL, 0.00, NULL),
(1462, 137, 0, 1034.89, '2027-02-22', NULL, 0.00, NULL),
(1463, 137, 0, 1034.89, '2027-03-22', NULL, 0.00, NULL),
(1464, 137, 0, 1034.89, '2027-04-20', NULL, 0.00, NULL),
(1465, 137, 0, 1034.89, '2027-05-20', NULL, 0.00, NULL),
(1466, 137, 0, 1034.89, '2027-06-21', NULL, 0.00, NULL),
(1467, 137, 0, 1034.89, '2027-07-20', NULL, 0.00, NULL),
(1468, 137, 0, 1034.89, '2027-08-20', NULL, 0.00, NULL),
(1469, 137, 0, 1034.89, '2027-09-20', NULL, 0.00, NULL),
(1470, 137, 0, 1034.89, '2027-10-20', NULL, 0.00, NULL),
(1471, 137, 0, 1034.89, '2027-11-22', NULL, 0.00, NULL),
(1472, 137, 0, 1034.89, '2027-12-20', NULL, 0.00, NULL),
(1473, 137, 0, 1034.89, '2028-01-20', NULL, 0.00, NULL),
(1474, 137, 0, 1034.89, '2028-02-21', NULL, 0.00, NULL),
(1475, 137, 0, 1034.89, '2028-03-20', NULL, 0.00, NULL),
(1476, 137, 0, 1034.89, '2028-04-20', NULL, 0.00, NULL),
(1477, 137, 0, 1034.89, '2028-05-22', NULL, 0.00, NULL),
(1478, 137, 0, 1034.89, '2028-06-20', NULL, 0.00, NULL),
(1479, 137, 0, 1034.89, '2028-07-20', NULL, 0.00, NULL),
(1480, 137, 0, 1034.89, '2028-08-21', NULL, 0.00, NULL),
(1481, 137, 0, 1034.89, '2028-09-20', NULL, 0.00, NULL),
(1482, 137, 0, 1034.89, '2028-10-20', NULL, 0.00, NULL),
(1483, 137, 0, 1034.89, '2028-11-20', NULL, 0.00, NULL),
(1484, 137, 0, 1034.89, '2028-12-20', NULL, 0.00, NULL),
(1485, 137, 0, 1034.89, '2029-01-22', NULL, 0.00, NULL),
(1486, 137, 0, 1034.89, '2029-02-20', NULL, 0.00, NULL),
(1487, 137, 0, 1034.89, '2029-03-20', NULL, 0.00, NULL),
(1488, 137, 0, 1034.89, '2029-04-20', NULL, 0.00, NULL),
(1489, 137, 0, 1034.89, '2029-05-21', NULL, 0.00, NULL),
(1490, 138, 0, 1475.00, '2025-12-22', NULL, 0.00, NULL),
(1491, 138, 0, 1475.00, '2026-01-20', NULL, 0.00, NULL),
(1492, 138, 0, 1475.00, '2026-02-20', NULL, 0.00, NULL),
(1493, 138, 0, 1475.00, '2026-03-20', NULL, 0.00, NULL),
(1494, 138, 0, 15000.00, '2026-03-20', NULL, 0.00, NULL),
(1495, 138, 0, 1475.00, '2026-04-20', NULL, 0.00, NULL),
(1496, 138, 0, 1475.00, '2026-05-20', NULL, 0.00, NULL),
(1497, 138, 0, 1475.00, '2026-06-22', NULL, 0.00, NULL),
(1498, 138, 0, 1475.00, '2026-07-20', NULL, 0.00, NULL),
(1499, 138, 0, 1475.00, '2026-08-20', NULL, 0.00, NULL),
(1500, 138, 0, 1475.00, '2026-09-21', NULL, 0.00, NULL),
(1501, 138, 0, 1475.00, '2026-10-20', NULL, 0.00, NULL),
(1502, 138, 0, 1475.00, '2026-11-20', NULL, 0.00, NULL),
(1503, 138, 0, 1475.00, '2026-12-21', NULL, 0.00, NULL),
(1504, 138, 0, 1475.00, '2027-01-20', NULL, 0.00, NULL),
(1505, 138, 0, 1475.00, '2027-02-22', NULL, 0.00, NULL),
(1506, 138, 0, 1475.00, '2027-03-22', NULL, 0.00, NULL),
(1507, 138, 0, 1475.00, '2027-04-20', NULL, 0.00, NULL),
(1508, 138, 0, 1475.00, '2027-05-20', NULL, 0.00, NULL),
(1509, 138, 0, 1475.00, '2027-06-21', NULL, 0.00, NULL),
(1510, 138, 0, 1475.00, '2027-07-20', NULL, 0.00, NULL),
(1511, 138, 0, 1475.00, '2027-08-20', NULL, 0.00, NULL),
(1512, 138, 0, 1475.00, '2027-09-20', NULL, 0.00, NULL),
(1513, 138, 0, 1475.00, '2027-10-20', NULL, 0.00, NULL),
(1514, 138, 0, 1475.00, '2027-11-22', NULL, 0.00, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `obra_id` int(11) NOT NULL,
  `fornecedor_id` int(11) NOT NULL,
  `comprador_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `data_pedido` date DEFAULT NULL,
  `data_entrega` date DEFAULT NULL,
  `qtd_pedida` decimal(10,2) DEFAULT NULL,
  `valor_unitario` decimal(15,2) DEFAULT NULL,
  `valor_bruto_pedido` decimal(15,2) DEFAULT NULL,
  `qtd_recebida` decimal(10,2) DEFAULT NULL,
  `valor_total_rec` decimal(15,2) DEFAULT NULL,
  `saldo_qtd` decimal(10,2) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `empresa_id` int(11) DEFAULT NULL,
  `numero_of` varchar(50) DEFAULT NULL,
  `historia` text DEFAULT NULL,
  `dt_baixa` date DEFAULT NULL,
  `forma_pagamento` varchar(100) DEFAULT NULL,
  `cotacao` varchar(100) DEFAULT NULL,
  `todos` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas_imob`
--

CREATE TABLE `vendas_imob` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nome_casa` varchar(100) DEFAULT NULL,
  `codigo_compra` varchar(50) DEFAULT NULL,
  `qtd_parcelas_total` int(11) DEFAULT NULL,
  `valor_total` decimal(15,2) DEFAULT 0.00,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `data_contrato` date DEFAULT NULL,
  `nome_empresa` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vendas_imob`
--

INSERT INTO `vendas_imob` (`id`, `cliente_id`, `nome_casa`, `codigo_compra`, `qtd_parcelas_total`, `valor_total`, `data_inicio`, `data_fim`, `data_contrato`, `nome_empresa`) VALUES
(123, 98, 'CONTRATO 00000000001863', '00000000001863', NULL, 41600.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(124, 99, 'CONTRATO 00000000001825', '00000000001825', NULL, 42150.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(125, 100, 'CONTRATO 00000000000486', '00000000000486', NULL, 163110.84, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(126, 101, 'CONTRATO 00000000002015', '00000000002015', NULL, 85773.20, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(127, 102, 'CONTRATO 00000000002270', '00000000002270', NULL, 21758.01, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(128, 103, 'CONTRATO 00000000001876', '00000000001876', NULL, 39000.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(129, 104, 'CONTRATO 00000000002012', '00000000002012', NULL, 72360.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(130, 105, 'CONTRATO 00000000001762', '00000000001762', NULL, 123724.92, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(131, 106, 'CONTRATO 00000000000950', '00000000000950', NULL, 52848.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(132, 107, 'CONTRATO 00000000002360', '00000000002360', NULL, 10473.20, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(133, 108, 'CONTRATO 00000000002358', '00000000002358', NULL, 5225.50, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(134, 109, 'CONTRATO 00000000000875', '00000000000875', NULL, 3000.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(135, 110, 'CONTRATO 00000000001936', '00000000001936', NULL, 55992.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(136, 111, 'CONTRATO 00000000002403', '00000000002403', NULL, 78096.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(137, 112, 'CONTRATO 00000000001884', '00000000001884', NULL, 43465.38, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA'),
(138, 113, 'CONTRATO 00000000001931', '00000000001931', NULL, 50400.00, '2025-01-01', '2036-12-30', '2025-12-10', 'PURA GESTAO DE OBRAS E PROJETOS LTDA');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes_imob`
--
ALTER TABLE `clientes_imob`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `compradores`
--
ALTER TABLE `compradores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fornecedores_resumo`
--
ALTER TABLE `fornecedores_resumo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `materiais`
--
ALTER TABLE `materiais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `movimentacoes_detalhe`
--
ALTER TABLE `movimentacoes_detalhe`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obras`
--
ALTER TABLE `obras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `parcelas_imob`
--
ALTER TABLE `parcelas_imob`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `obra_id` (`obra_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `comprador_id` (`comprador_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `idx_pedidos_obra` (`obra_id`),
  ADD KEY `idx_pedidos_empresa` (`empresa_id`);

--
-- Índices de tabela `vendas_imob`
--
ALTER TABLE `vendas_imob`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes_imob`
--
ALTER TABLE `clientes_imob`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de tabela `compradores`
--
ALTER TABLE `compradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19020;

--
-- AUTO_INCREMENT de tabela `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19879;

--
-- AUTO_INCREMENT de tabela `fornecedores_resumo`
--
ALTER TABLE `fornecedores_resumo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `materiais`
--
ALTER TABLE `materiais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29500;

--
-- AUTO_INCREMENT de tabela `movimentacoes_detalhe`
--
ALTER TABLE `movimentacoes_detalhe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obras`
--
ALTER TABLE `obras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `parcelas_imob`
--
ALTER TABLE `parcelas_imob`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1515;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `vendas_imob`
--
ALTER TABLE `vendas_imob`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `contratos`
--
ALTER TABLE `contratos`
  ADD CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`);

--
-- Restrições para tabelas `obras`
--
ALTER TABLE `obras`
  ADD CONSTRAINT `obras_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Restrições para tabelas `parcelas_imob`
--
ALTER TABLE `parcelas_imob`
  ADD CONSTRAINT `parcelas_imob_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas_imob` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`comprador_id`) REFERENCES `compradores` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_4` FOREIGN KEY (`material_id`) REFERENCES `materiais` (`id`);

--
-- Restrições para tabelas `vendas_imob`
--
ALTER TABLE `vendas_imob`
  ADD CONSTRAINT `vendas_imob_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_imob` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
