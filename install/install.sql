/** feeds */
DROP TABLE IF EXISTS `feeds`;
CREATE TABLE `feeds` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `feed` enum('yahoo') COLLATE utf8_czech_ci DEFAULT NULL,
  `type` enum('csv','process','export','import') COLLATE utf8_czech_ci DEFAULT NULL,
  `mapper` text COLLATE utf8_czech_ci,
  `validator` text COLLATE utf8_czech_ci,
  `callback` varchar(2000) COLLATE utf8_czech_ci NOT NULL DEFAULT '[]' COMMENT '@hidden@unedit',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
INSERT INTO `feeds` VALUES ('1', 'Content:Csv:import', 'yahoo', 'import', '{\"aal\":\"AAL_Peak\",\"aapl\":\"AAPL_Peak\"}', null, '[]');
/** spice */
DROP TABLE IF EXISTS `spice`;
CREATE TABLE `spice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `key` text COLLATE utf8_czech_ci,
  `query` text COLLATE utf8_czech_ci,
  `arguments` text COLLATE utf8_czech_ci,
  `filters` text COLLATE utf8_czech_ci,
  `views_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;