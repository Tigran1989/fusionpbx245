<?php


include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
include "app_languages.php";
if (permission_exists('extension_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
$domain_uuid = $_SESSION['domain_uuid'];
$action = $_REQUEST['action'];
if ($action == 'getlivecalls') {
	$sql = "select val from v_dashboard where domain_uuid='$domain_uuid' and itemname='maxlivecalls'";

	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetch(PDO::FETCH_ASSOC);
	$maxlivecalls = $result['val'];
	
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'],
							  $_SESSION['event_socket_password']);
	if ($fp) {
	
		$result = event_socket_request($fp, 'api show calls');
		
		$calls = explode("\n", $result);
		
		foreach ($calls as $c) {
			list ($uuid) = explode(',', $c);
			if (strlen($uuid) != 36) {
				continue;
			}
			
			$domain_uuid = trim(event_socket_request($fp, "api uuid_getvar $uuid domain_uuid"));
			$live_calls[$domain_uuid] += 1;
		}
		
		$calls = $live_calls[$domain_uuid] ?  $live_calls[$domain_uuid] : 0;
		
		$domain_uuid = $_SESSION['domain_uuid'];
$sql = "select itemname,val from v_dashboard where domain_uuid='$domain_uuid' and itemname='maxlivecalls'";

		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_ASSOC);

		$maxcurrent = $result['val'];
		#{"last_month_calls":0,"liverecordings":0,"answered_calls":0,"livevoicemails":0,"livecalls":0,"this_month_calls":0}
		#$data = array('livecalls' => array('value' => $calls, 'min' => 0, 'max' => $maxlivecalls));
		$data = array('livecalls' => $calls, 'maxconcurrent' => $maxcurrent); 
		echo json_encode($data);
	}
} else {
	$sql .= "select * from v_active_calls order by id limit 60";

	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$data = array();
	
	$i = 1;
	foreach ($result as &$row) {
		$k = sprintf("%02d", $i);
		$data['Minute'][$i-1] = $k;
		$data['answered'][] = array($k, (int)$row['counts']);

		
		
		$i++;
	}
	
	#print_r($data);
	echo json_encode($data);
#echo '{"Minute":["01","02","03","04","05","06","07","08","09","10","11","12"],"total":[["01",10],["02",0],["03",0],["04",0],["05",0],["06",0],["07",0]],"answered":[["01",1],["02",5],["03",6],["04",4],["05",20],["06",21],["07",22]],"failed":[["01",0],["02",0],["03",0],["04",0],["05",0],["06",0],["07",0]],"profit":[["01",0],["02",0],["03",0],["04",0],["05",0],["06",0],["07",0]]}';
#echo '{"Minute":["01","02","03","04","05"],"total":[["01",0],["02",0],["03",0],["04",0],["05",0]],"answered":[["01","1"],["02","2"],["03","6"],["04","8"],["05","6"]],"failed":[["01",0],["02",0],["03",0],["04",0],["05",0]],"profit":[["01",0],["02",0],["03",0],["04",0],["05",0]]}';
}