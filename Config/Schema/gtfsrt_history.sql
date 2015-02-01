/*
Navicat MySQL Data Transfer

Source Server         : brislinkv2
Source Server Version : 50540
Source Host           : 192.168.1.156:3306
Source Database       : brislinkv2_struct

Target Server Type    : MYSQL
Target Server Version : 50540
File Encoding         : 65001

Date: 2015-02-01 23:09:20
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `gtfsrt_history`
-- ----------------------------
DROP TABLE IF EXISTS `gtfsrt_history`;
CREATE TABLE `gtfsrt_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `object_id` varchar(65) NOT NULL,
  `object_data` text NOT NULL,
  `object_data_hash` varchar(32) NOT NULL,
  `trip_id` varchar(65) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_id` (`object_id`,`timestamp`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of gtfsrt_history
-- ----------------------------
