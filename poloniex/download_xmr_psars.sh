#!/bin/bash

CURRENT=`date +"%F %H:%M:00"`;
OLD=`date --date="24 hours ago" +"%F %H:%M:00"`
echo $CURRENT;
echo $OLD;

php poloniex_import_chart_data_with_PSAR.php BTC_XMR 300 0.02 0.2 2016-03-01

SQL="SELECT date, trend, low, high, close, af_min, af_max FROM chart_data_psar WHERE market='BTC_XMR' AND period=300 AND date BETWEEN '$OLD' AND '$CURRENT' ORDER BY date DESC;";
echo $SQL | mysql -u ${DATABASE_USER} -p${DATABASE_PASS} -h ${DATABASE_HOST} poloniex

