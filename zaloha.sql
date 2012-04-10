-- Adminer 3.3.4 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ac_action`;
CREATE TABLE `ac_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` int(10) unsigned NOT NULL,
  `create` int(10) unsigned NOT NULL COMMENT 'SkautisID',
  `name` varchar(64) NOT NULL,
  `leader` int(10) unsigned NOT NULL COMMENT 'SkautisID',
  `executor` int(10) unsigned NOT NULL COMMENT 'SkautisID',
  `start` date NOT NULL,
  `end` date NOT NULL,
  `plac` varchar(64) NOT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `unit_id` (`unit_id`),
  CONSTRAINT `ac_action_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `ac_units` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `ac_actionsTroops`;
CREATE TABLE `ac_actionsTroops` (
  `action_id` int(10) unsigned NOT NULL,
  `troop_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `ac_chits`;
CREATE TABLE `ac_chits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actionId` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `recipient` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `purpose` varchar(40) COLLATE utf8_czech_ci NOT NULL,
  `price` float NOT NULL,
  `priceText` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `category` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `ac_chits_ibfk_1` FOREIGN KEY (`category`) REFERENCES `ac_chitsCategory` (`short`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_chits` (`id`, `actionId`, `date`, `recipient`, `purpose`, `price`, `priceText`, `category`, `deleted`) VALUES
(1,	22,	'2012-04-04',	'd',	'd',	2,	'2',	'j',	0),
(2,	22,	'2012-03-01',	'Konířová Dorota',	'pokus',	12000,	'12000',	'pp',	1),
(3,	22,	'2012-03-13',	'Konířová Dorota',	'Účastnické poplatky',	37,	'37',	'pp',	1),
(4,	22,	'2012-03-13',	'Konířová Dorota',	'Účastnické poplatky',	161,	'161',	'pp',	0),
(5,	22,	'2012-04-05',	'Čapek Pavel',	'chleba, lizatko',	109,	'4*26+5',	't',	0);

DROP TABLE IF EXISTS `ac_chitsCategory`;
CREATE TABLE `ac_chitsCategory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `short` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('in','out') COLLATE utf8_czech_ci NOT NULL DEFAULT 'out',
  `orderby` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `short` (`short`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `orderby`, `deleted`) VALUES
(1,	'Přijmy od účastníků',	'pp',	'in',	100,	0),
(2,	'Služby',	's',	'out',	100,	0),
(3,	'Potraviny',	't',	'out',	100,	0),
(4,	'Jízdné',	'j',	'out',	100,	0),
(5,	'Nájemné',	'n',	'out',	100,	0),
(6,	'Materiál',	'm',	'out',	100,	0),
(7,	'Převod do stř. pokladny',	'pr',	'out',	100,	0),
(8,	'Neurčeno',	'un',	'out',	101,	0);

DROP TABLE IF EXISTS `ac_participant`;
CREATE TABLE `ac_participant` (
  `actionId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `payment` float NOT NULL,
  UNIQUE KEY `actionId_userId` (`actionId`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_participant` (`actionId`, `userId`, `payment`) VALUES
(1,	1,	0),
(1,	123,	200),
(1,	124,	222);

DROP TABLE IF EXISTS `ac_units`;
CREATE TABLE `ac_units` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `owner` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `created` date NOT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `ac_units` (`id`, `name`, `owner`, `created`, `deleted`) VALUES
(23506,	'621 - okres Blansko',	'',	'2012-02-08',	0);

DROP TABLE IF EXISTS `acl`;
CREATE TABLE `acl` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `role_id` tinyint(3) unsigned NOT NULL,
  `resource_id` tinyint(3) unsigned DEFAULT NULL,
  `privilege_id` tinyint(3) unsigned DEFAULT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `resource_id` (`resource_id`),
  KEY `privilege_id` (`privilege_id`),
  CONSTRAINT `acl_ibfk_4` FOREIGN KEY (`resource_id`) REFERENCES `acl_resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `acl_ibfk_5` FOREIGN KEY (`role_id`) REFERENCES `acl_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `acl_ibfk_6` FOREIGN KEY (`privilege_id`) REFERENCES `acl_privileges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `acl` (`id`, `role_id`, `resource_id`, `privilege_id`, `allowed`) VALUES
(4,	2,	NULL,	NULL,	'Y'),
(7,	14,	4,	NULL,	'Y');

DROP TABLE IF EXISTS `acl_actionAccess`;
CREATE TABLE `acl_actionAccess` (
  `actionId` int(10) unsigned NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  KEY `userId` (`userId`),
  KEY `actionId` (`actionId`),
  CONSTRAINT `acl_actionAccess_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `acl_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `acl_actionAccess_ibfk_4` FOREIGN KEY (`actionId`) REFERENCES `ac_action` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `acl_privileges`;
CREATE TABLE `acl_privileges` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `acl_privileges` (`id`, `label`, `description`) VALUES
(1,	'view',	''),
(2,	'edit',	''),
(3,	'delete',	''),
(4,	'add',	'');

DROP TABLE IF EXISTS `acl_resources`;
CREATE TABLE `acl_resources` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(64) DEFAULT NULL,
  `description` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `acl_resources` (`id`, `label`, `description`) VALUES
(2,	'user',	'modul user pro správu uživatelů'),
(4,	'ucetnictvi',	'ucetnictvi bez napojení na další app'),
(6,	'skautis',	'propojení skautISu a ucetnictvi');

DROP TABLE IF EXISTS `acl_roles`;
CREATE TABLE `acl_roles` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` tinyint(3) unsigned DEFAULT NULL,
  `label` varchar(64) NOT NULL,
  `desc` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `acl_roles` (`id`, `parentId`, `label`, `desc`) VALUES
(1,	NULL,	'guest',	''),
(2,	NULL,	'admin',	''),
(14,	NULL,	'demo',	''),
(15,	1,	'basic',	'základní role pro běžného uživatele');

DROP TABLE IF EXISTS `acl_users`;
CREATE TABLE `acl_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(40) COLLATE utf8_czech_ci NOT NULL,
  `skautID` int(10) unsigned DEFAULT NULL COMMENT 'skautIS ID',
  `role` tinyint(3) unsigned NOT NULL,
  `nick` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `hash` varchar(40) COLLATE utf8_czech_ci NOT NULL COMMENT 'náhodná hodnota',
  `email` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`),
  UNIQUE KEY `id` (`id`),
  KEY `role` (`role`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `acl_users_ibfk_1` FOREIGN KEY (`role`) REFERENCES `acl_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `acl_users` (`id`, `username`, `password`, `skautID`, `role`, `nick`, `hash`, `email`, `deleted`) VALUES
(1,	'joe',	'732b1b3f8acfe8f0c1506ddb47c12cb94669f758',	2057,	2,	'Joe Doe',	'0830ca2660f77488a210d8c8ed0712d5b6649e7f',	'',	0);

DROP TABLE IF EXISTS `skautis_functions`;
CREATE TABLE `skautis_functions` (
  `type` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `skautis_functions` (`type`, `name`) VALUES
(1,	'UnitRegistrationSummary'),
(1,	'PersonOtherDetail'),
(1,	'AdvertisingCategoryUpdate'),
(1,	'StatementAll'),
(1,	'RealtyInsert'),
(1,	'RealtyDetail'),
(1,	'QualificationTypeAll'),
(1,	'MembershipReasonAll'),
(1,	'MembershipDetail'),
(1,	'FunctionAll'),
(1,	'QualificationDeleteHistory'),
(1,	'RequestAll'),
(1,	'UnitAllUnit'),
(1,	'StatementComputeIsError'),
(1,	'MeetingDateDelete'),
(1,	'AdvertisingCategoryInsert'),
(1,	'AccountUpdate'),
(1,	'PersonDetail'),
(1,	'UnitMistakeReportInsert'),
(1,	'RegistrationCategoryDelete'),
(1,	'PersonInsert'),
(1,	'PersonMistakeReportDelete'),
(1,	'StatementErrors'),
(1,	'UnitCancelInsert'),
(1,	'AdvertisingCategoryDetail'),
(1,	'AdvertisingCategoryAll'),
(1,	'PersonUpdateBasic'),
(1,	'UnitTypeAll'),
(1,	'EducationDelete'),
(1,	'PersonRegistrationAll'),
(1,	'PersonDetailSecurityCode'),
(1,	'FunctionDeleteHistory'),
(1,	'UnitRegistrationMembers'),
(1,	'UnitDetailRegistry'),
(1,	'UnitDetailMembersRegistry'),
(1,	'PersonPhoto'),
(1,	'AccountInsert'),
(1,	'StatementTypeAll'),
(1,	'PersonContactAll'),
(1,	'FunctionTypeDelete'),
(1,	'PersonRegistrationDelete'),
(1,	'PersonRegistrationAllPerson'),
(1,	'PersonAllLogin'),
(1,	'MistakeDetail'),
(1,	'AccountDetail'),
(1,	'PersonUpdateUser'),
(1,	'UnitRegistrationUpdate'),
(1,	'UnitAll'),
(1,	'FunctionReasonAll'),
(1,	'DegreeAll'),
(1,	'PersonDetailIdentificationCode'),
(1,	'EducatationSeminaryUpdate'),
(1,	'EducatationSeminaryAll'),
(1,	'ContactTypeAll'),
(1,	'AssuranceAll'),
(1,	'AlignmentTypeAll'),
(1,	'UnitRegistrationInsert'),
(1,	'UnitRegistrationAll'),
(1,	'StatementUpdate'),
(1,	'RegistrationCategoryAll'),
(1,	'PersonContactDelete'),
(1,	'OfferUpdate'),
(1,	'EducationTypeAll'),
(1,	'DegreeDelete'),
(1,	'MembershipAllPerson'),
(1,	'UnitLogo'),
(1,	'EducatationSeminaryInsert'),
(1,	'UnitRegistrationDetail'),
(1,	'UnitContactDelete'),
(1,	'StatementEntryTypeAll'),
(1,	'RealtyTypeAll'),
(1,	'QualificationUpdate'),
(1,	'PersonAllRegistrationCategory'),
(1,	'UnitTreeRenew'),
(1,	'AdvertisingSummary'),
(1,	'PersonUpdateAddress'),
(1,	'UnitTreeUpdate'),
(1,	'StatementInsert'),
(1,	'StatementDetail'),
(1,	'OfferInsert'),
(1,	'MeetingDateUpdate'),
(1,	'FunctionAllRegistry'),
(1,	'PersonAllUstredi'),
(1,	'PersonAllCatalog'),
(1,	'UnitRegistrationCheck'),
(1,	'UnitInsertUnit'),
(1,	'QualificationInsert'),
(1,	'OccupationDelete'),
(1,	'FunctionUpdate'),
(1,	'QualificationInsertHistory'),
(1,	'RequestUpdate'),
(1,	'PersonAllHelpdesk'),
(1,	'MembershipRenew'),
(1,	'StatementEntryAllTotals'),
(1,	'AdvertisingUpdate'),
(1,	'PersonAllEventCamp'),
(1,	'UnitTreeDetail'),
(1,	'UnitContactAll'),
(1,	'StatementEntryUpdate'),
(1,	'RegistrationCategoryInsert'),
(1,	'QualificationDetail'),
(1,	'MembershipCategoryAll'),
(1,	'MembershipAll'),
(1,	'FunctionTypeUpdate'),
(1,	'EducationUpdate'),
(1,	'FunctionAllPerson'),
(1,	'UnitRegistrationReport'),
(1,	'RequestStateAll'),
(1,	'RequestInsert'),
(1,	'MeetingDateInsert'),
(1,	'MeetingDateDetail'),
(1,	'AlignmentUpdate'),
(1,	'AdvertisingCategoryDelete'),
(1,	'AccountAll'),
(1,	'UnitAllCamp'),
(1,	'UnitMistakeReportDelete'),
(1,	'PersonAll'),
(1,	'FunctionInsert'),
(1,	'FunctionDetail'),
(1,	'EducationInsert'),
(1,	'RegistrationCategoryCopyFromParentUnit'),
(1,	'PersonMistakeReportInsert'),
(1,	'FunctionInsertHistory'),
(1,	'RequestDetail'),
(1,	'UnitCancelAll'),
(1,	'PersonAllEventCongressFunction'),
(1,	'BankDetail'),
(1,	'AdvertisingDetail'),
(1,	'PersonAllEventCampMulti'),
(1,	'UnitTreeAll'),
(1,	'StatementEntryAll'),
(1,	'OfferTypeAll'),
(1,	'FunctionTypeInsert'),
(1,	'FunctionTypeDetail'),
(1,	'DegreeTypeAll'),
(1,	'PersonRegistrationInsert'),
(1,	'QualificationUpdateHistory'),
(1,	'AlignmentInsert'),
(1,	'AlignmentDetail'),
(1,	'AccountDelete'),
(1,	'PersonAllPublic'),
(1,	'UnitContactUpdate'),
(1,	'SexAll'),
(1,	'PersonContactUpdate'),
(1,	'OfferAll'),
(1,	'OccupationAll'),
(1,	'DegreeUpdate'),
(1,	'PersonParseIdentificationCode'),
(1,	'UnitCancelTypeAll'),
(1,	'BankAll'),
(1,	'UnitUpdate'),
(1,	'TroopArtAll'),
(1,	'PersonContactInsert'),
(1,	'FunctionTypeAll'),
(1,	'FunctionUpdateHistory'),
(1,	'UnitAllRegistry'),
(1,	'PersonCatalogSummary'),
(1,	'UnitContactInsert'),
(1,	'RealtyAll'),
(1,	'OccupationUpdate'),
(1,	'DegreeInsert'),
(1,	'MeetingDateAll'),
(1,	'PersonOtherUpdate'),
(1,	'EducatationSeminaryDelete'),
(1,	'AlignmentAll'),
(1,	'UnitDetail'),
(1,	'UnitTreeReasonAll'),
(1,	'StatementDelete'),
(1,	'RealtyUpdate'),
(1,	'QualificationAll'),
(1,	'OfferDelete'),
(1,	'MembershipUpdate'),
(1,	'EducationAll'),
(1,	'UnitRegistrationAllChild'),
(1,	'PersonAllExport'),
(1,	'StatementAllChild'),
(1,	'UnitAllRegistryBasic'),
(1,	'PersonUpdate'),
(1,	'OccupationInsert'),
(1,	'OccupationDetail'),
(1,	'MembershipTypeAll'),
(1,	'MembershipInsert'),
(1,	'UnitRegistrationSummary'),
(1,	'PersonOtherDetail'),
(1,	'AdvertisingCategoryUpdate'),
(1,	'StatementAll'),
(1,	'RealtyInsert'),
(1,	'RealtyDetail'),
(1,	'QualificationTypeAll'),
(1,	'MembershipReasonAll'),
(1,	'MembershipDetail'),
(1,	'FunctionAll'),
(1,	'QualificationDeleteHistory'),
(1,	'RequestAll'),
(1,	'UnitAllUnit'),
(1,	'StatementComputeIsError'),
(1,	'MeetingDateDelete'),
(1,	'AdvertisingCategoryInsert'),
(1,	'AccountUpdate'),
(1,	'PersonDetail'),
(1,	'UnitMistakeReportInsert'),
(1,	'RegistrationCategoryDelete'),
(1,	'PersonInsert'),
(1,	'PersonMistakeReportDelete'),
(1,	'StatementErrors'),
(1,	'UnitCancelInsert'),
(1,	'AdvertisingCategoryDetail'),
(1,	'AdvertisingCategoryAll'),
(1,	'PersonUpdateBasic'),
(1,	'UnitTypeAll'),
(1,	'EducationDelete'),
(1,	'PersonRegistrationAll'),
(1,	'PersonDetailSecurityCode'),
(1,	'FunctionDeleteHistory'),
(1,	'UnitRegistrationMembers'),
(1,	'UnitDetailRegistry'),
(1,	'UnitDetailMembersRegistry'),
(1,	'PersonPhoto'),
(1,	'AccountInsert'),
(1,	'StatementTypeAll'),
(1,	'PersonContactAll'),
(1,	'FunctionTypeDelete'),
(1,	'PersonRegistrationDelete'),
(1,	'PersonRegistrationAllPerson'),
(1,	'PersonAllLogin'),
(1,	'MistakeDetail'),
(1,	'AccountDetail'),
(1,	'PersonUpdateUser'),
(1,	'UnitRegistrationUpdate'),
(1,	'UnitAll'),
(1,	'FunctionReasonAll'),
(1,	'DegreeAll'),
(1,	'PersonDetailIdentificationCode'),
(1,	'EducatationSeminaryUpdate'),
(1,	'EducatationSeminaryAll'),
(1,	'ContactTypeAll'),
(1,	'AssuranceAll'),
(1,	'AlignmentTypeAll'),
(1,	'UnitRegistrationInsert'),
(1,	'UnitRegistrationAll'),
(1,	'StatementUpdate'),
(1,	'RegistrationCategoryAll'),
(1,	'PersonContactDelete'),
(1,	'OfferUpdate'),
(1,	'EducationTypeAll'),
(1,	'DegreeDelete'),
(1,	'MembershipAllPerson'),
(1,	'UnitLogo'),
(1,	'EducatationSeminaryInsert'),
(1,	'UnitRegistrationDetail'),
(1,	'UnitContactDelete'),
(1,	'StatementEntryTypeAll'),
(1,	'RealtyTypeAll'),
(1,	'QualificationUpdate'),
(1,	'PersonAllRegistrationCategory'),
(1,	'UnitTreeRenew'),
(1,	'AdvertisingSummary'),
(1,	'PersonUpdateAddress'),
(1,	'UnitTreeUpdate'),
(1,	'StatementInsert'),
(1,	'StatementDetail'),
(1,	'OfferInsert'),
(1,	'MeetingDateUpdate'),
(1,	'FunctionAllRegistry'),
(1,	'PersonAllUstredi'),
(1,	'PersonAllCatalog'),
(1,	'UnitRegistrationCheck'),
(1,	'UnitInsertUnit'),
(1,	'QualificationInsert'),
(1,	'OccupationDelete'),
(1,	'FunctionUpdate'),
(1,	'QualificationInsertHistory'),
(1,	'RequestUpdate'),
(1,	'PersonAllHelpdesk'),
(1,	'MembershipRenew'),
(1,	'StatementEntryAllTotals'),
(1,	'AdvertisingUpdate'),
(1,	'PersonAllEventCamp'),
(1,	'UnitTreeDetail'),
(1,	'UnitContactAll'),
(1,	'StatementEntryUpdate'),
(1,	'RegistrationCategoryInsert'),
(1,	'QualificationDetail'),
(1,	'MembershipCategoryAll'),
(1,	'MembershipAll'),
(1,	'FunctionTypeUpdate'),
(1,	'EducationUpdate'),
(1,	'FunctionAllPerson'),
(1,	'UnitRegistrationReport'),
(1,	'RequestStateAll'),
(1,	'RequestInsert'),
(1,	'MeetingDateInsert'),
(1,	'MeetingDateDetail'),
(1,	'AlignmentUpdate'),
(1,	'AdvertisingCategoryDelete'),
(1,	'AccountAll'),
(1,	'UnitAllCamp'),
(1,	'UnitMistakeReportDelete'),
(1,	'PersonAll'),
(1,	'FunctionInsert'),
(1,	'FunctionDetail'),
(1,	'EducationInsert'),
(1,	'RegistrationCategoryCopyFromParentUnit'),
(1,	'PersonMistakeReportInsert'),
(1,	'FunctionInsertHistory'),
(1,	'RequestDetail'),
(1,	'UnitCancelAll'),
(1,	'PersonAllEventCongressFunction'),
(1,	'BankDetail'),
(1,	'AdvertisingDetail'),
(1,	'PersonAllEventCampMulti'),
(1,	'UnitTreeAll'),
(1,	'StatementEntryAll'),
(1,	'OfferTypeAll'),
(1,	'FunctionTypeInsert'),
(1,	'FunctionTypeDetail'),
(1,	'DegreeTypeAll'),
(1,	'PersonRegistrationInsert'),
(1,	'QualificationUpdateHistory'),
(1,	'AlignmentInsert'),
(1,	'AlignmentDetail'),
(1,	'AccountDelete'),
(1,	'PersonAllPublic'),
(1,	'UnitContactUpdate'),
(1,	'SexAll'),
(1,	'PersonContactUpdate'),
(1,	'OfferAll'),
(1,	'OccupationAll'),
(1,	'DegreeUpdate'),
(1,	'PersonParseIdentificationCode'),
(1,	'UnitCancelTypeAll'),
(1,	'BankAll'),
(1,	'UnitUpdate'),
(1,	'TroopArtAll'),
(1,	'PersonContactInsert'),
(1,	'FunctionTypeAll'),
(1,	'FunctionUpdateHistory'),
(1,	'UnitAllRegistry'),
(1,	'PersonCatalogSummary'),
(1,	'UnitContactInsert'),
(1,	'RealtyAll'),
(1,	'OccupationUpdate'),
(1,	'DegreeInsert'),
(1,	'MeetingDateAll'),
(1,	'PersonOtherUpdate'),
(1,	'EducatationSeminaryDelete'),
(1,	'AlignmentAll'),
(1,	'UnitDetail'),
(1,	'UnitTreeReasonAll'),
(1,	'StatementDelete'),
(1,	'RealtyUpdate'),
(1,	'QualificationAll'),
(1,	'OfferDelete'),
(1,	'MembershipUpdate'),
(1,	'EducationAll'),
(1,	'UnitRegistrationAllChild'),
(1,	'PersonAllExport'),
(1,	'StatementAllChild'),
(1,	'UnitAllRegistryBasic'),
(1,	'PersonUpdate'),
(1,	'OccupationInsert'),
(1,	'OccupationDetail'),
(1,	'MembershipTypeAll'),
(1,	'MembershipInsert');

DROP TABLE IF EXISTS `skautis_wsdl`;
CREATE TABLE `skautis_wsdl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `skautis_wsdl` (`id`, `name`) VALUES
(1,	'organization');

DROP VIEW IF EXISTS `acl_userActionView`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `acl_userActionView` AS select `us`.`id` AS `userId`,`ac`.`id` AS `actionId`,`ac`.`name` AS `actionName`,`ac`.`deleted` AS `deleted` from ((`acl_actionAccess` `aa` left join `ac_action` `ac` on((`ac`.`id` = `aa`.`actionId`))) left join `acl_users` `us` on((`us`.`id` = `aa`.`userId`)));

-- 2012-04-05 15:34:31
