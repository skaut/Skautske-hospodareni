SET NAMES utf8;

SET time_zone = '+00:00';

SET foreign_key_checks = 0;

SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `ac_camp_participants` (
  `participantId` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `actionId` int(10) unsigned NOT NULL,
  `payment` float(9,2) unsigned DEFAULT NULL,
  `repayment` float(9,2) unsigned DEFAULT NULL COMMENT 'vratka',
  `isAccount` enum('N','Y') COLLATE utf8_czech_ci DEFAULT 'N' COMMENT 'placeno na účet?',
  PRIMARY KEY (`participantId`),
  KEY `actionId` (`actionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `ac_chits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `num` varchar(5) COLLATE utf8_czech_ci DEFAULT NULL,
  `date` date NOT NULL,
  `recipient` varchar(64) COLLATE utf8_czech_ci DEFAULT NULL,
  `purpose` varchar(40) COLLATE utf8_czech_ci NOT NULL,
  `price` float(9,2) NOT NULL,
  `priceText` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `category` int(10) unsigned NOT NULL,
  `budgetCategoryIn` int(10) unsigned DEFAULT NULL,
  `budgetCategoryOut` int(10) unsigned DEFAULT NULL,
  `lock` int(10) unsigned DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `deleted` (`deleted`),
  KEY `eventId` (`eventId`),
  CONSTRAINT `ac_chits_ibfk_1` FOREIGN KEY (`eventId`) REFERENCES `ac_object` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `ac_chitsCategory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `short` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('in','out') COLLATE utf8_czech_ci NOT NULL DEFAULT 'out',
  `orderby` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `short` (`short`),
  KEY `deleted` (`deleted`),
  KEY `orderby` (`orderby`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES
(1,	'Přijmy od účastníků',	'pp',	'in',	100,	0),
(2,	'Služby',	's',	'out',	100,	0),
(3,	'Potraviny',	't',	'out',	100,	0),
(4,	'Jízdné',	'j',	'out',	100,	0),
(5,	'Nájemné',	'n',	'out',	100,	0),
(6,	'Materiál',	'm',	'out',	100,	0),
(7,	'Převod do stř. pokladny',	'pr',	'out',	100,	0),
(8,	'Neurčeno',	'un',	'out',	101,	0),
(9,	'Převod z pokladny střediska',	'ps',	'in',	100,	0),
(10,	'Cestovné',	'c',	'out',	100,	0),
(11,	'Hromadný příjem od úč.',	'hpd',	'in',	100,	0);

CREATE TABLE `ac_chitsView` (`id` bigint(20) unsigned, `eventId` int(11), `date` date, `num` varchar(5), `recipient` varchar(64), `purpose` varchar(40), `price` float(9,2), `priceText` varchar(100), `category` int(10) unsigned, `budgetCategoryIn` int(10) unsigned, `budgetCategoryOut` int(10) unsigned, `lock` int(10) unsigned, `deleted` tinyint(4), `clabel` varchar(64), `cshort` varchar(64), `ctype` enum('in','out'));

CREATE TABLE `ac_object` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skautisId` int(10) unsigned NOT NULL,
  `type` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `prefix` varchar(6) COLLATE utf8_czech_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `skautisId_type` (`skautisId`,`type`),
  KEY `type` (`type`),
  CONSTRAINT `ac_object_ibfk_2` FOREIGN KEY (`type`) REFERENCES `ac_object_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `ac_object_type` (
  `id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_object_type` (`id`) VALUES
('camp'),
('general'),
('unit');

CREATE TABLE `ac_unit_budget_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `objectId` int(11) NOT NULL,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('in','out') COLLATE utf8_czech_ci NOT NULL DEFAULT 'out',
  `parentId` int(10) unsigned DEFAULT NULL,
  `value` float unsigned NOT NULL DEFAULT '0',
  `year` smallint(5) unsigned NOT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `objectId_year` (`objectId`,`year`),
  KEY `parentId` (`parentId`),
  CONSTRAINT `ac_unit_budget_category_ibfk_2` FOREIGN KEY (`objectId`) REFERENCES `ac_object` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `ac_unit_budget_category_ibfk_4` FOREIGN KEY (`parentId`) REFERENCES `ac_unit_budget_category` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `pa_bank` (
  `unitId` int(11) unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `daysback` int(10) unsigned DEFAULT NULL COMMENT 'počet dní nazpět',
  PRIMARY KEY (`unitId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `pa_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `groupType` varchar(20) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'typ entity',
  `unitId` int(11) unsigned NOT NULL,
  `sisId` int(11) DEFAULT NULL COMMENT 'ID entity ve skautisu',
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `amount` float unsigned DEFAULT NULL,
  `maturity` date DEFAULT NULL,
  `ks` int(4) unsigned DEFAULT NULL,
  `email_info` text COLLATE utf8_czech_ci,
  `email_demand` text COLLATE utf8_czech_ci,
  `state` varchar(20) COLLATE utf8_czech_ci NOT NULL DEFAULT 'open',
  `state_info` varchar(250) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `last_pairing` datetime DEFAULT NULL,
  `smtp_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `objectId` (`sisId`),
  KEY `groupType` (`groupType`),
  KEY `objectId_2` (`unitId`),
  KEY `state` (`state`),
  KEY `smtp_id` (`smtp_id`),
  CONSTRAINT `pa_group_ibfk_1` FOREIGN KEY (`groupType`) REFERENCES `pa_group_type` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pa_group_ibfk_4` FOREIGN KEY (`state`) REFERENCES `pa_group_state` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pa_group_ibfk_6` FOREIGN KEY (`smtp_id`) REFERENCES `pa_smtp` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `pa_group_state` (
  `id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `pa_group_state` (`id`, `label`) VALUES
('canceled',	'Zrušená'),
('closed',	'Uzavřená'),
('open',	'Otevřená');

CREATE TABLE `pa_group_type` (
  `id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `pa_group_type` (`id`) VALUES
('camp'),
('registration');

CREATE TABLE `pa_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(10) unsigned NOT NULL,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `email` text COLLATE utf8_czech_ci,
  `personId` int(11) DEFAULT NULL,
  `amount` float NOT NULL,
  `maturity` date NOT NULL,
  `vs` varchar(10) COLLATE utf8_czech_ci DEFAULT NULL,
  `ks` varchar(4) COLLATE utf8_czech_ci DEFAULT NULL,
  `note` varchar(64) COLLATE utf8_czech_ci DEFAULT NULL,
  `transactionId` bigint(11) unsigned DEFAULT NULL,
  `dateClosed` datetime DEFAULT NULL,
  `paidFrom` varchar(64) COLLATE utf8_czech_ci DEFAULT NULL,
  `state` varchar(20) COLLATE utf8_czech_ci NOT NULL DEFAULT 'preparing',
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `groupId` (`groupId`),
  CONSTRAINT `pa_payment_ibfk_1` FOREIGN KEY (`state`) REFERENCES `pa_payment_state` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pa_payment_ibfk_5` FOREIGN KEY (`groupId`) REFERENCES `pa_group` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `pa_payment_state` (
  `id` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `orderby` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `pa_payment_state` (`id`, `label`, `orderby`) VALUES
('canceled',	'Zrušena',	10),
('completed',	'Dokončena',	3),
('preparing',	'Připravena',	1),
('send',	'Odeslána',	2);

CREATE TABLE `pa_smtp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unitId` int(11) unsigned NOT NULL,
  `host` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `secure` varchar(64) COLLATE utf8_czech_ci NOT NULL DEFAULT 'ssl',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `unitId` (`unitId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `report_chitsView` (`category` varchar(64), `sum(price)` double(19,2), `cnt` bigint(21), `rok` int(4));

CREATE TABLE `report_eventsView` (`Year` int(4), `Type` varchar(20), `Count` bigint(21));

CREATE TABLE `tc_commands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `purpose` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `place` varchar(64) COLLATE utf8_czech_ci DEFAULT NULL,
  `passengers` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `vehicle_id` int(10) unsigned DEFAULT NULL,
  `fuel_price` float(9,2) NOT NULL,
  `amortization` float(9,2) NOT NULL,
  `note` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `closed` datetime DEFAULT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contract_id` (`contract_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `tc_commands_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `tc_contracts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tc_commands_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `tc_vehicle` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `tc_command_types` (
  `commandId` int(10) unsigned NOT NULL,
  `typeId` varchar(5) COLLATE utf8_czech_ci NOT NULL DEFAULT 'auv',
  KEY `commandId` (`commandId`),
  KEY `typeId` (`typeId`),
  CONSTRAINT `tc_command_types_ibfk_5` FOREIGN KEY (`commandId`) REFERENCES `tc_commands` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tc_command_types_ibfk_6` FOREIGN KEY (`typeId`) REFERENCES `tc_travelTypes` (`type`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `tc_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` int(10) unsigned NOT NULL COMMENT 'SkautIS ID jednotky',
  `unit_person` varchar(64) COLLATE utf8_czech_ci NOT NULL COMMENT 'jméno osoby zastupující jednotku',
  `driver_name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `driver_address` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `driver_birthday` date NOT NULL,
  `driver_contact` varchar(64) COLLATE utf8_czech_ci DEFAULT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `template` int(11) NOT NULL DEFAULT '2' COMMENT '1-old, 2-podle NOZ',
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `tc_travels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command_id` int(10) unsigned NOT NULL,
  `start_place` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_place` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `distance` float(9,2) unsigned NOT NULL,
  `type` varchar(5) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tc_id` (`command_id`),
  KEY `type` (`type`),
  CONSTRAINT `tc_travels_ibfk_1` FOREIGN KEY (`command_id`) REFERENCES `tc_commands` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tc_travels_ibfk_3` FOREIGN KEY (`type`) REFERENCES `tc_travelTypes` (`type`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `tc_travelTypes` (
  `type` varchar(5) COLLATE utf8_czech_ci NOT NULL,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `hasFuel` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `order` tinyint(4) NOT NULL DEFAULT '10',
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `tc_travelTypes` (`type`, `label`, `hasFuel`, `order`) VALUES
('a',	'autobus',	0,	45),
('auv',	'auto vlastní',	1,	50),
('l',	'letadlo',	0,	9),
('mov',	'motocykl vlastní',	1,	30),
('o',	'osobní vlak',	0,	40),
('p',	'pěšky',	0,	10),
('r',	'rychlík',	0,	40);

CREATE TABLE `tc_vehicle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` int(10) unsigned NOT NULL COMMENT 'ID jednotky ze SkautISu ',
  `type` varchar(64) COLLATE utf8_czech_ci NOT NULL COMMENT 'značka auta ',
  `registration` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `consumption` float(7,4) unsigned NOT NULL COMMENT 'spotřeba',
  `note` varchar(64) COLLATE utf8_czech_ci NOT NULL DEFAULT '' COMMENT 'volitelná poznámka ',
  `archived` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `ac_chitsView`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `ac_chitsView` AS select `ch`.`id` AS `id`,`ch`.`eventId` AS `eventId`,`ch`.`date` AS `date`,`ch`.`num` AS `num`,`ch`.`recipient` AS `recipient`,`ch`.`purpose` AS `purpose`,`ch`.`price` AS `price`,`ch`.`priceText` AS `priceText`,`ch`.`category` AS `category`,`ch`.`budgetCategoryIn` AS `budgetCategoryIn`,`ch`.`budgetCategoryOut` AS `budgetCategoryOut`,`ch`.`lock` AS `lock`,`ch`.`deleted` AS `deleted`,`cat`.`label` AS `clabel`,`cat`.`short` AS `cshort`,`cat`.`type` AS `ctype` from (`ac_chits` `ch` left join `ac_chitsCategory` `cat` on((`ch`.`category` = `cat`.`id`))) where (`ch`.`deleted` = 0);

DROP TABLE IF EXISTS `report_chitsView`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `report_chitsView` AS select `c`.`label` AS `category`,sum(`ch`.`price`) AS `sum(price)`,count(0) AS `cnt`,year(`ch`.`date`) AS `rok` from (`ac_chitsView` `ch` left join `ac_chitsCategory` `c` on((`ch`.`category` = `c`.`id`))) where (`ch`.`ctype` = 'out') group by `ch`.`category`,year(`ch`.`date`) order by year(`ch`.`date`) desc;

DROP TABLE IF EXISTS `report_eventsView`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `report_eventsView` AS select year(`c`.`date`) AS `Year`,`o`.`type` AS `Type`,count(distinct `o`.`skautisId`) AS `Count` from (`ac_chits` `c` left join `ac_object` `o` on((`c`.`eventId` = `o`.`id`))) group by `o`.`type`,year(`c`.`date`) order by year(`c`.`date`) desc,`o`.`type`;
