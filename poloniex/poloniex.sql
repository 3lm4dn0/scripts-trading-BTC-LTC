SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `poloniex`
--
CREATE DATABASE IF NOT EXISTS `poloniex` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `poloniex`;

-- --------------------------------------------------------

--
-- Estrutura da táboa `chart_data`
--

DROP TABLE IF EXISTS `chart_data`;
CREATE TABLE IF NOT EXISTS `chart_data` (
`id` int(10) unsigned NOT NULL,
  `market` varchar(8) NOT NULL,
  `period` smallint(5) unsigned NOT NULL DEFAULT '300',
  `date` datetime NOT NULL,
  `high` double(16,8) unsigned NOT NULL,
  `low` double(16,8) unsigned NOT NULL,
  `open` double(16,8) unsigned NOT NULL,
  `close` double(16,8) unsigned NOT NULL,
  `volume` double(16,8) unsigned NOT NULL,
  `quoteVolume` double(16,8) unsigned NOT NULL,
  `weightedAverage` double(16,8) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=129416 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da táboa `chart_data_psar`
--

DROP TABLE IF EXISTS `chart_data_psar`;
CREATE TABLE IF NOT EXISTS `chart_data_psar` (
  `market` varchar(10) NOT NULL,
  `period` smallint(5) unsigned NOT NULL DEFAULT '300',
  `date` datetime NOT NULL,
  `af_min` double(12,8) unsigned NOT NULL DEFAULT '0.02500000',
  `af_max` double(12,8) unsigned NOT NULL DEFAULT '0.05000000',
  `high` double(16,8) unsigned NOT NULL,
  `low` double(16,8) unsigned NOT NULL,
  `open` double(16,8) unsigned NOT NULL,
  `close` double(16,8) unsigned NOT NULL,
  `volume` double(16,8) unsigned NOT NULL,
  `quoteVolume` double(16,8) unsigned NOT NULL,
  `weightedAverage` double(16,8) unsigned NOT NULL,
  `ep` double(16,8) unsigned NOT NULL,
  `af` double(16,8) unsigned NOT NULL,
  `psar` double(16,8) unsigned NOT NULL,
  `trend` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da táboa `trade_history`
--

DROP TABLE IF EXISTS `trade_history`;
CREATE TABLE IF NOT EXISTS `trade_history` (
`id` int(10) unsigned NOT NULL,
  `market` varchar(10) NOT NULL,
  `globalTradeID` bigint(20) unsigned NOT NULL,
  `tradeID` int(10) unsigned NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(4) NOT NULL,
  `rate` double(16,8) unsigned NOT NULL,
  `amount` double(16,8) unsigned NOT NULL,
  `total` double(16,8) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9594121 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da táboa `user_balances`
--

DROP TABLE IF EXISTS `user_balances`;
CREATE TABLE IF NOT EXISTS `user_balances` (
`id` int(10) unsigned NOT NULL,
  `coin` varchar(10) NOT NULL,
  `avaliable` double(16,8) unsigned NOT NULL,
  `onOrders` double(16,8) unsigned NOT NULL,
  `btcValue` double(16,8) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da táboa `user_open_orders`
--

DROP TABLE IF EXISTS `user_open_orders`;
CREATE TABLE IF NOT EXISTS `user_open_orders` (
`id` int(10) unsigned NOT NULL,
  `market` varchar(10) NOT NULL,
  `orderNumber` int(10) unsigned NOT NULL,
  `type` varchar(4) NOT NULL,
  `rate` double(16,8) unsigned NOT NULL,
  `amount` double(16,8) unsigned NOT NULL,
  `total` double(16,8) unsigned NOT NULL,
  `date` datetime NOT NULL,
  `margin` int(10) unsigned NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'OPEN'
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da táboa `user_trade_history`
--

DROP TABLE IF EXISTS `user_trade_history`;
CREATE TABLE IF NOT EXISTS `user_trade_history` (
`id` int(10) unsigned NOT NULL,
  `market` varchar(10) NOT NULL,
  `globalTradeID` int(10) unsigned NOT NULL,
  `tradeID` int(10) unsigned NOT NULL,
  `date` datetime NOT NULL,
  `rate` double(16,8) unsigned NOT NULL,
  `amount` double(16,8) unsigned NOT NULL,
  `total` double(16,8) unsigned NOT NULL,
  `fee` double(16,8) unsigned NOT NULL,
  `orderNumber` int(10) unsigned NOT NULL,
  `type` varchar(4) NOT NULL,
  `category` varchar(20) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=361 DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chart_data`
--
ALTER TABLE `chart_data`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `market_period_date_unique` (`market`,`date`,`period`);

--
-- Indexes for table `chart_data_psar`
--
ALTER TABLE `chart_data_psar`
 ADD UNIQUE KEY `market_data_period_unique` (`market`,`date`,`period`,`af_min`,`af_max`);

--
-- Indexes for table `trade_history`
--
ALTER TABLE `trade_history`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `globalTradeID` (`globalTradeID`), ADD UNIQUE KEY `market_tradeid_unique` (`market`,`tradeID`);

--
-- Indexes for table `user_balances`
--
ALTER TABLE `user_balances`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_open_orders`
--
ALTER TABLE `user_open_orders`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `markey_orderNumber_date_unique` (`market`,`orderNumber`,`date`);

--
-- Indexes for table `user_trade_history`
--
ALTER TABLE `user_trade_history`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `globalTradeID` (`globalTradeID`), ADD UNIQUE KEY `market_tradeID_unique` (`market`,`tradeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chart_data`
--
ALTER TABLE `chart_data`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=129416;
--
-- AUTO_INCREMENT for table `trade_history`
--
ALTER TABLE `trade_history`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9594121;
--
-- AUTO_INCREMENT for table `user_balances`
--
ALTER TABLE `user_balances`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `user_open_orders`
--
ALTER TABLE `user_open_orders`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `user_trade_history`
--
ALTER TABLE `user_trade_history`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=361;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
