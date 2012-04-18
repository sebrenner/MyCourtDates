/*
 Navicat MySQL Data Transfer

 Source Server         : MyCourtDates
 Source Server Version : 50161
 Source Host           : todayspodcast.com
 Source Database       : todayspo_MyCourtDates

 Target Server Version : 50161
 File Encoding         : utf-8

 Date: 04/14/2012 18:08:17 PM
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
  `userBarNumber` varchar(12) NOT NULL,
  `addOnBarNumber` varchar(12) NOT NULL,
  `vintage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `prefs` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `principalBarNumber` (`userBarNumber`,`addOnBarNumber`),
  KEY `addOnBarNumber` (`addOnBarNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='Maps the principal/subscriber id to their add-on ids.\nStores';

-- ----------------------------
--  Table structure for `addOnCaseNumber_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `addOnCaseNumber_tbl`;
CREATE TABLE `addOnCaseNumber_tbl` (
  `userBarNumber` varchar(16) NOT NULL,
  `caseNumber` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `barNumberCaseNumber_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `barNumberCaseNumber_tbl`;
CREATE TABLE `barNumberCaseNumber_tbl` (
  `barNumber` varchar(16) NOT NULL,
  `caseNumber` varchar(16) NOT NULL,
  `type` enum('ofRecord','addOn') NOT NULL,
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
  `vintage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
--  Table structure for `user_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `user_tbl`;
CREATE TABLE `user_tbl` (
  `userBarNumber` varchar(16) NOT NULL,
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
  PRIMARY KEY (`userBarNumber`)
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
--  Procedure structure for `getAddOnBarNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getAddOnBarNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getAddOnBarNumber`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT addOnbarNumber
FROM addOnBarNumber_tbl
WHERE principalBarNumber = MYPARAM
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getAddOnCaseNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getAddOnCaseNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getAddOnCaseNumber`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT 	caseNumber
FROM 	addOnCaseNumber_tbl
WHERE 	userBarNumber = MYPARAM
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `getCase_tbl`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getCase_tbl`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getCase_tbl`(IN UBARNUM varchar(12), IN FIRSTN varchar(16), IN MIDN varchar(16), IN LASTN varchar(16), IN EML varchar(48), IN PHN varchar(16), IN MBL varchar(16), IN STR varchar(48), IN STR2 varchar(48), IN CTY varchar(24), IN ST varchar(2), IN ZP varchar(16), IN SUBSTART date, IN SUBEXP date)
    MODIFIES SQL DATA
REPLACE INTO user_tbl(
	userBarNumber, 
	fName, 
	mName, 
	lName, 
	email,
	phone,
	mobile,
	street,
	street2,
	city,
	state,
	zip,
	subStart,
	subExpire
) 
VALUES ( 
	UBARNUM, 
	FIRSTN, 
	MIDN, 
	LASTN, 
	EML,
	PHN,
	MBL,
	STR,
	STR2,
	CTY,
	ST,
	ZP,
	SUBSTART,
	SUBEXP
)
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
--  Procedure structure for `getUserExpiration`
-- ----------------------------
DROP PROCEDURE IF EXISTS `getUserExpiration`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `getUserExpiration`(IN MYPARAM varchar(24))
    READS SQL DATA
SELECT subExpire 
FROM user_tbl 
where barNumber = MYPARAM
LIMIT 1
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
--  Procedure structure for `setAddOnBarNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `setAddOnBarNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `setAddOnBarNumber`(IN UBARNUM varchar(16), IN ADDBARNUM varchar(16))
    READS SQL DATA
REPLACE INTO addOnBarNumber_tbl(
	userBarNumber, 
	addOnbarNumber
) 
VALUES ( 
	UBARNUM, 
	ADDBARNUM
)
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `setBarNumberCaseNumber`
-- ----------------------------
DROP PROCEDURE IF EXISTS `setBarNumberCaseNumber`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `setBarNumberCaseNumber`(IN BARNUM varchar(16), IN CNUM varchar(16), IN TYPE varchar(16))
    MODIFIES SQL DATA
REPLACE INTO barNumberCaseNumber_tbl(
	barNumber, 
	caseNumber,
	type
) 
VALUES ( 
	BARNUM, 
	CNUM,
	TYPE
	)
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `setCase_tbl`
-- ----------------------------
DROP PROCEDURE IF EXISTS `setCase_tbl`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `setCase_tbl`(IN CNUM varchar(12), IN PNTFS varchar(16), IN DEFS varchar(16), IN JID varchar(16), IN FDATE date, IN TOTDEPOSIT varchar(16), IN TOTCOSTS varchar(16), IN RACE varchar(48), IN DOB date, IN SEX varchar(24))
    MODIFIES SQL DATA
REPLACE INTO case_tbl(
	caseNumber,
	plaintiffs,
	defendants, 
	judge_id,
	filedDate, 
	totDeposit,
	totCosts,
	race,
	dob,
	sex
) 
VALUES ( 
	CNUM,
	PNTFS,
	DEFS, 
	JID,
	FDATE, 
	TOTDEPOSIT, 
	TOTCOSTS, 
	RACE,
	DOB,
	SEX
)
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `setNAC_tbl`
-- ----------------------------
DROP PROCEDURE IF EXISTS `setNAC_tbl`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `setNAC_tbl`(IN CNUM varchar(12), TDATE varchar(16), ACTV varchar(1), LOC varchar(32), SETNG varchar(48))
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

-- ----------------------------
--  Procedure structure for `setUser_tbl`
-- ----------------------------
DROP PROCEDURE IF EXISTS `setUser_tbl`;
delimiter ;;
CREATE DEFINER=`todayspo_MyCtAdm`@`65.27.148.%` PROCEDURE `setUser_tbl`(IN UBARNUM varchar(12), IN FIRSTN varchar(16), IN MIDN varchar(16), IN LASTN varchar(16), IN EML varchar(48), IN PHN varchar(16), IN MBL varchar(16), IN STR varchar(48), IN STR2 varchar(48), IN CTY varchar(24), IN ST varchar(2), IN ZP varchar(16), IN SUBSTART date, IN SUBEXP date)
    MODIFIES SQL DATA
REPLACE INTO user_tbl(
	userBarNumber, 
	fName, 
	mName, 
	lName, 
	email,
	phone,
	mobile,
	street,
	street2,
	city,
	state,
	zip,
	subStart,
	subExpire
) 
VALUES ( 
	UBARNUM, 
	FIRSTN, 
	MIDN, 
	LASTN, 
	EML,
	PHN,
	MBL,
	STR,
	STR2,
	CTY,
	ST,
	ZP,
	SUBSTART,
	SUBEXP
)
 ;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
