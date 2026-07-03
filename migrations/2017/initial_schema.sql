SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

CREATE TABLE `ac_camp_cashbooks` (
                                     `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:skautis_camp_id)',
                                     `cashbook_id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                                     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_cashbook` (
                               `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                               `type` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                               `cash_chit_number_prefix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
                               `bank_chit_number_prefix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
                               `note` longtext COLLATE utf8mb4_czech_ci NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chit_scan` (
                                `id` int NOT NULL AUTO_INCREMENT,
                                `chit_id` int NOT NULL,
                                `file_path` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:file_path)',
                                PRIMARY KEY (`id`),
                                KEY `IDX_FEC2BFD22AEA3AE4` (`chit_id`),
                                CONSTRAINT `FK_FEC2BFD22AEA3AE4` FOREIGN KEY (`chit_id`) REFERENCES `ac_chits` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chit_to_item` (
                                   `chit_id` int NOT NULL,
                                   `item_id` int NOT NULL,
                                   PRIMARY KEY (`chit_id`,`item_id`),
                                   UNIQUE KEY `UNIQ_2EA9AB79126F525E` (`item_id`),
                                   KEY `IDX_2EA9AB792AEA3AE4` (`chit_id`),
                                   CONSTRAINT `FK_2EA9AB79126F525E` FOREIGN KEY (`item_id`) REFERENCES `ac_chits_item` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                                   CONSTRAINT `FK_2EA9AB792AEA3AE4` FOREIGN KEY (`chit_id`) REFERENCES `ac_chits` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chits` (
                            `id` int NOT NULL AUTO_INCREMENT,
                            `eventId` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                            `num` varchar(5) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:chit_number)',
                            `date` date NOT NULL COMMENT '(DC2Type:chronos_date)',
                            `recipient` varchar(64) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:recipient)',
                            `locked` int DEFAULT NULL,
                            `payment_method` varchar(13) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                            PRIMARY KEY (`id`),
                            KEY `IDX_DBBC2DBC2B2EBB6C` (`eventId`),
                            CONSTRAINT `FK_DBBC2DBC2B2EBB6C` FOREIGN KEY (`eventId`) REFERENCES `ac_cashbook` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chitsCategory` (
                                    `id` int NOT NULL AUTO_INCREMENT,
                                    `name` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                                    `shortcut` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                                    `operation_type` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                    `virtual` tinyint(1) NOT NULL,
                                    `priority` smallint NOT NULL,
                                    `deleted` tinyint NOT NULL,
                                    PRIMARY KEY (`id`),
                                    UNIQUE KEY `UNIQ_43247D652EF83F9C` (`shortcut`),
                                    KEY `deleted` (`deleted`),
                                    KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chitsCategory_object` (
                                           `category_id` int NOT NULL,
                                           `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                           PRIMARY KEY (`category_id`,`type`),
                                           KEY `IDX_824C4F2512469DE2` (`category_id`),
                                           KEY `type` (`type`),
                                           CONSTRAINT `FK_824C4F259C370B71` FOREIGN KEY (`category_id`) REFERENCES `ac_chitsCategory` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_chits_item` (
                                 `id` int NOT NULL AUTO_INCREMENT,
                                 `purpose` varchar(120) COLLATE utf8mb4_czech_ci NOT NULL,
                                 `price` double NOT NULL,
                                 `priceText` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                                 `category` int NOT NULL,
                                 `category_operation_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:string_enum)',
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_education_cashbooks` (
                                          `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:skautis_education_id)',
                                          `year` int NOT NULL,
                                          `cashbook_id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                                          PRIMARY KEY (`id`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_event_cashbooks` (
                                      `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:skautis_event_id)',
                                      `cashbook_id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_participants` (
                                   `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:payment_id)',
                                   `participant_id` int NOT NULL,
                                   `event_id` int NOT NULL,
                                   `payment` int NOT NULL COMMENT '(DC2Type:money)',
                                   `repayment` int NOT NULL COMMENT '(DC2Type:money)',
                                   `account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                                   `event_type` varchar(9) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                   PRIMARY KEY (`id`),
                                   KEY `eventId` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_unit_budget_category` (
                                           `id` int NOT NULL AUTO_INCREMENT,
                                           `unit_id` int NOT NULL,
                                           `label` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                                           `type` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                           `parentId` int DEFAULT NULL,
                                           `value` double NOT NULL DEFAULT '0',
                                           `year` smallint NOT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `unitId_year` (`unit_id`,`year`),
                                           KEY `IDX_356BCD1F10EE4CEE` (`parentId`),
                                           CONSTRAINT `FK_356BCD1F10EE4CEE` FOREIGN KEY (`parentId`) REFERENCES `ac_unit_budget_category` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_unit_cashbooks` (
                                     `id` int NOT NULL,
                                     `unit_id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:unit_id)',
                                     `year` smallint NOT NULL,
                                     `cashbook_id` varchar(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:cashbook_id)',
                                     PRIMARY KEY (`id`,`unit_id`),
                                     KEY `IDX_1243558BF8BD700D` (`unit_id`),
                                     CONSTRAINT `FK_1243558BF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `ac_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `ac_units` (
                            `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:unit_id)',
                            `active_cashbook_id` int NOT NULL,
                            `next_cashbook_id` int NOT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `google_oauth` (
                                `id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:oauth_id)',
                                `unit_id` char(36) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:unit_id)',
                                `email` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                                `token` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                                `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                                PRIMARY KEY (`id`),
                                UNIQUE KEY `unitid_email` (`unit_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `log` (
                       `id` int NOT NULL AUTO_INCREMENT,
                       `unit_id` int NOT NULL,
                       `date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                       `user_id` int NOT NULL,
                       `description` longtext COLLATE utf8mb4_czech_ci NOT NULL,
                       `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                       `type_id` int DEFAULT NULL,
                       PRIMARY KEY (`id`),
                       KEY `unitId` (`unit_id`),
                       KEY `typeId` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_bank_account` (
                                   `id` int NOT NULL AUTO_INCREMENT,
                                   `unit_id` int NOT NULL,
                                   `name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                                   `token` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                                   `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                                   `allowed_for_subunits` tinyint(1) NOT NULL,
                                   `number_prefix` varchar(6) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                                   `number_number` varchar(10) COLLATE utf8mb4_czech_ci NOT NULL,
                                   `number_bank_code` varchar(4) COLLATE utf8mb4_czech_ci NOT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_group` (
                            `id` int NOT NULL AUTO_INCREMENT,
                            `groupType` varchar(20) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT 'typ entity(DC2Type:string_enum)',
                            `sisId` int DEFAULT NULL COMMENT 'ID entity ve skautisu',
                            `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                            `amount` double DEFAULT NULL,
                            `due_date` date DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                            `constant_symbol` int DEFAULT NULL,
                            `next_variable_symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:variable_symbol)',
                            `state` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                            `note` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                            `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                            `last_pairing` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                            `smtp_id` int DEFAULT NULL,
                            `bank_account_id` int DEFAULT NULL,
                            `oauth_id` char(36) COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:oauth_id)',
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_group_email` (
                                  `id` int NOT NULL AUTO_INCREMENT,
                                  `group_id` int NOT NULL,
                                  `type` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                  `template_subject` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                                  `template_body` longtext COLLATE utf8mb4_czech_ci NOT NULL,
                                  `enabled` tinyint(1) NOT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `IDX_7A67EADBFE54D947` (`group_id`),
                                  CONSTRAINT `FK_7A67EADBFE54D947` FOREIGN KEY (`group_id`) REFERENCES `pa_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_group_unit` (
                                 `id` int NOT NULL AUTO_INCREMENT,
                                 `unit_id` int NOT NULL,
                                 `group_id` int NOT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `IDX_FB5A0CD6FE54D947` (`group_id`),
                                 CONSTRAINT `FK_FB5A0CD6FE54D947` FOREIGN KEY (`group_id`) REFERENCES `pa_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_payment` (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `group_id` int NOT NULL,
                              `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                              `person_id` int DEFAULT NULL,
                              `amount` float NOT NULL,
                              `due_date` date NOT NULL COMMENT '(DC2Type:chronos_date)',
                              `variable_symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT '(DC2Type:variable_symbol)',
                              `constant_symbol` smallint DEFAULT NULL,
                              `note` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                              `transactionId` varchar(64) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                              `closed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                              `closed_by_username` varchar(64) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                              `bank_account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
                              `state` varchar(20) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                              `transaction_payer` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                              `transaction_note` varchar(255) COLLATE utf8mb4_czech_ci DEFAULT NULL,
                              `date` date DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_payment_email_recipients` (
                                               `id` int NOT NULL AUTO_INCREMENT,
                                               `payment_id` int NOT NULL,
                                               `email_address` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:email_address)',
                                               PRIMARY KEY (`id`),
                                               KEY `IDX_A3FBD6514C3A3BB` (`payment_id`),
                                               CONSTRAINT `FK_A3FBD6514C3A3BB` FOREIGN KEY (`payment_id`) REFERENCES `pa_payment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `pa_payment_sent_emails` (
                                          `id` int NOT NULL AUTO_INCREMENT,
                                          `payment_id` int NOT NULL,
                                          `type` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                                          `time` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                                          `sender_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `IDX_95359C6C4C3A3BB` (`payment_id`),
                                          CONSTRAINT `FK_95359C6C4C3A3BB` FOREIGN KEY (`payment_id`) REFERENCES `pa_payment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `tc_commands` (
                               `id` int NOT NULL AUTO_INCREMENT,
                               `contract_id` int DEFAULT NULL,
                               `unit_id` int NOT NULL,
                               `purpose` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                               `place` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                               `fellow_passengers` varchar(256) COLLATE utf8mb4_czech_ci NOT NULL,
                               `vehicle_id` int DEFAULT NULL,
                               `fuel_price` int NOT NULL COMMENT '(DC2Type:money)',
                               `amortization` int NOT NULL COMMENT '(DC2Type:money)',
                               `note` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                               `closed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                               `driver_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                               `driver_contact` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                               `driver_address` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                               `next_travel_id` int NOT NULL,
                               `owner_id` int DEFAULT NULL,
                               `unit` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                               `transport_types` json NOT NULL COMMENT '(DC2Type:transport_types)',
                               PRIMARY KEY (`id`),
                               KEY `IDX_4D5B6D0C545317D1` (`vehicle_id`),
                               CONSTRAINT `FK_4D5B6D0C545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `tc_vehicle` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `tc_contracts` (
                                `id` int NOT NULL AUTO_INCREMENT,
                                `unit_id` int NOT NULL,
                                `unit_representative` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                                `driver_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                                `driver_address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                                `driver_birthday` date DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                                `driver_contact` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
                                `since` date DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                                `until` date DEFAULT NULL COMMENT '(DC2Type:chronos_date)',
                                `template_version` smallint NOT NULL COMMENT '1-old, 2-podle NOZ',
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `tc_travels` (
                              `id` int NOT NULL,
                              `command_id` int NOT NULL,
                              `start_place` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                              `start_date` date NOT NULL COMMENT '(DC2Type:chronos_date)',
                              `end_place` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
                              `distance` double DEFAULT NULL,
                              `transport_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:string_enum)',
                              `has_fuel` smallint NOT NULL,
                              `price` int DEFAULT NULL COMMENT '(DC2Type:money)',
                              PRIMARY KEY (`id`,`command_id`),
                              KEY `IDX_F53E53633E1689A` (`command_id`),
                              CONSTRAINT `FK_F53E53633E1689A` FOREIGN KEY (`command_id`) REFERENCES `tc_commands` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `tc_vehicle` (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `unit_id` int NOT NULL,
                              `type` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                              `registration` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                              `consumption` double NOT NULL,
                              `note` varchar(64) COLLATE utf8mb4_czech_ci NOT NULL,
                              `archived` tinyint(1) NOT NULL DEFAULT '0',
                              `subunit_id` int DEFAULT NULL,
                              `metadata_author_name` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
                              `metadata_created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `tc_vehicle_roadworthy_scan` (
                                              `id` int NOT NULL AUTO_INCREMENT,
                                              `vehicle_id` int NOT NULL,
                                              `file_path` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL COMMENT '(DC2Type:file_path)',
                                              PRIMARY KEY (`id`),
                                              KEY `IDX_270D2917545317D1` (`vehicle_id`),
                                              CONSTRAINT `FK_270D2917545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `tc_vehicle` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

INSERT INTO `ac_chitsCategory` (`id`, `name`, `shortcut`, `operation_type`, `virtual`, `priority`, `deleted`) VALUES
                                                                                                                  (1,	'Přijmy od účastníků',	'pp',	'in',	0,	100,	0),
                                                                                                                  (2,	'Služby',	's',	'out',	0,	100,	0),
                                                                                                                  (3,	'Potraviny',	't',	'out',	0,	100,	0),
                                                                                                                  (4,	'Jízdné',	'j',	'out',	0,	100,	0),
                                                                                                                  (5,	'Nájemné',	'n',	'out',	0,	100,	0),
                                                                                                                  (6,	'Materiál',	'm',	'out',	0,	100,	0),
                                                                                                                  (7,	'Převod do stř. pokladny',	'pr',	'out',	1,	100,	0),
                                                                                                                  (8,	'Neurčeno',	'un',	'out',	0,	101,	0),
                                                                                                                  (9,	'Převod z pokladny střediska',	'ps',	'in',	1,	100,	0),
                                                                                                                  (10,	'Cestovné',	'c',	'out',	0,	100,	0),
                                                                                                                  (11,	'Hromadný příjem od úč.',	'hpd',	'in',	0,	100,	0),
                                                                                                                  (12,	'Neurčeno',	'np',	'in',	0,	101,	0),
                                                                                                                  (13,	'Převod z odd. pokladny',	'zd',	'in',	1,	100,	0),
                                                                                                                  (14,	'Převod do odd. pokladny',	'dd',	'out',	1,	100,	0),
                                                                                                                  (15,	'Převod z akce',	'za',	'in',	1,	100,	0),
                                                                                                                  (16,	'Převod do akce',	'da',	'out',	1,	100,	0),
                                                                                                                  (17,	'Příspěvky samosprávy',	'sa',	'in',	0,	100,	0),
                                                                                                                  (18,	'Ostatní příjmy',	'op',	'in',	0,	100,	0),
                                                                                                                  (19,	'Vybavení',	'v',	'out',	0,	100,	0),
                                                                                                                  (20,	'Vratka úč. poplatku',	'vr',	'out',	1,	100,	0),
                                                                                                                  (21,	'Vratka úč. poplatku - dítě',	'vrc',	'out',	1,	100,	0),
                                                                                                                  (22,	'Vratka úč. poplatku - dospělý',	'vra',	'out',	1,	100,	0);

INSERT INTO `ac_chitsCategory_object` (`category_id`, `type`) VALUES
                                                                  (1,	'general'),
                                                                  (2,	'general'),
                                                                  (2,	'unit'),
                                                                  (3,	'general'),
                                                                  (3,	'unit'),
                                                                  (4,	'general'),
                                                                  (4,	'unit'),
                                                                  (5,	'general'),
                                                                  (5,	'unit'),
                                                                  (6,	'general'),
                                                                  (6,	'unit'),
                                                                  (7,	'camp'),
                                                                  (7,	'general'),
                                                                  (7,	'unit'),
                                                                  (8,	'camp'),
                                                                  (8,	'general'),
                                                                  (8,	'unit'),
                                                                  (9,	'camp'),
                                                                  (9,	'general'),
                                                                  (9,	'unit'),
                                                                  (10,	'general'),
                                                                  (10,	'unit'),
                                                                  (11,	'general'),
                                                                  (12,	'camp'),
                                                                  (12,	'general'),
                                                                  (12,	'unit'),
                                                                  (13,	'camp'),
                                                                  (13,	'general'),
                                                                  (13,	'unit'),
                                                                  (14,	'camp'),
                                                                  (14,	'general'),
                                                                  (14,	'unit'),
                                                                  (15,	'unit'),
                                                                  (16,	'unit'),
                                                                  (17,	'general'),
                                                                  (18,	'general'),
                                                                  (19,	'general'),
                                                                  (20,	'education'),
                                                                  (20,	'general'),
                                                                  (21,	'camp'),
                                                                  (22,	'camp');
