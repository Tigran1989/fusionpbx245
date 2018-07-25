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

require_once "resources/require.php";

$LOG  = fopen("/tmp/aws.log", "a");
$sql  = "select * from v_default_settings ";
$sql .= "where default_setting_category='AWS' ";
$sql .= "and default_setting_enabled='true' ";


aws_log($sql);
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$aws_base_domain = '';
$aws_key_name    = 'my-aws-account';
$aws_id			 = '';
$aws_key		 = '';
$aws_ip			 = '';

if (count($result) > 0) {
	foreach ($result as &$row) {
		//get the salt from the database
		//default_setting_uuid|default_setting_category|default_setting_subcategory|default_setting_name|default_setting_value|default_setting_enabled|default_setting_
		aws_log($row["default_setting_subcategory"]  . " == " . $row["default_setting_value"]);
		if ($row["default_setting_subcategory"] == "base_domain") {
			$aws_base_domain = $row["default_setting_value"];
		} else if ($row["default_setting_subcategory"] == "id") {
			$aws_id	= $row["default_setting_value"];
		} else if ($row["default_setting_subcategory"] == "key") {
			$aws_key = $row["default_setting_value"];
		} else if ($row["default_setting_subcategory"] == "ip") {
			$aws_ip = $row["default_setting_value"];
		} else {}
	}
		
}

function is_aws_enabled() {
	global $aws_id, $aws_key, $aws_base_domain, $aws_ip;

	return $aws_ip ? 1 : 0;
}

function create_domain($domain) {
	global $aws_id, $aws_key, $aws_base_domain, $aws_ip;

	if (!is_aws_enabled()) {
		return false;
	}
	#$cmd = "route53  --id $aws_id --key $aws_key record create $aws_base_domain. " .
	#	   "--name $domain. --type A --ttl 300  --value $aws_ip ";
	$cmd = "curl -k \"http://api.velantro.net/api/api.pl?action=route53&subaction=CREATE&domain=$domain&ip=$aws_ip\"";
	aws_log ($cmd);
	exec($cmd);							
	
}

function update_domain($uid, $domain) {
	global $db, $aws_id, $aws_key, $aws_base_domain, $aws_ip;

	if (!is_aws_enabled()) {
		return false;
	}

	$sql  = "select * from v_domains ";
	$sql .= "where domain_uuid='$uid'";
	aws_log($sql);
	$prep_statement = $db->prepare(check_sql($sql));
	
	$old_domain    = '';
	$prep_statement->execute();
	
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

	foreach ($result as &$row) {
		$old_domain = $row["domain_name"];
		break;
	}

	if ($old_domain != $domain) {
		delete_domain($old_domain);
		create_domain($domain);
	}
}

function delete_domain($domain) {
	global $aws_id, $aws_key, $aws_base_domain, $aws_ip;
	if (!is_aws_enabled()) {
		return false;
	}

	#$cmd = "route53 --id $aws_id --key $aws_key  record delete $aws_base_domain. " .
	#	   "--name $domain. --type A --ttl 300";
	$cmd = "curl -k \"http://api.velantro.net/api/api.pl?action=route53&subaction=DELETE&domain=$domain&ip=$aws_ip\"";

	aws_log( $cmd);
	exec($cmd);
}

function aws_log($string) {
	global $LOG;
	fwrite($LOG, $string . "\n");
}

?>
