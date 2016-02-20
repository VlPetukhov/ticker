
CREATE TABLE `currency_pair` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pair` varchar(8) NOT NULL COMMENT 'Pair name 6-8 symbols, like USDRUR, USDEUR, etc.',
  `name` varchar(45) NOT NULL COMMENT 'Pair name for displaying, like USD/EUR',
  `description` varchar(255) DEFAULT NULL COMMENT 'Optional description of currency pair',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Currency pair describing table';

LOCK TABLES `currency_pair` WRITE;
ALTER TABLE `currency_pair` DISABLE KEYS;
INSERT INTO `currency_pair` VALUES (1,'USDRUB','USD/RUB',NULL),(2,'EURRUB','EUR/RUB',NULL),(3,'USDCNY','USD/CNY','China\'s Yuan'),(4,'USDUAH','USD/UAH','Ukrainian hrivna'),(5,'USDBTC','USD/BTC',NULL),(6,'BTCRUR','BTC/RUR',NULL),(7,'BTCEUR','BTC/EUR',NULL),(8,'BTCUSD','BTC/USD',NULL);
ALTER TABLE `currency_pair` ENABLE KEYS;
UNLOCK TABLES;




CREATE TABLE `periods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value`  int(11) unsigned NOT NULL COMMENT 'Period time in seconds',
  `name` varchar(45) NOT NULL COMMENT 'Period name for displaying, like "5 minutes"',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Periods table';

LOCK TABLES `periods` WRITE;
ALTER TABLE `periods` DISABLE KEYS;
INSERT INTO `periods` VALUES (1,300,'5 minutes'),(2,3600,'1 hour'),(3,14400,'4 hours'),(4,86400,'24 hours'),(5,604800,'1 week'),(6,2629745,'30,43686 days( avg tropical month)'),(7,315556941,'1year (avg tropical year)');
ALTER TABLE `periods` ENABLE KEYS;
UNLOCK TABLES;



CREATE TABLE `yahoo_raw_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pair_id` int(10) unsigned NOT NULL COMMENT 'Currency pair ID',
  `ts` int(10) unsigned NOT NULL,
  `ask` float unsigned NOT NULL COMMENT 'Ask value',
  `bid` float unsigned NOT NULL COMMENT 'Bid value',
  PRIMARY KEY (`id`),
  KEY `yahoo_raw_data_ts_idx` (`ts`),
  KEY `yahoo_raw_data_currency_pair_fk_idx` (`pair_id`),
  CONSTRAINT `yahoo_raw_data_currency_pair_fk` FOREIGN KEY (`pair_id`) REFERENCES `currency_pair` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Yahoo currency rate raw data table';



CREATE TABLE `btce_raw_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pair_id` int(10) unsigned NOT NULL COMMENT 'Currency pair ID',
  `ts` int(10) unsigned NOT NULL,
  `ask` float unsigned NOT NULL COMMENT 'Ask value',
  `bid` float unsigned NOT NULL COMMENT 'Bid value',
  `high` float unsigned NOT NULL,
  `low` float unsigned NOT NULL,
  `avg_val` float unsigned NOT NULL,
  `vol` float unsigned NOT NULL,
  `vol_cur` float unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `btce_raw_data_ts_idx` (`ts`),
  KEY `btce_raw_data_currency_pair_fk_idx` (`pair_id`),
  CONSTRAINT `btce_raw_data_currency_pair_fk` FOREIGN KEY (`pair_id`) REFERENCES `currency_pair` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='BTCe currency rate raw data table';



CREATE TABLE `yahoo_stat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pair_id` int(10) unsigned NOT NULL COMMENT 'Currency pair ID',
  `period_id` int(10) unsigned NOT NULL COMMENT 'Period ID',
  `ts` int(10) unsigned NOT NULL,
  `ask` float unsigned NOT NULL COMMENT 'Ask value',
  `bid` float unsigned NOT NULL COMMENT 'Bid value',
  PRIMARY KEY (`id`),
  KEY `yahoo_stat_ts_idx` (`ts`),
  KEY `yahoo_stat_currency_pair_fk_idx` (`pair_id`),
  CONSTRAINT `yahoo_stat_currency_pair_fk` FOREIGN KEY (`pair_id`) REFERENCES `currency_pair` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Yahoo currency rate statistic table';



CREATE TABLE `btce_stat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pair_id` int(10) unsigned NOT NULL COMMENT 'Currency pair ID',
  `period_id` int(10) unsigned NOT NULL COMMENT 'Period ID',
  `ts` int(10) unsigned NOT NULL,
  `ask` float unsigned NOT NULL COMMENT 'Ask value',
  `bid` float unsigned NOT NULL COMMENT 'Bid value',
  `high` float unsigned NOT NULL,
  `low` float unsigned NOT NULL,
  `avg_val` float unsigned NOT NULL,
  `vol` float unsigned NOT NULL,
  `vol_cur` float unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `btce_stat_ts_idx` (`ts`),
  KEY `btce_stat_currency_pair_fk_idx` (`pair_id`),
  CONSTRAINT `btce_stat_currency_pair_fk` FOREIGN KEY (`pair_id`) REFERENCES `currency_pair` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='BTCe currency rate statistic table';


