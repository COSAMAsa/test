/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: billeterie
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `billets`
--

DROP TABLE IF EXISTS `billets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `cni` varchar(50) DEFAULT NULL,
  `type_passager` enum('adulte','enfant') DEFAULT NULL,
  `type_client` enum('senegalais','resident','non_resident') DEFAULT NULL,
  `id_place` int(11) DEFAULT NULL,
  `traversee_id` int(11) DEFAULT NULL,
  `code_qr` varchar(100) DEFAULT NULL,
  `prix` int(11) DEFAULT NULL,
  `statut` enum('valide','embarque') DEFAULT 'valide',
  `date_reservation` datetime DEFAULT current_timestamp(),
  `report_effectue` tinyint(4) DEFAULT 0,
  `frais_service` int(11) DEFAULT 500,
  `depart_client` varchar(50) DEFAULT NULL,
  `montant_rembourse` int(11) DEFAULT 0,
  `date_remboursement` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_place` (`id_place`),
  CONSTRAINT `billets_ibfk_1` FOREIGN KEY (`id_place`) REFERENCES `places` (`id_place`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billets`
--

LOCK TABLES `billets` WRITE;
/*!40000 ALTER TABLE `billets` DISABLE KEYS */;
INSERT INTO `billets` VALUES
(20,'miak','778569632','17659832','adulte','senegalais',18,6,'BILLET-1777023175',26500,'valide','2026-04-24 09:32:55',0,500,NULL,0,NULL),
(23,'test','775421210','1765199902563','adulte','senegalais',22,7,'BILLET-1777044488',26500,'valide','2026-04-24 15:28:08',0,500,NULL,0,NULL),
(24,'baba','765230102','1765199902563','adulte','resident',22,7,'BILLET-1777044682',26900,'embarque','2026-04-24 15:31:22',0,500,NULL,0,NULL),
(25,'baba','778542514','1765199902563','adulte','non_resident',22,7,'BILLET-1777045147',35000,'valide','2026-04-24 15:39:07',0,500,NULL,0,NULL),
(26,'aida','775886425','1765199902563','adulte','senegalais',21,7,'BILLET-1777046772',5000,'valide','2026-04-24 16:06:12',0,500,NULL,0,NULL),
(27,'samba','775842123','1768200503625','adulte','senegalais',26,8,'BILLET-1777276931',26500,'valide','2026-04-27 08:02:11',0,500,NULL,0,NULL),
(28,'GORA','775842525','1765200002514','adulte','senegalais',30,9,'BILLET-1777289533',26500,'valide','2026-04-27 11:32:13',0,500,NULL,0,NULL),
(29,'Gora SECK','775294190','1756197305855','adulte','senegalais',25,8,'BILLET-1777291881',5000,'valide','2026-04-27 12:11:21',0,500,NULL,0,NULL),
(30,'ansoumana','775864255','1789200202365','adulte','senegalais',26,8,'BILLET-1777296177',26500,'valide','2026-04-27 13:22:57',0,500,NULL,0,NULL),
(31,'bamba','775842514','1765199905814','adulte','senegalais',30,9,'BILLET-1777372398',26500,'embarque','2026-04-28 10:33:18',0,500,NULL,0,NULL),
(32,'Assane','773009808','1700200009865','adulte','resident',31,9,'BILLET-1777372593',24900,'valide','2026-04-28 10:36:33',0,500,NULL,0,NULL),
(33,'Jeanne Dupont','775842514','1700200003214','adulte','resident',31,9,'BILLET-1777379980',24900,'embarque','2026-04-28 12:39:40',0,500,NULL,0,NULL),
(34,'Gora SECK','775294196','555289GF','adulte','non_resident',32,9,'BILLET-1777387139',18900,'embarque','2026-04-28 14:38:59',0,500,NULL,0,NULL),
(37,'henry twin','775421425','25413645','adulte','non_resident',29,9,'BILLET-1777390770',15900,'embarque','2026-04-28 15:39:30',0,500,NULL,0,NULL),
(39,'bailo ba','775842514','1789200202365','adulte','senegalais',29,9,'BILLET-1777462117',5000,'embarque','2026-04-29 11:28:37',0,500,NULL,0,NULL),
(40,'SAMBA BA','775841225','1765199902563','adulte','senegalais',29,9,'BILLET-1777480032',5000,'embarque','2026-04-29 16:27:12',0,500,NULL,0,NULL),
(41,'issa bodian','775842514','1768200502514','enfant','senegalais',32,9,'BILLET-1777483234',6500,'valide','2026-04-29 17:20:34',0,500,NULL,0,NULL),
(42,'Gora SECK','775294190','1756197305855','adulte','senegalais',32,9,'BILLET-1777486644',12500,'valide','2026-04-29 18:17:24',0,500,NULL,0,NULL),
(43,'Rouguyatou BA','775294190','1756197705456','adulte','resident',32,9,'BILLET-1777487422',12900,'embarque','2026-04-29 18:30:22',0,500,NULL,0,NULL),
(44,'Dia Seck Ramatoulaye ','775257409','2070199209811','adulte','senegalais',39,11,'BILLET-1777494009',24500,'valide','2026-04-29 20:20:09',0,500,NULL,0,NULL),
(48,'Fode','0615355436','363636373738','adulte','resident',40,13,'BILLET-1777545125',12900,'valide','2026-04-30 10:32:05',1,500,NULL,0,NULL),
(49,'Celly LY','778887676','1765199909876','adulte','senegalais',38,11,'BILLET-1777554705',26500,'valide','2026-04-30 13:11:45',0,500,NULL,0,NULL),
(50,'Celly LY','778887676','1765199909876','adulte','senegalais',38,11,'BILLET-1777555049',26500,'valide','2026-04-30 13:17:29',0,500,NULL,0,NULL),
(51,'Cheikh mbaye ','776317399','0012341969','adulte','senegalais',37,11,'BILLET-1777555134',5000,'valide','2026-04-30 13:18:54',0,500,NULL,0,NULL),
(52,'Dia Seck Ramatoulaye ','775257409','2070199209812','adulte','senegalais',39,11,'BILLET-1777585782',24500,'valide','2026-04-30 21:49:42',0,500,NULL,0,NULL),
(53,'Oscar this ','775726375','J1231273844999','adulte','resident',40,15,'BILLET-1777641708',12900,'valide','2026-05-01 13:21:48',1,500,NULL,0,NULL),
(54,'zaccaria toure','774346370','0115566666666','adulte','senegalais',37,11,'BILLET-1777647882',5000,'embarque','2026-05-01 15:04:42',0,500,NULL,0,NULL),
(55,'Younouss Gueye','775438769','A12354DF7','adulte','non_resident',46,13,'BILLET-1777827643',30900,'valide','2026-05-03 17:00:43',0,500,NULL,0,NULL),
(56,'Mouhamed Gueye','778194360','1444365488776','enfant','senegalais',52,14,'BILLET-1777828103',6500,'embarque','2026-05-03 17:08:23',0,500,NULL,0,NULL),
(57,'Ouly mbaye','774020197','2o234666','adulte','senegalais',46,13,'BILLET-1777843359',26500,'embarque','2026-05-03 21:22:39',0,500,NULL,0,NULL),
(58,'Thiam Aissa','777664719','28081986002','adulte','senegalais',46,13,'BILLET-1777882031',26500,'embarque','2026-05-04 08:07:11',0,500,NULL,0,NULL),
(59,'diop ansou','775842145','26251456','adulte','senegalais',46,13,'BILLET-1777977178',26500,'embarque','2026-05-05 10:32:58',0,500,NULL,0,NULL),
(60,'dop luc','335862514','5236412','adulte','resident',47,13,'BILLET-1777977282',24900,'embarque','2026-05-05 10:34:42',0,500,NULL,0,NULL),
(61,'briand tuiop','55824','2541365','adulte','non_resident',47,13,'BILLET-1777979137',28900,'embarque','2026-05-05 11:05:37',0,500,NULL,0,NULL),
(62,'briand tiop','55824','5863524','adulte','non_resident',47,13,'BILLET-1777979364',28900,'embarque','2026-05-05 11:09:24',0,500,NULL,0,NULL),
(63,'Marieme Diaw','772245217','2743197213382','adulte','senegalais',50,14,'BILLET-1777981280',26500,'valide','2026-05-05 11:41:20',0,500,NULL,0,NULL),
(64,'sow isma','771904229','1756854','adulte','senegalais',48,13,'BILLET-1777981478',12500,'embarque','2026-05-05 11:44:38',0,500,NULL,0,NULL),
(65,'UUGUJ','78555','54455','adulte','resident',47,13,'BILLET-1777984767',24900,'embarque','2026-05-05 12:39:27',0,500,NULL,0,NULL),
(68,'Gora SECK','775294190','23CK46921','enfant','resident',52,14,'BILLET-1778167722',6900,'embarque','2026-05-07 15:28:42',0,500,NULL,0,NULL),
(71,'luc volet','775842514','74582145',NULL,NULL,NULL,NULL,'BILLET-1778237102',24900,'valide','2026-05-08 10:45:02',0,500,NULL,0,NULL),
(72,'bouba','77586425','1789200202365','adulte','senegalais',93,23,'BILLET-1778254281',24500,'valide','2026-05-08 15:31:21',0,500,NULL,0,NULL),
(73,'Ansou diatta','77856485','2565412','adulte','senegalais',105,24,'BILLET-1778488987',500,'valide','2026-05-11 08:43:07',0,500,NULL,0,NULL),
(74,'frty','77586425','745825','adulte','senegalais',105,24,'BILLET-1778489811',500,'valide','2026-05-11 08:56:51',0,500,NULL,0,NULL),
(75,'uihuh','8488888','64654','adulte','senegalais',105,24,'BILLET-1778490300',12500,'valide','2026-05-11 09:05:00',0,500,NULL,0,NULL),
(76,'Hugo Marron','33526541452','12456325','adulte','resident',100,24,'BILLET-1778491269',26900,'valide','2026-05-11 09:21:09',0,500,NULL,0,NULL),
(77,'tens tui','856965','5841256','adulte','non_resident',102,24,'BILLET-1778492419',28900,'valide','2026-05-11 09:40:19',0,500,NULL,0,NULL),
(78,'Gora SECK','775294190',' 22KA850140','adulte','resident',102,24,'BILLET-1778492430',24900,'valide','2026-05-11 09:40:30',0,500,NULL,0,NULL),
(79,'Gora SECK','775294190',' 22KA850140','adulte','non_resident',107,24,'BILLET-1778492641',18900,'valide','2026-05-11 09:44:01',0,500,NULL,0,NULL),
(80,'mbaye tine','77586425','1768199902563','adulte','senegalais',104,24,'BILLET-1778502373',24500,'valide','2026-05-11 12:26:13',0,500,'',0,NULL),
(81,'FALL ASSANE','33586251456','12456321','adulte','senegalais',100,24,'BILLET-1778502775',26500,'valide','2026-05-11 12:32:55',0,500,'Carabane',0,NULL),
(82,'Samba Mballo','778709830','1765199909876','adulte','senegalais',102,24,'BILLET-1778535013',24500,'embarque','2026-05-11 21:30:13',0,500,'Carabane',0,NULL),
(83,'Gora SECK','775294190',' 22KA850140','adulte','senegalais',102,24,'BILLET-1778599940',24500,'valide','2026-05-12 15:32:20',0,500,'',0,NULL),
(84,'Baba ba','777015817','1765199909876','adulte','senegalais',129,27,'BILLET-1778881341',24500,'valide','2026-05-15 21:42:21',0,500,'',0,NULL),
(85,'Gora SECK','665433257766','332456545ggf','adulte','resident',132,27,'BILLET-1778937281',12900,'valide','2026-05-16 13:14:41',0,500,'',0,NULL),
(86,'baba b','775842514','125436524','adulte','senegalais',137,28,'BILLET-1779117433',26500,'valide','2026-05-18 15:17:13',0,500,'',0,NULL),
(87,'FALL ASSANE','33586251456','12456321','adulte','senegalais',135,28,'BILLET-1779117699',5000,'valide','2026-05-18 15:21:39',0,500,'',0,NULL),
(88,'jui kio','77586425','5841256','adulte','senegalais',135,28,'BILLET-1779122792',5000,'valide','2026-05-18 16:46:32',0,500,'',0,NULL),
(91,'Assane Fall ','772017828','01751199702190184','adulte','senegalais',140,28,'BILLET-1779175654',24500,'valide','2026-05-19 07:27:34',0,500,'Carabane',0,NULL),
(92,'ansoumana','77586425','1789200202365','adulte','senegalais',137,28,'BILLET-1779185675',26500,'valide','2026-05-19 10:14:35',0,500,'',0,NULL),
(93,'oumar','77586425','5841256','adulte','resident',135,28,'BILLET-1779207118',10900,'valide','2026-05-19 16:11:58',0,500,'',0,NULL),
(94,'test','77586425','5841256','adulte','senegalais',202,32,'BILLET-1779207167',26500,'valide','2026-05-19 16:12:47',0,500,'',0,NULL),
(95,'GORA SECK','775294190','5824321GH','adulte','senegalais',146,29,'BILLET-1779269818',26500,'valide','2026-05-20 09:36:58',0,500,'',0,NULL),
(96,'GORA SECK','775294190','5824321GH','adulte','resident',145,29,'BILLET-1779270744',26900,'valide','2026-05-20 09:52:24',0,500,'',0,NULL),
(97,'baba fall','77586425','5841256','adulte','senegalais',195,32,'BILLET-1779273320',5000,'valide','2026-05-20 10:35:20',0,500,'',0,NULL),
(98,'Assane FALL','772017828 ','13445667880955433','adulte','senegalais',184,31,'BILLET-1779278851',26500,'valide','2026-05-20 12:07:31',0,500,'Carabane',0,NULL),
(99,'zola sonko','77586425','5841256','adulte','senegalais',146,29,'BILLET-1779279602',26500,'','2026-05-20 12:20:02',0,500,'',26500,'2026-05-22 08:29:17'),
(100,'zola sonko','77586425','5841256','adulte','senegalais',196,32,'BILLET-1779281585',5000,'','2026-05-20 12:53:05',0,500,'',5000,'2026-05-22 08:28:22'),
(101,'Gora SECK','775294190','FGTTRGHJ65444','adulte','non_resident',145,29,'BILLET-1779282289',30900,'','2026-05-20 13:04:49',0,500,'Carabane',0,NULL),
(102,'ansoumana','77586425','5841256','enfant','resident',203,32,'BILLET-1779282328',13900,'','2026-05-20 13:05:28',0,500,'',13900,'2026-05-22 08:27:21'),
(103,'mange diatta','77586425','1765199902563','adulte','senegalais',144,29,'BILLET-1779283153',5000,'','2026-05-20 13:19:13',0,500,'',0,NULL),
(104,'luc leclon','558412','125463','adulte','non_resident',144,29,'BILLET-1779284182',15900,'','2026-05-20 13:36:22',0,500,'',0,NULL),
(105,'Tamsir sarr','7787169191','16652803883883','adulte','senegalais',173,31,'BILLET-1779302156',5000,'','2026-05-20 18:35:56',0,500,'',0,NULL),
(106,'luc leclon','558412','125463','adulte','resident',144,29,'BILLET-1779353564',10900,'','2026-05-21 08:52:44',0,500,'',0,NULL),
(107,'ansoumana','558412','125463','adulte','senegalais',144,29,'BILLET-1779353620',5000,'','2026-05-21 08:53:40',0,500,'',0,NULL),
(108,'ansoumana','558412','125463','adulte','senegalais',174,31,'BILLET-1779365955',5000,'','2026-05-21 12:19:15',0,500,'',0,NULL),
(109,'oumar','77586425','1765199902563','adulte','senegalais',173,31,'BILLET-1779454732',5000,'embarque','2026-05-22 12:58:52',0,500,'',5000,'2026-05-22 12:59:22'),
(110,'henry','77586425','1765199902563','adulte','resident',173,31,'BILLET-1779454985',10900,'embarque','2026-05-22 13:03:05',0,500,'',0,NULL),
(111,'henry','77586425','1765199902563','adulte','senegalais',196,32,'BILLET-1779455029',5000,'','2026-05-22 13:03:49',0,500,'',5000,'2026-05-22 13:05:16'),
(113,'Jeanne Dupont','3352464','1700200003214','adulte','senegalais',434,39,'BILLET-1780320020',5000,'valide','2026-06-01 13:20:20',0,500,'',0,NULL),
(114,'henry','77586425','1765199902563','adulte','senegalais',464,41,'BILLET-1780653756',5000,'valide','2026-06-05 10:02:36',0,500,'',0,NULL),
(115,'Isma','778790976','17650986788','adulte','senegalais',514,43,'BILLET-1780948351',5000,'valide','2026-06-08 19:52:31',0,500,'',0,NULL),
(116,'Bassene francis sountagne ','774070248','1126199300074','adulte','senegalais',535,43,'BILLET-1780948575',12500,'valide','2026-06-08 19:56:15',0,500,'',0,NULL),
(117,'Gora SECK','775294190','1756297305558','adulte','senegalais',518,43,'BILLET-1781020100',26500,'valide','2026-06-09 15:48:20',0,500,'',0,NULL),
(118,'ansoumana','77586425','5841256','adulte','senegalais',591,47,'BILLET-1781628881',5000,'valide','2026-06-16 16:54:41',0,500,'',0,NULL),
(119,'Samba','778762627','17650986788','adulte','senegalais',592,47,'BILLET-1781630220',5000,'valide','2026-06-16 17:17:00',0,500,'',0,NULL),
(120,'William WALLACE WESPER WALDKER','775294190','13246789876','adulte','senegalais',598,47,'BILLET-1781630304',26500,'valide','2026-06-16 17:18:24',0,500,'',0,NULL),
(121,'Babs Ba','7771717','17650986788','adulte','senegalais',613,48,'BILLET-1781786058',10,'valide','2026-06-18 12:35:10',0,10,'',0,NULL),
(122,'GORA SECK','775294190','1111112121222221','adulte','senegalais',614,48,'BILLET-1781786721',10,'valide','2026-06-18 12:48:03',0,10,'',0,NULL),
(123,'tony','3352464','1700200003214','adulte','senegalais',630,49,'BILLET-1781793113',5,'valide','2026-06-18 14:32:01',0,5,'',0,NULL),
(124,'BA Samba','77657766','177556','adulte','senegalais',631,49,'BILLET-1781813506',5,'valide','2026-06-18 20:12:14',0,5,'',0,NULL),
(125,'luiz','25666','25641','adulte','senegalais',632,49,'BILLET-1781872482',5,'valide','2026-06-19 12:35:08',0,5,'',0,NULL),
(126,'bailo','77858','17555','adulte','senegalais',633,49,'BILLET-1781872954',5,'valide','2026-06-19 12:43:34',0,5,'',0,NULL),
(127,'Gora seck','775294190','1133356666788877','adulte','senegalais',634,49,'BILLET-1781873317',5,'valide','2026-06-19 12:52:39',0,5,'',0,NULL),
(128,'Daouda Ndiaye','775294190','66655544227738','adulte','senegalais',635,49,'BILLET-1781874953',5,'valide','2026-06-19 13:16:09',0,5,'',0,NULL),
(129,'Samb Omar','775726375','1988199054321','adulte','senegalais',664,49,'BILLET-1781876411',5,'valide','2026-06-19 13:40:25',0,5,'',0,NULL),
(130,'test','3352464','1756325','adulte','senegalais',671,49,'BILLET-1781879136',5,'valide','2026-06-19 14:26:01',0,5,'',0,NULL),
(131,'Assane Fall ','772017828','17511997031901824','adulte','senegalais',672,49,'BILLET-1781882226',5,'valide','2026-06-19 15:17:21',0,5,'',0,NULL),
(132,'Micailou diagne','788901852','123456789012','adulte','senegalais',673,49,'BILLET-1781885721',5,'valide','2026-06-19 16:15:45',0,5,'Carabane',0,NULL),
(133,'BA Samba','876626','1762929','adulte','senegalais',674,49,'BILLET-1781888213',5,'valide','2026-06-19 16:57:28',0,5,'',0,NULL),
(134,'Aliou sow','775554433','1765199909876','adulte','senegalais',681,50,'BILLET-1781912780',5,'valide','2026-06-19 23:46:35',0,5,'',0,NULL),
(135,'Dione','770981919','17770','adulte','senegalais',682,50,'BILLET-1781943571',5,'valide','2026-06-20 08:20:02',0,5,'',0,NULL),
(136,'Rouguy ba ','775294199','2443665576885','adulte','senegalais',683,50,'BILLET-1781951635',5,'valide','2026-06-20 10:34:44',0,5,'',0,NULL),
(137,'Zaccaria Toure ','774346370 ','011983568344','adulte','senegalais',685,50,'BILLET-1781983624',5,'valide','2026-06-20 19:27:43',0,5,'Carabane',0,NULL),
(138,'Bdene','6675478','1455656','adulte','senegalais',686,50,'BILLET-1782000262',5,'valide','2026-06-21 00:04:50',0,5,'',0,NULL),
(139,'Ibou','66666','123444','adulte','senegalais',684,50,'BILLET-1782036639',5,'valide','2026-06-21 10:11:03',0,5,'',0,NULL),
(140,'ansoumana','77586425','5841256','adulte','senegalais',699,51,'BILLET-1782115846',5,'valide','2026-06-22 08:28:35',0,5,'',0,NULL),
(141,'Gora SECK','775294190','1214436667779987','adulte','senegalais',700,51,'BILLET-1782117199',5,'valide','2026-06-22 08:34:46',0,5,'',0,NULL),
(142,'tounka','7785855','1254633','adulte','senegalais',702,51,'BILLET-1782146457',5,'valide','2026-06-22 16:42:09',0,5,'',0,NULL),
(143,'Serigne ','776766','146677','adulte','senegalais',701,51,'BILLET-1782160037',5,'valide','2026-06-22 20:27:45',0,5,'',0,NULL),
(144,'Thiaw','777777','117772727','adulte','senegalais',721,52,'BILLET-1782245428',5,'valide','2026-06-23 20:11:07',0,5,'',0,NULL),
(145,'NOAH SIRET','775845','1768200502514','adulte','senegalais',729,53,'BILLET-1782397127',5,'valide','2026-06-25 14:19:11',0,5,'',0,NULL),
(146,'Gls','776666','167777','adulte','senegalais',730,53,'BILLET-1782398177',5,'valide','2026-06-25 14:36:53',0,5,'',0,NULL),
(147,'celly','7755224','1254632','adulte','senegalais',731,53,'BILLET-1782398700',5,'valide','2026-06-25 14:45:25',0,5,'',0,NULL),
(148,'Gora SECK','775294190',' 2KA85014','adulte','senegalais',734,53,'BILLET-1782399696',5,'valide','2026-06-25 15:02:23',0,5,'',0,NULL),
(149,'Cheikh Tidiane Ndiaye','775653724','11476198500842','adulte','senegalais',737,54,'BILLET-1782556562',5,'valide','2026-06-27 10:36:57',0,5,'',0,NULL),
(150,'marieme diaw','772245217','2751197213392','adulte','senegalais',750,55,'BILLET-1782728801',5,'valide','2026-06-29 10:28:49',0,5,'',0,NULL);
/*!40000 ALTER TABLE `billets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES
(1,3,'Modification rôle user ID: 8 -> admin','2026-05-05 17:31:00'),
(2,3,'Suppression utilisateur ID: 2','2026-05-05 17:31:08'),
(3,3,'Suppression utilisateur ID: 2','2026-05-05 17:31:12'),
(4,3,'Création utilisateur: jah@gmail.com','2026-05-05 17:31:27'),
(5,3,'Suppression utilisateur ID: 11','2026-05-05 17:34:35'),
(6,3,'Création utilisateur: jah@gmail.com','2026-05-05 17:34:40'),
(7,3,'Modification rôle user ID: 8 -> exploitation','2026-05-05 17:36:12'),
(8,5,'Création utilisateur: babatall@cosamasn','2026-05-05 17:36:58'),
(9,5,'Suppression utilisateur ID: 13','2026-05-05 17:37:43'),
(10,3,'Reset password user ID: 13','2026-05-05 17:37:46'),
(11,5,'Création utilisateur: babatall@cosamasn.com','2026-05-05 17:38:01'),
(12,3,'Reset password user ID: 13','2026-05-05 17:38:38'),
(13,3,'Modification rôle user ID: 8 -> admin','2026-05-06 12:11:02'),
(14,3,'Modification rôle user ID: 3 -> admin','2026-05-06 14:31:33'),
(15,3,'Modification rôle user ID: 3 -> admin','2026-05-06 14:34:20'),
(16,3,'Utilisateur a reset SON propre mot de passe','2026-05-06 14:34:27');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `places`
--

DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `places` (
  `id_place` int(11) NOT NULL AUTO_INCREMENT,
  `traversee_id` int(11) DEFAULT NULL,
  `type_place` varchar(50) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `restant` int(11) DEFAULT NULL,
  `numero_place` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_place`)
) ENGINE=InnoDB AUTO_INCREMENT=766 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `places`
--

LOCK TABLES `places` WRITE;
/*!40000 ALTER TABLE `places` DISABLE KEYS */;
INSERT INTO `places` VALUES
(9,4,'Pullman',4,4,NULL),
(10,4,'Cabine 2',5,5,NULL),
(11,4,'Cabine 4',5,5,NULL),
(12,4,'VIP',2,2,NULL),
(17,6,'Pullman',10,10,NULL),
(18,6,'Cabine 2 places',5,4,NULL),
(19,6,'Cabine 4 places',5,5,NULL),
(20,6,'Cabine 8 places',10,10,NULL),
(21,7,'Pullman',10,9,NULL),
(22,7,'Cabine 2 places',5,0,NULL),
(23,7,'Cabine 4 places',5,5,NULL),
(24,7,'Cabine 8 places',10,10,NULL),
(25,8,'Pullman',10,9,NULL),
(26,8,'Cabine 2 places',4,2,NULL),
(27,8,'Cabine 4 places',4,4,NULL),
(28,8,'Cabine 8 places',10,10,NULL),
(29,9,'Pullman',10,7,NULL),
(30,9,'Cabine 2 places',2,0,NULL),
(31,9,'Cabine 4 places',2,0,NULL),
(32,9,'Cabine 8 places',8,4,NULL),
(37,11,'Pullman',10,8,NULL),
(38,11,'Cabine 2 places',4,2,NULL),
(39,11,'Cabine 4 places',4,2,NULL),
(40,11,'Cabine 8 places',10,8,NULL),
(45,13,'Pullman',10,10,NULL),
(46,13,'Cabine 2 places',4,0,NULL),
(47,13,'Cabine 4 places',4,0,NULL),
(48,13,'Cabine 8 places',10,9,NULL),
(49,14,'Pullman',10,10,NULL),
(50,14,'Cabine 2 places',4,3,NULL),
(51,14,'Cabine 4 places',4,4,NULL),
(52,14,'Cabine 8 places',10,8,NULL),
(53,15,'Pullman',10,10,NULL),
(54,15,'Cabine 2 places',4,4,NULL),
(55,15,'Cabine 4 places',4,4,NULL),
(56,15,'Cabine 8 places',10,10,NULL),
(61,17,'Pullman',10,10,NULL),
(62,17,'Cabine 2 places',5,5,NULL),
(63,17,'Cabine 4 places',5,5,NULL),
(64,17,'Cabine 8 places',10,10,NULL),
(65,18,'Pullman',1000,1000,NULL),
(66,18,'Cabine 2 places',120,120,NULL),
(67,18,'Cabine 4 places',69,69,NULL),
(68,18,'Cabine 8 places',99,99,NULL),
(90,23,'Pullman',9,9,NULL),
(91,23,'Cabine 2 places Homme',5,5,NULL),
(92,23,'Cabine 2 places Femme',5,5,NULL),
(93,23,'Cabine 4 places Homme',5,4,NULL),
(94,23,'Cabine 4 places Femme',5,5,NULL),
(95,23,'Cabine 4 places Mixte',5,5,NULL),
(96,23,'Cabine 8 places Homme',5,5,NULL),
(97,23,'Cabine 8 places Femme',5,5,NULL),
(98,23,'Cabine 8 places Mixte',5,5,NULL),
(99,24,'Pullman',12,12,NULL),
(100,24,'Cabine 2 places Homme',4,2,NULL),
(101,24,'Cabine 2 places Femme',4,4,NULL),
(102,24,'Cabine 4 places Homme',4,0,NULL),
(103,24,'Cabine 4 places Femme',4,4,NULL),
(104,24,'Cabine 4 places Mixte',4,3,NULL),
(105,24,'Cabine 8 places Homme',4,1,NULL),
(106,24,'Cabine 8 places Femme',4,4,NULL),
(107,24,'Cabine 8 places Mixte',4,3,NULL),
(108,25,'Pullman',10,10,NULL),
(109,25,'Cabine 2 places Homme',4,4,NULL),
(110,25,'Cabine 2 places Femme',4,4,NULL),
(111,25,'Cabine 4 places Homme',4,4,NULL),
(112,25,'Cabine 4 places Femme',4,4,NULL),
(113,25,'Cabine 4 places Mixte',4,4,NULL),
(114,25,'Cabine 8 places Homme',4,4,NULL),
(115,25,'Cabine 8 places Femme',4,4,NULL),
(116,25,'Cabine 8 places Mixte',4,4,NULL),
(117,26,'Pullman',10,10,NULL),
(118,26,'Cabine 2 places Homme',5,5,NULL),
(119,26,'Cabine 2 places Femme',5,5,NULL),
(120,26,'Cabine 4 places Homme',5,5,NULL),
(121,26,'Cabine 4 places Femme',5,5,NULL),
(122,26,'Cabine 4 places Mixte',5,5,NULL),
(123,26,'Cabine 8 places Homme',5,5,NULL),
(124,26,'Cabine 8 places Femme',6,6,NULL),
(125,26,'Cabine 8 places Mixte',6,6,NULL),
(126,27,'Pullman',10,10,NULL),
(127,27,'Cabine 2 places Homme',2,2,NULL),
(128,27,'Cabine 2 places Femme',2,2,NULL),
(129,27,'Cabine 4 places Homme',1,0,NULL),
(130,27,'Cabine 4 places Femme',2,2,NULL),
(131,27,'Cabine 4 places Mixte',2,2,NULL),
(132,27,'Cabine 8 places Homme',2,1,NULL),
(133,27,'Cabine 8 places Femme',2,2,NULL),
(134,27,'Cabine 8 places Mixte',2,2,NULL),
(135,28,'Pullman',10,7,NULL),
(136,28,'Cabine 2 places Homme',2,2,NULL),
(137,28,'Cabine 2 places Femme',2,0,NULL),
(138,28,'Cabine 4 places Homme',2,2,NULL),
(139,28,'Cabine 4 places Femme',2,2,NULL),
(140,28,'Cabine 4 places Mixte',2,1,NULL),
(141,28,'Cabine 8 places Homme',2,2,NULL),
(142,28,'Cabine 8 places Femme',2,2,NULL),
(143,28,'Cabine 8 places Mixte',2,2,NULL),
(144,29,'Pullman',10,1,NULL),
(145,29,'Cabine 2 places Homme',2,1,NULL),
(146,29,'Cabine 2 places Femme',5,1,NULL),
(147,29,'Cabine 4 places Homme',5,5,NULL),
(148,29,'Cabine 4 places Femme',5,5,NULL),
(149,29,'Cabine 4 places Mixte',5,5,NULL),
(150,29,'Cabine 8 places Homme',5,5,NULL),
(151,29,'Cabine 8 places Femme',5,5,NULL),
(152,29,'Cabine 8 places Mixte',5,5,NULL),
(173,31,'Pullman',1,0,'A1'),
(174,31,'Pullman',1,1,'A2'),
(175,31,'Pullman',1,1,'A3'),
(176,31,'Pullman',1,1,'C2'),
(177,31,'Pullman',1,1,'C3'),
(178,31,'Pullman',1,1,'C4'),
(179,31,'Cabine 2 places Homme',1,1,'301.14A'),
(180,31,'Cabine 2 places Homme',1,1,'301.14B'),
(181,31,'Cabine 2 places Homme',1,1,'301.18A'),
(182,31,'Cabine 2 places Homme',1,1,'301.18B'),
(183,31,'Cabine 2 places Femme',1,1,'401.01A'),
(184,31,'Cabine 2 places Femme',1,0,'401.01B'),
(185,31,'Cabine 4 places Homme',1,1,'401.04A'),
(186,31,'Cabine 4 places Homme',1,1,'401.04B'),
(187,31,'Cabine 4 places Mixte',1,1,'401.16A'),
(188,31,'Cabine 4 places Mixte',1,1,'401.16B'),
(189,31,'Cabine 8 places Homme',1,1,'302.04A'),
(190,31,'Cabine 8 places Homme',1,1,'302.04B'),
(191,31,'Cabine 8 places Femme',1,1,'302.06A'),
(192,31,'Cabine 8 places Femme',1,1,'302.06B'),
(193,31,'Cabine 8 places Mixte',1,1,'302.08A'),
(194,31,'Cabine 8 places Mixte',1,1,'302.08B'),
(195,32,'Pullman',1,0,'A1'),
(196,32,'Pullman',1,1,'A2'),
(197,32,'Pullman',1,1,'A3'),
(198,32,'Pullman',1,1,'C1'),
(199,32,'Pullman',1,1,'C2'),
(200,32,'Pullman',1,1,'C3'),
(201,32,'Pullman',1,1,'C4'),
(202,32,'Cabine 2 places Homme',1,0,'301.14A'),
(203,32,'Cabine 2 places Femme',1,1,'401.01A'),
(204,32,'Cabine 4 places Homme',1,1,'401.04A'),
(205,32,'Cabine 4 places Femme',1,1,'401.14A'),
(206,32,'Cabine 4 places Mixte',1,1,'401.16A'),
(207,32,'Cabine 8 places Homme',1,1,'302.04A'),
(208,32,'Cabine 8 places Femme',1,1,'302.06A'),
(209,32,'Cabine 8 places Mixte',1,1,'302.08A'),
(210,33,'Pullman',1,1,'A1'),
(211,33,'Pullman',1,1,'A2'),
(212,33,'Pullman',1,1,'A3'),
(213,33,'Pullman',1,1,'A4'),
(214,33,'Pullman',1,1,'A5'),
(215,33,'Pullman',1,1,'B1'),
(216,33,'Pullman',1,1,'B2'),
(217,33,'Pullman',1,1,'B3'),
(218,33,'Pullman',1,1,'B4'),
(219,33,'Pullman',1,1,'B5'),
(220,33,'Pullman',1,1,'C1'),
(221,33,'Pullman',1,1,'C2'),
(222,33,'Pullman',1,1,'C3'),
(223,33,'Pullman',1,1,'C4'),
(224,33,'Pullman',1,1,'C5'),
(225,33,'Cabine 2 places Homme',1,1,'301.14A'),
(226,33,'Cabine 2 places Homme',1,1,'301.14B'),
(227,33,'Cabine 2 places Homme',1,1,'301.18A'),
(228,33,'Cabine 2 places Homme',1,1,'301.18B'),
(229,33,'Cabine 2 places Femme',1,1,'401.01A'),
(230,33,'Cabine 2 places Femme',1,1,'401.01B'),
(231,33,'Cabine 2 places Femme',1,1,'401.02A'),
(232,33,'Cabine 2 places Femme',1,1,'401.02B'),
(233,33,'Cabine 4 places Homme',1,1,'401.04A'),
(234,33,'Cabine 4 places Homme',1,1,'401.04B'),
(235,33,'Cabine 4 places Homme',1,1,'401.04C'),
(236,33,'Cabine 4 places Homme',1,1,'401.04D'),
(237,33,'Cabine 4 places Femme',1,1,'401.14A'),
(238,33,'Cabine 4 places Femme',1,1,'401.14B'),
(239,33,'Cabine 4 places Femme',1,1,'401.14C'),
(240,33,'Cabine 4 places Femme',1,1,'401.14D'),
(241,33,'Cabine 4 places Mixte',1,1,'401.16A'),
(242,33,'Cabine 4 places Mixte',1,1,'401.16B'),
(243,33,'Cabine 4 places Mixte',1,1,'401.16C'),
(244,33,'Cabine 4 places Mixte',1,1,'401.16D'),
(245,33,'Cabine 8 places Homme',1,1,'302.04A'),
(246,33,'Cabine 8 places Homme',1,1,'302.04B'),
(247,33,'Cabine 8 places Homme',1,1,'302.04C'),
(248,33,'Cabine 8 places Homme',1,1,'302.04D'),
(249,33,'Cabine 8 places Femme',1,1,'302.06A'),
(250,33,'Cabine 8 places Femme',1,1,'302.06B'),
(251,33,'Cabine 8 places Femme',1,1,'302.06C'),
(252,33,'Cabine 8 places Femme',1,1,'302.06D'),
(253,33,'Cabine 8 places Mixte',1,1,'302.08A'),
(254,33,'Cabine 8 places Mixte',1,1,'302.08B'),
(255,33,'Cabine 8 places Mixte',1,1,'302.08C'),
(256,33,'Cabine 8 places Mixte',1,1,'302.08D'),
(434,39,'Pullman',1,0,'A1'),
(435,39,'Cabine 2 places Homme',1,1,'301.14A'),
(436,39,'Cabine 2 places Femme',1,1,'401.01A'),
(440,39,'Cabine 8 places Homme',1,1,'302.04A'),
(444,40,'Pullman',1,1,'A1'),
(445,40,'Pullman',1,1,'A2'),
(446,40,'Cabine 2 places Homme',1,1,'301.14A'),
(447,40,'Cabine 2 places Femme',1,1,'301.15A'),
(448,40,'Cabine 4 places Homme',1,1,'401.08A'),
(449,40,'Cabine 4 places Femme',1,1,'401.01A'),
(450,40,'Cabine 4 places Mixte',1,1,'401.04A'),
(451,40,'Cabine 8 places Homme',1,1,'302.01A'),
(452,40,'Cabine 8 places Homme',1,1,'302.01C'),
(453,40,'Cabine 8 places Femme',1,1,'302.01B'),
(454,40,'Cabine 8 places Femme',1,1,'302.01D'),
(455,40,'Cabine 8 places Mixte',1,1,'302.01A'),
(456,40,'Cabine 8 places Mixte',1,1,'302.01B'),
(457,40,'Pullman',1,1,'A3'),
(458,39,'Pullman',1,1,'A2'),
(459,39,'Cabine 2 places Homme',1,1,'401.14A'),
(460,39,'Cabine 4 places Homme',1,1,'401.16A'),
(461,39,'Cabine 4 places Mixte',1,1,'401.04A'),
(462,39,'Cabine 8 places Homme',1,1,'302.08A'),
(463,39,'Cabine 8 places Mixte',1,1,'302.01A'),
(464,41,'Pullman',1,0,'A1'),
(465,41,'Pullman',1,1,'A2'),
(466,41,'Pullman',1,1,'A3'),
(467,41,'Cabine 2 places Homme',1,1,'301.14A'),
(468,41,'Cabine 2 places Homme',1,1,'301.14B'),
(469,41,'Cabine 2 places Femme',1,1,'301.15A'),
(470,41,'Cabine 2 places Femme',1,1,'301.15B'),
(471,41,'Cabine 4 places Homme',1,1,'401.08A'),
(472,41,'Cabine 4 places Homme',1,1,'401.08B'),
(473,41,'Cabine 4 places Femme',1,1,'401.01A'),
(474,41,'Cabine 4 places Femme',1,1,'401.01B'),
(475,41,'Cabine 4 places Mixte',1,1,'401.04A'),
(476,41,'Cabine 4 places Mixte',1,1,'401.04B'),
(477,41,'Cabine 8 places Homme',1,1,'302.01A'),
(478,41,'Cabine 8 places Homme',1,1,'302.01C'),
(479,41,'Cabine 8 places Femme',1,1,'302.01B'),
(480,41,'Cabine 8 places Femme',1,1,'302.01D'),
(481,41,'Cabine 8 places Mixte',1,1,'302.01A'),
(482,41,'Cabine 8 places Mixte',1,1,'302.01B'),
(483,41,'Pullman',1,1,'A4'),
(484,41,'Pullman',1,1,'A5'),
(485,42,'Pullman',1,1,'A1'),
(486,42,'Pullman',1,1,'A2'),
(487,42,'Pullman',1,1,'A3'),
(488,42,'Pullman',1,1,'A4'),
(489,42,'Pullman',1,1,'A5'),
(490,42,'Cabine 2 places Homme',1,1,'301.14A'),
(491,42,'Cabine 2 places Homme',1,1,'301.14B'),
(492,42,'Cabine 2 places Homme',1,1,'301.18A'),
(493,42,'Cabine 2 places Femme',1,1,'301.15A'),
(494,42,'Cabine 2 places Femme',1,1,'301.15B'),
(495,42,'Cabine 2 places Femme',1,1,'401.01A'),
(496,42,'Cabine 4 places Homme',1,1,'401.08A'),
(497,42,'Cabine 4 places Homme',1,1,'401.08B'),
(498,42,'Cabine 4 places Homme',1,1,'401.14A'),
(499,42,'Cabine 4 places Femme',1,1,'401.01A'),
(500,42,'Cabine 4 places Femme',1,1,'401.01B'),
(501,42,'Cabine 4 places Femme',1,1,'401.11A'),
(502,42,'Cabine 4 places Mixte',1,1,'401.04A'),
(503,42,'Cabine 4 places Mixte',1,1,'401.04B'),
(504,42,'Cabine 4 places Mixte',1,1,'401.04C'),
(505,42,'Cabine 8 places Homme',1,1,'302.01A'),
(506,42,'Cabine 8 places Homme',1,1,'302.01C'),
(507,42,'Cabine 8 places Homme',1,1,'302.01E'),
(508,42,'Cabine 8 places Femme',1,1,'302.01B'),
(509,42,'Cabine 8 places Femme',1,1,'302.01D'),
(510,42,'Cabine 8 places Femme',1,1,'302.03C'),
(511,42,'Cabine 8 places Mixte',1,1,'302.01A'),
(512,42,'Cabine 8 places Mixte',1,1,'302.01B'),
(513,42,'Cabine 8 places Mixte',1,1,'302.01C'),
(514,43,'Pullman',1,0,'A1'),
(515,43,'Pullman',1,1,'A2'),
(516,43,'Pullman',1,1,'A3'),
(517,43,'Pullman',1,1,'A4'),
(518,43,'Cabine 2 places Homme',1,0,'301.14A'),
(519,43,'Cabine 2 places Homme',1,1,'301.14B'),
(520,43,'Cabine 2 places Homme',1,1,'301.18A'),
(521,43,'Cabine 2 places Homme',1,1,'301.18B'),
(522,43,'Cabine 2 places Femme',1,1,'301.15A'),
(523,43,'Cabine 2 places Femme',1,1,'301.15B'),
(524,43,'Cabine 4 places Homme',1,1,'401.08A'),
(525,43,'Cabine 4 places Homme',1,1,'401.08B'),
(526,43,'Cabine 4 places Femme',1,1,'401.01A'),
(527,43,'Cabine 4 places Femme',1,1,'401.01B'),
(528,43,'Cabine 4 places Femme',1,1,'401.11A'),
(529,43,'Cabine 4 places Mixte',1,1,'401.04A'),
(530,43,'Cabine 4 places Mixte',1,1,'401.04B'),
(531,43,'Cabine 8 places Homme',1,1,'302.01A'),
(532,43,'Cabine 8 places Homme',1,1,'302.01C'),
(533,43,'Cabine 8 places Femme',1,1,'302.01B'),
(534,43,'Cabine 8 places Femme',1,1,'302.01D'),
(535,43,'Cabine 8 places Mixte',1,0,'302.01A'),
(536,43,'Cabine 8 places Mixte',1,1,'302.01B'),
(537,44,'Pullman',1,1,'A1'),
(538,44,'Pullman',1,1,'A2'),
(539,44,'Cabine 2 places Homme',1,1,'301.14A'),
(540,44,'Cabine 2 places Homme',1,1,'301.14B'),
(541,44,'Cabine 2 places Femme',1,1,'301.15A'),
(542,44,'Cabine 2 places Femme',1,1,'301.15B'),
(543,44,'Cabine 4 places Homme',1,1,'401.08A'),
(544,44,'Cabine 4 places Homme',1,1,'401.08B'),
(545,44,'Cabine 4 places Femme',1,1,'401.01A'),
(546,44,'Cabine 4 places Femme',1,1,'401.01B'),
(547,44,'Cabine 4 places Mixte',1,1,'401.04A'),
(548,44,'Cabine 4 places Mixte',1,1,'401.04B'),
(549,44,'Cabine 8 places Homme',1,1,'302.01A'),
(550,44,'Cabine 8 places Homme',1,1,'302.01C'),
(551,44,'Cabine 8 places Femme',1,1,'302.01B'),
(552,44,'Cabine 8 places Femme',1,1,'302.01D'),
(553,44,'Cabine 8 places Mixte',1,1,'302.01A'),
(554,44,'Cabine 8 places Mixte',1,1,'302.01B'),
(555,45,'Pullman',1,1,'A1'),
(556,45,'Pullman',1,1,'A2'),
(557,45,'Cabine 2 places Homme',1,1,'301.14A'),
(558,45,'Cabine 2 places Homme',1,1,'301.14B'),
(559,45,'Cabine 2 places Femme',1,1,'301.15A'),
(560,45,'Cabine 2 places Femme',1,1,'301.15B'),
(561,45,'Cabine 4 places Homme',1,1,'401.08A'),
(562,45,'Cabine 4 places Homme',1,1,'401.08B'),
(563,45,'Cabine 4 places Femme',1,1,'401.01A'),
(564,45,'Cabine 4 places Femme',1,1,'401.01B'),
(565,45,'Cabine 4 places Mixte',1,1,'401.04A'),
(566,45,'Cabine 4 places Mixte',1,1,'401.04B'),
(567,45,'Cabine 8 places Homme',1,1,'302.01A'),
(568,45,'Cabine 8 places Homme',1,1,'302.01C'),
(569,45,'Cabine 8 places Femme',1,1,'302.01B'),
(570,45,'Cabine 8 places Femme',1,1,'302.01D'),
(571,45,'Cabine 8 places Mixte',1,1,'302.01A'),
(572,45,'Cabine 8 places Mixte',1,1,'302.01B'),
(591,47,'Pullman',1,0,'A2.6'),
(592,47,'Pullman',1,0,'A2.7'),
(593,47,'Pullman',1,1,'A2.8'),
(594,47,'Pullman',1,1,'A2.9'),
(595,47,'Cabine 2 places Homme',1,1,'301.14A'),
(596,47,'Cabine 2 places Homme',1,1,'301.14B'),
(597,47,'Cabine 2 places Femme',1,1,'301.15A'),
(598,47,'Cabine 2 places Femme',1,0,'301.15B'),
(599,47,'Cabine 4 places Homme',1,1,'301.17A'),
(600,47,'Cabine 4 places Homme',1,1,'301.17B'),
(601,47,'Cabine 4 places Homme',1,1,'401.24D'),
(602,47,'Cabine 4 places Homme',1,1,'401.30A'),
(603,47,'Cabine 4 places Femme',1,1,'401.01A'),
(604,47,'Cabine 4 places Femme',1,1,'401.01B'),
(605,47,'Cabine 4 places Mixte',1,1,'401.04A'),
(606,47,'Cabine 4 places Mixte',1,1,'401.04B'),
(607,47,'Cabine 8 places Homme',1,1,'302.01A'),
(608,47,'Cabine 8 places Homme',1,1,'302.01B'),
(609,47,'Cabine 8 places Femme',1,1,'302.03A'),
(610,47,'Cabine 8 places Femme',1,1,'302.03B'),
(611,47,'Cabine 8 places Mixte',1,1,'302.01A'),
(612,47,'Cabine 8 places Mixte',1,1,'302.01B'),
(613,48,'Pullman',1,0,'A2.6'),
(614,48,'Pullman',1,0,'A2.7'),
(615,48,'Cabine 2 places Homme',1,1,'301.14A'),
(616,48,'Cabine 2 places Homme',1,1,'301.14B'),
(617,48,'Cabine 2 places Femme',1,1,'301.15A'),
(618,48,'Cabine 4 places Homme',1,1,'301.17A'),
(619,48,'Cabine 4 places Homme',1,1,'301.17B'),
(620,48,'Cabine 4 places Femme',1,1,'401.01A'),
(621,48,'Cabine 4 places Femme',1,1,'401.01B'),
(622,48,'Cabine 4 places Mixte',1,1,'401.04A'),
(623,48,'Cabine 4 places Mixte',1,1,'401.04B'),
(624,48,'Cabine 8 places Homme',1,1,'302.01A'),
(625,48,'Cabine 8 places Homme',1,1,'302.01B'),
(626,48,'Cabine 8 places Femme',1,1,'302.03A'),
(627,48,'Cabine 8 places Femme',1,1,'302.03B'),
(628,48,'Cabine 8 places Mixte',1,1,'302.01A'),
(629,48,'Cabine 8 places Mixte',1,1,'302.01B'),
(630,49,'Pullman',1,0,'A2.6'),
(631,49,'Pullman',1,0,'A2.7'),
(632,49,'Pullman',1,0,'A2.8'),
(633,49,'Pullman',1,0,'A2.9'),
(634,49,'Pullman',1,0,'B2.10'),
(635,49,'Pullman',1,0,'B2.11'),
(636,49,'Cabine 2 places Homme',1,1,'301.14A'),
(637,49,'Cabine 2 places Homme',1,1,'301.14B'),
(638,49,'Cabine 2 places Homme',1,1,'301.18A'),
(639,49,'Cabine 2 places Homme',1,1,'301.18B'),
(640,49,'Cabine 2 places Femme',1,1,'301.15A'),
(641,49,'Cabine 2 places Femme',1,1,'301.15B'),
(642,49,'Cabine 4 places Homme',1,1,'301.17A'),
(643,49,'Cabine 4 places Homme',1,1,'301.17B'),
(644,49,'Cabine 4 places Femme',1,1,'401.01A'),
(645,49,'Cabine 4 places Femme',1,1,'401.01B'),
(646,49,'Cabine 4 places Mixte',1,1,'401.04A'),
(647,49,'Cabine 4 places Mixte',1,1,'401.04B'),
(648,49,'Cabine 8 places Homme',1,1,'302.01A'),
(650,49,'Cabine 8 places Homme',1,1,'302.01C'),
(654,49,'Cabine 8 places Femme',1,1,'302.03C'),
(656,49,'Cabine 8 places Mixte',1,1,'302.01A'),
(657,49,'Cabine 8 places Mixte',1,1,'302.01B'),
(658,49,'Cabine 8 places Mixte',1,1,'302.01C'),
(659,49,'Cabine 8 places Mixte',1,1,'302.01D'),
(664,49,'Pullman',1,0,'G9'),
(669,49,'Cabine 8 places Mixte',1,1,'302.03B'),
(670,49,'Cabine 8 places Mixte',1,1,'302.03D'),
(671,49,'Pullman',1,0,'B2.12'),
(672,49,'Pullman',1,0,'B2.13'),
(673,49,'Pullman',1,0,'B2.14'),
(674,49,'Pullman',1,0,'B2.15'),
(675,49,'Pullman',1,1,'B2.2'),
(676,49,'Pullman',1,1,'B2.3'),
(677,49,'Pullman',1,1,'B2.4'),
(678,49,'Pullman',1,1,'B2.5'),
(679,49,'Pullman',1,1,'B2.6'),
(680,49,'Cabine 8 places Femme',1,1,'302.03A'),
(681,50,'Pullman',1,0,'A2.6'),
(682,50,'Pullman',1,0,'A2.7'),
(683,50,'Pullman',1,0,'A2.8'),
(684,50,'Pullman',1,0,'A2.9'),
(685,50,'Pullman',1,0,'B2.10'),
(686,50,'Pullman',1,0,'B2.11'),
(687,50,'Pullman',1,1,'B2.12'),
(688,50,'Pullman',1,1,'B2.13'),
(689,50,'Pullman',1,1,'B2.14'),
(690,50,'Pullman',1,1,'B2.15'),
(691,50,'Pullman',1,1,'B2.2'),
(692,50,'Pullman',1,1,'B2.3'),
(693,50,'Pullman',1,1,'B2.4'),
(694,50,'Pullman',1,1,'B2.5'),
(695,50,'Pullman',1,1,'B2.6'),
(696,50,'Pullman',1,1,'B2.7'),
(697,50,'Pullman',1,1,'B2.8'),
(698,50,'Pullman',1,1,'B2.9'),
(699,51,'Pullman',1,0,'A2.6'),
(700,51,'Pullman',1,0,'A2.7'),
(701,51,'Pullman',1,0,'A2.8'),
(702,51,'Pullman',1,0,'A2.9'),
(703,51,'Pullman',1,1,'B2.10'),
(704,51,'Pullman',1,1,'B2.11'),
(705,51,'Pullman',1,1,'B2.12'),
(706,51,'Pullman',1,1,'B2.13'),
(707,51,'Pullman',1,1,'B2.14'),
(708,51,'Pullman',1,1,'B2.15'),
(709,51,'Pullman',1,1,'B2.2'),
(710,51,'Pullman',1,1,'B2.3'),
(711,51,'Pullman',1,1,'B2.4'),
(712,51,'Pullman',1,1,'B2.5'),
(713,51,'Pullman',1,1,'B2.6'),
(714,51,'Pullman',1,1,'B2.7'),
(715,51,'Pullman',1,1,'B2.8'),
(716,51,'Pullman',1,1,'B2.9'),
(717,51,'Pullman',1,1,'C2.10'),
(718,51,'Pullman',1,1,'C2.11'),
(719,51,'Pullman',1,1,'C2.12'),
(720,51,'Pullman',1,1,'C2.13'),
(721,52,'Pullman',1,0,'A2.6'),
(722,52,'Pullman',1,1,'A2.7'),
(723,52,'Pullman',1,1,'A2.8'),
(724,52,'Pullman',1,1,'A2.9'),
(725,52,'Pullman',1,1,'B2.3'),
(726,52,'Pullman',1,1,'B2.4'),
(727,52,'Pullman',1,1,'B2.5'),
(728,52,'Pullman',1,1,'B2.6'),
(729,53,'Pullman',1,0,'A2.6'),
(730,53,'Pullman',1,0,'A2.7'),
(731,53,'Pullman',1,0,'B2.3'),
(732,53,'Pullman',1,1,'B2.4'),
(733,53,'Pullman',1,1,'C2.14'),
(734,53,'Pullman',1,0,'C2.15'),
(735,53,'Pullman',1,1,'D2.11'),
(736,53,'Pullman',1,1,'D2.12'),
(737,54,'Pullman',1,0,'A2.6'),
(738,54,'Pullman',1,1,'A2.7'),
(739,54,'Pullman',1,1,'A2.8'),
(740,54,'Pullman',1,1,'A2.9'),
(741,54,'Pullman',1,1,'B2.10'),
(742,54,'Pullman',1,1,'B2.11'),
(743,54,'Pullman',1,1,'B2.3'),
(744,54,'Pullman',1,1,'B2.4'),
(745,54,'Pullman',1,1,'B2.5'),
(746,54,'Pullman',1,1,'B2.6'),
(747,54,'Pullman',1,1,'B2.7'),
(748,54,'Pullman',1,1,'B2.8'),
(749,55,'Pullman',1,1,'A2.6'),
(750,55,'Pullman',1,0,'A2.7'),
(751,55,'Pullman',1,1,'A2.8'),
(752,55,'Pullman',1,1,'A2.9'),
(753,55,'Pullman',1,1,'B2.10'),
(754,55,'Pullman',1,1,'B2.11'),
(755,55,'Pullman',1,1,'B2.12'),
(756,55,'Pullman',1,1,'B2.13'),
(757,55,'Pullman',1,1,'B2.14'),
(758,55,'Pullman',1,1,'B2.15'),
(759,55,'Pullman',1,1,'B2.2'),
(760,56,'Pullman',1,1,'A2.6'),
(761,56,'Pullman',1,1,'A2.7'),
(762,56,'Pullman',1,1,'A2.8'),
(763,56,'Pullman',1,1,'B2.3'),
(764,56,'Pullman',1,1,'B2.4'),
(765,56,'Pullman',1,1,'B2.5');
/*!40000 ALTER TABLE `places` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remboursements`
--

DROP TABLE IF EXISTS `remboursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `remboursements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billet_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `frais` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `date_remboursement` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remboursements`
--

LOCK TABLES `remboursements` WRITE;
/*!40000 ALTER TABLE `remboursements` DISABLE KEYS */;
INSERT INTO `remboursements` VALUES
(1,105,5000.00,500.00,5500.00,'2026-05-21 12:41:38',3,NULL),
(2,105,5000.00,500.00,5500.00,'2026-05-21 12:41:58',3,NULL),
(3,103,5000.00,500.00,5500.00,'2026-05-21 12:46:17',3,NULL),
(4,101,30900.00,500.00,31400.00,'2026-05-21 12:46:53',3,NULL),
(5,104,15900.00,500.00,16400.00,'2026-05-21 13:00:26',3,NULL),
(6,102,13900.00,500.00,13900.00,'2026-05-22 08:27:21',3,NULL),
(7,100,5000.00,500.00,5000.00,'2026-05-22 08:28:22',3,NULL),
(8,99,26500.00,500.00,26500.00,'2026-05-22 08:29:17',3,NULL),
(9,109,5000.00,500.00,5000.00,'2026-05-22 12:59:22',3,NULL),
(10,111,5000.00,500.00,5000.00,'2026-05-22 13:05:16',3,NULL);
/*!40000 ALTER TABLE `remboursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations_attente`
--

DROP TABLE IF EXISTS `reservations_attente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_attente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `cni` varchar(50) DEFAULT NULL,
  `id_place` int(11) DEFAULT NULL,
  `traversee_id` int(11) DEFAULT NULL,
  `code_qr` varchar(100) DEFAULT NULL,
  `type_client` varchar(50) DEFAULT NULL,
  `type_passager` varchar(50) DEFAULT NULL,
  `prix` int(11) DEFAULT NULL,
  `frais_service` int(11) DEFAULT NULL,
  `depart_client` varchar(100) DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations_attente`
--

LOCK TABLES `reservations_attente` WRITE;
/*!40000 ALTER TABLE `reservations_attente` DISABLE KEYS */;
INSERT INTO `reservations_attente` VALUES
(1,'BILLET-1781872482','luiz','25666','25641',632,49,'BILLET-1781872482','senegalais','adulte',5,5,'','traite','2026-06-19 12:34:42'),
(2,'BILLET-1781872895','bailo','568558','78546',633,49,'BILLET-1781872895','senegalais','adulte',5,5,'','attente','2026-06-19 12:41:35'),
(3,'BILLET-1781872937','bailo','77858','17555',633,49,'BILLET-1781872937','senegalais','adulte',5,5,'','attente','2026-06-19 12:42:17'),
(4,'BILLET-1781872954','bailo','77858','17555',633,49,'BILLET-1781872954','senegalais','adulte',5,5,'','traite','2026-06-19 12:42:34'),
(5,'BILLET-1781873317','Gora seck','775294190','1133356666788877',634,49,'BILLET-1781873317','senegalais','adulte',5,5,'','traite','2026-06-19 12:48:37'),
(6,'BILLET-1781874925','Daouda Ndiaye','775294190','66655544227738',635,49,'BILLET-1781874925','senegalais','adulte',5,5,'','attente','2026-06-19 13:15:25'),
(7,'BILLET-1781874953','Daouda Ndiaye','775294190','66655544227738',635,49,'BILLET-1781874953','senegalais','adulte',5,5,'','traite','2026-06-19 13:15:53'),
(8,'BILLET-1781875925','Samb Omar','775726375','1988199054321',656,49,'BILLET-1781875925','senegalais','adulte',12500,5,'','attente','2026-06-19 13:32:05'),
(9,'BILLET-1781876411','Samb Omar','775726375','1988199054321',664,49,'BILLET-1781876411','senegalais','adulte',5,5,'','traite','2026-06-19 13:40:11'),
(10,'BILLET-1781877188','Thiam Ndiaye','777664719','2010DKR50019',665,49,'BILLET-1781877188','resident','adulte',10900,5,'','attente','2026-06-19 13:53:08'),
(11,'BILLET-1781879136','test','3352464','1756325',671,49,'BILLET-1781879136','senegalais','adulte',5,5,'','traite','2026-06-19 14:25:36'),
(12,'BILLET-1781879532','SAMB OUMAR','775726375','222233334444',677,49,'BILLET-1781879532','senegalais','adulte',5,5,'','attente','2026-06-19 14:32:12'),
(13,'BILLET-1781880212','AW DETHIE','776196988','1255589746',678,49,'BILLET-1781880212','senegalais','adulte',5,5,'','attente','2026-06-19 14:43:32'),
(14,'BILLET-1781882113','Assane Fall ','772017828 ','17511997190201824',644,49,'BILLET-1781882113','senegalais','adulte',24500,5,'','attente','2026-06-19 15:15:13'),
(15,'BILLET-1781882226','Assane Fall ','772017828','17511997031901824',672,49,'BILLET-1781882226','senegalais','adulte',5,5,'','traite','2026-06-19 15:17:06'),
(16,'BILLET-1781885721','Micailou diagne','788901852','123456789012',673,49,'BILLET-1781885721','senegalais','adulte',5,5,'Carabane','traite','2026-06-19 16:15:21'),
(17,'BILLET-1781888213','BA Samba','876626','1762929',674,49,'BILLET-1781888213','senegalais','adulte',5,5,'','traite','2026-06-19 16:56:53'),
(18,'BILLET-1781912780','Aliou sow','775554433','1765199909876',681,50,'BILLET-1781912780','senegalais','adulte',5,5,'','traite','2026-06-19 23:46:20'),
(19,'BILLET-1781943571','Dione','770981919','17770',682,50,'BILLET-1781943571','senegalais','adulte',5,5,'','traite','2026-06-20 08:19:31'),
(20,'BILLET-1781951635','Rouguy ba ','775294199','2443665576885',683,50,'BILLET-1781951635','senegalais','adulte',5,5,'','traite','2026-06-20 10:33:55'),
(21,'BILLET-1781983624','Zaccaria Toure ','774346370 ','011983568344',685,50,'BILLET-1781983624','senegalais','adulte',5,5,'Carabane','traite','2026-06-20 19:27:04'),
(22,'BILLET-1781995349','sali','782345678','123657449',684,50,'BILLET-1781995349','senegalais','adulte',5,5,'Carabane','attente','2026-06-20 22:42:29'),
(23,'BILLET-1782000262','Bdene','6675478','1455656',686,50,'BILLET-1782000262','senegalais','adulte',5,5,'','traite','2026-06-21 00:04:22'),
(24,'BILLET-1782036639','Ibou','66666','123444',684,50,'BILLET-1782036639','senegalais','adulte',5,5,'','traite','2026-06-21 10:10:39'),
(25,'BILLET-1782115846','ansoumana','77586425','5841256',699,51,'BILLET-1782115846','senegalais','adulte',5,5,'','traite','2026-06-22 08:10:46'),
(26,'BILLET-1782117199','Gora SECK','775294190','1214436667779987',700,51,'BILLET-1782117199','senegalais','adulte',5,5,'','traite','2026-06-22 08:33:19'),
(27,'BILLET-1782145640','Babs Ba','3352464','1700200003214',721,52,'BILLET-1782145640','senegalais','adulte',5,5,'','attente','2026-06-22 16:27:20'),
(28,'BILLET-1782146265','Babs Ba','7758454','1700200003214',701,51,'BILLET-1782146265','senegalais','adulte',5,5,'','attente','2026-06-22 16:37:45'),
(29,'BILLET-1782146303','Babs Ba','7758454','1700200003214',701,51,'BILLET-1782146303','senegalais','adulte',5,5,'','attente','2026-06-22 16:38:23'),
(30,'BILLET-1782146346','Babs Ba','3352464','1700200003214',701,51,'BILLET-1782146346','senegalais','adulte',5,5,'','attente','2026-06-22 16:39:06'),
(31,'BILLET-1782146373','Babs Ba','3352464','1700200003214',701,51,'BILLET-1782146373','senegalais','adulte',5,5,'','attente','2026-06-22 16:39:33'),
(32,'BILLET-1782146412','Ansou','33333','111111',701,51,'BILLET-1782146412','senegalais','adulte',5,5,'','attente','2026-06-22 16:40:12'),
(33,'BILLET-1782146457','tounka','7785855','1254633',702,51,'BILLET-1782146457','senegalais','adulte',5,5,'','traite','2026-06-22 16:40:57'),
(34,'BILLET-1782160037','Serigne ','776766','146677',701,51,'BILLET-1782160037','senegalais','adulte',5,5,'','traite','2026-06-22 20:27:17'),
(35,'BILLET-1782245428','Thiaw','777777','117772727',721,52,'BILLET-1782245428','senegalais','adulte',5,5,'','traite','2026-06-23 20:10:28'),
(36,'BILLET-1782383839','NOAH SIRET','775845','175855',722,52,'BILLET-1782383839','senegalais','adulte',5,5,'','attente','2026-06-25 10:37:19'),
(37,'BILLET-1782383845','NOAH SIRET','775845','175855',722,52,'BILLET-1782383845','senegalais','adulte',5,5,'','attente','2026-06-25 10:37:25'),
(38,'BILLET-1782383849','NOAH SIRET','775845','175855',722,52,'BILLET-1782383849','senegalais','adulte',5,5,'','attente','2026-06-25 10:37:29'),
(39,'BILLET-1782383906','NOAH SIRET','775845','175855',722,52,'BILLET-1782383906','senegalais','adulte',5,5,'','attente','2026-06-25 10:38:26'),
(40,'BILLET-1782383915','NOAH SIRET','775845','175855',722,52,'BILLET-1782383915','senegalais','adulte',5,5,'','attente','2026-06-25 10:38:35'),
(41,'BILLET-1782384283','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384283','senegalais','adulte',5,5,'','attente','2026-06-25 10:44:43'),
(42,'BILLET-1782384288','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384288','senegalais','adulte',5,5,'','attente','2026-06-25 10:44:48'),
(43,'BILLET-1782384297','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384297','senegalais','adulte',5,5,'','attente','2026-06-25 10:44:57'),
(44,'BILLET-1782384653','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384653','senegalais','adulte',5,5,'','attente','2026-06-25 10:50:53'),
(45,'BILLET-1782384657','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384657','senegalais','adulte',5,5,'','attente','2026-06-25 10:50:57'),
(46,'BILLET-1782384661','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384661','senegalais','adulte',5,5,'','attente','2026-06-25 10:51:01'),
(47,'BILLET-1782384680','NOAH SIRET','775845','1768200502514',722,52,'BILLET-1782384680','senegalais','adulte',5,5,'','attente','2026-06-25 10:51:20'),
(48,'BILLET-1782384764','NOAH SIRET','775845','20219670513000107	',722,52,'BILLET-1782384764','senegalais','adulte',5,5,'','attente','2026-06-25 10:52:44'),
(49,'BILLET-1782396541','NOAH SIRET','775845','175855',729,53,'BILLET-1782396541','senegalais','adulte',5,5,'','attente','2026-06-25 14:09:01'),
(50,'BILLET-1782396547','NOAH SIRET','775845','175855',729,53,'BILLET-1782396547','senegalais','adulte',5,5,'','attente','2026-06-25 14:09:07'),
(51,'BILLET-1782396597','NOAH SIRET','775845','175855',729,53,'BILLET-1782396597','senegalais','adulte',5,5,'','attente','2026-06-25 14:09:57'),
(52,'BILLET-1782396600','NOAH SIRET','775845','175855',729,53,'BILLET-1782396600','senegalais','adulte',5,5,'','attente','2026-06-25 14:10:00'),
(53,'BILLET-1782396602','NOAH SIRET','775845','175855',729,53,'BILLET-1782396602','senegalais','adulte',5,5,'','attente','2026-06-25 14:10:02'),
(54,'BILLET-1782396605','NOAH SIRET','775845','175855',729,53,'BILLET-1782396605','senegalais','adulte',5,5,'','attente','2026-06-25 14:10:05'),
(55,'BILLET-1782396990','NOAH SIRET','775845','175855',729,53,'BILLET-1782396990','senegalais','adulte',5,5,'','attente','2026-06-25 14:16:30'),
(56,'BILLET-1782397013','NOAH SIRET','775845','1768200502514',729,53,'BILLET-1782397013','senegalais','adulte',5,5,'','attente','2026-06-25 14:16:53'),
(57,'BILLET-1782397127','NOAH SIRET','775845','1768200502514',729,53,'BILLET-1782397127','senegalais','adulte',5,5,'','traite','2026-06-25 14:18:47'),
(58,'BILLET-1782398177','Gls','776666','167777',730,53,'BILLET-1782398177','senegalais','adulte',5,5,'','traite','2026-06-25 14:36:17'),
(59,'BILLET-1782398700','celly','7755224','1254632',731,53,'BILLET-1782398700','senegalais','adulte',5,5,'','traite','2026-06-25 14:45:00'),
(60,'BILLET-1782399696','Gora SECK','775294190',' 2KA85014',734,53,'BILLET-1782399696','senegalais','adulte',5,5,'','traite','2026-06-25 15:01:36'),
(61,'BILLET-1782440479','Moussa','7700000000','1498199918900',732,53,'BILLET-1782440479','senegalais','adulte',5,5,'Carabane','attente','2026-06-26 02:21:19'),
(62,'BILLET-1782556562','Cheikh Tidiane Ndiaye','775653724','11476198500842',737,54,'BILLET-1782556562','senegalais','adulte',5,5,'','traite','2026-06-27 10:36:02'),
(63,'BILLET-1782728801','marieme diaw','772245217','2751197213392',750,55,'BILLET-1782728801','senegalais','adulte',5,5,'','traite','2026-06-29 10:26:41');
/*!40000 ALTER TABLE `reservations_attente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations_temp`
--

DROP TABLE IF EXISTS `reservations_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `cni` varchar(50) DEFAULT NULL,
  `id_place` int(11) DEFAULT NULL,
  `traversee_id` int(11) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `frais_service` decimal(10,2) DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'PENDING',
  `code_transaction` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations_temp`
--

LOCK TABLES `reservations_temp` WRITE;
/*!40000 ALTER TABLE `reservations_temp` DISABLE KEYS */;
INSERT INTO `reservations_temp` VALUES
(1,'OM_6a351de38001d7.42191070',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PENDING',NULL,'2026-06-19 10:45:55');
/*!40000 ALTER TABLE `reservations_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traversees`
--

DROP TABLE IF EXISTS `traversees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `traversees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `depart` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `date_depart` datetime DEFAULT NULL,
  `bateau` varchar(100) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `places_disponibles` int(11) DEFAULT NULL,
  `date_jour` date GENERATED ALWAYS AS (cast(`date_depart` as date)) STORED,
  `reference_voyage` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bateau_date` (`bateau`,`date_depart`),
  UNIQUE KEY `unique_bateau_jour` (`bateau`,`date_jour`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traversees`
--

LOCK TABLES `traversees` WRITE;
/*!40000 ALTER TABLE `traversees` DISABLE KEYS */;
INSERT INTO `traversees` VALUES
(4,'dkr','zig','2026-04-22 15:26:00','asd',NULL,NULL,'2026-04-22',NULL),
(6,'ZIG','DKR','2026-04-24 09:28:00','asd',NULL,NULL,'2026-04-24',NULL),
(7,'DAKAR','ZIGUINCHOR','2026-04-25 09:39:00','A.S.D',NULL,NULL,'2026-04-25',NULL),
(8,'ZIGUINCHOR','DAKAR','2026-04-27 20:00:00','A.S.D',NULL,NULL,'2026-04-27',NULL),
(9,'DAKAR','ZIGUINCHOR','2026-04-29 19:00:00','A.S.D',NULL,NULL,'2026-04-29',NULL),
(11,'DAKAR','ZIGUINCHOR','2026-05-01 20:00:00','AGUENE',NULL,NULL,'2026-05-01',NULL),
(13,'DAKAR','ZIGUINCHOR','2026-05-05 19:00:00','DIAMBOGNE',NULL,NULL,'2026-05-05',NULL),
(14,'ZIGUINCHOR','DAKAR','2026-05-07 18:12:00','A.S.D',NULL,NULL,'2026-05-07',NULL),
(15,'DAKAR','ZIGUINCHOR','2026-05-08 18:13:00','A.S.D',NULL,NULL,'2026-05-08',NULL),
(17,'ZIGUINCHOR','DAKAR','2026-04-30 13:00:00','A.S.D',NULL,NULL,'2026-04-30',NULL),
(18,'DAKAR','ZIGUINCHOR','2026-05-06 10:11:00','A.S.D',NULL,NULL,'2026-05-06',NULL),
(23,'ZIGUINCHOR','DAKAR','2026-05-10 13:00:00','A.S.D',NULL,NULL,'2026-05-10',NULL),
(24,'DAKAR','ZIGUINCHOR','2026-05-12 19:00:00','A.S.D',NULL,NULL,'2026-05-12',NULL),
(25,'ZIGUINCHOR','DAKAR','2026-05-14 12:36:00','A.S.D',NULL,NULL,'2026-05-14',NULL),
(26,'DAKAR','ZIGUINCHOR','2026-05-15 19:00:00','A.S.D',NULL,NULL,'2026-05-15',NULL),
(27,'DAKAR','ZIGUINCHOR','2026-05-17 13:00:00','A.S.D',NULL,NULL,'2026-05-17',NULL),
(28,'DAKAR','ZIGUINCHOR','2026-05-19 20:00:00','AGUENE',NULL,NULL,'2026-05-19',NULL),
(29,'ZIGUINCHOR','DAKAR','2026-05-21 13:33:00','A.S.D',NULL,NULL,'2026-05-21',NULL),
(31,'DAKAR','ZIGUINCHOR','2026-05-22 20:00:00','A.S.D',NULL,NULL,'2026-05-22',NULL),
(32,'ZIGUINCHOR','DAKAR','2026-05-23 13:00:00','A.S.D',NULL,NULL,'2026-05-23',NULL),
(33,'DAKAR','ZIGUINCHOR','2026-05-24 13:30:00','A.S.D',NULL,NULL,'2026-05-24',NULL),
(39,'ZIGUINCHOR','DAKAR','2026-06-04 14:00:00','A.S.D',NULL,NULL,'2026-06-04',NULL),
(40,'DAKAR','ZIGUINCHOR','2026-06-02 20:00:00','A.S.D',NULL,NULL,'2026-06-02',NULL),
(41,'DAKAR','ZIGUINCHOR','2026-06-05 19:59:00','A.S.D',NULL,NULL,'2026-06-05','V24 DKR-ZIG 2026-06-05'),
(42,'ZIGUINCHOR','DAKAR','2026-06-07 13:00:00','A.S.D',NULL,NULL,'2026-06-07','V25 ZIG-DKR 2026-06-07'),
(43,'DAKAR','ZIGUINCHOR','2026-06-09 19:00:00','A.S.D',NULL,NULL,'2026-06-09','V26 DKR-ZIG 2026-06-09'),
(44,'ZIGUINCHOR','DAKAR','2026-06-11 13:59:00','A.S.D',NULL,NULL,'2026-06-11','V27 ZIG-DKR 2026-06-11'),
(45,'ZIGUINCHOR','DAKAR','2026-06-14 13:00:00','A.S.D',NULL,NULL,'2026-06-14','V28 ZIG-DKR 2026-06-14'),
(47,'DAKAR','ZIGUINCHOR','2026-06-16 20:00:00','A.S.D',NULL,NULL,'2026-06-16','V29 DKR-ZIG 2026-06-16'),
(48,'ZIGUINCHOR','DAKAR','2026-06-18 13:00:00','A.S.D',NULL,NULL,'2026-06-18','V30 ZIG-DKR 2026-06-18'),
(49,'DAKAR','ZIGUINCHOR','2026-06-19 20:00:00','A.S.D',NULL,NULL,'2026-06-19','V31 DKR-ZIG 2026-06-19'),
(50,'ZIGUINCHOR','DAKAR','2026-06-21 14:00:00','A.S.D',NULL,NULL,'2026-06-21','V32 ZIG-DKR 2026-06-21'),
(51,'DAKAR','ZIGUINCHOR','2026-06-23 20:00:00','A.S.D',NULL,NULL,'2026-06-23','V33 DKR-ZIG 2026-06-23'),
(52,'ZIGUINCHOR','DAKAR','2026-06-25 14:00:00','A.S.D',NULL,NULL,'2026-06-25','V34 ZIG-DKR 2026-06-25'),
(53,'DAKAR','ZIGUINCHOR','2026-06-26 20:00:00','A.S.D',NULL,NULL,'2026-06-26','V35 DKR-ZIG 2026-06-26'),
(54,'ZIGUINCHOR','DAKAR','2026-06-28 13:00:00','A.S.D',NULL,NULL,'2026-06-28','V36 ZIG-DKR 2026-06-28'),
(55,'DAKAR','ZIGUINCHOR','2026-06-30 20:00:00','A.S.D',NULL,NULL,'2026-06-30','V37 DKR-ZIG 2026-06-30'),
(56,'ZIGUINCHOR','DAKAR','2026-07-02 20:00:00','A.S.D',NULL,NULL,'2026-07-02','V38 ZIG-DKR 2026-07-02');
/*!40000 ALTER TABLE `traversees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','agent','finance','exploitation') DEFAULT 'agent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `force_password_change` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'asmin','admin@gmail.com','$2y$10$W7Sg0KUQ9RpSJEIRkTftAOl9NDZYAMvyNrf1PyyBkLN.H5vwzaPOK','agent','2026-04-24 08:12:53',1),
(3,'baba','baba@gmail.com','$2y$10$iNxGyLbSX5GdAMnluBKHn.om5rCvCbG/Nsw18oV6Ce86YakvI7Atu','admin','2026-04-27 08:43:36',0),
(4,'sali','sali@gmail.com','$2y$10$rin6hJsNZhIfeJGS2XKa6eHfAOa1WD3lF.Gw5O6GT2jN6vgdUwSCS','finance','2026-04-27 10:28:21',0),
(5,'GORA SECK','gora.seck@cosamasn.com','$2y$10$AvKOUEgC0FdbB/mGEn0RNuIYK5X3y0sD.JpcOduWfTcRtKdVMg0fi','admin','2026-04-27 12:46:22',0),
(8,'oumar','oumar@cosamasn.com','$2y$10$EZ6R6y2Jw5dKoJkr9jJmweSF4Uo.Y4jnvAFAC0ay7vi0L.zeMsnnK','admin','2026-04-28 08:28:18',0),
(9,'yaga','yaga@gmail.com','$2y$10$AxUmBpJvCG5Azsk0FRjX9exktQMAPf1ZArlD3yIfzwp7YK8BIPU2i','exploitation','2026-04-28 08:46:05',0),
(10,'root','root@cosamasn.com','$2y$10$OQZMUVrgfApIKRQu7CRHNeJ4DbrIdKXPd8hlCKwufL23KJKUZN6Yi','admin','2026-05-05 17:27:31',1),
(12,'jah','jah@gmail.com','$2y$10$imcO2SH3n8WaHzB2zq4eWeeUigI0mSbw.DOKw8blIOLHZD6.qtU9S','agent','2026-05-05 17:34:40',0),
(14,'BABA TALL','babatall@cosamasn.com','$2y$10$TAqGHUPpt5t/MFfmJVt.wepro0ysjEd4aIPAhBhd.QWFE6M4i3W6.','admin','2026-05-05 17:38:01',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-29 12:42:37
