CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `surname` varchar(45) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='User table';


CREATE TABLE `btce_statistic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair` varchar(6) NOT NULL,
  `name` varchar(10) DEFAULT NULL,
  `ts` int(11) NOT NULL,
  `ask` varchar(45) NOT NULL,
  `bid` varchar(45) NOT NULL,
  `high` varchar(45) NOT NULL,
  `low` varchar(45) NOT NULL,
  `avg_val` varchar(45) NOT NULL,
  `vol` varchar(45) NOT NULL,
  `vol_cur` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pair` (`pair`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;


CREATE TABLE `yahoo_statistic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair` varchar(6) NOT NULL,
  `name` varchar(10) DEFAULT NULL,
  `ts` int(11) NOT NULL,
  `ask` varchar(45) NOT NULL,
  `bid` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pair` (`pair`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
