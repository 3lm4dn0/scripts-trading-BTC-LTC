# Scripts to trade in Poloniex or Bittrex
---

This repo is a set of several PHP scripts to trade cryptocurrencies

## Supported crypto exchanges:

- [Poloniex API](https://poloniex.com/support/api/)
- [Bittrex API](https://bittrex.com/Home/Api)
- [Btcchina (deprecated API)](https://www.btcc.com/apidocs)

## Content

- bittrex/APIBittrex.php: API Class to access all API from Bittrex, public and private account
- poloniex/APIPoloniex.php: API Class to access all API from Poloniex, public and private account
- poloniex/APICrytoWatch.php: API Class to access all data from datacharts
- btcchina.php: Script to trade BTC/CNY on BTCchina web exchange

## Configuration

1. Set API environment variables

You need to create enviroment vars with the API key and API secret
```
~$ export [POL | BTT]_KEY="your API key"
~$ export [POL | BTT]_PASS="your API secret"
``` 

2. Create `poloniex` database (Optional)

PSAR indicator and "import" scripts requires a MySQL database called `poloniex`. 

- Import poloniex.sql script to create database and the required tables:
```
~$ mysql -h your_host -u your_user -p < poloniex/poloniex.sql
```

- Set database access environment vars
```
~$ export DATABASE_HOST="localhost"
~$ export DATABASE_USER="your_username"
~$ export DATABASE_PASS="your_password"
```

## Poloniex public samples

```
# Get all currency pairs
~$ php poloniex_get_all_currencypairs.php

# Get information about a pair
~$ php poloniex_get_ticker.php BTC_ETH

# Find first 10 ask orders with more than 1000 ether in Poloniex
~$ php poloniex_find_big_orders_book.php BTC_ETH SELL 1000 10
```

## Poloniex private account samples

```
# Get all your balances
~$ php poloniex_get_my_balances.php 

# Place an order to BUY ether with all your BTCs in your account at the price 0.03141592 in Poloniex
~$ php poloniex_post_order.php BTC_ETH BUY all 0.03141592 

# Cancel all orders, SELL or BUY
~$ php poloniex_cancel_order.php BTC_XMR all
```

## Autotrader samples

- Autotrade calculating trend price using PSAR indicator in ETH/BTC pair 
with all your available amounts checked each 300 seconds
with PSAR values between 0.02 and 0.2
with the buy threshold at 0.001 and the sell threshold at 0.002

```
~$ poloniex_autotrader.php BTC_ETH all 300 0.02 0.2 0.001 0.002
```

- Autotrade in ETH/BTC pair with 1 ether 
at 0.0005 different from last trade to buy and
at 0.001 different from last trade to sell

```
~$ poloniex_autotrader_schedule.php BTC_ETH 1 0.0005 0.001

```

## Requirements

- PHP
- MySQL for Poloniex
 
## License

The source code for the web project is licensed under the GPL version 3 or later
(see LICENSE).
