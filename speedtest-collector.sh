#!/bin/bash
# 
# Runs the speedtest-cli using https://speedtest.net 
# Usually two servers are queried. Output results in CSV file 
# for import to sqlite db
#
function getCSVString () {
	# if speedtest failes, we need to create a dummy entry
	
	# Get the timestamp string in the same format that speedtest generates - and we need UTC time
	local RIGHTNOW=$(date --utc +%Y-%m-%dT%H:%M:%SZ)

	# Change the server id according to the main section
	if [ $1 = "19035" ] 
	then
		echo "19035, Vodafone Kabel Deutschland,Berlin, Germany,$RIGHTNOW,111.11,0.0,0.0,0.0 "
	fi
	if [ $1 = "1475" ] 
	then
		echo "1475,IPB GmbH, Berlin, Germany,$RIGHTNOW,111.11,0.0,0.0,0.0"
	fi
}
	
function runTest () {
	# Run the Speedtest-CLI against the server listed in the main section. 
	# The CSV output will be saved in a tmpfile
	/usr/bin/speedtest --csv --server $1 > /tmp/speedtest.tmp
	if [ $? -gt 0 ] 
	then
		# Speedtest failed so create a zero entry in place of any error message
		getCSVString $1 > /tmp/speedtest.tmp
	fi

	# save output ready for next server test
	cat /tmp/speedtest.tmp >> /tmp/speedtest.csv
}

## Main - Run the tests customize your servers here ##
# Add your servers here, see https://github.com/ldelouw/speedtest-statistics/blob/master/README.md
# for more information
## 

# Remove the old CSV file
rm -f /tmp/speedtest.csv

runTest "19035"
sleep 10

runTest "1475"

# Add more tests as needed, ensure you customize the PHP script as well when using more than two servers.
#runTest "2643"

# Import to SQLite
/usr/bin/sqlite3 -batch /var/www/html/speedtest-collector.db <<"EOF"
.separator ","
.import /tmp/speedtest.csv bandwidth
EOF
