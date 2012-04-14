/*
 Navicat MySQL Data Transfer

 Source Server         : MyCourtDates
 Source Server Version : 50161
 Source Host           : todayspodcast.com
 Source Database       : todayspo_MyCourtDates

 Target Server Version : 50161
 File Encoding         : utf-8

 Date: 04/14/2012 16:25:18 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `NAC_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `NAC_tbl`;
CREATE TABLE `NAC_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` enum('1','0') NOT NULL,
  `caseNumber` varchar(16) NOT NULL,
  `timeDate` datetime NOT NULL,
  `setting` varchar(32) NOT NULL,
  `location` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `active` (`active`,`caseNumber`,`timeDate`,`setting`,`location`),
  KEY `caseNumber` (`caseNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `addOnBarNumber_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `addOnBarNumber_tbl`;
CREATE TABLE `addOnBarNumber_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `principalBarNumber` varchar(12) NOT NULL,
  `addOnBarNumber` varchar(12) NOT NULL,
  `vintage` datetime NOT NULL,
  `prefs` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `principalBarNumber` (`principalBarNumber`,`addOnBarNumber`),
  KEY `addOnBarNumber` (`addOnBarNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Maps the principal/subscriber id to their add-on ids.\nStores';

-- ----------------------------
--  Table structure for `barNumberCaseNumber_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `barNumberCaseNumber_tbl`;
CREATE TABLE `barNumberCaseNumber_tbl` (
  `barNumber` varchar(16) NOT NULL,
  `caseNumber` varchar(16) NOT NULL,
  KEY `barNumber` (`barNumber`),
  KEY `caseNumber` (`caseNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `case_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `case_tbl`;
CREATE TABLE `case_tbl` (
  `caseNumber` varchar(16) NOT NULL,
  `plaintiffs` varchar(128) NOT NULL,
  `defendants` varchar(128) NOT NULL,
  `judge_id` int(8) NOT NULL,
  `filedDate` date NOT NULL,
  `totDeposit` float(8,0) DEFAULT NULL,
  `totCosts` float(8,0) DEFAULT NULL,
  `race` varchar(32) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` enum('M','F') DEFAULT NULL,
  PRIMARY KEY (`caseNumber`),
  UNIQUE KEY `caseNumber` (`caseNumber`),
  KEY `caseNumber_2` (`caseNumber`),
  KEY `caseNumber_3` (`caseNumber`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `judge_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `judge_tbl`;
CREATE TABLE `judge_tbl` (
  `judgeId` int(8) NOT NULL,
  `fName` varchar(24) NOT NULL,
  `lName` varchar(24) NOT NULL,
  `mName` varchar(24) NOT NULL,
  `location` varchar(24) NOT NULL,
  `civPhone` varchar(16) NOT NULL,
  `crimPhone` varchar(16) NOT NULL,
  PRIMARY KEY (`judgeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `subscriber_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `subscriber_tbl`;
CREATE TABLE `subscriber_tbl` (
  `barNumber` varchar(16) NOT NULL,
  `fName` varchar(24) NOT NULL,
  `lName` varchar(24) NOT NULL,
  `mName` varchar(24) NOT NULL,
  `email` varchar(48) NOT NULL,
  `phone` varchar(16) NOT NULL,
  `mobile` varchar(16) DEFAULT NULL,
  `street` varchar(32) NOT NULL,
  `street2` varchar(32) DEFAULT NULL,
  `city` varchar(24) NOT NULL,
  `state` varchar(24) NOT NULL,
  `zip` varchar(12) NOT NULL,
  `subStart` datetime NOT NULL,
  `subExpire` datetime NOT NULL,
  PRIMARY KEY (`barNumber`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  View structure for `NAC_view`
-- ----------------------------
DROP VIEW IF EXISTS `NAC_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` SQL SECURITY DEFINER VIEW `NAC_view` AS select `NAC_tbl`.`caseNumber` AS `caseNumber`,`NAC_tbl`.`timeDate` AS `timeDate`,`NAC_tbl`.`location` AS `location`,`case_tbl`.`plaintiffs` AS `plaintiffs`,`case_tbl`.`defendants` AS `defendants`,`case_tbl`.`filedDate` AS `filedDate`,`case_tbl`.`sex` AS `sex`,`case_tbl`.`dob` AS `dob`,`case_tbl`.`race` AS `race`,`case_tbl`.`totCosts` AS `totCosts`,`case_tbl`.`totDeposit` AS `totDeposit`,`judge_tbl`.`fName` AS `fName`,`judge_tbl`.`lName` AS `lName`,`judge_tbl`.`mName` AS `mName`,`judge_tbl`.`civPhone` AS `civPhone`,`judge_tbl`.`crimPhone` AS `crimPhone` from ((`NAC_tbl` join `case_tbl` on((`NAC_tbl`.`caseNumber` = `case_tbl`.`caseNumber`))) join `judge_tbl` on((`case_tbl`.`judge_id` = `judge_tbl`.`judgeId`))) order by `NAC_tbl`.`timeDate`;

-- ----------------------------
--  Procedure structure for `getActiveNAC`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getActiveNAC`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getActiveNAC`()
    READS SQL DATA
SELECT *
FROM NAC_tbl
WHERE active = 1
ORDER BY timeDate ASC
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getAddOnBarNumbers`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getAddOnBarNumbers`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getAddOnBarNumbers`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT addOnbarNumber
FROM addOnBarNumber_tbl
WHERE principalBarNumber = MYPARAM
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getExpiration`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getExpiration`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getExpiration`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT subExpire 
FROM subscriber_tbl 
where barNumber = MYPARAM
LIMIT 1
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getNACbyBarNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getNACbyBarNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getNACbyBarNumber`(IN MYPARAM varchar(24))
SELECT
	NAC_tbl.caseNumber,
	NAC_tbl.timeDate,
	NAC_tbl.location,
	case_tbl.plaintiffs,
	case_tbl.defendants,
	case_tbl.filedDate,
	case_tbl.sex,
	case_tbl.dob,
	case_tbl.race,
	case_tbl.totCosts,
	case_tbl.totDeposit,
	judge_tbl.fName,
	judge_tbl.lName,
	judge_tbl.mName,	
	judge_tbl.civPhone,
	judge_tbl.crimPhone
FROM NAC_tbl

INNER JOIN case_tbl
ON NAC_tbl.caseNumber = case_tbl.caseNumber

INNER JOIN judge_tbl
ON case_tbl.judge_id = judge_tbl.judgeId

INNER JOIN barNumberCaseNumber_tbl
ON case_tbl.caseNumber = barNumberCaseNumber_tbl.caseNumber
AND barNumberCaseNumber_tbl.barNumber = MYPARAM
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getNACByCaseNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getNACByCaseNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getNACByCaseNumber`(IN MYPARAM varchar(12))
    READS SQL DATA
SELECT *
FROM NAC_tbl
WHERE caseNumber = MYPARAM
ORDER BY timeDate ASC
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getNACbyCnum`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getNACbyCnum`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getNACbyCnum`(IN MYPARAM varchar(24))
SELECT *
FROM NAC_tbl
WHERE caseNum = MYPARAM
ORDER BY timeDate ASC
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getVintageOfSched`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getVintageOfSched`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getVintageOfSched`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT 	addOnBarNumber, MAX( vintage ) AS vintage
FROM   	addOnBarNumber_tbl
WHERE 	addOnBarNumber = MYPARAM
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `updateNAC_tbl`
-- ----------------------------
DROP PROCEDURE IF EXISTS `updateNAC_tbl`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `updateNAC_tbl`(IN CNUM varchar(12), TDATE varchar(16), ACTV varchar(1), LOC varchar(32), SETNG varchar(48))
    MODIFIES SQL DATA
REPLACE INTO NAC_tbl
	( caseNumber, 
	timeDate, 
	active, 
	location, 
	setting ) 
VALUES ( 
	CNUM, 
	TDATE, 
	ACTV, 
	LOC, 
	SETNG 
)
 ;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
