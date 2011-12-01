<?php
// Load WP necessities
$wp_root = explode('wp-content', __FILE__);
$wp_root = $wp_root[0];


if( file_exists( $wp_root.'wp-load.php' ) ) {
  require_once( $wp_root.'wp-load.php' );
}

// Are you a proper ajax call?
//if( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH']) ) { die( "<img src=\"./kp.jpg\"><p>Bad kitty. No ponies for you!</p>" ); }
//if( $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
  if( isset( $_REQUEST['route'] ) ) {
    switch( $_REQUEST['route'] ) {
      case 'getData':
        $json = json_encode( OACClimateRiskAjax::query( $_REQUEST['loc'] ) );
        $seconds = 120; //399600; // Approximately 4 days <-- SHOULD BE AN OACBase configuration option
        header("Cache-Control: private, max-age={$seconds}");
        header("Expires: ".gmdate('r', time()+$seconds));
        echo $json;
      break;
      case 'getLabels':
        $json = json_encode( OACClimateRiskAjax::build_labels() );
        echo $json;
      break;
      default:
        echo '{}';
      break;
    }
  } else {
    echo '{}';
  }
//} else {
//  die( "<img src=\"./kp.jpg\"><p>Bad kitty. No ponies for you!</p>" );
//}

class OACClimateRiskAjax {
  private static function gen_html_row( $arr ) {
    $line = '';
    for( $i=0; $i < count($arr); $i++ ){
      $line .= '<td class="col-'.$i.' selectable">'.$arr[$i].'</td>';
    }
    return $line;
  }

  public static function build_labels() {
    $len_units = OACBase::get_unit( '', 'smalllen' );
    $temp_units = OACBase::get_unit( '', 'temp' );
    $vartypes = array('RAIN' => array(), 'TMAX' => array(), 'TMIN' => array(), 'NABS' => array(), 'XABS'=>array());
    foreach( $vartypes as $vartype => $_trash ) {
      for( $tab=0; $tab < 4; $tab++ ) {
        $xlabel = $ylabel = $yunits = '';
        if( ( $tab == 0 ) || ( $tab == 3 ) ) {
          $xlabel = __( 'Month', 'oac_climaterisk' );
          if( $vartype == 'RAIN' ) {
            $yunits = $len_units['abbr'];
            $ylabel = __( 'Rainfall', 'oac_climaterisk' ).' ('.$yunits.')';
          } else {
            $yunits = "°".substr( $temp_units['abbr'], -1);
            $ylabel = __( 'Temperature', 'oac_climaterisk' ).'  ('.$yunits.')';
          }
        } else {
          $yunits = '%';
          $ylabel = __( 'Probability', 'oac_climaterisk' ).' (%)';
          if( $vartype == 'RAIN' ) {
            $xlabel = __( 'Rainfall', 'oac_climaterisk' ).' ('.$len_units['abbr'].')';
          } else {
            $xunits = "°".substr( $temp_units['abbr'], -1);
            $xlabel = __( 'Temperature', 'oac_climaterisk' ).'  ('.$xunits.')';
          }
        }
        //echo $vartype;
        $vartypes[$vartype][] = array('x' => $xlabel, 'y' => $ylabel, 'yunit' => $yunits);
      }
    }
    return $vartypes;
  }

  public static function query( $loc ) {
    global $wpdb;
    $to_return = array( 'data' => array(), 'html' => array() );
    $query = 'SELECT * FROM statistic_calculation_py, location_py WHERE (location_py.location_id = statistic_calculation_py.station_ID) AND (location_py.oac_scope_location_id = %s) ORDER BY climatevariable, Tab, climateid, ID;';
    $results = $wpdb->get_results( $wpdb->prepare( $query, $loc ), ARRAY_A );
    foreach( $results as $row ) {
      $vartype = $row['climatevariable'];
      $tab = $row['Tab'];
      $phase = $row['climateid'];
      $bin = $row['BIN'];
      $sort = $row['ID'];
      $data = array();
      for( $i=-1; ++$i < 12; ) {
        $data[] = (float) $row['MM_'.$i];
      }
      // If tab{1|4} we need to generate a total if the vartype{RAIN}
      if( !array_key_exists( $vartype, $to_return['data'] ) ){
        $to_return['data'][$vartype] = array(array(array(), array(), array(), array()), array(array(), array(), array(), array()), array(array(), array(), array(), array()), array(array(), array(), array(), array()));
        $to_return['html'][$vartype] = array(array(), array(), array(), array());
      }

      if( $tab == '0' || $tab == '3' ) {
        $to_return['data'][$vartype][$tab][($phase-1)][] = $data;
      } else {
        for($i=-1;++$i < 12;) {
          if( !array_key_exists( $i, $to_return['data'][$vartype][$tab][($phase-1)] ) )
            $to_return['data'][$vartype][$tab][($phase-1)][$i] = array();
          $to_return['data'][$vartype][$tab][($phase-1)][$i][] = $data[$i];
        }
      }
      // HTML Table Generation
      if( !array_key_exists( ($phase-1), $to_return['html'][$vartype][$tab] ) ) {
        $to_return['html'][$vartype][$tab][($phase-1)] = '';
      }
      $to_return['html'][$vartype][$tab][($phase-1)] .= '<tr><td class="label" style="#f3f3f1">'.$bin.'</td>'.self::gen_html_row( $data ).(( $vartype == 'RAIN' && ( $tab == 0 || $tab == 3 ) ) ? '<td class="total"><strong>'.($data[0]+$data[1]+$data[2]+$data[3]+$data[4]+$data[5]+$data[6]+$data[7]+$data[8]+$data[9]+$data[10]+$data[11]).'</strong></td>' : '').'</tr>';
    }
    return $to_return;
  }
}

?>
