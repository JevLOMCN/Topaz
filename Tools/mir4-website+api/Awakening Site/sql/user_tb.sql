-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: mm_user_db
-- ------------------------------------------------------
CREATE TABLE `user_tb` (
  `UserUID` bigint(20) NOT NULL AUTO_INCREMENT,
  `AccountUID` bigint(20) NOT NULL,
  `Username` varchar(45) NOT NULL,
  `PasswordHash` varchar(64) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Status` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserUID`),
  KEY `idx_user_tb_AccountUID_Username_PasswordHash` (`AccountUID`,`Username`,`PasswordHash`)
) ENGINE=InnoDB AUTO_INCREMENT=4471 DEFAULT CHARSET=latin1;