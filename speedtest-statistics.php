<!DOCTYPE HTML>
<html>
<head> 
<style>
body {
       margin: 0;
       padding: 0
     }
#myChart {
     position: top;
     width: 100%;
     height: 100%; 
   }
</style> 
<meta charset="utf-8" /> 
<title>Bandwidth statistics - Up- and downstream speeds in last 24 hours</title>
<script src="scripts/Chart.bundle.js"></script>
</head>
<body>
<h3>bandwidth statistics - last 24 hours </h3>
<?php



// Get out public IP address, JFYI
$ip = file_get_contents('https://api.ipify.org');

// If no server ID has been set, use a default one, change if needed
if (!isset($_GET['serverid']))
{
	$serverid="19035";
	}
else {
	$serverid=$_GET['serverid'];
}

print "Displaying statistics for Server ID ".$serverid. "\n<br>";

if (!empty($ip)) {
	print "Your public IPv4 address is ". $ip." ";
	$objects=json_decode(file_get_contents("https://api.iptoasn.com/v1/as/ip/91.65.158.140"));

	$as[1] = $objects->{'as_number'};
	$as[3] = $objects->{'as_description'};
	
	# Disabled as the API has limited use.
	# $as=str_getcsv(file_get_contents("https://api.hackertarget.com/aslookup/?q=$ip"));
	print "<br>";
	print "Autonomous System is <a href=\"https://bgp.he.net/AS".$as[1]."\">"."AS ".$as[1]." ".$as[3]."</a>";
}
else {
	print "IP lookup not possible, check SELinux and firewall settings";
}

?>

<canvas id="myChart"></canvas>
<script>
	var bandwidth_data = <?php
	class MyDB extends SQLite3 {
		function __construct() {
			$this->open('speedtest-collector.db');
		}
	}
	$db = new MyDB();
	if(!$db) {
		echo $db->lastErrorMsg();
	} else {
		echo "";
	}
	
	// Sanitize user input
	settype($serverid, 'integer');

	$tz=exec('date +"%Z"',$tz);

	// Limit the query to the last 48 sample as we collect data every 30 minutes from two servers.
	$sql = 'SELECT serverid,  strftime("%H:%M-'.$tz.'", times, "localtime") || " " || strftime("%Y-%m-%d", times) AS timestamp, sponsor, servername, download, upload  from (select * FROM bandwidth WHERE serverid='.$serverid.' ORDER BY times DESC LIMIT 48) ORDER BY times ASC;';

	// Execute and return error if unsuccessful
	$ret = $db->query($sql);
	if(!$ret){
		echo $db->lastErrorMsg();
	} else {
		while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
			$results[] = $row;
		}
	$data = json_encode($results);
	}
	echo $data;
	?>
	;
	// values we need the the chart
	var bwlabels = [], bwdata_down = [], bwdata_up = [];
	var mbps_down, mpbs_up, bvalue_down, bvalue_up;
	for (var i = 0; i < bandwidth_data.length ; i++){
		bwlabels.push(bandwidth_data[i].timestamp);
		// Convert from bps to mbps
		mbps_down = Math.round(bandwidth_data[i].download/1000).toFixed(3)/1000;
		mbps_up = Math.round(bandwidth_data[i].upload/1000).toFixed(3)/1000;
		bvalue_down = mbps_down.toLocaleString(undefined, { minimumFractionDigits: 3 });
		bvalue_up = mbps_up.toLocaleString(undefined, { minimumFractionDigits: 3 });
		bwdata_down.push(bvalue_down);
		bwdata_up.push(bvalue_up);
	}
	var bar_color = 'rgba(0, 128, 255, 0.9)';
	var ctx = document.getElementById("myChart").getContext('2d');
	var myChart = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: bwlabels,
			datasets: [
			{
				label: 'Mbps downstream',
				backgroundColor: "#3e95cd",
				data: bwdata_down
			}, {
				label: 'Mbps upstream',
				backgroundColor: "#8e5ea2",
				data: bwdata_up
			}

		]
		},
		options: {
			responsive: false,
			scales: {
				xAxes: [{
					ticks: {
						autoSkip: false,
						maxTicksLimit: 48
					}
				}],
				yAxes: [{
					ticks: {
						beginAtZero:true
					}
				}]
			}
		}
	});
</script>

<?php
	// Sanitize user input
	settype($serverid, 'integer');

	// Build the query to get a list of servers stored in the database. Note that the database should be 
	// cleaned up from time to time to not get too bloated
        $sql2 = 'SELECT DISTINCT serverid, sponsor, servername FROM bandwidth';
	$ret = $db->query($sql2);
        if(!$ret){
                echo $db->lastErrorMsg();
        } else {
                while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
			$results_serverid[] = $row;
		}
	}

	$db->close();

	// Create a form and iterate trough the server list to print the select input type
	print "<form target=\"_self\" method=\"get\" name=\"serverlist\">";
	print "<select name=\"serverid\" onchange=\"this.form.submit()\">";
	for ($i = 0; $i < count($results_serverid); $i++) {
		print "\n";
		print '<option name="serverid" value="';
		print $results_serverid[$i]['serverid'];
		if ($results_serverid[$i]['serverid']==$serverid)
		{
			print "\" selected".">";
		}
		else 
		{
			print "\">";
		}
		print $results_serverid[$i]['sponsor']." ".$results_serverid[$i]['servername']."</option>\n";
	}

?>
</select>
<input type="submit">
</form>
<font size="2">speedtest-statistics is &copy; 2019 by Luc de Louw <a href="mailto: luc@delouw.ch">luc@delouw.ch</a>. See <a href="https://github.com/ldelouw/speedtest-statistics">https://github.com/ldelouw/speedtest-statistics</a> for more information</font>
</body>
</html>
