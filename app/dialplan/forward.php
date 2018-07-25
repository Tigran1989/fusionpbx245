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
if (permission_exists('dialplan_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

	if ($_GET["action"] == 'updatedest') {
		$forwardstr = $_GET["forwardstr"];
		#$dest= $_GET["dest"];
		foreach(explode(',', $forwardstr)  as $v) {			
			list ($did, $dest,$expire) = explode('-', $v, 3);
			//echo "$did == $dest\n";
			$domain_name = $_SESSION['domain_name'];
			$sql = "select did,dest,expireat from v_global_forward where did='$did'";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				
				if ($row['did']) {
					$prep_statement = $db->prepare("update v_global_forward set dest='$dest',expireat='$expire' where did='$did'");
				} else {
					$prep_statement = $db->prepare("insert into v_global_forward (did,dest,expireat) values ('$did','$dest','$expire')");
	
				}
				
				$prep_statement->execute();
				$file = "/usr/local/freeswitch/conf/dialplan/public2/0000_$did.xml";
	
				if ($dest) {
					if ($expire) {
						$expire_string = "<condition date-time=\"0000-00-00 00:00~$expire\"/>";
					}
					$xml = "
<extension name=\"$did\" >
   <condition field=\"context\" expression=\"public\" />
   <condition field=\"destination_number\" expression=\"^$did$\" >
   $expire_string
       <action application=\"set\" data=\"domain=$domain_name\" />
       <action application=\"set\" data=\"domain_name=$domain_name\" />
       <action application=\"set\" data=\"call_direction=inbound\" />
       <action application=\"transfer\" data=\"$dest XML $domain_name\" />
   </condition>
</extension>
";
				echo $xml;
					
					file_put_contents($file, $xml);
				} else {
					unlink($file);
				}
				
			}
		}

		$_SESSION["reload_xml"] = true;
		echo '<script>window.location.href="forward.php"</script>';
		exit(0);
	}
	
//set the http values as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];
	$dialplan_context = $_GET["dialplan_context"];
	$app_uuid = $_GET["app_uuid"];

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
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		echo "			<strong>Inbound Routes</strong>\n";
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		echo "			<strong>Outbound Routes</strong>\n";
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		echo "			<strong>Time Conditions</strong>\n";
	}
	else {
		echo "			<strong>Global Forward</strong>\n";
	}
	 	
	echo "		</span>\n";
	echo "	</td>\n";
	echo "	<td align='right'>\n";
	if (true && strlen($app_uuid) == 0) {
		echo "		<input type='button' class='btn' value='Save All' onclick=\"openurl('alldid');return false;\">\n";
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
		echo "			Route incoming calls to destinations based on one \n";
		echo "			or more conditions. It can send incoming calls to an IVR Menu, \n";
		echo "			Call Group, Extension, External Number, Script. Order is important when an \n";
		echo "			anti-action is used or when there are multiple conditions that match. \n";
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		//outbound routes
		echo "			Route outbound calls to gateways, tdm, enum and more. \n";
		echo "			When a call matches the conditions the call to outbound routes . \n";
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		//time conditions
		echo "			Time conditions route calls based on time conditions. You can  \n";
		echo "			use time conditions to send calls to an IVR Menu, External numbers, \n";
		echo "			Scripts, or other destinations.  \n";
	}
	else {
		//dialplan
		if (if_group("superadmin")) {
			echo "			It is for setting global forward here\n";
		}
		else {
						echo "			It is for setting global forward here\n";

		}
	}
	echo "		</span>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	</table>";

	echo "	<br />";
	echo "	<br />";

	//get the number of rows in the dialplan
	$sql = "select count(*) as num_rows from v_dialplans ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($app_uuid) == 0) {
		//hide inbound routes
			$sql .= "and app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		//hide outbound routes
			#$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		$sql .= "and app_uuid = '".$app_uuid."' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}
	unset($prep_statement, $result);

	$rows_per_page = 150;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
	$offset = $rows_per_page * $page;

	$sql = "select * from v_dialplans left join v_global_forward on v_dialplans.dialplan_number=v_global_forward.did ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($app_uuid) == 0) {
		//hide inbound routes
			$sql .= "and app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		//hide outbound routes
			#$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		$sql .= "and app_uuid = '".$app_uuid."' ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; } else { $sql .= "order by dialplan_order asc, dialplan_name asc "; }
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('dialplan_name', 'Name', $order_by, $order);
	echo th_order_by('dialplan_number', 'Number', $order_by, $order);
	echo th_order_by('dest', 'Destination', $order_by, $order);
	echo th_order_by('expire_datetime', 'Expire at', $order_by, $order);

	#echo th_order_by('dialplan_enabled', 'Enabled', $order_by, $order);
	#echo th_order_by('dialplan_description', 'Description', $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		if (permission_exists('inbound_route_add')) {
			echo "			<a href='".PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		if (permission_exists('outbound_route_add')) {
			echo "			<a href='".PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		if (permission_exists('time_conditions_add')) {
			echo "			<a href='".PROJECT_PATH."/app/time_conditions/time_condition_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	else {
		if (permission_exists('dialplan_add')) {
			echo "			<a href='dialplan_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";
			echo '<script language="javascript" type="text/javascript" src="/includes/jquery/jquery-1.7.2.min.js"></script>';
			echo '
			<script>
				function openurl(didstring) {					
					if (didstring == "alldid") {
						didstring = $("#alldid").val();
					}
					
					var didarray   = didstring.split(",");
					
					var forwardstr = "";
					
					var i;
					for(var i=0; i<didarray.length; i++){
						var did  = didarray[i];

						var dest = $("#did_" + did).val();
						var expire = $("#did_expire_" + did).val();
						if (forwardstr) {
							forwardstr += ",";							
						}
						
						forwardstr += did + "-" + dest + "-" + expire;
					}
					//window.location.href="forward.php?action=updatedest&did=" + did + "&dest=" + dest;
					window.location.href="forward.php?action=updatedest&forwardstr=" + forwardstr;
				}
			</script>
			';
			
	$alldidstring = '';
	if ($result_count > 0) {
		foreach($result as $row) {
			$app_uuid = $row['app_uuid'];
			
			if ($alldidstring) {
				$alldidstring .= ',';	
			}
			
			$alldidstring .= $row['dialplan_number'];
			
			
			echo "<tr >\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_name']."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_number']."</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>+1 <input id=did_" . $row['dialplan_number'] . "  name=did_" . $row['dialplan_number'] . " value='" . $row['dest']. "'size=10 maxlength=10/> &nbsp;&nbsp;(input the 10 digits)</td>\n";
			echo "   <td valign='top' class='".$row_style[$c]."'>".
				"<input id=did_expire_" . $row['dialplan_number'] . "  name=did_expire_" . $row['dialplan_number'] . "type='text' class='formfld' style='min-width: 115px; width: 115px;' name='start_stamp_begin' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='Expire at' value='" . $row['expireat'] . "'>" ."</td>\n";
			
			#echo "   <td valign='top' class='".$row_style[$c]."'>&nbsp;&nbsp;".$row['dialplan_enabled']."</td>\n";
			#echo "   <td valign='top' class='row_stylebg' width='30%'>".$row['dialplan_description']."&nbsp;</td>\n";
			echo "   <td valign='top' align='right'>\n";

			if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
				if (true) {
					echo "		<a href='#' onclick=\"openurl('" . $row['dialplan_number'] . "'); return false;\" alt='edit'>$v_link_label_edit</a>\n";
				}
				
			}
			elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
				if (permission_exists('outbound_route_edit')) {
					echo "		<a href='dialplan_edit.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('outbound_route_delete')) {
					echo "		<a href='dialplan_delete.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				}
			}
			elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
				if (permission_exists('time_conditions_edit')) {
					echo "		<a href='dialplan_edit.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('time_conditions_delete')) {
					echo "		<a href='dialplan_delete.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				}
			}
			else {
				if (permission_exists('dialplan_edit')) {
					echo "		<a href='dialplan_edit.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('dialplan_delete')) {
					echo "		<a href='dialplan_delete.php?id=".$row['dialplan_uuid']."&app_uuid=$app_uuid' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				}
			}
			echo "   </td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	
	echo "<input type=hidden name=alldid id=alldid value=\"$alldidstring\"/>"; 
	echo "<tr>\n";
	echo "<td colspan='6'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			&nbsp;";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4") {
		if (permission_exists('inbound_route_add')) {
			echo "			<a href='".PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	elseif ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3") {
		if (permission_exists('outbound_route_add')) {
			echo "			<a href='".PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	elseif ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1") {
		if (permission_exists('time_conditions_add')) {
			echo "			<a href='".PROJECT_PATH."/app/time_conditions/time_condition_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}
	else {
		if (permission_exists('dialplan_add')) {
			echo "			<a href='dialplan_add.php' alt='add'>$v_link_label_add</a>\n";
		}
	}

	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "<br />\n";
	if ($v_path_show) {
		echo $_SESSION['switch']['dialplan']['dir'];
	}
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
