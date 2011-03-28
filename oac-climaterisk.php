<?php
/*
 * Plugin Name: OAC: Climate Risk Tool
 * Version: 1.0
 * Plugin URI: http://open.agroclimate.org/downloads/
 * Description: Climate Risk Tool - Description TODO
 * Author: The Open AgroClimate Project
 * Author URI: http://open.agroclimate.org/
 * License: BSD Modified
 */

class OACClimateRiskAdmin {
    public function oac_climaterisk_admin_init() {
        $plugin_dir = basename( dirname( __FILE__ ) );
        load_plugin_textdomain( 'oac_climaterisk', null, $plugin_dir . '/languages' );
        if( !current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permission to access this page.', 'oac_climaterisk' ) );
        }
        // Anything else needed to be run (like POST or GET redirection)
        // wp_scoper_admin_action_handler();
    }

    public function oac_climaterisk_admin_menu() {
        add_submenu_page( 'oac_menu', __( 'Climate Risk Tool', 'oac_climaterisk' ), __( 'Climate Risk Tool', 'oac_climaterisk' ), 'manage_options', 'oac_climaterisk_handle', array( 'OACClimateRiskAdmin', 'oac_climaterisk_admin_page' ) );
    }

    public function oac_climaterisk_admin_page() {
    ?>
        <div class="wrap">
        <?php screen_icon( 'tools' ); ?>
        <h2><?php _e( 'Climate Risk Tool Settings', 'oac_climaterisk' ); ?></h2>
        <p>Information</p>
        <p>More information here</p>
        </div>
    <?php
    }


    public function oac_climaterisk_install_harness() {
        OACBase::init();
        wp_scoper_admin_setup_scopes( 'location', __FILE__ );
    }

    public function oac_climaterisk_uninstall_harness() {
        OACBase::init();
        wp_scoper_admin_cleanup_scopes( __FILE__ );
    }
} // class OACClimateRiskAdmin

class OACClimateRisk {
    private static $location_scope = null;
    private static $plugin_url = '';

    public static function initialize() {
        OACBase::init();
	$plugin_dir = basename( dirname( __FILE__ ) );
        load_plugin_textdomain( 'oac_climaterisk', null, $plugin_dir . '/languages' );
        $filter = null;
        $filters = get_option( 'oac_scope_filters', null );
        if( $filters !== null ) $filter = array_key_exists( 'location_climaterisk', $filters ) ? $filters['location_climaterisk'] : null;
        self::$location_scope = new WPScoper( 'location', $filter );
    }


    public static function ui_panel()  {
        $output =  '<div id="climaterisk-ui-container" class="oac-ui-container">';
        $output .= '<div id="oac-user-input-panel" class="oac-user-input">';
        $len_unit = OACBase::get_unit('', 'smalllen' );
        $temp_unit = OACBase::get_unit( '', 'temp');
        // Generate a DDL for variable type
        $ddl = array(
            'RAIN' => __( 'Total Rainfall', 'oac_climaterisk' ).' ('.$len_unit['abbr'].")", 
            'TMIN' => __( 'Average Min. Temp', 'oac_climaterisk' ).' ('.$temp_unit['abbr'].')',
            'TMAX' => __( 'Average Max. Temp', 'oac_climaterisk' ).' ('.$temp_unit['abbr'].')', 
            'NABS' => __( 'Monthly Min. Temp', 'oac_climaterisk' ).' ('.$temp_unit['abbr'].')',
            'XABS' => __( 'Monthly Max. Temp', 'oac_climaterisk' ).' ('.$temp_unit['abbr'].')');
        $output .= '<label for="vartype">'.__( 'Variable Type', 'oac_climaterisk' ).'</label>';
        $output .= '<select name="vartype" id="vartype" class="oac-input oac-select">';
        foreach( $ddl as $option_val => $option_display ) {
            $output .= '<option value="'.$option_val.'">'.$option_display.'</option>';
        }
        $output .= '</select>';
        $output .= self::$location_scope->generateNestedDDL( '', true );
        $output .= OACBase::display_enso_selector();
        $output .= '</div>';
        $output .= '<div id="oac-output-panel" class="oac-output">';
        $output .= self::tabs();
        $output .= '</div></div>';
        return $output;
    }

    public static function month_table_header( $year = false ) {
        return '<thead><tr><th id="vartype-index"></th><th>'.__( 'Jan', 'oac_climaterisk' ).'</th><th>'.__( 'Feb', 'oac_climaterisk' ).'</th><th>'.__( 'Mar', 'oac_climaterisk' ).'</th><th>'.__( 'Apr', 'oac_climaterisk' ).'</th><th>'.__( 'May', 'oac_climaterisk' ).'</th><th>'.__( 'Jun', 'oac_climaterisk' ).'</th><th>'.__( 'Jul', 'oac_climaterisk' ).'</th><th>'.__( 'Aug', 'oac_climaterisk' ).'</th><th>'.__( 'Sep', 'oac_climaterisk' ).'</th><th>'.__( 'Oct').'</th><th>'.__( 'Nov', 'oac_climaterisk' ).'</th><th>'.__( 'Dec', 'oac_climaterisk' ).'</th>'.(( $year ) ? '<th>'.__( 'Year', 'oac_climaterisk' ).'</th>' : '').'</tr></thead>';
    }
    
    public static function tabs() {
        $output = <<<ENDTABS
        <div>
            <ul id="tabs">
ENDTABS;
        $output .= '<li class="selected">'.__( 'Average &amp; Deviation', 'oac_climaterisk' ).'</li>';
        $output .= '<li>'.__( 'Probability Distribution', 'oac_climaterisk' ).'</li>';
        $output .= '<li>'.__( 'Probability of Exceedance', 'oac_climaterisk' ).'</li>';
        $output .= '<li>'.__( 'Last 5 Years', 'oac_climaterisk' ).'</li>';
        $output .= <<<ENDTABS
            </ul>
            <div class="tabcontent selected" id="tabs-1" style="font-size: .6em;">
                <table id="avg-deviation-table" class="oac-table">
ENDTABS;
        $output .= self::month_table_header( true );
        $output .= '<tbody></tbody>';
        $output .= <<<ENDTABS
                </table>
                <div id="avg-deviation-chart" class="oac-chart"></div>
            </div>
            <div class="tabcontent" id="tabs-2" style="font-size: .6em;">
                <table id="prob-dist-table" class="oac-table">
ENDTABS;
        $output .= self::month_table_header();
        $output .= '<tbody></tbody>';
        $output .= <<<ENDTABS
                </table>
                <div id="prob-dist-chart" class="oac-chart"></div>
            </div>
            <div class="tabcontent" id="tabs-3" style="font-size: .6em;">
                <table id="prob-exceed-table" class="oac-table">
ENDTABS;
        $output .= self::month_table_header();
        $output .= '<tbody></tbody>';
        $output .= <<<ENDTABS
                </table>
                <div id="prob-exceed-chart" class="oac-chart"></div>
            </div>
            <div class="tabcontent" id="tabs-4" style="font-size: .6em;">
                <table id="five-year-table" class="oac-table">
ENDTABS;
        $output .= self::month_table_header( true );
        $output .= '<tbody></tbody>';
        $output .= <<<ENDTABS
                </table>
                <div id="five-year-chart" class="oac-chart"></div>
            </div>
        </div>
ENDTABS;
        return $output;
    }

    public static function output() {
        $output = self::ui_panel();
        return $output;
    }

    public static function hijack_header() {
        global $post;
        global $is_IE;
        $regex = get_shortcode_regex();
        preg_match('/'.$regex.'/s', $post->post_content, $matches);
        if ((isset( $matches[2])) && ($matches[2] == 'oac_climaterisk')) {
            wp_enqueue_style( 'oacclimaterisk', plugins_url( 'css/oac-climaterisk.css', __FILE__ ), array( 'oacbase' ) );
            wp_register_script( 'oac_climaterisk', plugins_url( 'js/oac-climaterisk.js', __FILE__ ),
                array( 'oac-base', 'mootools-array-math', 'mootools-table-colsel', 'oac-barchart', 'oac-linechart' )
            );
            wp_enqueue_script( 'oac_climaterisk' );
            add_action( 'wp_head', array( 'OACBase', 'ie_conditionals' ), 3 );
        }
    }
}

// WordPress Hooks and Actions
register_activation_hook( __FILE__, array( 'OACClimateRiskAdmin', 'oac_climaterisk_install_harness' ) );
register_deactivation_hook( __FILE__, array( 'OACClimateRiskAdmin', 'oac_climaterisk_uninstall_harness' ) );
if( is_admin() ) {
    add_action( 'admin_menu', array( 'OACClimateRiskAdmin', 'oac_climaterisk_admin_menu' ) );
    add_action( 'admin_init', array( 'OACClimateRiskAdmin', 'oac_climaterisk_admin_init' ) );
} else {
    // Add front-end specific actions/hooks here
    add_action( 'init', array( 'OACClimateRisk', 'initialize' ) );
    add_action( 'template_redirect', array( 'OACClimateRisk', 'hijack_header' ) );
    add_shortcode('oac_climaterisk', array( 'OACClimateRisk', 'output' ) );
}
// Add all non-specific actions/hooks here
//
//
?>
