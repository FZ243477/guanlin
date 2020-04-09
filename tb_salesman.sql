/*
Navicat MySQL Data Transfer

Source Server         : 绿城二期测试
Source Server Version : 50614
Source Host           : 125.124.142.51:3306
Source Database       : lvcheng

Target Server Type    : MYSQL
Target Server Version : 50614
File Encoding         : 65001

Date: 2020-04-03 16:01:23
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tb_salesman
-- ----------------------------
DROP TABLE IF EXISTS `tb_salesman`;
CREATE TABLE `tb_salesman` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_no` varchar(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tb_salesman
-- ----------------------------
INSERT INTO `tb_salesman` VALUES ('1', '10012', '熊为虎', '13637556642', null, null, null);

-- ----------------------------
-- Table structure for tb_salesman_user
-- ----------------------------
DROP TABLE IF EXISTS `tb_salesman_user`;
CREATE TABLE `tb_salesman_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `salesman_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tb_salesman_user
-- ----------------------------
