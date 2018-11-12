SET FOREIGN_KEY_CHECKS=0;
/** feeds */
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
CREATE TABLE `spice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `key` text COLLATE utf8_czech_ci,
  `query` text COLLATE utf8_czech_ci,
  `arguments` text COLLATE utf8_czech_ci,
  `filters` text COLLATE utf8_czech_ci
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/** help */
CREATE TABLE `help` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `json` text COLLATE utf8_czech_ci COMMENT '@hidden',
  `source` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL COMMENT '@hidden@unedit',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `source` (`source`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
INSERT INTO `help` VALUES ('1', '{\"help1\":\"your text\", \r\n\"help2\":\"your text\",}', 'Module:Presenter:action:parameter');
/** log */
CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL COMMENT '@unedit',
  `handle` enum('done','csv','prepare') COLLATE utf8_czech_ci DEFAULT NULL COMMENT '@unedit',
  `date` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '@unedit',
  `users_id` int(11) unsigned DEFAULT NULL COMMENT '@unedit',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`) USING BTREE,
  CONSTRAINT `log_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=627 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;