#!/bin/bash

CURRENT=`date +"%F %H:%M:00"`;
OLD=`date --date="10 minutes ago" +"%F %H:%M:00"`
echo $CURRENT;
echo $OLD;

php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.02 0.1 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.02 0.15 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.02 0.2 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.025 0.05 2016-01-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.025 0.075 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.025 0.1 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.025 0.15 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.025 0.2 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.03 0.1 2016-01-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.03 0.15 2016-01-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.03 0.2 2016-03-01
php poloniex_import_chart_data_with_PSAR.php BTC_ETH 60 0.03 0.3 2016-03-01

SQL="SELECT date, trend, low, high, close, af_min, af_max FROM chart_data_psar WHERE market='BTC_ETH' AND period=60 AND date BETWEEN '$OLD' AND '$CURRENT' ORDER BY date DESC;";
echo $SQL | mysql -u ${DATABASE_USER} -p${DATABASE_PASS} -h ${DATABASE_HOST} poloniex

