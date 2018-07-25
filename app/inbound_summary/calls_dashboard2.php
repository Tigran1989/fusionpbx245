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

$domain_uuid = $_SESSION['domain_uuid'];
$sql = "select itemname,val from v_dashboard where domain_uuid='$domain_uuid'";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
        $variables[$row['itemname']] = $row['val'];
    }   
     
    extract($variables);   
}

$sql = "select gateway_uuid,domain_uuid,gateway from v_gateways";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 1;
    foreach($result as $row) {
        $gateways[$row['gateway_uuid']]['gateway'] = $row['gateway'];
		if ($gateway_list_json) {
			$gateway_list_json .= ',';
		}
		$gateway_list_json .= "{data : gateway$i" . "_data, label : '" . $row['gateway']. "'}"; #json_encode(array('data' => "gateway$i" . "_data", 'label' => $row['gateway']));
		$i++;
    }
		
}
$start = date("Y-m-d", time()-30*24*3600) . " 00:00:00";
for($i=0; $i < 31; $i++) {
	if ($day_list) {
		$day_list .= ',';
	}
	
	$day_list .= "'" . date("m/d/Y", time()-$i*24*3600) . "'";
}

$sql = "SELECT substr(last_arg,15,36) as gwuuid, date(start_stamp) as day, count(*) calls FROM v_xml_cdr WHERE start_stamp >= '$start' " .
		"and last_arg LIKE 'sofia/gateway/%'  and domain_uuid='$domain_uuid' group by gwuuid,day";
		

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
        $gateways[$row['gwuuid']]['data'][$row['day']] = $row['calls'];
    }
}


$sql = "SELECT destination_number did FROM v_destinations WHERE domain_uuid='$domain_uuid'";
		

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
		$did = $row['did'];
		if ($did_list_json) {
			$did_list_json .= ',';
		}
        $did_list_json .= "{data : did$did" . "_data, label : '$did'}";
		$did_list[] = $did;
    }
}

#$gateway_list_json .= "{data : gateway$i" . "_data, label : '" . $row['gateway']. "'}"; #json_encode(array('data' => "gateway$i" . "_data", 'label' => $row['gateway']));

$sql = "SELECT from_did, date(start_stamp) as day, count(*) as  calls FROM v_xml_cdr WHERE start_stamp >= '$start' " .
		"and direction LIKE 'inbound' and domain_uuid='$domain_uuid' group by from_did,day";
		

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
        $dids[$row['from_did']]['data'][$row['day']] = $row['calls'];
    }
}


$sql = "SELECT extension FROM v_extensions WHERE domain_uuid='$domain_uuid'";
		

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
		$extension = $row['extension'];
		if ($extension_list_str) {
			$extension_list_str .= ',';
		}
		$extension_list_str .= "'$extension'";
    }
	
}

#$gateway_list_json .= "{data : gateway$i" . "_data, label : '" . $row['gateway']. "'}"; #json_encode(array('data' => "gateway$i" . "_data", 'label' => $row['gateway']));

$sql = "SELECT destination_number as extension, date(start_stamp) as day, count(*) as  calls FROM v_xml_cdr WHERE start_stamp >= '$start' " .
		"and direction = 'inbound' and domain_uuid='$domain_uuid' and destination_number IN ($extension_list_str) group by extension,day";
		
#echo $sql, "\n";

$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

$result_count = count($result);
if ($result_count > 0) {
	$i = 0;
    foreach($result as $row) {
        $extensions[$row['extension']]['data'][$row['day']] = $row['calls'];
    }
}

    $sql = "SELECT caller_id_number as extension, date(start_stamp) as day, count(*) as  calls FROM v_xml_cdr WHERE start_stamp >= '$start' " .
    "and direction = 'outbound' and domain_uuid='$domain_uuid' and caller_id_number IN ($extension_list_str) group by extension,day";
    
    
    $prep_statement = $db->prepare(check_sql($sql));
    $prep_statement->execute();
    $result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    
    $result_count = count($result);
    if ($result_count > 0) {
        $i = 0;
        foreach($result as $row) {
            $extensions_outbound[$row['extension']]['data'][$row['day']] = $row['calls'];
        }
    }
    
#print_r($gateways);
?>
<link href="/resources/css/font-awesome.min.css" rel="stylesheet" />
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<link href="/resources/css/style.min.css" rel="stylesheet" />
<link href="/resources/css/style-fix.css" rel="stylesheet" />

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<script type="text/javascript" src="/resources/javascript/highcharts.js"></script>
<script type="text/javascript" src="/resources/javascript/exporting.js"></script>
<script type="text/javascript" src="/resources/javascript/highcharts-3d.js"></script>
<script src="/resources/javascript/excanvas.js"></script>
<script src="/resources/javascript/jquery.flot.js"></script>

<script src="/resources/javascript/jquery.flot.resize.min.js"></script>

<script src="/resources/javascript/jquery.easy-pie-chart.min.js"></script>	
<script src="/resources/javascript/jquery.flot.pie.js"></script>
<script src="/resources/javascript/jquery.knob.modified.js"></script>	
<script src="/resources/javascript/jquery.sparkline.min.js"></script>	
<script src="/resources/javascript/raphael.2.1.0.min.js"></script>
<script src="/resources/javascript/justgage.1.0.1.min.js"></script>
<script type="text/javascript">
	
 $(window).load(function(){
  /* ---------- Sparkline Charts ---------- */
	//generate random number for charts
	randNum = function(){
		//return Math.floor(Math.random()*101);
		return (Math.floor( Math.random()* (1+40-20) ) ) + 20;
	}

	var chartColours = ['#2FABE9', '#FA5833', '#b9e672', '#bbdce3', '#9a3b1b', '#5a8022', '#2c7282'];

	//sparklines (making loop with random data for all 7 sparkline)
	i=1;
	for (i=1; i<9; i++) {
	 	var data = [[1, 3+randNum()], [2, 5+randNum()], [3, 8+randNum()], [4, 11+randNum()],[5, 14+randNum()],[6, 17+randNum()],[7, 20+randNum()], [8, 15+randNum()], [9, 18+randNum()], [10, 22+randNum()]];
	 	placeholder = '.sparkLineStats' + i;

			$(placeholder).sparkline(data, {
				width: 100,//Width of the chart - Defaults to 'auto' - May be any valid css width - 1.5em, 20px, etc (using a number without a unit specifier won't do what you want) - This option does nothing for bar and tristate chars (see barWidth)
				height: 30,//Height of the chart - Defaults to 'auto' (line height of the containing tag)
				lineColor: '#2FABE9',//Used by line and discrete charts to specify the colour of the line drawn as a CSS values string
				fillColor: '#f2f7f9',//Specify the colour used to fill the area under the graph as a CSS value. Set to false to disable fill
				spotColor: '#467e8c',//The CSS colour of the final value marker. Set to false or an empty string to hide it
				maxSpotColor: '#b9e672',//The CSS colour of the marker displayed for the maximum value. Set to false or an empty string to hide it
				minSpotColor: '#FA5833',//The CSS colour of the marker displayed for the mimum value. Set to false or an empty string to hide it
				spotRadius: 2,//Radius of all spot markers, In pixels (default: 1.5) - Integer
				lineWidth: 1//In pixels (default: 1) - Integer
			});

	}

	/* ---------- Pie chart ---------- */
	var data = [
	{ label: "Internet Explorer",  data: 12},
	{ label: "Mobile",  data: 27},
	{ label: "Safari",  data: 85},
	{ label: "Opera",  data: 64},
	{ label: "Firefox",  data: 90},
	{ label: "Chrome",  data: 112}
	];
	
	if($("#piechart").length)
	{
		$.plot($("#piechart"), data,
		{
			series: {
					pie: {
							show: true
					}
			},
			grid: {
					hoverable: true,
					clickable: true
			},
			legend: {
				show: false
			},
			colors: ["#FA5833", "#2FABE9", "#FABB3D", "#78CD51"]
		});
		
		function pieHover(event, pos, obj)
		{
			if (!obj)
					return;
			percent = parseFloat(obj.series.percent).toFixed(2);
			$("#hover").html('<span style="font-weight: bold; color: '+obj.series.color+'">'+obj.series.label+' ('+percent+'%)</span>');
		}
		$("#piechart").bind("plothover", pieHover);
	}
  
 });
</script>
<script type="text/javascript">       


	$(function() {
		
			
			var call_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var call_data=[];
		
			var vistor_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var vistor_data=[];
			
			var anlogcall_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var anlogcall_data=[];
			
			var localcall_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var localcall_data=[];
			
			var tollfeecall_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var tollfeecall_data=[];
			var last_monthdays=[<?php echo $day_list;?>];
			var xticks=[];
		
			//widget datas end
			for (var i = 0; i < 30; i+=5) {
				xticks.push([i,last_monthdays[i]]);
			}
		
			//widget datas
			<?php
			$i = 1;
			foreach($gateways as $gwuuid => $data) {
				$data_list = array();
				for($j=0; $j < 31; $j++) {
					$day = date("Y-m-d", time()-$j*24*3600);
					if ($data['data'][$day]) {
						$data_list[] = $data['data'][$day];
					} else {
						$data_list[] = 0;
					}
				}
			?>
			var gateway<?php echo $i ?>_rawdata=[<?php echo join(',', $data_list); ?>];
			var gateway<?php echo $i ?>_data=[];
			<?php $i++;}?>
			
			<?php
			foreach($did_list as $did) {
			?>
			var did<?php echo $did ?>_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			var did<?php echo $did ?>_data=[];
			<?php } ?>
			
			<?php
			foreach($dids as $did => $data) {
				$data_list = array();
				for($j=0; $j < 31; $j++) {
					$day = date("Y-m-d", time()-$j*24*3600);
					if ($data['data'][$day]) {
						$data_list[] = $data['data'][$day];
					} else {
						$data_list[] = 0;
					}
				}
			?>
			var did<?php echo $did ?>_rawdata=[<?php echo join(',', $data_list); ?>];
			var did<?php echo $did ?>_data=[];
			<?php } ?>
			
			
			<?php
			foreach($extensions as $extension => $data) {
				if ($extension_list_json) {
					$extension_list_json .= ',';
				}
				$extension_list_json .= "{data : extension$extension" . "_data, label : '$extension'}";
				$data_list = array();
				for($j=0; $j < 31; $j++) {
					$day = date("Y-m-d", time()-$j*24*3600);
					if ($data['data'][$day]) {
						$data_list[] = $data['data'][$day];
					} else {
						$data_list[] = 0;
					}
				}
			?>
			var extension<?php echo $extension ?>_rawdata=[<?php echo join(',', $data_list); ?>];
			var extension<?php echo $extension ?>_data=[];
			<?php } ?>
      
      <?php
        foreach($extensions_outbound as $extension => $data) {
        if ($extension_outbound_list_json) {
            $extension_outbound_list_json .= ',';
        }
        $extension_outbound_list_json .= "{data : extension$extension" . "_outbound_data, label : '$extension'}";
        $data_outbound_list = array();
        for($j=0; $j < 31; $j++) {
            $day = date("Y-m-d", time()-$j*24*3600);
            if ($data['data'][$day]) {
                $data_outbound_list[] = $data['data'][$day];
            } else {
                $data_outbound_list[] = 0;
            }
        }
      ?>
      var extension<?php echo $extension ?>_outbound_rawdata=[<?php echo join(',', $data_outbound_list); ?>];
      var extension<?php echo $extension ?>_outbound_data=[];
      <?php } ?>
			
			//var widget1440_rawdata=[10,0,0,0,0,0,0,0,0,0,0,0,0,0,30,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,20];
			//	var widget1440_data=[10,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,20,0,0,0,0,0,0,0,0,0,0,0,20];
							var widget36_rawdata=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
				var widget60_data=[];
					
			
			for (var i = 0; i < 31; i++) {
				vistor_data.push([i,vistor_rawdata[i]]);
				call_data.push([i,call_rawdata[i]]);
				anlogcall_data.push([i,anlogcall_rawdata[i]]);
				localcall_data.push([i,localcall_rawdata[i]]);
				tollfeecall_data.push([i,tollfeecall_rawdata[i]]);
				<?php
				$i = 1;
				foreach($gateways as $gwuuid => $data) { ?>
				gateway<?php echo $i ?>_data.push([i, gateway<?php echo $i ?>_rawdata[i]]);
					
				<?php $i++;}?>
				
				<?php
				foreach($did_list as $did) { ?>
				did<?php echo $did ?>_data.push([i, did<?php echo $did ?>_rawdata[i]]);
					
				<?php }?>
				
				<?php
				foreach($extensions as $extension => $data) { ?>
				extension<?php echo $extension ?>_data.push([i, extension<?php echo $extension ?>_rawdata[i]]);
					
				<?php }?>
                <?php
                    foreach($extensions_outbound as $extension => $data) { ?>
                        extension<?php echo $extension ?>_outbound_data.push([i, extension<?php echo $extension ?>_outbound_rawdata[i]]);
      
                <?php }?>
								//widget1440_data.push([i, widget1440_rawdata[i]]);
								
			}
			
			
		
			/*var z = $.plot($("#widget_calldata"), [
				<?php //echo $gateway_list_json; ?>

				], {
					
					series : {
						lines : {
							show : true,
							lineWidth : 2,
							fill : false,
							fillColor : {
								colors : [ {
									opacity : 0.5
								}, {
									opacity : 0.2
								} ]
							}
						},
						points : {
							show : true,
							lineWidth : 2
						},
						shadowSize : 0
					},
					legend : {
						show : true,
						noColumns:3
					},
					grid : {
						hoverable : true,
						clickable : true,
						tickColor : "#f9f9f9",
						borderWidth : 0
					},
					colors : [ "#3B5998" , "#1BB2E9" ],
					xaxis : {
						ticks : xticks,
						tickDecimals : 0
					},
					yaxis : {
						ticks : 3,
						tickDecimals : 0
					}
				}
			);*/                          	
			
		
			var z = $.plot("#visitor_vs_call", [
			<?php echo $did_list_json ?>
			], {
				series : {
					lines : {
						show : true,
						lineWidth : 2,
						fill : true,
						fillColor : {
							colors : [ {
								opacity : 0.5
							}, {
								opacity : 0.2
							} ]
						}
					},
					points : {
						show : true,
						lineWidth : 2
					},
					shadowSize : 0
				},
				grid : {
					hoverable : true,
					clickable : true,
					tickColor : "#f9f9f9",
					borderWidth : 0
				},
				colors : [ "#3B5998" , "#1BB2E9" ],
				xaxis : {
					ticks : xticks,
					tickDecimals : 0
				},
				yaxis : {
					ticks : 3,
					tickDecimals : 0
				}
			});
		
			function showTooltip(x, y, contents) {
				$("<div id='tooltip'>" + contents + "</div>").css({
					position: "absolute",
					display: "none",
					top: y + 5,
					left: x + 5,
					border: "1px solid #fdd",
					padding: "2px",
					"background-color": "#fee",
					opacity: 0.80
				}).appendTo("body").fadeIn(200);
			}
		
		
			/* $("#visitor_vs_call").bind("plotclick", function (event, pos, item) {
				if (item) {
					$("#clickdata").text(" - click point " + item.dataIndex + " in " + item.series.label);
					plot.highlight(item.series, item.datapoint);
				}
			});
		 */
			
		
			var plot = $.plot("#voipcall_anlogcall", [
				<?php echo $extension_list_json; ?> 
				], {
				series: {
					lines: {
						show: true
					},
					points: {
						show: true
					}
				},
				colors:[ "#78cd51","#FA5833", "#2FABE9" ],
				grid: {
					hoverable: true,
					clickable: true,
					borderWidth : 0,
					tickColor : "#f9f9f9",
				},
				yaxis: {
					
				},
				xaxis: {
					ticks: xticks
				}
			});	
		
      var plot = $.plot("#voipcall_anlogcall2", [
                        <?php echo $extension_outbound_list_json; ?>
                        ], {
                        series: {
                        lines: {
                        show: true
                        },
                        points: {
                        show: true
                        }
                        },
                        colors:[ "#78cd51","#FA5833", "#2FABE9" ],
                        grid: {
                        hoverable: true,
                        clickable: true,
                        borderWidth : 0,
                        tickColor : "#f9f9f9",
                        },
                        yaxis: {
                        
                        },
                        xaxis: {
                        ticks: xticks
                        }
                        });
			var previousPoint = null;
			var previousPointIndex=null;
			$("#visitor_vs_call,#widget_calldata,#voipcall_anlogcall, #voipcall_anlogcall2").bind("plothover", function (event, pos, item) {
		
				if (item) {
					if (previousPoint != item.series.label || previousPointIndex!=item.dataIndex) {
						previousPoint = item.series.label;
						previousPointIndex=item.dataIndex;
						$("#tooltip").remove();
						var x = item.datapoint[0].toFixed(2),
						y = item.datapoint[1].toFixed(2);
						showTooltip(item.pageX, item.pageY,
						item.series.label + " of " +last_monthdays[parseInt(x)]  + " = " + parseInt(y));
					}
				} else {
					$("#tooltip").remove();
					previousPoint = null;
				}
			});
		
				if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
					$.browser.device = (/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase()));
				}
		
			var width=$('#live-dashboard-graph').width();
		
			
		
			window.vconnect_dashboard_live_calls_jg = new JustGage({
		         id: "live_calls_jg",
		         value: 0,
		         min: 0,
		         max: 100,
		         title: "LIVE CALLS",
		         titleFontColor:"#383e4b",
		         label: "Calls Now"
		     });
		
		
			window.vconnect_dashboard_max_concurrent_jg= new JustGage({
		        id: "max_concurrent_jg",
		        value: 0,
		        min: 0,
		        max: 100,
		        title: "MAX CONCURRENT",
		        titleFontColor:"#383e4b",
		        label: "average"
		    });
      
            window.vconnect_dashboard_max_voicemail_jg= new JustGage({
                                                                id: "max_voicemail_jg",
                                                                value: 1,
                                                                min: 0,
                                                                max: 20,
                                                                title: "VOICEMAIL STORAGE (GB)",
                                                                titleFontColor:"#383e4b",
                                                                label: "vm now"
                                                                }); 
		
            window.vconnect_dashboard_max_recording_jg= new JustGage({
                                                               id: "max_recording_jg",
                                                               value:10,
                                                               min: 0,
                                                               max: 20,
                                                               title: "RECORDING STORAGE (GB)",
                                                               titleFontColor:"#383e4b",
                                                               label: "rec now"
                                                               });
			$('#live_calls_jg , #max_concurrent_jg, #max_voicemail_jg, #max_recording_jg').find('text').each(function(){
				var text=$(this).text();
				if(text=='LIVE CALLS'||text=='MAX CONCURRENT'){
					$(this).css('font','bold .8em Arial');
				}
			});
		
		    
		    if(window.vconnect_dashboard_live_refresh==undefined){
		    	window.vconnect_dashboard_live_refresh=setInterval(function() {
		    		if($("#dashboard_hidden_live_refresh_point").length>0){
		    			var $live_last_month_calls=$("#live_last_month_calls");
		    		    var $live_voicemails=$("#live_voicemails");
		    		    var $live_ansered_calls=$("#live_ansered_calls");
		    			$.post("calls_dashboard_data.php?action=getlivecalls",{},function(data){
		    				
		    				window.vconnect_dashboard_live_calls_jg.refresh(data.livecalls);
                               window.vconnect_dashboard_max_concurrent_jg.refresh(data.maxconcurrent);
                              // window.vconnect_dashboard_max_voicemail_jg.refresh(data.voicemail);
                              // window.vconnect_dashboard_max_recording_jg.refresh(data.recording);

		    				//$live_last_month_calls.val(data.last_month_calls).trigger('change');
		    				//$live_voicemails.val(data.livevoicemails).trigger('change');
		    				//$live_ansered_calls.val(data.answered_calls).trigger('change');
		    			},"json");
		    		}
		         },
		         15000);
		    }
			
		    if(!$.browser.device){
			    var width=$('#live-dashboard-graph').width();
			    var allowedWidth=120;
			    if(width*14.8936/100<120){
			    	allowedWidth=width*14.8936/100;
			    }
			}else{
				var allowedWidth=120;
			}
		
		
			 $('.percentage-light').easyPieChart({
			        barColor: function(percent) {
			            percent /= 100;
			            //return "rgb(" + Math.round(255 * (1-percent)) + ", " + Math.round(255 * percent) + ", 0)";
			            return "#14EB00";
			        },
			        trackColor: '#fafafa',
			        scaleColor: false,
			        lineCap: 'butt',
			        rotate: -90,
			        lineWidth: 15,
			        animate: 1000,
			       // size:allowedWidth,
			        onStep: function(value) {
			            this.$el.find('span').text(~~value);
			        }
			    });
		
			/*  $(".circleStatsItem").each(function(){
				 alert("ddd");
						var $div=$(this).find("div");
						var $input=$div.find("input");
						var $newinput=$("<span></span>");
						$newinput.attr("style",$input.attr("style"));
		
						$div.append($newinput);
		
				 });
			 */
		
			 $(".livecircleChart").each(function() {
			        var b = $(this).parent().css("color");
			        var width=$('#live-dashboard-graph').width();
			        var allowedWidth=120;
			        if(width*14.8936/100<120){
			        	allowedWidth=width*14.8936/100;
			        }
			        if(!$.browser.device){
			        	allowedWidth=120;
			        }
			        if(!$.browser.device){
				        $(this).data('width',allowedWidth);
				        $(this).parent().css('border-radius',allowedWidth/2);
				        $(this).parent().css('width',allowedWidth+'px');
				        $(this).parent().css('height',allowedWidth+'px');
			    	}else{
			    		allowedWidth=120;
			    		$(this).data('width',120);
			    		$(this).parent().css('border-radius',allowedWidth/2);
			    	}
			        $(this).knob({
			            min: 0,
			            max: 1000,
			            readOnly: true,
			            width: allowedWidth,
			            height: allowedWidth,
			            fgColor: b,
			            dynamicDraw: false,
			            thickness: 0.2,
			            tickColorizeValues: true,
			            skin: "tron"
			        });
		//	        var canvas=$(this).siblings('canvas');
		//	        var ctx=$(this).siblings('canvas').get(0).getContext('2d');
		//	        ctx.font="1em Arial";
		//	        ctx.fillText("0",10,10);
			    });
						
			 /* ---------- Pie chart ---------- */
				var data = [
				{ data :0,label : 'Ajay'},{ data :0,label : 'nathan'},{ data :0,label : 'darryl'},{ data :0,label : 'jon'},{ data :0,label : 'Velantro'},{ data :0,label : 'colocationamerica'},{ data :0,label : 'MyUser'}				];

				function labelFormatter(label, series) {
					return "<div style='font-size:6pt; text-align:center; padding:1px; color:white;'>" + label + Math.round(series.percent) + "%</div>";
				}
				
				
				$("#sms_total_sent_count").text("0");
		
			
			 
		
		});
/* $(window).resize(function(){
    if($(window).width() < 979){
      $('#page #main_content .span10').css('max-width','600px');
	  $('#page #main_content .span10').css('width','99%');
	}
   if($(window).width() < 1199){
      $('#page #main_content .span10').css('max-width','780px');
	  $('#page #main_content .span10').css('width','99%');
	}
	if($(window).width() > 1199){
      $('#page #main_content .span10').css('max-width','970px');
	  $('#page #main_content .span10').css('width','99%');
	}
}); */
		
</script>



<?php


//echo "<div align='center'>";




echo "</div>\n";
//echo " <div id='call_graph_data' class='call_graph_data'></div>";

?>

					<div id="content" class="col-xs-12">
			
				<div id="pageContent" style="">
	<!-- -------------------------------------------- content start ---------------------------------------------- -->			
					
		<div class="row-fluid alpha">
					<div class="box span12">
							<div class="box-header">
								<h2><i class="icon-tasks"></i>LIVE DASHBOARD</h2>
							</div>
							<div class="box-content">
								
								<div id="live-dashboard-graph" class="circleStats">
									<div class="span2 offset1">	
										<div style="display: inline-block;position:relative;">
										
									
											
											<div id="live_calls_jg" style="width: 10em;height:8em"></div>
																						
										</div>
										
									</div>
									<div class="span2">	
										<div style="display: inline-block;position:relative;">
		
											<div id="max_concurrent_jg" style="width: 10em;height:8em"></div>
																						
										</div>
										
									</div>

                                    
<div class="span2" style="">
	<div style="display: inline-block;position:relative;">

		<div id="max_recording_jg" style="width: 10em;height:8em"></div>
	
	</div>

</div>

								<div class="span2" style="">
                                        <div style="display: inline-block;position:relative;">

                                            <div id="max_voicemail_jg" style="width: 10em;height:8em"></div>
                                           
                                        </div>

                                    </div>

<!--
								<div id="voicemail-div" style="position:relative;" class="span2">


										<h3 class="live_db_title">VOICEMAIL</h3>
				                    	<div class="circleStatsItem orange">
				                    				                        	
											<i class="icon-thumbs-up"></i>
											<span class="plus"></span>
											<span class="percent"></span>
				                        	<input type="text" value="10" class="livecircleChart" id="live_voicemails"/>
				                    	</div>
				                    	<label >Last Month 0</label>
				                    	 <label  >This Month 0</label>
									</div> 
									-->
									<!-- <div class="span2" >
										<h3 class="live_db_title" style="min-width: 160px;margin-left: -15px;">RECORDING STORAGE</h3>
										 <div class="percentage-light" data-percent="0" style="margin: 0px auto;">
										 
										 	<span>10</span>%</div>
										 <label>20GB</label>
						            </div> -->
						            <div class="span2" style="display: none;">
										<h3 class="live_db_title" style="min-width: 160px;margin-left: -15px;">VOICEMAIL STORAGE</h3>
										 
										 
                                        <div style="display: inline-block;position:relative;">

                                            <div id="max_voicemail_jg" style="width: 10em;height:8em"></div>
                                            	
                                        </div>

                                   
										 	
										<!--  <label>20GB</label> -->
						            </div>
									<div style="position:relative;display: none;" class="span2" style=''>
								
										<h3 class="live_db_title">CALLS</h3>
				                    	<div class="circleStatsItem blue">
				                    		<i class="icon-bar-chart"></i>
											<span class="plus"></span>
											<span class="percent"></span>
				                        	<input type="text" value="129" class="livecircleChart" id="live_ansered_calls"/>
				                    	</div>
				                    	<label >Last Month 0</label>
				                    	<label >This Month 0</label>
				                    	  
									</div>
									<div class="span2" style="display: none">
										<h3 class="live_db_title">SMS STATS</h3>
										<div id="donutchart"  style="height: 180px;width:180px;margin: 0 auto;top:-20px;"></div>
										<label style="position:relative;top: -25px;">
										Total Sent:<span id="sms_total_sent_count">0</span><br>
										SMS Balance:0										
										</label>
									</div>
		
				                </div>
								<div class="clearfix"></div>
							</div>	
						</div><!--/span-->
				</div>
		<div class="row-fluid">
			<div class="box span4">
				<div class="box-header">
					<h2>
						<i class="icon-align-justify"></i><span class="break">Monthly
							Overview</span>
					</h2>
				</div>
				<div class="box-content">
					<div class="sparkLineStats">
						<ul class="unstyled">
							<li>
								<div class="sparkLineStats3"></div>
								All Calls:: 
								<span class="number"><?php echo $all_calls; ?></span>
							</li>
							<li>
								<div class="sparkLineStats4"></div>
								Outbound Calls: 
								<span class="number"><?php echo $outbound_calls; ?></span>
							</li>
							<li>
								<div class="sparkLineStats5"></div>
								Inbound Calls:
								<span class="number"><?php echo $inbound_calls; ?></span>
							</li>
							<li>
								<div class="sparkLineStats6"></div>
								Toll Free: <span class="number"><?php echo $tollfree_calls; ?></span>
							</li>
							<li>
								<div class="sparkLineStats7"></div>
								Avg Outbound Call Duration: 
								<span class="number"><?php echo $avg_outbound_duration; ?></span>
							</li>
							<li>
								<div class="sparkLineStats8"></div>
								Avg Inbound Call Duration: 
								<span class="number"><?php echo $avg_inbound_duration; ?></span>
							</li>
							<li>
								<div class="sparkLineStats7"></div>
								Total Outbound Call Duration: 
								<span class="number"><?php echo $total_outbound_duration; ?></span>
							</li>
							<li>
								<div class="sparkLineStats7"></div>
								Total Inbound Call Duration: 
								<span class="number"><?php echo $total_inbound_duration; ?></span>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<!--/span-->
			<div class="box span8">
				<div class="box-header">
					<h2>
						<i class="icon-align-justify"></i><span class="break">Monthly Outbound Call Statistics Per Gateway</span>
					</h2>
				</div>
				<div style="position:relative;" class="box-content">
						
						<div style="height: 300px; padding: 0px; position: relative;" id="piechart">
							<span style="position: absolute; top: 5.5px; left: 228px;" id="pieLabel0" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(250,88,51);">Internet Explorer<br>3%</div></span>
							<span style="position: absolute; top: 15.5px; left: 289.5px;" id="pieLabel1" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(47,171,233);">Mobile<br>7%</div></span>
							<span style="position: absolute; top: 100.5px; left: 365.5px;" id="pieLabel2" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(250,187,61);">Safari<br>22%</div></span>
							<span style="position: absolute; top: 237.5px; left: 316px;" id="pieLabel3" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(120,205,81);">Opera<br>16%</div></span>
							<span style="position: absolute; top: 238.5px; left: 164.5px;" id="pieLabel4" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(200,70,40);">Firefox<br>23%</div></span>
							<span style="position: absolute; top: 53.5px; left: 135.5px;" id="pieLabel5" class="pieLabel"><div style="font-size:x-small;text-align:center;padding:2px;color:rgb(37,136,186);">Chrome<br>29%</div></span>
						</div>
				</div>
			</div>
			<!--/span-->
		</div>
		
		<div class="row-fluid">
			<div class="box span12">
				<div class="box-header">
					<h2>
						<i class="icon-align-justify"></i><span class="break">Inbound Calls Per DID</span>
					</h2>
				</div>
				<div class="box-content" style="position:relative;">
					<div id="visitor_vs_call" class="center" style="height: 300px;"></div>
					
				</div>
			</div>
			<!--/span-->
		</div>
		
		<div class="row-fluid">
			<div class="box span12">
				<div class="box-header">
					<h2>
						<i class="icon-align-justify"></i><span class="break">Inbound Calls Per Extension</span>
					</h2>
				</div>
				<div class="box-content" style="position:relative;">
										<div id="voipcall_anlogcall" class="center" style="height: 300px;"></div>
								<!-- <p id="hoverdata">
						Mouse position at (<span id="x">0</span>, <span id="y">0</span>). <span
							id="clickdata"></span>
					</p>
		  -->
				</div>
			</div>
			<!--/span-->
		</div>

<div class="row-fluid">
<div class="box span12">
<div class="box-header">
<h2>
<i class="icon-align-justify"></i><span class="break">Outbound Calls Per Extension</span>
</h2>
</div>
<div class="box-content" style="position:relative;">
<div id="voipcall_anlogcall2" class="center" style="height: 300px;"></div>
<!-- 			<p id="hoverdata">
Mouse position at (<span id="x">0</span>, <span id="y">0</span>). <span
id="clickdata"></span>
</p>
-->
</div>
</div>
<!--/span-->
</div>


		<input id="dashboard_hidden_live_refresh_point" type="hidden">

	<!-- ------------------------------------------- content end ------------------------------------------ -->
				</div>	
			</div>				

			
		
<?php

require_once "resources/footer.php";
?>
