CREATE TABLE `btce_avg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair` varchar(6) NOT NULL,
  `name` varchar(10) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  `ts` int(11) NOT NULL,
  `avg_ask` varchar(45) NOT NULL,
  `avg_bid` varchar(45) NOT NULL,
  `avg_high` varchar(45) NOT NULL,
  `avg_low` varchar(45) NOT NULL,
  `period_avg_val` varchar(45) NOT NULL,
  `avg_vol` varchar(45) NOT NULL,
  `avg_vol_cur` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pair` (`pair`),
  KEY `period` (`period`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

CREATE TABLE `yahoo_avg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair` varchar(6) NOT NULL,
  `name` varchar(10) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  `ts` int(11) NOT NULL,
  `avg_ask` varchar(45) NOT NULL,
  `avg_bid` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pair` (`pair`),
  KEY `period` (`period`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8;