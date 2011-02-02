<?php
// Load WP necessities
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
	require_once( $wp_root.'wp-load.php' );
}

// Are you a proper ajax call?
if( $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
	if( isset( $_REQUEST['route'] ) ) {
		switch( $_REQUEST['route'] ) {
			case 'allData':
				$etag = md5( print_r( $_REQUEST, true ) );
				$data = array();
				for( $i = 0; $i < 4; $i++ ) {
					$data[] = OACClimateRiskAjax::fetchStats( $_REQUEST['vartype'], null, $i, $_REQUEST['location'] );
				}
				$json = json_encode( $data );
				$seconds = 399600; // Approximately 4 days <-- SHOULD BE AN OACBase configuration option
				header("Cache-Control: private, max-age={$seconds}");
				header("Expires: ".gmdate('r', time()+$seconds));
				echo $json;
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
		$query = "SELECT climatevariable, BIN, climateid, Tab, MM_0, MM_1, MM_2, MM_3, MM_4, MM_5, MM_6, MM_7, MM_8, MM_9, MM_10, MM_11";
		if( ( $tab == 0 ) || ( $tab == 3 ) ) {
			$query .= ", (MM_0+MM_1+MM_2+MM_3+MM_4+MM_5+MM_6+MM_7+MM_8+MM_9+MM_10+MM_11) AS YR";
		}
		$query .= " FROM location_py, `statistic_calculation_py` WHERE ( location_py.`location_id` = `statistic_calculation_py`.`station_ID` ) AND (`statistic_calculation_py`.`climatevariable`= %s)";  
		$args = array( $vartype );
		if( ! is_null( $enso ) ) {
			$query .= " AND (`statistic_calculation_py`.`climateid`=%d)";
			$args[] = $enso;
		}
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
		if( $tab == 0 ) {
			$sort = false;
		}
		$results = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
		foreach( $results as $row ) {
			$enso  = $row['climateid'];
			$rowindex = (string) $row['BIN'];
			unset( $row['climatevariable'] );
			unset( $row['BIN']);
			unset( $row['Tab']);
			unset( $row['climateid']);
			foreach( $row as $item ) {
				if( is_null( $item ) ) {
					$data[$enso][$rowindex][] = __("N/A");
				} else {
					$data[$enso][$rowindex][] = (float) $item;
				}
			}
		}

		if( $sort ) {
			$sortFunc = 'knsort';
			if( $tab == 3 ) {
				$sortFunc = 'knrsort';
			}
			$tempData = null;
			for( $enso = 1; $enso < 5; $enso++ ) {
				if( isset( $data[$enso] )) {
					$data[$enso] = call_user_func( array( 'OACBase', $sortFunc ), $data[$enso]) ;
					if( isset( $data[$enso]['More'] ) ) {
						$tempData = $data[$enso]['More'];
						unset( $data[$enso]['More'] );
						$data[$enso]['More'] = $tempData;
					}
				}
			}
		}
	
		$html = array();
		for( $enso = 1; $enso < 5; $enso++ ) {
			$html[$enso] = '';
			if( isset( $data[$enso] ) ) {
				foreach( $data[$enso] as $rowIndex => $rowData ) {
					$html[$enso] .= "<tr id=\"{$rowIndex}\"><td class=\"index_col\">{$rowIndex}</td>";				
					for( $i = 0; $i < count( $rowData ); $i++ ) {
						$html[$enso] .= '<td class="col_'.$i.'">'.$rowData[$i].'</td>';
					}
					$html[$enso] .= "</tr>";
				}
			}
		}
		
		$data = array('data'=>$data, 'html'=>$html);
		return $data;
	}
}

?>
