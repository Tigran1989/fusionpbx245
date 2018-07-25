<?php

include "root.php";
require_once "resources/require.php";



//get the html values and set them as variables
	


//show the header
require_once "resources/header.php";


//fs cmd
$action = $argv[1];
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if ($fp) {
	if ($action == 'savelivecalls') {
			#cronly get count of live calls, and save max count

		$result = event_socket_request($fp, 'api show calls');
		//$switch_result = eval($switch_cmd);
		#echo $switch_result;
		#preg_match('/(\d+) total\./', $switch_result, $arr);
		#$live_call_count = $arr[1];
		#echo "live call count: $live_call_count\n";
		$calls = explode("\n", $result);
		
		foreach ($calls as $c) {
			list ($uuid) = explode(',', $c);
			if (strlen($uuid) != 36) {
				continue;
			}
			
			$domain_uuid = trim(event_socket_request($fp, "api uuid_getvar $uuid domain_uuid"));
			$live_calls[$domain_uuid] += 1;
		}
		
		foreach ($live_calls as $k => $v) {
			echo "$k=$v\n";
			
			
			$prep_statement = $db->prepare("update v_dashboard set val='$v' where domain_uuid='$k' AND itemname='maxlivecalls' and val < '$v'");
	    
			$prep_statement->execute();
			
			$sql = "insert into v_dashboard (itemname,val,enabled,domain_uuid) select 'maxlivecalls','$v',true,'$k' WHERE NOT EXISTS (SELECT 1 FROM v_dashboard WHERE domain_uuid='$k' AND itemname='maxlivecalls')";
			
			echo $sql;
			$prep_statement = $db->prepare($sql);
	    
			$prep_statement->execute();
		}
	
	} else {
		$start = date("Y-m-") . "01 00:00:00";
		
		$sql = "select domain_uuid,context,direction,billsec,from_did,hangup_cause_q850 status from v_xml_cdr where start_stamp >= '$start'";
		echo $sql;
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		$all_calls = 0; $inbound_calls = 0; $outbound_calls = 0; $tollfree_calls = 0;
		$inbound_calls_duration = 0; $outbound_calls_duration = 0;
		$avg_outbound_duration = 0; $avg_inbound_duration = 0;
		foreach($result as $call) {
			$duuid = $call['domain_uuid'];
            $data[$duuid]['all']++;
            
            if ($call['status'] != 16) {
                continue;
            }
            
            $data[$duuid]['all_calls']++;
            if ($call['context']) {
                $data[$duuid]['dname'] = $call['context'];
            }
			if ($call['direction'] == 'inbound') {
				$data[$duuid]['inbound_calls']++;
				if (is_tollfree_did($call['from_did'])) {
					$data[$duuid]['tollfree_calls']++;
                    
				}
				$data[$duuid]['inbound_calls_duration'] += $call['billsec']; 
			} else {
				$data[$duuid]['outbound_calls']++;
				$data[$duuid]['outbound_calls_duration']  += $call['billsec'];
			}
		}
        $cmd = "df -hl | grep '/\$' | awk '{print $2}'";
        echo $cmd, "\n";
        $disk_size  = system($cmd);
        echo "disk_size: $disk_size\n";

        $cmd = "df -l | grep '/\$' | awk '{print $2}'";
        echo $cmd, "\n";
        $disk_raw_size  = system($cmd);
        echo "disk_raw_size: $disk_raw_size\n";
        
        
        
		foreach ($data as $domain_uuid => $v) {
			$sql = "delete from v_dashboard where domain_uuid='$domain_uuid' and itemname in ('all_calls','inbound_calls','outbound_calls','inbound_calls_duration','outbound_calls_duration','tollfree_calls','avg_outbound_duration','avg_inbound_duration')";
            echo "\n$sql\n";
            $domain_name = $v['dname'];
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
            $all = $v['all'] ? $v['all'] : 0;
			$all_calls = $v['all_calls'] ? $v['all_calls'] : 0;
			$inbound_calls = $v['inbound_calls'] ? $v['inbound_calls'] : 0;
			$outbound_calls = $v['outbound_calls'] ? $v['outbound_calls'] : 0;
			$tollfree_calls = $v['tollfree_calls'] ? $v['tollfree_calls'] : 0;
			$inbound_calls_duration = $v['inbound_calls_duration'] ? $v['inbound_calls_duration'] : 0;
			$outbound_calls_duration = $v['outbound_calls_duration'] ? $v['outbound_calls_duration'] : 0;
			$avg_outbound_duration = $v['outbound_calls'] >0  ? $v['outbound_calls_duration'] / $v['outbound_calls'] : 0;
			$avg_inbound_duration = $v['inbound_calls'] ? $v['inbound_calls_duration'] / $v['inbound_calls'] : 0;
			echo "$avg_outbound_duration, $avg_inbound_duration\n";
			$avg_outbound_duration  = gmdate('H:i:s',(int)$avg_outbound_duration);
			$avg_inbound_duration  = gmdate('H:i:s',(int)$avg_inbound_duration);
            
            $recording_size = system("du -s /usr/local/freeswitch/recordings/$domain_name | awk '{print \$1}'");
            $recording_storage_percentage = (int)($recording_size * 100 / $disk_raw_size);
            
            $voicemail_size = system("du -s /usr/local/freeswitch/storage/voicemail/default/$domain_name | awk '{print \$1}'");
            $voicemail_storage_percentage = (int)($voicemail_size * 100 / $disk_raw_size);
	
			$voicemail_calls = system("find /usr/local/freeswitch/storage/voicemail/default/$domain_name -name 'msg*.wav'| wc -l");
            $voicemail_calls = trim($voicemail_calls);
            
			$sql = "insert into v_dashboard (itemname,val,enabled,domain_uuid) values ('all','$all',true,'$domain_uuid'),('all_calls','$all_calls',true,'$domain_uuid'),('inbound_calls','$inbound_calls',true,'$domain_uuid'),('outbound_calls','$outbound_calls',true,'$domain_uuid'),('inbound_calls_duration','$inbound_calls_duration',true,'$domain_uuid'),('outbound_calls_duration','$outbound_calls_duration',true,'$domain_uuid'),('tollfree_calls','$tollfree_calls',true,'$domain_uuid'),('avg_outbound_duration','$avg_outbound_duration',true,'$domain_uuid'),('avg_inbound_duration','$avg_inbound_duration',true,'$domain_uuid'),('recording_storage_percentage','$recording_storage_percentage',true,'$domain_uuid'),('voicemail_storage_percentage','$voicemail_storage_percentage',true,'$domain_uuid'),('voicemail_calls','$voicemail_calls',true,'$domain_uuid')";
			
			echo $sql;
			$prep_statement = $db->prepare($sql);
		
			$prep_statement->execute();
			
		}
        
        $prep_statement = $db->prepare("delete from v_dashboard where itemname='disk_size'");
        $prep_statement->execute();
        $prep_statement = $db->prepare("insert into v_dashboard (itemname,val,enabled) values ('disk_size', '$disk_size', true)");
        $prep_statement->execute();

			/*
		
	*/
		
		
	}	

    
    
} else {
	echo "fp is null";
}


function is_tollfree_did($did) {
	$len = strlen($did);
	if ($len != 10 && $len != 11) {
		return false;
	}
	
	if ($len == 10) {
		$prefix = substr($did, 0, 3);
	}
	
	if ($len == 11) {
		$prefix = substr($did, 1, 3);
	}

	if ($prefix == '800' || $prefix == '811' || $prefix == '822' || $prefix == '833' || $prefix == '844' || $prefix == '855' || $prefix == '866' || $prefix == '877' || $prefix == '888' || $prefix == '899') {
		return true;
	}
	
	return false;
}
?>
