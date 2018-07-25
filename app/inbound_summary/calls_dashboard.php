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
include "app_languages.php";
if (permission_exists('call_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

$conference_name = trim($_REQUEST["c"]);
$tmp_conference_name = str_replace("_", " ", $conference_name);


require_once "resources/header.php";
$document['title'] = $text['title'];
$colors = array('Aqua','Aquamarine','Bisque', 'Blue', 'BlueViolet', 'Brown', 'Cyan','DarkBlue','DarkRed','DeepPink', 'Gold','Lime', 'Pink', 'Red','Green', 'Yellow', 'SeaGreen', 'Purple','Navy', 'Maroon');
$sql = "select destination_number from v_destinations where domain_uuid='$domain_uuid' and destination_enabled='true'";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
        if ($series) {
        	$series .= ",\n";
        }
        
    	$name = $row['destination_number'];
    	$color= $colors[$i++];
    	
    	$series .= 
"		{
		      name: '$name',
		      type: 'spline',
		      color:'$color',
		      yAxis:1,
		      data: response_data['$name'],
		      marker: {
			  	enabled: true
		      },
		      dashStyle: 'shortdot'
		  }";
    }
}
?>
<script type="text/javascript" src="/resources/javascript/highcharts.js"></script>
<script type="text/javascript" src="/resources/javascript/exporting.js"></script>
<script type="text/javascript" src="/resources/javascript/highcharts-3d.js"></script>

<script type="text/javascript">

function build_summary_graph(){ 

	$.ajax({
	    type:'POST',
	    url: "calls_dashboard_data2.php",
	    dataType: 'JSON',
	    cache    : false,
	    async    : false,   
	    success: function(response_data) {
		    $('#call_graph_data').highcharts({
			  chart: {
			      zoomType: 'xy'
			  },
			  title: {
			      text: 'ABC'
			  },
			  xAxis: [{
			      categories:response_data['Minute']
			  }],
			  yAxis: [{
				  min: 0,
				  title: {
				      text: 'Total Inbound Calls'
				  }
			      }, {
				  min: 0,
				  opposite: true, //optional, you can have it on the same side.
				  title: {
				      text: 'Inbound Calls Per Hour'
				  }
			      }],
			  tooltip: {
					  backgroundColor: '#FEFEC5',
					  borderColor: 'black',
					  borderRadius: 10,
					  borderWidth: 2,
					  formatter: function() {
					      return this.series.name+': <b>'+this.y+'</b>';
					  }
			  },
			  legend: {
		
		
			      layout: 'horizontal',
			      align: 'center',
			      x: 0,
			      verticalAlign: 'top',
			      y: 0,
			      backgroundColor: '#EFEFEF'
			  },
			  series: [
					     
						<?php echo $series; ?>
			  		]
		});
	  }
	    });
	}
            
	$(document).ready(function() {
		build_summary_graph();
	});
</script>



<?php


echo "<div align='center'>";

echo "<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
echo "	<tr>\n";
echo "	<td align='left'><b>Inbound Calls Summary</b><br>\n";
echo "		Analyse  Inbound calls for today by DIDS \n";
echo "	</td>\n";
echo "	<td align='right'>\n";

echo "		<table>\n";
echo "		<td align='left' valign='middle'>\n";
echo "			<div id=\"form_label\">\n";
echo "			<div id=\"url\"></div>\n";
echo "		</td>\n";
echo "		<td align='left' valign='middle'>\n";
echo "			<div id=\"form_label\"></div><input type=\"text\" class=\"formfld\" style=\"width: 100%;\" id=\"form_value\" name=\"form_value\" />\n";
echo "		</td>\n";
echo "		</tr>\n";
echo "		</table>\n";

echo "	</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "	<tr class='border'>\n";
echo "	<td align=\"left\">\n";
echo "		<div id=\"ajax_reponse\"></div>\n";
echo "		<div id=\"time_stamp\" style=\"visibility:hidden\">".date('Y-m-d-s')."</div>\n";
echo "	</td>";
echo "	</tr>";
echo "</table>";


echo "</div>\n";
echo " <div id='call_graph_data' class='call_graph_data'></div>";




echo "<script type=\"text/javascript\">\n";
echo "<!--\n";
echo "function get_transfer_cmd(uuid) {\n";
echo "	destination = document.getElementById('form_value').value;\n";
echo "	cmd = \"uuid_transfer \"+uuid+\" -bleg \"+destination+\" xml ".trim($_SESSION['user_context'])."\";\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "\n";
echo "function get_park_cmd(uuid) {\n";
echo "	cmd = \"uuid_transfer \"+uuid+\" -bleg *6000 xml ".trim($_SESSION['user_context'])."\";\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "\n";
echo "function get_record_cmd(uuid, prefix, name) {\n";
echo "	cmd = \"uuid_record \"+uuid+\" start ".$_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/\"+uuid+\".wav\";\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "-->\n";
echo "</script>\n";

require_once "resources/footer.php";
?>
