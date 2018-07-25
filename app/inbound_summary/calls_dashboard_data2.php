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

/*
$m = new Memcached();
$m->addServer('localhost', 11211);

$ac = $m->get('activecalls');
if ($m->getResultCode() == Memcached::RES_NOTFOUND) {
   $ac = array();
   $m->set('activecalls', $ac);
}

if (count($ac) > 20) {
	array_shift($ac);
}

$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {
	//show the error message
	$msg = "<div align='center'>Connection to Event Socket failed.<br /></div>";
	echo "<div align='center'>\n";
	echo "<table width='40%'>\n";
	echo "<tr>\n";
	echo "  <th align='left'>Message</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "  <td class='row_style1'><strong>$msg</strong></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";
} else {
	//show the command result
	$result = trim(event_socket_request($fp, 'api show calls count'));
	preg_match('/^(\d+) total\.$/', $result, $d);
	array_push($ac, $d[1]);
	
	$m->set('activecalls', $ac);
}
*/

$day = $_REQUEST['day'];
if (!$day) {
	$day = date("Y-m-d");
}
  
$sql = "select from_did,start_stamp,billsec from v_xml_cdr where hangup_cause_q850=16 and from_did !='' and start_stamp >= '$day 00:00:00' and domain_uuid='$domain_uuid' order by start_stamp";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
    foreach($result as $row) {
        preg_match('/(\d\d\d\d)\-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/', $row['start_stamp'], $arr);
        $h = intval($arr[4]);
        $inbound[$row['from_did']][$h] += 1;
        #echo $h;
    }
}
unset ($sql, $prep_statement, $result, $row_count);

#print_r($inbound);
for($i=1; $i < 24; $i++) {
	$k = sprintf("%02d", $i);
	$data['Minute'][$i-1] = $k;
	foreach ($inbound as $did => $val) {
		#echo $did;
		$data[$did][] = array($k, (int)$inbound[$did][$i]);
	}
	
}

echo json_encode($data);
