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
			wp_die( __( 'You do not have sufficient permission to access this page.' ) );
		}
		// Anything else needed to be run (like POST or GET redirection)
		// wp_scoper_admin_action_handler();
	}

	public function oac_climaterisk_admin_menu() {
		add_submenu_page( 'oac_menu', 'Climate Risk Tool', 'Climate Risk Tool', 'manage_options', 'oac_climaterisk_handle', array( 'OACClimateRiskAdmin', 'oac_climaterisk_admin_page' ) );
	}

	public function oac_climaterisk_admin_page() {
	?>
		<div class="wrap">
		<?php screen_icon( 'tools' ); ?>
		<h2><?php _e( 'Configure Climate Risk Tool', 'oac_climaterisk' ); ?></h2>
		<p>Information</p>
		<p>More information here</p>
		</div>
	<?php
	}


	public function oac_climaterisk_install_harness() {
		OACBase::oac_base_init();
		wp_scoper_admin_setup_scopes( 'location', __FILE__ );
	}

	public function oac_climaterisk_uninstall_harness() {
		OACBase::oac_base_init();
		wp_scoper_admin_cleanup_scopes( __FILE__ );
	}
} // class OACClimateRiskAdmin

class OACClimateRisk {
	private static $location_scope = null;
	private static $in_use = false;
	private static $plugin_url = '';

	public static function initialize() {
		OACBase::oac_base_init();
		self::$location_scope = new WPScoper( 'location' );
		self::$plugin_url = plugins_url( 'oac-climaterisk-js-handler.php', __FILE__ );
		wp_enqueue_script( 'wp-scoper' );
		wp_enqueue_style ( 'jquery-ui' );
	}


	public static function ui_panel()  {
		$output =  '<div id="oac-ui-container">';
		$output .= '<div id="oac-user-input-panel" class="oac-user-input">';
		// Generate a DDL for variable type
		$ddl = array(
			'RAIN' => __( 'Total Rainfall (in.)' ), 
			'TMIN' => __( 'Average Min. Temp (&#176;F)' ),
			'TMAX' => __( 'Average Max. Temp (&#176;F)' ), 
			'NABS' => __( 'Monthly Min. Temp (&#176;F)' ),
			'XABS' => __( 'Monthly Max. Temp (&#176;F)' ));
		$output .= '<label for="vartype">Variable Type</label>';
		$output .= '<select name="vartype" id="vartype">';
		foreach( $ddl as $option_val => $option_display ) {
			$output .= '<option value="'.$option_val.'">'.$option_display.'</option>';
		}
		$output .= '</select>';
		$output .= self::$location_scope->generateNestedDDL( '', true );
		$output .= '</div>';
		$output .= '<div id="oac-output-panel" class="oac-output">';
		$output .= self::tabs();
		$output .= '</div>';
		return $output;
	}

	public static function tabs() {
		$output = <<<ENDTABS
		<div id="tabs">
			<ul>
				<li><a href="#tabs-1">Tab 1</a></li>
				<li><a href="#tabs-2">Tab 2</a></li>
				<li><a href="#tabs-3">Tab 3</a></li>
				<li><a href="#tabs-4">Tab 4</a></li>
				<li><a href="#tabs-5">Tab 5</a></li>
			</ul>
			<div id="tabs-1">
				<p class="current-var"><p>
				<p class="current-loc"></p>
				<p>Tab 1 content</p>
			</div>
			<div id="tabs-2">
				<p class="current-var"></p>
				<p class="current-loc"></p>
				<p>Tab 2 content</p>
			</div>
			<div id="tabs-3">
				<p class="current-var"></p>
				<p class="current-loc"></p>
				<p>Tab 3 content</p>
			</div>
			<div id="tabs-4">
				<p class="current-var"></p>
				<p class="current-loc"></p>
				<p>Tab 4 content</p>
			</div>
			<div id="tabs-5">
				<p class="current-var"></p>
				<p class="current-loc"></p>
				<p>Tab 5 content</p>
			</div>
		</div>
ENDTABS;
		return $output;
	}

	public static function output() {
		self::$in_use = true;
		$output = '<p>This is my output. There are many like it, but this one is mine.</p>';
		$output .= self::ui_panel();
		return $output;
	}

	public static function run_scripts() {
		if( ! self::$in_use )
			return;
		
		wp_register_script( 'oac_climaterisk', plugins_url( 'js/oac-climaterisk.js', __FILE__ ), array( 'jquery-ui-tabs' ) );
		wp_print_scripts( 'oac_climaterisk' );
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
	add_shortcode('oac_climaterisk', array( 'OACClimateRisk', 'output' ) );
	add_action( 'wp_footer', array( 'OACClimateRisk', 'run_scripts' ) );
}
// Add all non-specific actions/hooks here
?>
