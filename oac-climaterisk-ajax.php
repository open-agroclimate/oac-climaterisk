<?php
// Load WP necessities
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}


/* Broken caching code 
$etag = md5( $_REQUEST['route'].$_REQUEST['vartype'].$_REQUEST['enso'].$_REQUEST['location'] );
header( 'ETag: "'.$etag.'"' );
if( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) {
	error_log( $etag );
	error_log( stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));
	if( $etag == str_replace('"', '', stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) ){
		header( 'HTTP 1.0 304 Not Modified' );
		die();
	}
}
*/
// Are you a proper ajax call?
if( $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
	if( isset( $_REQUEST['route'] ) ) {
		switch( $_REQUEST['route'] ) {
			case 'allData':
				$etag = md5( print_r( $_REQUEST, true ) );
				$data = array();
				for( $i = 0; $i < 4; $i++ ) {
					$data[] = array('data'=> OACClimateRiskAjax::fetchStats( $_REQUEST['vartype'], $_REQUEST['enso'], $i, $_REQUEST['location'] ) );
				}
				echo json_encode( array( 'allData' => $data ) );
				break;
			default:
				if( ! isset( $_REQUEST['option'] ) ) {
					$_REQUEST['option'] = null;
				}
				echo json_encode( array( 'data'=>OACClimateRiskAjax::fetchStats( $_REQUEST['vartype'], $_REQUEST['enso'], $_REQUEST['tab'], $_REQUEST['location'], $_REQUEST['option'] ) ) );
				break;
		}
	}
} else {
	die( "<img src=\"./kp.jpg\"><p>Bad kitty. No ponies for you!</p>" );
}

class OACClimateRiskAjax {
	public static function fetchStats( $vartype, $enso, $tab, $location, $option = null ) {
		global $wpdb;
		$data = array();
		if( $tab == 3 ) $enso = 4;
		$query ="SELECT MM_0, MM_1, MM_2, MM_3, MM_4, MM_5, MM_6, MM_7, MM_8, MM_9, MM_10, MM_11, climatevariable, BIN, Tab FROM location_py, `statistic_calculation_py` WHERE ( location_py.`location_id` = `statistic_calculation_py`.`station_ID` ) AND (`statistic_calculation_py`.`climatevariable`= %s) AND (`statistic_calculation_py`.`climateid`=%d)";  
		$args = array( $vartype, $enso );
		$query .= " AND (`statistic_calculation_py`.`Tab`=%d)"; 
		$args[] = $tab;
		if( $tab === 0  ) {
			if( ! is_null( $option ) ) {
				$query .= " AND (`statistic_calculation_py`.`BIN`=%s)";
				$args[] = $option;

			}
		}
		$query .= " AND (`location_py`.`oac_scope_location_id` = %s)";
		$args[] = $location;
		$sort = true;	
		switch( $tab ) {
			case 0:
				$sort = false;
			case 3:
				/*if( is_null( $option ) ) {
					$query .= "ORDER BY BIN DESC LIMIT 1";
				}*/
				$results = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
				foreach( $results as $row ) {
					$index = $row['BIN'];
					unset( $row['climatevariable'] );
					unset( $row['BIN']);
					unset( $row['Tab']);
					foreach( $row as $item ) {
						$data[$index][] = (float) $item;
					}
				}
				break;
			default:
				if( is_null( $option ) ) {
					$option = ((int) date('n') ) - 1;
				}
				$results = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
				foreach( $results as $row ) {
					$index = $row['BIN'];
					unset( $row['climatevariable'] );
					unset( $row['BIN'] );
					unset( $row['Tab'] );
					for( $i = 0; $i < 12; $i++ ) {
						$data[$index][] = (float) $row['MM_'.$i];
					}
				}
		}
		if( $sort ) {
			$tempData = null;
			ksort( $data, SORT_NUMERIC );
			if( isset( $data['More'] ) ) {
				$tempData = $data['More'];
				unset( $data['More'] );
				$data['More'] = $tempData;
			}
		}
		return $data;
	}
}

?>
