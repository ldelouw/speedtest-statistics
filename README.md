# speedtest-statistics
Collects up- and downstream speed with speedtest-cli from http://www.speedtest.net to SQLite DB and draws graphs using PHP and JavaScript. It consists of two parts, a Shell script and a PHP script.

# Purpose
* Just for fun
* Archiving results and contact the provider with historical data in the case the bandwidth provided is lower as guaranteed in the contract

# Screenshot

![screenshot](https://github.com/ldelouw/speedtest-statistics/raw/master/speedtest-stats.png)

## Dependencies

* SQLite 3 -> https://www.sqlite.org
* PHP 7 (older probably work as well)
* speedtest-cli -> https://github.com/sivel/speedtest-cli
* Chartjs -> https://www.chartjs.org

# Installation

The following procedure is for Fedora 29. It may probably work similar with other distributions as well.

## Install some packages needed

```bash
dnf -y install python3-speedtest-cli.noarch php php-pdo php-json
```

If you want to have your public IP shown in the output tell SELinux about it. The -P paramter is needed to make it 
persistent over reboots

```bash
setsebool -P httpd_can_network_connect 1
```

## Customize and install
Customize and install the shellscript speedtest-collector.sh to a handy place of your choice and add a crontab enty. The customization includes the place where the sqlite database file is located (i.e. /var/www/html/speedtest-collector.db

### Servers to query

The following will get you the list of servers, sorted reverse by distance.

```bash
speedtest --list|tac
```
Pick two servers , one should be in the same AS (autonomous system) as your provider, if possible. The other one should be one not too far away.
My output looks as following:

```bash
19035) Vodafone Kabel Deutschland (Berlin, Germany) [3.53 km]
17137) Cronon AG (Berlin, DE) [3.53 km]
10259) Interoute VDC (Berlin, Germany) [3.53 km]
 6417) SysEleven GmbH (Berlin, Germany) [3.53 km]
 1475) IPB GmbH (Berlin, Germany) [1.59 km]
Retrieving speedtest.net configuration...
```

I was picking numbers 19035 (Provider Network) and 1475 (Shortest distance)

Look for the section "Main - Run the tests customize your servers here"

I added the following:

```bash
runTest "19035"
sleep 10

runTest "1475"
```

Put the same values in the function getCSVString


## Create the database

```bash
sqlite3 /var/www/html/speedtest-collector.db
```

Populate the DB with a table

```SQL
CREATE TABLE IF NOT EXISTS "bandwidth" ("serverid" INTEGER NOT NULL , "sponsor" VARCHAR NOT NULL , "servername" VARCHAR NOT NULL , "times" DATETIME PRIMARY KEY NOT NULL UNIQUE , "distance" FLOAT NOT NULL , "ping" FLOAT NOT NULL , "download" FLOAT NOT NULL , "upload" FLOAT NOT NULL );
```

## Create the crontab

```bash
*/30 * * * * /root/speedtest-collector.sh
```

## Install the Chart Javascripts

```bash
mkdir /var/www/html/scripts
wget https://www.chartjs.org/dist/2.7.3/Chart.bundle.js -O /var/www/html/scripts/Chart.bundle.js
restorecon -Rv /var/www/html
```
## Future enhancements

* Add a second graph for the Ping values
* Choosing timeframes

## Feedback
Is welcome
