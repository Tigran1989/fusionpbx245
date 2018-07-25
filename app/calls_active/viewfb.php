<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_active_view')) {
        //access granted
}
else {
        echo "access denied";
        exit;
}

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";
	
	if ($_GET["action"] == 'restart' || $_GET["action"] == 'start' || $_GET["action"] == 'stop') {
		$lock_file = '/tmp/fail2ban_restart.lock';

		$of = fopen($lock_file,'w');
		if($of){
			fwrite($of,$_GET["action"]);
		}
		fclose($of);
		
		while (1) {
			if (file_exists($lock_file)) {
				sleep(1);
			} else {
				break;
			}
		}
		echo "Fail2ban  " . $_GET['action']  . "...";
		echo '<script>window.location.href="viewfb.php"</script>';
		exit(0);
	}
	
//set the http values as php variables
	
	$out = exec("ps aux | grep fail2ban-server | grep -v 'grep' | wc -l");
	
	$fail2ban_status = $out ? 'Stop' : 'Start';

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "<td align=\"center\">\n";
	echo "<br />";

	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "	<td align='left'>\n";
	echo "		<span class=\"vexpl\">\n";
	
	 	
	echo "		</span>\n";
	echo "	</td>\n";
	echo "	<td align='right'>\n";
	if (true && strlen($app_uuid) == 0) {
		echo "		<input type='button' class='btn' value='Refresh' onclick=\"openurl('refresh');return false;\">\n";
		echo "		<input type='button' class='btn' value='$fail2ban_status Fail2ban' onclick=\"openurl('$fail2ban_status');return false;\">\n";

		echo "		<input type='button' class='btn' value='Restart Fail2ban' onclick=\"openurl('restart');return false;\">\n";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "	<td align='left' colspan='2'>\n";
	echo "		<span class=\"vexpl\">\n";

	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		//inbound routes
	
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		//outbound routes
	
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		//time conditions
		
	}
	else {
		//dialplan
		if (if_group("superadmin")) {
			echo "			View Fail2ban here\n";
		}
		else {
			echo "			View Fail2ban here\n";

		}
	}
	echo "		</span>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>";

	echo "	<br />";
	echo "	<br />";

	$result_count = 0;
	$fh = fopen("/tmp/fail2ban_now.log", "r");
	while (!feof($fh)) {
		$result_count++;
		$line = fgets($fh);
	   #echo $line;
	    list ($t, $f, $a, $i, $ext) = explode(';', $line);
		$result[] =  array('timestr' => $t, 'filter' => $f, 'action' => $a, 'ip' => $i, 'ext' => $ext);
	}
	fclose($fh);
	#print_r($result);
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('timestr', 'Time', $order_by, $order);
	echo th_order_by('filter', 'Filter', $order_by, $order);
	echo th_order_by('ip', 'Ip', $order_by, $order);
	echo th_order_by('Extension', 'ext', $order_by, $order);

	#echo th_order_by('dialplan_enabled', 'Enabled', $order_by, $order);
	#echo th_order_by('dialplan_description', 'Description', $order_by, $order);
	
	echo "</tr>\n";
	echo '<script language="javascript" type="text/javascript" src="/resources/jquery/jquery-1.7.2.min.js"></script>';
	echo '
	<script>
		function openurl(action) {
			if (action == "restart") {
				window.location.href="viewfb.php?action=restart";
			} else if (action == "Start") {
				window.location.href="viewfb.php?action=start";
			} else if (action == "Stop") {
				window.location.href="viewfb.php?action=stop";
			} else {
				window.location.href="viewfb.php";
			}
		}
	</script>
	';
			
	if ($result_count > 0) {
		foreach($result as $row) {
			
			echo "<tr >\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['timestr']."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['filter']."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['ip']."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['ext']."</td>\n";
			echo "</tr>\n";

			#
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	

	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "<br />\n";
	
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

//unset the variables
	unset ($result_count);
	unset ($result);
	unset ($key);
	unset ($val);
	unset ($c);
?>