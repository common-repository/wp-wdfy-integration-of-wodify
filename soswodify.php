<?php
/*
Plugin Name: WP WDFY Integration of Wodify
Plugin URI: http://amrap42.28a.de/wp-wdfy-integration-of-wodfiy-wordpress-plugin/
Description: Integrate Wodify into Wordpress
Version: 4.04
Author: AMRAP42 - Stefan Osterburg 
Author URI:  http://amrap42.28a.de/
Text Domain: wp-wdfy-integration-of-wodify
Domain Path: /languages
License:     GPL3
 
WP WDFY Integration of Wodify is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.
 
WP WDFY Integration of Wodify is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Wodify for Wordpress. If not, see http://www.gnu.org/licenses/gpl-3.0
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
include( plugin_dir_path( __FILE__ ).'functions/wodify_api.php');
include( plugin_dir_path( __FILE__ ).'functions/functions.php');
include( plugin_dir_path( __FILE__ ).'functions/eventlist.php');
include( plugin_dir_path( __FILE__ ).'widgets/wodify_wod_widget.php');
include( plugin_dir_path( __FILE__ ).'widgets/wodify_classes_widget.php');
include( plugin_dir_path( __FILE__ ).'shortcodes.php');
include( plugin_dir_path( __FILE__ ).'restapi.php');
//include( plugin_dir_path( __FILE__ ).'booking-request.php');
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include( plugin_dir_path( __FILE__ ).'blocks.php');

//TODO ideas
/*
calendar shortcode to display week calendar: existing plugin
Wodify Link zum reservieren, 
whiteboard widget or popup on calendar /WOD????	
leaderboard shortcode/widget (by wod?)
PRs of the day/week?
	
*/
$wdfy_plugin_version=null;
function wdfy_plugin_get_version() {
    global $wdfy_plugin_version;
	if ($wdfy_plugin_version)
		return $wdfy_plugin_version;
	if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
    return $plugin_folder[$plugin_file]['Version'];
}

function wdfy_cron_activation() {
	$scheduleoption = get_option('wdfy_classes_cron');
	if (!$scheduleoption)
	{
		add_option('wdfy_classes_cron','15minutes');
		$scheduleoption = '15minutes';
	}
	$currentschedule = wp_get_schedule('wdfy_cron_cache_classes');	
	
	if (!$currentschedule || $currentschedule!=$scheduleoption)
	{
		wp_clear_scheduled_hook( 'wdfy_cron_cache_classes' );
		if ('none' != $scheduleoption)
		{
			wp_schedule_event(time(), $scheduleoption, 'wdfy_cron_cache_classes');
		}
	}
	
	$scheduleoption = get_option('wdfy_wodpublish_cron');
	if (!$scheduleoption)
	{
		add_option('wdfy_wodpublish_cron','none');
		$scheduleoption = 'none';
	}
	$currentschedule = wp_get_schedule('wdfy_cron_wodpublish');	
	
	if (!$currentschedule || $currentschedule!=$scheduleoption)
	{
		wp_clear_scheduled_hook( 'wdfy_cron_wodpublish' );
		if ('none' != $scheduleoption)
		{
			wp_schedule_event(time(), $scheduleoption, 'wdfy_cron_wodpublish');
		}
	}
}

register_deactivation_hook(__FILE__, 'wdfy_deactivation');
//all cleanup on deactivation goes here
function wdfy_deactivation() {
	wp_clear_scheduled_hook('wdfy_cron_cache_classes');
	wp_clear_scheduled_hook( 'wdfy_cron_wodpublish' );
}

function wdfy_cron_wodpublish()
{
	wdfy_checkCreateWODPost();
}
add_action('wdfy_cron_wodpublish', 'wdfy_cron_wodpublish');

function wdfy_cron_cache_classes()
{
	$datetime = new DateTime();
	$futuredays = 7;
	do
	{
			$locations=SOS_Wodify_API::wodifyLocations();
			if ($locations)
			{
				if (is_array($locations))
					{
					foreach ($locations as $loc)
					{
						$locationid = SOS_Wodify_API::getLocationId($loc->name);
						$classes =SOS_Wodify_API::wodifyClasses( $locationid, $datetime->format('Y/m/d') ,null,false);
					}
				}
				else
				{
					$locationid = SOS_Wodify_API::getLocationId($locations->name);
					$classes =SOS_Wodify_API::wodifyClasses( $locationid, $datetime->format('Y/m/d') ,null,false);	
				}
			}
			$futuredays--;
			$datetime->modify('+1 day');
	} while ($futuredays>-1);
	set_transient('wdfy_cache_date',new DateTime(),7*24*3600);
	
}
add_action('wdfy_cron_cache_classes', 'wdfy_cron_cache_classes');
	
class SOS_Wodify_Plugin {

	// class instance
	static $instance;
	
	// class constructor
	public function __construct() {
		self::$instance = &$this;
	
		if ( is_admin() ){ // admin actions
			add_action( 'admin_menu', array( &$this, 'plugin_menu' ) );
			add_action( 'admin_init', array( &$this, 'register_mysettings') );
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) ,array( &$this, 'wpwdfy_plugin_action_links' ));
			
			add_action( 'admin_enqueue_scripts', array(&$this,'wdfy_admin_scripts' ));
			
		} else {
			// non-admin enqueues, actions, and filters	
		}
		add_action( 'plugins_loaded', array( &$this, 'sos_wodify_lang' ), 5 );
		add_action( 'widgets_init', array( &$this,'register_widgets' ));
		add_action('wp_enqueue_scripts', array( &$this, 'enqueue_style'),10,0);		
		add_filter( 'cron_schedules',  array( &$this,'wdfy_schedules' )); 
	
		if (!get_option('wodify_timezone'))
		{
			update_option('wodify_timezone',wpwdfy_get_timezone_string());
		}
		
		register_activation_hook(__FILE__, 'wdfy_cron_activation');
		wdfy_cron_activation();
		
	}

	function wdfy_schedules( $schedules ) {
	
	$schedules['15minutes'] = array(
		'interval' => 15 * 60, 
		'display' => __( 'Every 15 minutes', 'wp-wdfy-integration-of-wodify' )
	  );
	$schedules['30minutes'] = array(
		'interval' => 30 * 60, 
		'display' => __( 'Every 30 minutes', 'wp-wdfy-integration-of-wodify' )
	  );

	 $schedules['2hours'] = array(
		'interval' => 2*60 * 60, 
		'display' => __( 'Every 2 hours', 'wp-wdfy-integration-of-wodify' )
	  );
	 $schedules['4hours'] = array(
		'interval' => 2*60 * 60, 
		'display' => __( 'Every 4 hours', 'wp-wdfy-integration-of-wodify' )
	  );
	  return $schedules;
	}

	function wdfy_admin_scripts( $hook ) {
 
		if( is_admin() ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'wdfy_styles', plugin_dir_url( __FILE__ ) . 'css/admin.css',array(),wdfy_plugin_get_version());
			// Include our custom jQuery file with WordPress Color Picker dependency
			wp_enqueue_script( 'custom-script-handle', plugins_url( 'js/colorpicker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
			
		}
	}	

	function wpwdfy_plugin_action_links( $links ) {
		$settings_link = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=wp-wdfy-integration-of-wodify') ) .'">'.__('Settings','wp-wdfy-integration-of-wodify').'</a>'; 
		array_unshift( $links, $settings_link );
		return $links;
	}
	
	function enqueue_style()
	{
		wp_enqueue_style("soswodify", plugins_url('css/style.css', __FILE__), array(),wdfy_plugin_get_version());
	}
	function register_widgets()
	{
		register_widget( 'SOS_Wodify_WOD_Widget' );
		register_widget( 'SOS_Wodify_Classes_Widget' );
	}
	function sos_wodify_lang() {
		load_plugin_textdomain( 'wp-wdfy-integration-of-wodify', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}
	public function register_mysettings() { 
		register_setting( 'wodify_group', 'wodify_apikey');
		register_setting( 'wodify_group', 'wodify_debugmode');
		register_setting( 'wodify_group', 'wodify_program');
		register_setting( 'wodify_group', 'wodify_location');
		register_setting( 'wodify_group', 'wodify_timezone');
		register_setting( 'wodify_group', 'wdfy_apiactive');
		register_setting( 'wodify_group', 'wdfy_api_program_short');
		register_setting( 'wodify_group', 'wdfy_api_program');
		register_setting( 'wodify_group', 'wdfy_alexa_magicnumber');
		
		
		register_setting( 'wodify_group', 'wdfy_schema_siteimage');
		register_setting( 'wodify_group', 'wdfy_schema_phone');
		register_setting( 'wodify_group', 'wdfy_schema_pricerange');
		register_setting( 'wodify_group', 'wdfy_schema_addjson');
		
		register_setting( 'wodify_group', 'wdfy_prg_bgcolor');
		register_setting( 'wodify_group', 'wdfy_prg_inactive');
		register_setting( 'wodify_group', 'wdfy_prg_url');
		register_setting( 'wodify_group', 'wdfy_prg_image');
		register_setting( 'wodify_group', 'wdfy_prg_restshortname');

		register_setting( 'wodify_group', 'wdfy_coach_url');
		register_setting( 'wodify_group', 'wdfy_classes_cron');
		register_setting( 'wodify_group', 'wdfy_publishdatesetting');
		register_setting( 'wodify_group', 'wdfy_publishoffset');
		
		register_setting( 'wodify_group', 'wdfy_wodpublish_cron');
		register_setting( 'wodify_group', 'wdfy_wodpublish_days');
		register_setting( 'wodify_group', 'wdfy_wpub_autocreate');
		register_setting( 'wodify_group', 'wdfy_wpub_author');
		register_setting( 'wodify_group', 'wdfy_wpub_location');
		register_setting( 'wodify_group', 'wdfy_wpub_program');
		register_setting( 'wodify_group', 'wdfy_wpub_category');
		register_setting( 'wodify_group', 'wdfy_wpub_posttype');
		register_setting( 'wodify_group', 'wdfy_wpub_publish');
		register_setting( 'wodify_group', 'wdfy_wpub_images');
		register_setting( 'wodify_group', 'wdfy_wpub_thumb');
		register_setting( 'wodify_group', 'wdfy_show_wodimages');
		register_setting( 'wodify_group', 'wdfy_local_images');

		register_setting( 'wodify_group', 'wdfy_wpub_title');
		register_setting( 'wodify_group', 'wdfy_wpub_incomp');
		register_setting( 'wodify_group', 'wdfy_wpub_excomp');
	
		
	}	

	public function plugin_menu() {
		$hook = add_options_page(
			__('Wodify Integration Settings','wp-wdfy-integration-of-wodify'),
			__('Wodify Integration','wp-wdfy-integration-of-wodify'),
			'manage_options',
			'wp-wdfy-integration-of-wodify',
			array(&$this,'plugin_settings_page')
		);
	}
	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
	
		?>
		
		<div class="wrap">
			 <h1><?php _e('Wodify Integration','wp-wdfy-integration-of-wodify');?></h1>
			
			<h2 class="nav-tab-wrapper">
			<a href="#" id="wdfyfirsttab" class="nav-tab"><?php _e('About WP WDFY','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" id="wdfyfirsttab" class="nav-tab"><?php _e('General Options','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('Program Settings','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('Coaches Settings','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('Classes Settings','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('WOD Settings','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('REST-API/Alexa-API','wp-wdfy-integration-of-wodify');?></a>
			<a href="#" class="nav-tab"><?php _e('Shortcode Documentation','wp-wdfy-integration-of-wodify');?></a>
		</h2>
		<form method="post" action="options.php"> 
	<?php
		settings_fields( 'wodify_group' );
		do_settings_sections( 'wodify_group' );
		wp_enqueue_script( 'wdfy_admin_js', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), wdfy_plugin_get_version(), true );
		?>
		
		<section class="wdfysection_welcome">
		 <h2><?php _e('About this plugin','wp-wdfy-integration-of-wodify');?></h2>
			 
			 <p><?php _e('This plugin is provided by <a href="http://www.amrap42.de" target="_blank">AMRAP42 - Stefan Osterburg</a>.','wp-wdfy-integration-of-wodify');?></p>
			 
			 <p><strong><img valign="middle" style="padding-right: 10px;" align="left" src="<?php echo plugins_url('img/donation.png', __FILE__); ?>"><?php _e('If you like the plugin and find it valuable for your site, please support me as the author - any amount will be appreciated.','wp-wdfy-integration-of-wodify');?></strong><br>
			 <strong><a href="https://www.paypal.me/amrap42/10" target=_blank"  rel="noopener noreferrer"> <?php _e('Please send your donation via Paypal by clicking here.','wp-wdfy-integration-of-wodify');?></a></strong><br clear="all"></p>
			 
			 <p><?php _e('DISCLAIMER: The plugin and its <a href="http://amrap42.28a.de" target="_blank"  rel="noopener">author</a> are in no way associated with or endorsed by <a href="http://www.wodify.com" target="_blank" rel="noopener noreferrer">WODIFY</a>. The plugin relies on the API provided by Wodify.','wp-wdfy-integration-of-wodify');?></p>
			 
			 <p><?php 
			 _e('If you have ideas, problems or suggestions you can find the relevant contact information on <a href=" http://amrap42.28a.de/contact/" target="_blank" rel="noopener">http://amrap42.28a.de/contact/</a>.','wp-wdfy-integration-of-wodify');
			 ?>
			 <h2><?php _e('Support','wp-wdfy-integration-of-wodify');?></h2>
			 <p><?php 
			 _e('If you have contacted me with a plugin problem on your site, I might ask you to send me additional information. 
			 In this case please use the following button and send me the resulting text file in an e-mail. ','wp-wdfy-integration-of-wodify');
			 echo '<br><br><a class="button" href="'.plugin_dir_url(__FILE__).'functions/diagnostic.php">';
			 _e("Download diagnostic information",'wp-wdfy-integration-of-wodify');
			 echo "</a><br><br>";
			  _e('The resulting file will not contain any login information or your API key. ','wp-wdfy-integration-of-wodify');
			 ?></p>			 
		</section>
		
		
		<section class="wdfysection">
		
		<table class="form-table">
		<tr valign="top"><td colspan="2">
		<h2><?php _e('General Options','wp-wdfy-integration-of-wodify');?></h2>
		</td></tr>
		<tr valign="top">
        <th scope="row"><?php _e('Wodify API Key','wp-wdfy-integration-of-wodify');?></th>
        <td><input type="text" name="wodify_apikey" size="40 "value="<?php echo esc_attr( get_option('wodify_apikey') ); ?>" /><br>
		<?php
		
		$wdfyconnect=wdfy_refresh_lookupdata();
		if ($wdfyconnect)
		{
			echo '<div class="notice notice-success is-dismissible">'. __("Successfully connected to Wodify.<br>Reload page to check again.",'wp-wdfy-integration-of-wodify').'</div>';
	
		}
		else
			echo '<div class="error is-dismissible">'.__("Currently no connection to Wodify.<br>Change API key and save settings, or reload page to check again.",'wp-wdfy-integration-of-wodify').'</div>';
		echo sprintf(__('To find you API Key please see the description on this <a href="%s" target="_blank"  rel="noopener noreferrer">Wodify Support page</a>.','wp-wdfy-integration-of-wodify'),'https://wodify.zendesk.com/hc/en-us/articles/208736908-How-do-I-set-up-WOD-integration-without-WordPress-'); 
		?>		
		</td></tr>

		// new parameter checkbox to activate debug mode
		<tr valign="top">
		<th scope="row"><?php _e('Activate API Debug Mode','wp-wdfy-integration-of-wodify');?></th>
		<td><input type="checkbox" name="wodify_debugmode" <?php if (get_option('wodify_debugmode')) echo "checked"; ?> /><br>



		
		<tr valign="top">
        <th scope="row"><?php _e('Default Wodify Location to use for shortcodes and widgets','wp-wdfy-integration-of-wodify');?></th>
        <td><?php
		$locations=SOS_Wodify_API::wodifyLocations();
		$location = esc_attr( get_option('wodify_location'));
		if ($locations)
		{
			?>
			<select class="select" name="wodify_location">
			<option value=""<?php if ($location=="") echo " selected"; ?>><?php _e( '(Please select)','wp-wdfy-integration-of-wodify'); ?></option>
			<?php
				if (is_array($locations))
				{
					foreach ($locations as $loc)
					{
						echo '<option value="'.$loc->name.'" ';
						if ($location==esc_attr($loc->name))
						{
							echo "selected";
						}
						echo '>'.$loc->name."</option>";
					}
				}
				else
				{
					echo '<option value="'.$locations->name.'" ';
						if ($location==esc_attr($locations->name))
						{
							echo "selected";
						}
						echo '>'.$locations->name."</option>";
				}
				echo "</select>";			
		}
		else
		{
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}
		?>	
		</td>
		</tr>
		<tr valign="top">
        <th scope="row"><?php _e('Default Wodify Program to use for shortcodes and widgets','wp-wdfy-integration-of-wodify');?></th>
        <td>
		<?php
		$programs=SOS_Wodify_API::wodifyPrograms();
		
		$program = esc_attr( get_option('wodify_program'));
		if ($programs)
		{
			?>
			<select class="select" name="wodify_program">
				<option value=""<?php if ($program=="") echo " selected"; ?>><?php _e( '(Please select)','wp-wdfy-integration-of-wodify'); ?></option>
			<?php
				if (is_array($programs))
				{
					foreach ($programs as $prog)
					{
						echo '<option value="'.$prog->name.'" ';
						if ($program==esc_attr($prog->name))
						{
							echo "selected";
						}
						echo '>'.$prog->name."</option>";
					}
				}
				else
				{
					echo '<option value="'.$prog->name.'" selected>'.$prog->name."</option>";
				}
				echo "</select>";	
		}
		else
		{
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}
		?>
		</td></tr>	
		 <tr valign="top"><th scope="row"><?php _e('Wodify Time zone','wp-wdfy-integration-of-wodify');?></th>
        <td><select type="text" name="wodify_timezone">
		<?php
			$timezones = DateTimeZone::listIdentifiers();
			foreach ($timezones as $zone){
				echo '<option value="'.$zone.'"';
				if ($zone == get_option('wodify_timezone'))
					echo " selected";
				echo '>'.$zone.'</selected>';
			}
		?>
		</select></td></tr>
		<tr valign="top"><td colspan="2">
		<h2><?php _e('Schema.org Options','wp-wdfy-integration-of-wodify');?></h2>
		<?php _e('If you use the [wdfyevents] shortcode or editor block this will create a <a href="https://schema.org/Event" target=_blank"  rel="noopener noreferrer">schema.org event markup</a> for search engines. Use these options to define the attributes. Please note that more settings should be made in the Programs settings section.','wp-wdfy-integration-of-wodify');?>
	
		</td></tr>
		
		  <tr valign="top">
        <th scope="row"><?php _e('Location image','wp-wdfy-integration-of-wodify');?></th>
		<?
			$wdfy_siteimage = get_option('wdfy_schema_siteimage');
			if (!$wdfy_siteimage)
			{
				update_option('wdfy_schema_siteimage',get_custom_logo());
			}
		
		?>
        <td><input type="url" name="wdfy_schema_siteimage" size="80" value="<?php echo esc_attr( get_option('wdfy_schema_siteimage') ); ?>" /><br>
		<?php _e('Content for the "image" attribute in the "location" section of the event markup. Please provide a fully qualified image URL (incl. http/https). This will also be used as default for the event image. You can choose to make individual settings on the Programs settings page.','wp-wdfy-integration-of-wodify'); ?>
		</td></tr>
	
	
		<tr><th scope="row"><?php _e('Phone number','wp-wdfy-integration-of-wodify');?></th>
		<?
			$wdfy_siteimage = get_option('wdfy_schema_phone');		
		?>
	
	    <td><input type="phone" name="wdfy_schema_phone" size="30" value="<?php echo esc_attr( get_option('wdfy_schema_phone') ); ?>" /><br>
		<?php _e('Content for "telephone" in the "location" section of the event markup.','wp-wdfy-integration-of-wodify'); ?>
		</td></tr>
	
		<tr><th scope="row"><?php _e('Price Range','wp-wdfy-integration-of-wodify');?></th>
		<?
			$wdfy_siteimage = get_option('wdfy_schema_pricerange');		
		?>
	
	   <td><input type="text" name="wdfy_schema_pricerange" size="30" value="<?php echo esc_attr( get_option('wdfy_schema_pricerange') ); ?>" /><br>
		<?php _e('Content for "priceRange" attribute in the "location" section of the event markup. Typically, values such as $ = inexpensive, $$ = moderate, $$$ = expensive, $$$$ = very expensive are used.','wp-wdfy-integration-of-wodify'); ?>
		</td></tr>
		
		<tr><th scope="row"><?php _e('Additional schema.org JSON','wp-wdfy-integration-of-wodify');?></th>
		<?
			$wdfy_siteimage = get_option('wdfy_schema_addjson');		
		?>
	
	   <td><textarea rows="4" cols="50" name="wdfy_schema_addjson" size="80"><?php echo esc_attr( get_option('wdfy_schema_addjson') ); ?></textarea><br>
		<?php _e('Please add any extra JSON code, that you would like to add to the event markup. Anything specified here will be added on top-level at the end of each event markup.','wp-wdfy-integration-of-wodify'); ?>
		<br>
		<?php _e('Example:<br><code>"offers": {"@type": "Offer","url": "http://yourwebsite.com",...  }</code>','wp-wdfy-integration-of-wodify'); ?>
		</td></tr>
		
			</table>
		</section>
		
		<section class="wdfysection">
		<h2><?php _e('Program Settings','wp-wdfy-integration-of-wodify');?></h2>
		
		<p><?php _e('Please specify setting for the different Wodify programs. These will be used to determine what and how classes are displayed.','wp-wdfy-integration-of-wodify');?>
		<ul>
		<li><strong><?php echo __('Program color','wp-wdfy-integration-of-wodify');?>:</strong>
		<?php _e('Background color for this program in the calendar widget','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong><?php echo __('Deactivate','wp-wdfy-integration-of-wodify');?>:</strong>
		<?php _e('Deactivate this program in calendar widget, event shortcode and blocks.','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong><?php echo __('Description URL','wp-wdfy-integration-of-wodify');?>:</strong>
		<?php _e('Class titles in calendar widget will be linked to the respective URL, e.g. a course description on your website.','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong><?php echo __('schema.org event image URL','wp-wdfy-integration-of-wodify');?>:</strong>
		<?php _e('If you use the [wdfyevents] shortcode or editor block this will create a schema.org event markup for search engines. Please enter a fully qualified URL (inkl. http/https of an image that can be used for this event markup specifica for each program.','wp-wdfy-integration-of-wodify');?>
		</li>
		</ul>
		
		<?php
		$prg_bgcolors	= get_option('wdfy_prg_bgcolor');
		$prg_inactive	= get_option('wdfy_prg_inactive');
		$prg_url   		= get_option('wdfy_prg_url');
		$prg_image   	= get_option('wdfy_prg_image');
		$programs=SOS_Wodify_API::wodifyPrograms();
		if (!$prg_bgcolors)
			$prg_bgcolors = array();
		if (!$prg_inactive)
			$prg_inactive = array();
		if (!$prg_url)
			$prg_url = array();
		if (!$prg_image)
			$prg_image = array();
	
		if ($programs)
		{
			?>
			<table class="wp-list-table widefat striped">
			<tr>
				<th><strong><?php echo __('Program name','wp-wdfy-integration-of-wodify');?></strong></th>
				<th><strong><?php echo __('Program color','wp-wdfy-integration-of-wodify');?></strong></th>
				<th style="text-align: center;"><strong><?php _e('Deactivate','wp-wdfy-integration-of-wodify');?></strong></th>
				<th><strong><?php _e('Description URL','wp-wdfy-integration-of-wodify');?></strong></th> 
				<th><strong><?php _e('Schema.org event image URL','wp-wdfy-integration-of-wodify');?></strong></th> 
			</tr>		
			<?php
				
				if (is_array($programs))
				{
					foreach ($programs as $prog)
					{					
						if (array_key_exists(sanitize_title_with_dashes($prog->name),$prg_url))	{
							$programurl=$prg_url[sanitize_title_with_dashes($prog->name)];
						} 
						else {
							$programurl='';
						}
						
						if (array_key_exists(sanitize_title_with_dashes($prog->name),$prg_image))	{
							$imageurl=$prg_image[sanitize_title_with_dashes($prog->name)];
						} 
						else {
							$imageurl='';
						}
						
						if (array_key_exists(sanitize_title_with_dashes($prog->name),$prg_bgcolors)) {
							$programbgcolor=$prg_bgcolors[sanitize_title_with_dashes($prog->name)];
						}
						else {
							$programbgcolor='';
						}
						
						if (array_key_exists(sanitize_title_with_dashes($prog->name),$prg_inactive)) {
							$programinactive=$prg_inactive[sanitize_title_with_dashes($prog->name)];
						} 
						else {
							$programinactive=false;
						}
						echo '<tr><td>'.$prog->name.'</td>';
						// add # to color if not there
						if ($programbgcolor && $programbgcolor[0] != '#')
							$programbgcolor = '#'.$programbgcolor;
						if ($prog->color[0] != '#')
							$prog->color = '#'.$prog->color;

						if (!$programbgcolor)
								$programbgcolor = $prog->color;

						echo '<td>';?><input class="wdfy-color-field" name="<?php 
									echo 'wdfy_prg_bgcolor['.sanitize_title_with_dashes($prog->name).']'; 
							?>" type="text" value="<?php 
									echo $programbgcolor; 
							?>" data-default-color="<?php echo $prog->color; ?>" />
						</td>
						<td style="text-align: center;"><input name="<?php 
									echo 'wdfy_prg_inactive['.sanitize_title_with_dashes($prog->name).']'; 
							?>" type="checkbox" <?php 
									if ($programinactive)
									echo "checked" 
							?>/>
						</td><td><input name="<?php 
									echo 'wdfy_prg_url['.sanitize_title_with_dashes($prog->name).']'; 
							?>" type="text" size="50" value="<?php 
									echo $programurl;
							?>"/></td>
							<td><input name="<?php 
									echo 'wdfy_prg_image['.sanitize_title_with_dashes($prog->name).']'; 
							?>" type="text" size="50" value="<?php 
									echo $imageurl;
							?>"/></td>
							
							</tr><?php
					}
				}
				echo "</table>";	
		}
		else {
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}?>
		</section>
		

		<section class="wdfysection">
		<h2><?php _e('Coaches Settings','wp-wdfy-integration-of-wodify');?></h2>
		<p><?php _e('You can specify URLS for each Wodify coach. This will be used to link the coach name to the respective page when it is shown e.g. in the classes widget','wp-wdfy-integration-of-wodify');?></p>
		<?php
		$coach_url   		= get_option('wdfy_coach_url');
		if (!$coach_url)
			$coach_url = array();
		$coaches=SOS_Wodify_API::wodifyCoaches();
		
		if ($coaches)
		{
			?>
			<table class="wp-list-table striped"><tr><td><strong><?php echo __('Coach name','wp-wdfy-integration-of-wodify');?></strong></td><td><strong><?php _e('Coach About Page URL','wp-wdfy-integration-of-wodify');?></strong></td><tr><tr><?php
				if (is_array($coaches))
				{
					foreach ($coaches as $coach)
					{					
						if (array_key_exists(sanitize_title_with_dashes($coach->first_name.' '.$coach->last_name),$coach_url))
						{
							$coachurl=$coach_url[sanitize_title_with_dashes($coach->first_name.' '.$coach->last_name)];
						}
						else
						{
							$coachurl='';
						}
							
						echo '<tr><td>'.$coach->first_name.' '.$coach->last_name.'</td>';
						echo '<td>';?>							
						<input name="<?php 
									echo 'wdfy_coach_url['.sanitize_title_with_dashes($coach->first_name.' '.$coach->last_name).']'; 
							?>" type="text" size="50" value="<?php 
									echo $coachurl;
							?>"/></td>
						<?php
					}
				}
				echo "</table>";	
		}
		else
		{
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}?>
		</section>
		
		<section class="wdfysection">
		<h2><?php _e('Classes Settings','wp-wdfy-integration-of-wodify');?></h2>
		<p>
		<?php
			_e('Classes caching is used to reduce page load times for your visitors by avoiding Wodify API calls. If you are displaying reservation info in your classes widgets, you will probably need a higher frequency than if you don\'t. Caching uses the Wordpress cron mechanism, which is triggered whenever users visit your site. On low traffic websites you might need to add a real cron job to load your page for triggering caching.','wp-wdfy-integration-of-wodify')
		?></p>
		<table class="form-table">
		 <tr valign="top">
        <th scope="row"><?php _e('Caching frequency for Wodify classes information','wp-wdfy-integration-of-wodify');?></th>
        <td><select type="text" name="wdfy_classes_cron">
		<option value="none" <?php 
			if ('none' == get_option('wdfy_classes_cron')) 
				echo "selected";
			echo ">";
			echo __('No classes caching','wp-wdfy-integration-of-wodify');
		?>
		</option>	
		<?php
			$schedules=wp_get_schedules();
			
			foreach ($schedules as $key => $schedule)
			{
				if (in_array($key,array("15minutes","30minutes","2hours","4hours","hourly","twicedaily","daily")) || ($key ==get_option('wdfy_classes_cron')))
				{
				echo '<option value="'.$key.'" ';
				if ($key == get_option('wdfy_classes_cron')) 
					echo "selected";
				echo ">";
				echo $schedule['display'];
				echo '</option>';	
				}
			}
			
			
		?>
		</select></td></tr>
		</table>		
				
		</section>
		
		
				<section class="wdfysection">
		<h2><?php _e('WOD Publishing Settings','wp-wdfy-integration-of-wodify');?></h2><p><?php
			_e('Wodify uses two publish dates: If WordPress posts are activated in Wodify, the <strong>Blog Publish Date</strong> specifies when Wodify is supposed to generate a WordPress post with the WOD. The <strong>Internal Publish Date</strong> specifies, when the WOD can be viewed within the Wodify app.','wp-wdfy-integration-of-wodify');
			echo " ";
			_e('This plugin normally respects this setting, if you do not explicitely chose to ignore it in a widget or shortcode. The following setting specifies, which of the two dates the plugin should use.','wp-wdfy-integration-of-wodify')			;
			echo " ";
			_e('You can also define an offset in hours to apply to the Wodify publish date to post your WOD earlier (negative values) or later than specified by Wodify.','wp-wdfy-integration-of-wodify')		
		?></p>
		<table class="form-table"><tr valign="top">
        <th scope="row"><?php _e('Wodify publish date','wp-wdfy-integration-of-wodify');?></th>
        <td><select name="wdfy_publishdatesetting">
		<option value="blogpublishdate" <?php 
			if ('blogpublishdate' == get_option('wdfy_publishdatesetting')) 
				echo "selected";
			echo ">";
			echo __('Blog Publish Date','wp-wdfy-integration-of-wodify');
			?>
		</option>	
		<option value="internalpublishdate" <?php 
			if ('internalpublishdate' == get_option('wdfy_publishdatesetting')) 
				echo "selected";
			echo ">";
			echo __('Internal Publish Date','wp-wdfy-integration-of-wodify');
		?>
		</option></select></td></tr>
		
		 <tr valign="top">
        <th scope="row"><?php _e('Publish offset in hours:','wp-wdfy-integration-of-wodify');?></th>
        <td><input type="number" name="wdfy_publishoffset" value="<?php 
			if (get_option('wdfy_publishoffset'))
				echo get_option('wdfy_publishoffset'); 
			else
				echo "0";
			?>"/></td></tr>
		</table>
		
		
		<h2><?php _e('WOD Images','wp-wdfy-integration-of-wodify');?></h2>	
		<?php 
			_e('Please decide what the default for showing WOD images is. You can override the setting for each widget, shortcode or automated post.','wp-wdfy-integration-of-wodify');		
			echo "<br><br>";
			_e('Images in WODs can be downloaded to the local media library when a WOD is first displayed. By default images are loaded from the original Wodify location. Featured images in automated posts are always downloaded to the media library.','wp-wdfy-integration-of-wodify');		
			?>
		<table class="form-table"><tr valign="top">
		
		<tr valign="top">
        <th scope="row"><?php _e('Show WOD images by default?','wp-wdfy-integration-of-wodify');?></th>
        <td><select name="wdfy_show_wodimages">
		
		<option value="false" <?php 
			if ('false' == get_option('wdfy_show_wodimages')) 
				echo "selected";
			echo ">";
			echo __('No','wp-wdfy-integration-of-wodify');
		?>
		</option>
		<option value="true" <?php 
			if ('true' == get_option('wdfy_show_wodimages')) 
				echo "selected";
			echo ">";
			echo __('Yes','wp-wdfy-integration-of-wodify');
			?>
		</option>	
		</select>		
		</td></tr>	
		
		<tr valign="top">
        <th scope="row"><?php _e('Download WOD images to media library?','wp-wdfy-integration-of-wodify');?></th>
        <td><select name="wdfy_local_images">
		
		<option value="remote" <?php 
			if ('remote' == get_option('wdfy_local_images')) 
				echo "selected";
			echo ">";
			echo __('No','wp-wdfy-integration-of-wodify');
		?>
		</option>
		<option value="local" <?php 
			if ('local' == get_option('wdfy_local_images')) 
				echo "selected";
			echo ">";
			echo __('Yes','wp-wdfy-integration-of-wodify');
			?>
		</option>	
		</select>		
		</td></tr>	
		</table>

		<h2><?php _e('WOD Posting','wp-wdfy-integration-of-wodify');?></h2>
		<p><?php _e('WP WDFY can automatically create blog posts for your WODs, if the standard Wodify feature does not meet your requirements. It uses the Wordpress cron mechanism, which is triggered whenever users visit your site. On low traffic websites you might need to add a real cron job to load your page for triggering WOD posts.  ','wp-wdfy-integration-of-wodify');
				echo ' ';
				_e('The following two settings define when posts will be created. You can setup the actual publish date of each post per program below. However, if you chose immediate publishing, posts will be on your website as soon as they are created. ','wp-wdfy-integration-of-wodify');?></p>
		
		<table class="form-table">
		 <tr valign="top">
        <th scope="row"><?php _e('Frequency to check for new Wodify WODs to post:','wp-wdfy-integration-of-wodify');?></th>
        <td><select type="text" name="wdfy_wodpublish_cron">
		<option value="none" <?php 
			if ('none' == get_option('wdfy_wodpublish_cron')) 
				echo "selected";
			echo ">";
			echo __('No WOD posting ','wp-wdfy-integration-of-wodify');
		?>
		</option>	
		<?php
			$schedules=wp_get_schedules();
			
			foreach ($schedules as $key => $schedule)
			{
				if (in_array($key,array("15minutes","30minutes","2hours","4hours","hourly","twicedaily","daily")) || ($key ==get_option('wdfy_wodpublish_cron')))
				{
				echo '<option value="'.$key.'" ';
				if ($key == get_option('wdfy_wodpublish_cron')) 
					echo "selected";
				echo ">";
				echo $schedule['display'];
				echo '</option>';	
				}
			}
		?>
		</select></td></tr>
		<?php 
			$wdfy_wodpublish_days = get_option('wdfy_wodpublish_days');
			if (!$wdfy_wodpublish_days&&'0'!==$wdfy_wodpublish_days)
				$wdfy_wodpublish_days='3';
		?>
		<tr valign="top">
        <th scope="row"><?php _e('Days to create WOD posts in advance:','wp-wdfy-integration-of-wodify');?></th>
        <td><select type="text" name="wdfy_wodpublish_days">
		<option value="0" <?php if ('0' == $wdfy_wodpublish_days) echo "selected";	?>>0</option>	
		<option value="1" <?php if ('1' == $wdfy_wodpublish_days) echo "selected";	?>>1</option>	
		<option value="2" <?php if ('2' == $wdfy_wodpublish_days) echo "selected";	?>>2</option>	
		<option value="3" <?php if ('3' == $wdfy_wodpublish_days) echo "selected";	?>>3</option>	
		<option value="4" <?php if ('4' == $wdfy_wodpublish_days) echo "selected";	?>>4</option>	
		<option value="5" <?php if ('5' == $wdfy_wodpublish_days) echo "selected";	?>>5</option>	
		<option value="6" <?php if ('6' == $wdfy_wodpublish_days) echo "selected";	?>>6</option>	
		<option value="7" <?php if ('7' == $wdfy_wodpublish_days) echo "selected";	?>>7</option>	
		</select></td></tr>
		</table>
		
		<p>
		<?php _e('Please specify WOD publishing settings for the different Wodify programs. If you have multiple Wodify Locations settings can differ per location.','wp-wdfy-integration-of-wodify');?>
		<br>
		<?php _e('You can use the following place holders for the post title:','wp-wdfy-integration-of-wodify');?>
	    <?php _e('%1 for program name:','wp-wdfy-integration-of-wodify');?>,
		<?php _e('%2 for WOD date','wp-wdfy-integration-of-wodify');?>,
		<?php _e('%3 for WOD name','wp-wdfy-integration-of-wodify');?>.<br>
		<?php _e('Included and excluded components are comma separated lists of WOD components to limit the output to (include) or to skip in output (exclude). Use "Header" for the WOD header section, "Announcements for the respective section.','wp-wdfy-integration-of-wodify');
		echo "<br>";
		 _e('For WOD images you can decide separately if you would like to include them in the post content and/or set the first image as post thumbnail.','wp-wdfy-integration-of-wodify');
		$wdfy_wpub_author   		= get_option('wdfy_wpub_author');
		if (!$wdfy_wpub_author)
			$wdfy_wpub_author = array();
		$wdfy_wpub_autocreate   	= get_option('wdfy_wpub_autocreate');
		if (!$wdfy_wpub_autocreate)
			$wdfy_wpub_autocreate = array();
		$wdfy_wpub_location   	= get_option('wdfy_wpub_location');
		if (!$wdfy_wpub_location)
			$wdfy_wpub_location = array();
		$wdfy_wpub_program   	= get_option('wdfy_wpub_program');
		if (!$wdfy_wpub_program)
			$wdfy_wpub_program = array();
		
		$wdfy_wpub_category   	= get_option('wdfy_wpub_category');
		if (!$wdfy_wpub_category)
			$wdfy_wpub_category = array();
		
		$wdfy_wpub_posttype   	= get_option('wdfy_wpub_posttype');
		if (!$wdfy_wpub_posttype)
			$wdfy_wpub_posttype = array();
		
		
		$wdfy_wpub_publish   	= get_option('wdfy_wpub_publish');
		if (!$wdfy_wpub_publish)
			$wdfy_wpub_publish = array();
		
		$wdfy_wpub_title   	= get_option('wdfy_wpub_title');
		if (!$wdfy_wpub_title)
			$wdfy_wpub_title = array();
		
		$wdfy_wpub_incomp  	= get_option('wdfy_wpub_incomp');
		if (!$wdfy_wpub_incomp)
			$wdfy_wpub_incomp = array();
		
		$wdfy_wpub_images  	= get_option('wdfy_wpub_images');
		if (!$wdfy_wpub_images)
			$wdfy_wpub_images = array();
		
		$wdfy_wpub_thumb  	= get_option('wdfy_wpub_thumb');
		if (!$wdfy_wpub_thumb)
			$wdfy_wpub_thumb = array();
		
		$wdfy_wpub_excomp   	= get_option('wdfy_wpub_excomp');
		if (!$wdfy_wpub_excomp)
			$wdfy_wpub_excomp = array();

		$programs=SOS_Wodify_API::wodifyPrograms();
		
		if ($programs)
		{
			?>
			<table id="wdfy_wpubtable" class="wp-list-table widefat striped ">
			<tr>
				<td><strong><?php echo __('Location','wp-wdfy-integration-of-wodify');?></strong></td>
				<td><strong><?php echo __('Program','wp-wdfy-integration-of-wodify');?></strong></td>
				<td><strong><?php echo __('Create Post','wp-wdfy-integration-of-wodify');?></strong></td>
				<td><strong><?php _e('Post author','wp-wdfy-integration-of-wodify');?></strong></td>
				<td><strong><?php _e('Post type','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Category(s)','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Publishing settings','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Post title','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Post Images','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Post Thumbnail','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Include components','wp-wdfy-integration-of-wodify');?></strong></td> 
				<td><strong><?php _e('Exclude components','wp-wdfy-integration-of-wodify');?></strong></td> 	
			</tr>
			<?php
				foreach ($wdfy_wpub_location as $key=>$location)
				{
					
					if ($location)
					{
						echo "<tr><td>";
						wpwdfy_lookupfield_location("wdfy_wpub_location[]","","",$location,__("Remove line",'wp-wdfy-integration-of-wodify'));
						echo "</td><td>";
						if (array_key_exists($key,$wdfy_wpub_program))
						{
							$program = $wdfy_wpub_program[$key];
						}
						else
						{
							$program = "";
						}
						wpwdfy_lookupfield_program("wdfy_wpub_program[]","", "",$program,__( '(Please select)','wp-wdfy-integration-of-wodify'));
						echo '</td><td style="text-align: center;">';
						
						echo '<select name="wdfy_wpub_autocreate[]">';
						echo '<option value="true" ';
						if (array_key_exists($key,$wdfy_wpub_autocreate) && $wdfy_wpub_autocreate[$key])
							echo 'selected';
						echo '>'.__('Yes','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="false" ';
						if (!array_key_exists($key,$wdfy_wpub_autocreate) || 'false'==$wdfy_wpub_autocreate[$key])
							echo 'selected';
						echo '>'.__('No','wp-wdfy-integration-of-wodify').'</option>';
				
						echo '</select>';
						
						echo '</td><td>';
						
						echo '<input type="text" name="wdfy_wpub_author[]" value="';
						if (array_key_exists($key,$wdfy_wpub_author))
							echo $wdfy_wpub_author[$key];
						echo '"/>';
						
						echo '</td>';
						
						//version 2.2
						echo '<td>';
						echo '<select name="wdfy_wpub_posttype[]">';
						$args = array(
							'public'   => true,
						);
						$posttypes = get_post_types($args,'objects');
						
						if (!is_array($wdfy_wpub_posttype) || !array_key_exists($key,$wdfy_wpub_posttype))
						{
							$wdfy_wpub_posttype[$key] ='post';
						}
						foreach ($posttypes as $posttype)
						{
							echo '<option value="'.$posttype->name.'"';
				
							if ($posttype->name == $wdfy_wpub_posttype[$key])
								echo ' selected';
							echo '>'.$posttype->labels->singular_name.'</option>';
						}
						echo '</select></td><td>';					
						
						
						echo '<input type="text" name="wdfy_wpub_category[]" value="';
						if (array_key_exists($key,$wdfy_wpub_category))
							echo $wdfy_wpub_category[$key];
						echo '"/>';
						
						echo '</td><td>';
						echo '<select name="wdfy_wpub_publish[]">';
						
						echo '<option value="draft" ';
						if (array_key_exists($key,$wdfy_wpub_publish) && 'draft'==$wdfy_wpub_publish[$key])
							echo 'selected';
						echo '>'.__('Manual / Draft','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="date" ';
						if (!array_key_exists($key,$wdfy_wpub_publish) || 'date'==$wdfy_wpub_publish[$key])
							echo 'selected';
						echo '>'.__('Wodify publish date','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="immediate" ';
						if (array_key_exists($key,$wdfy_wpub_publish) && 'immediate'==$wdfy_wpub_publish[$key])
							echo 'selected';
						echo '>'.__('Immediately','wp-wdfy-integration-of-wodify').'</option>';
					
						echo '</select>';
											
						echo '</td><td>';
						echo '<input type="text" name="wdfy_wpub_title[]" value="';
						if (array_key_exists($key,$wdfy_wpub_title))
							echo $wdfy_wpub_title[$key];
						echo '"/>';
						
						echo '</td><td>';
						
						echo '<select name="wdfy_wpub_images[]">';
												
						echo '<option value="no" ';
						if (array_key_exists($key,$wdfy_wpub_images) && 'no'==$wdfy_wpub_images[$key])
							echo 'selected';
						echo '>'.__('No','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="yes" ';
						if (array_key_exists($key,$wdfy_wpub_images) && 'yes'==$wdfy_wpub_images[$key])
							echo 'selected';
						echo '>'.__('Yes','wp-wdfy-integration-of-wodify').'</option>';
										
						echo '</select>';
						echo '</td><td>';
						
						echo '<select name="wdfy_wpub_thumb[]">';
						
						echo '<option value="no" ';
						if (!array_key_exists($key,$wdfy_wpub_thumb) || 'no'==$wdfy_wpub_thumb[$key])
							echo 'selected';
						echo '>'.__('No','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="yes" ';
						if (array_key_exists($key,$wdfy_wpub_thumb) && 'yes'==$wdfy_wpub_thumb[$key])
							echo 'selected';
						echo '>'.__('Yes','wp-wdfy-integration-of-wodify').'</option>';
					
						echo '</select>';							
										
						echo '</td><td>';
						
						echo '<input type="text" name="wdfy_wpub_incomp[]" value="';
						if (array_key_exists($key,$wdfy_wpub_incomp))
							echo $wdfy_wpub_incomp[$key];
						echo '"/>';
						
						echo '</td><td>';
						echo '<input type="text" name="wdfy_wpub_excomp[]" value="';
						if (array_key_exists($key,$wdfy_wpub_excomp))
							echo $wdfy_wpub_excomp[$key];
						echo '"/>';
						
						echo "</td></tr>";
					}
				}
			?>
			<tr id="wdfy_wodp_empty"><td ><?php wpwdfy_lookupfield_location("wdfy_wpub_location[]","","","",__("(Please select)",'wp-wdfy-integration-of-wodify')); ?></td>
			<td><?php wpwdfy_lookupfield_program("wdfy_wpub_program[]","", "","",__( '(Please select)','wp-wdfy-integration-of-wodify')); ?></td>
			
			<td><select name="wdfy_wpub_autocreate[]">
			<option value="false"><?php _e('No','wp-wdfy-integration-of-wodify');?></option>
			<option value="true"><?php _e('Yes','wp-wdfy-integration-of-wodify');?></option>		
			</select>
			</td>
						
			<td><input type="text" name="wdfy_wpub_author[]"/></td>
			<td><select name="wdfy_wpub_posttype[]"><?php
				$args = array(
					'public'   => true,
				);
				$posttypes = get_post_types($args,'objects');
				foreach ($posttypes as $posttype)
				{
					echo '<option value="'.$posttype->name.'">'.$posttype->labels->singular_name.'</option>';
				}
			?></select></td>
			
			<td><input type="text" name="wdfy_wpub_category[]"/></td>
			<td><select name="wdfy_wpub_publish[]"><?php
				echo '<option value="draft">';
				echo __('Manual / Draft','wp-wdfy-integration-of-wodify').'</option>';
				
				echo '<option value="date" selected>';
				echo __('Wodify publish date','wp-wdfy-integration-of-wodify').'</option>';
				
				echo '<option value="immediate">';
				echo __('Immediately','wp-wdfy-integration-of-wodify').'</option>';
			?></select></td>
			<td><input type="text" name="wdfy_wpub_title[]"/></td>
			
			<td><?php
						echo '<select name="wdfy_wpub_images[]">';
						
						echo '<option value="no" ';
						echo '>'.__('No','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="yes" ';
						echo '>'.__('Yes','wp-wdfy-integration-of-wodify').'</option>';
					
						echo '</select>';
						echo '</td><td>';
						
						echo '<select name="wdfy_wpub_thumb[]">';
						
						echo '<option value="no" ';
						echo '>'.__('No','wp-wdfy-integration-of-wodify').'</option>';
						
						echo '<option value="yes" ';
						echo '>'.__('Yes','wp-wdfy-integration-of-wodify').'</option>';
					
						echo '</select></td>';		
						
						
				?>
			<td><input type="text" name="wdfy_wpub_incomp[]"/></td>
			<td><input type="text" name="wdfy_wpub_excomp[]"/></td></tr>
			</table><?php
		}
		else
		{
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}?></p>
		<p>
			<?php _e('To have a specific WOD post recreated, you can simply delete the post (Move to trash AND empty trash!). The post will be recreated the next time WOD posting is activated by the cron mechanism.','wp-wdfy-integration-of-wodify');?>
		</p>
		<h2><?php _e('WOD Caching','wp-wdfy-integration-of-wodify');?></h2>
		<p><?php echo sprintf(__('WODs are cached automatically when they are first retrieved from Wodify through a shortcode, widget, or automated posts to minimize page load times for your visitors.
		To avoid that any visitor will trigger a Wodify API call, you can set up an external cron job that frequently loads the specific page(s) that display(s) the WOD. 
		Use the URL parameter <em>updatewodcache</em> to trigger a cache refresh, e.g. <strong>%s/?updatewodcache</strong> will refresh the cache for a WOD displayed on your home page. 
		A "No WOD found" message is cached for only 20 minutes. If you expect these messages to show up on your webpage, you should set the cron to load the page with a frequency of 
		less than 20 minutes.','wp-wdfy-integration-of-wodify'),home_url());?></p>		
		
		</section>

		<section class="wdfysection">
		<h2><?php _e('REST-API/Alexa-API Settings','wp-wdfy-integration-of-wodify');?></h2>
		<p><?php _e('WP WDFY can provide a REST API to use in Alexa Skills or Siri shortcuts. <br>You can require a numeric password "magic number" to be sent with each request, a value of 0 means that this password will not be checked (but is still required as empty parameter in the API-call (see below).','wp-wdfy-integration-of-wodify');
				?></p>
		
		<table class="form-table">
		<tr valign="top">
        <th scope="row"><?php _e('REST-API active','wp-wdfy-integration-of-wodify');?></th>
		
		 <td>
		<input name="wdfy_apiactive" type="checkbox" <?php 
									if (get_option('wdfy_apiactive'))
										echo "checked";
										
							?>/>
							
		</td></tr>
		<tr valign="top">
        <th scope="row"><?php _e('Magic number','wp-wdfy-integration-of-wodify');?></th>
		
		 <td>
		<?
			$wdfy_alexa_magicnumber   	= get_option('wdfy_alexa_magicnumber');
			if (!isset($wdfy_alexa_magicnumber))
			$wdfy_alexa_magicnumber = '0';
		?>
			<input name="wdfy_alexa_magicnumber" type="number" value="<?php 
										echo $wdfy_alexa_magicnumber;
										
		?>"/>
		</td></tr>
		</table>
		
				
		<h2><?php _e('Wodify / API program mappings','wp-wdfy-integration-of-wodify');?></h2>
				
		<?php 
			
		$wdfy_api_program_short   	= get_option('wdfy_api_program_short');
		if (!$wdfy_api_program_short)
			$wdfy_api_program_short = array();
		 
		$wdfy_api_program   	= get_option('wdfy_api_program');
		if (!$wdfy_api_program)
			$wdfy_api_program = array();
			

		$programs=SOS_Wodify_API::wodifyPrograms();
		
		if ($programs)
		{
			?>
			<table id="wdfy_apitable" class="wp-list-table widefat striped ">
			<tr>
				<td><strong><?php echo __('API short name/id for program','wp-wdfy-integration-of-wodify');
							echo "</strong><br>";
							echo __('(no spaces or special characters)','wp-wdfy-integration-of-wodify'); 
				
				?>
				<td><strong><?php echo __('Wodify Program to map to','wp-wdfy-integration-of-wodify');?></strong></td>
			</tr>
			<?php
				foreach ($wdfy_api_program_short as $key=>$apiprogram_short)
				{
					
					if ($apiprogram_short)
					{

						echo '<tr><td><input type="text" size="30" name="wdfy_api_program_short[]" value="';
						echo $apiprogram_short;
						echo '"/>';
						echo '</td><td>';
						
						if (array_key_exists($key,$wdfy_api_program))
						{
							$program = $wdfy_api_program[$key];
						}
						else
						{
							$program = "";
						}
						wpwdfy_lookupfield_program("wdfy_api_program[]","", "",$program,__( '(Please select)','wp-wdfy-integration-of-wodify'));
						echo '</td>';
						
						echo "</tr>";
					}
				}
			?>
			<tr id="wdfy_apip_empty"><td >
				<input type="text" name="wdfy_api_program_short[]" size="30"/>
			</td>
			<td><?php wpwdfy_lookupfield_program("wdfy_wpub_program[]","", "","",__( '(Please select)','wp-wdfy-integration-of-wodify')); ?></td>
			
			</tr>
			</table><?php
		}
		else
		{
			echo __("Error accessing Wodify. Please check if you correctly entered and saved your API key.","wp-wdfy-integration-of-wodify");
		}?></p>
		
		<h2><?php _e('API documentation','wp-wdfy-integration-of-wodify');?></h2>
		<p>
		<?php
		 
			_e('The REST-API for WP WDFY on your site has one method <strong><code>wod-alexa</code></strong>  that is available at the following address: ','wp-wdfy-integration-of-wodify');
			echo '<a href="'.get_rest_url(null,'wp-integration-of-wodify/v1/').'" target="_blank">'.get_rest_url(null,'wp-integration-of-wodify/v1/').'</a><br><br>';
			_e('The API takes 4 mandatory parameters in the URL, which must all be specified, even if only a default value is passed','wp-wdfy-integration-of-wodify');
			echo "<ul>";
				echo "<li><strong><code>date</code></strong>: ".__('Specifies the date to get the speakable text for in ISO8601 date format, e.g. 2021-01-21','wp-wdfy-integration-of-wodify')."</li>";
				echo "<li><strong><code>program</code></strong>: ".__('Specifies the program to get the WOD for using one of the short names defined above','wp-wdfy-integration-of-wodify')."</li>";
				echo "<li><strong><code>speakmode</code></strong>: ".__('0 for Alexa, 1 for Siri. For Alexa linebreaks will be converted to speech pause markup, which Siri does not interpret.','wp-wdfy-integration-of-wodify')."</li>";
				echo "<li><strong><code>password</code></strong>: ".__('The numeric password (numbers only!) setup above, pass 0 if not used.','wp-wdfy-integration-of-wodify')."</li>";
			echo "</ul><strong>";
			_e('Example','wp-wdfy-integration-of-wodify');
			echo "</strong><ul>";
			echo "<li>";
			$apiext="wod-alexa/date=2021-01-21/program=crossfit/speakmode=0/password=0";
			echo '<code><a href="'.get_rest_url(null,'wp-integration-of-wodify/v1/').$apiext.'" target="_blank">'.get_rest_url(null,'wp-integration-of-wodify/v1/').$apiext.'</a></code><br>';
			echo __('will give you the workout for 2021-01-22 and the program you mapped to the shortname "crossfit" above formatted for Alexa, assuming you have not set a password.','wp-wdfy-integration-of-wodify' );
			echo "</li>";
			echo "</ul>";
			_e('The output will be a JSON containing only the <code>speakOutput</code> element.','wp-wdfy-integration-of-wodify');
		?>
		</p>

		<h2><?php _e('Building your own Alexa Skill','wp-wdfy-integration-of-wodify');?></h2>
		<p>
		<?php
		 
			_e('I will not go into the details of describing the development of Alexa skills here, there is plenty of documentation out there for you to use. And I am also not an expert on Alexa skills, so I will hardly able to help you. ','wp-wdfy-integration-of-wodify');
			echo "<br><br>";
			_e('Here is just a quick and dirty example implementation of a <code>GetWorkout</code> intent:','wp-wdfy-integration-of-wodify');
			
			 
			?>
			<br><br><code><textarea rows="20" cols="160">
var http = require('http'); 
const URL='http://example.org/wp-json/wp-integration-of-wodify/v1/wod-alexa'

const getHttp = function(url, query) {
    return new Promise((resolve, reject) => {
        const request = http.get(`${url}/${query}`, response => {
            response.setEncoding('utf8');
           
            let returnData = '';
            if (response.statusCode < 200 || response.statusCode >= 300) {
                return reject(new Error(`${response.statusCode}: ${response.req.getHeader('host')} ${response.req.path}`));
            }
           
            response.on('data', chunk => {
                returnData += chunk;
            });
           
            response.on('end', () => {
                resolve(returnData);
            });
           
            response.on('error', error => {
                reject(error);
            });
        });
        request.end();
    });
}

const GetworkoutIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'GetWorkout';
    },
    async handle(handlerInput) {
        const theDate = handlerInput.requestEnvelope.request.intent.slots.date.value;
        const theMagicNumber = handlerInput.requestEnvelope.request.intent.slots.magicnumber.value;
        
        const progres = handlerInput.requestEnvelope.request.intent.slots.programm.resolutions.resolutionsPerAuthority[0];
        if (progres.status.code !=='ER_SUCCESS_MATCH')
        {
             let speakOutput = 'I have not found a suitable program.';
             const repromptOutput = 'break time= "500ms"/>Please try again.';
             handlerInput.responseBuilder
                .speak(speakOutput )
            return handlerInput.responseBuilder
            .getResponse();
        }
        else
        {
            const theProgram = handlerInput.requestEnvelope.request.intent.slots.programm.resolutions.resolutionsPerAuthority[0].values[0].value.id;
        
            let speakOutput = ' ';
            const repromptOutput = '<break time= "500ms"/>What else can I do for you';
       
            try {
           
				const response = await getHttp(URL, 'date='+theDate+'/program='+theProgram+'/speakmode=0/password='+theMagicNumber); //theDate
				speakOutput += " " + JSON.parse(response).speakOut;
				speakOutput+='';
           
				handlerInput.responseBuilder
					.speak(speakOutput )
					//.reprompt(repromptOutput)
               
        } catch(error) {
				handlerInput.responseBuilder
					.speak('There has been a problem. Please try again.')
					.reprompt(repromptOutput)
        }
   
        return handlerInput.responseBuilder
            .getResponse();
        }
    }
};
</textarea></code>
		</p>
	<h2><?php _e('Building Siri-Shortcuts','wp-wdfy-integration-of-wodify');?></h2>
		<p>
		<?php
		 
			_e('You can also use the API to create a Siri shortcut on your iPhone/iPad. Again, I am not an expert on this, but the below image might give you an idea how to use the Shortcuts app. ','wp-wdfy-integration-of-wodify');
			echo "<br><br>";
			$imgurl=plugin_dir_url( __FILE__ )."img/siri_shortcut.png";
			echo '<a target="_blank" href="'.$imgurl.'"><img width="300" src="'.$imgurl.'"></a>';
			echo "<br>";
			_e ('(Click to enlarge)','wp-wdfy-integration-of-wodify');
		?>
		<br>
	</section>
	
		<section class="wdfysection">
		<h2><?php _e('Shortcode Documentation','wp-wdfy-integration-of-wodify');?></h2>
		<p>
		<?php _e('This plugin provides the following shortcodes that you can use in your posts and pages. Please note that most attributes are case-sensitive.','wp-wdfy-integration-of-wodify')	?></p>
		<h2>[wdfywod]</h2>
		<?php _e('Output a selected WOD into a post or page.','wp-wdfy-integration-of-wodify'); ?>
		<h3><?php _e('Attributes','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>location</strong>: <?php _e('Location name from Wodify, defaults to setting from settings page.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>date</strong>: <?php _e('Date in Format Y/m/d; or relative to today\'s date "+1", "+2", "-1", ...; or use "MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN" to display WOD for a specific upcoming weekday. Defaults to today\'s date.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>program</strong>: <?php _e('Name of the program to display the WOD for, defaults to the program chosen from the settings page.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>ignorepublishdate</strong>: <?php _e('By default the shortcode will respect the BlogPublish Date set in Wodify. If this attribute is set to "true" the WOD will be displayed anyway if available.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>includecomponents</strong>: <?php _e('comma seperated list of WOD component names. If specified, only components with these names will be shown, names are case-sensitive. Use "Header" for the WOD header (name and comment) section, "Announcements" for announcemnts.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>excludecomponents</strong>: <?php _e('comma seperated list of WOD component names. If specified,  components with the specified names will not be shown, names are case-sensitive. Use "Header" for the WOD header (name and comment) section, "Announcements" for announcemnts.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>cache</strong>: <?php _e('Set to "false" in order to always access Wodify and disable local caching, defaults to "true".','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>showimages</strong>: <?php _e('Allows you to override the general setting for images set "true" to show images, "false" to skip WOD images.','wp-wdfy-integration-of-wodify');?></li>
		</ul>
		<h3><?php _e('Examples','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>[wdfywod] </strong>: <?php _e("Display todays's WOD for default location and program",'wp-wdfy-integration-of-wodify');?></li>
		<li><strong>[wdfywod date="TUE"] </strong>: <?php _e("Display (upcoming) Tuesday's WOD for default location and program.",'wp-wdfy-integration-of-wodify');?></li>
		<li><strong>[wdfywod date="2016/10/08" location="AMRAP42" program="Crossfit"]</strong>: <?php _e('Display WOD for the specified date, location, and program.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>[wdfywod date="+1" ignorepublishdate="true" excludecomponents="Header,Warm-up" showimages="false"] </strong>: <?php _e("Show tomorrow's WOD without respecting Wodify publish settings. Do not show the header information and the Warm-up section. Do not include WOD images.",'wp-wdfy-integration-of-wodify');?></li>
		</ul>
		
		
		<h2>[wdfylink]</h2>
		<?php _e('Insert a link to a Wodify application web page (user must be logged in or login manually) or the Wodify homepage','wp-wdfy-integration-of-wodify'); ?>
		<h3><?php _e('Attributes','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>device</strong>: <?php _e('"mobile" to link mobile web app, "desktop" to link to desktop web page. "auto" to use responsive css to determine link target, defaults to "auto"','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>page</strong>: <?php _e('Wodify page to link to. "wod" (default) | "calendar" | "whiteboard" | "home"','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>newtab</strong>: <?php _e('"true" to open the link in a new tab, defaults to "false".','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>logo</strong>: <?php 
			_e('Defines if a logo or text link is inserted and what logo is used:','wp-wdfy-integration-of-wodify');?><br>&nbsp;&nbsp;&nbsp;
			 <?php _e('"black" | "black2" |"vblack" | "vblack2" logos for black background "2" indicates double size, "v" vertical logo','wp-wdfy-integration-of-wodify');?><br>&nbsp;&nbsp;&nbsp;
			<?php 
			_e(' "white" | "white2" |"vwhite" | "vwhite2" logos for white background "2" indicates double size, "v" vertical logo','wp-wdfy-integration-of-wodify');?><br>&nbsp;&nbsp;&nbsp;
			<?php 
			_e(' "color" | "color2" |"vcolor" | "vcolor2" colored logos (for white background) "2" indicates double size, "v" vertical logo','wp-wdfy-integration-of-wodify');?><br>&nbsp;&nbsp;&nbsp;
			<?php 
			_e('"none" (default) create text link with text enclosed in shortcode (default Text: "Wodify")','wp-wdfy-integration-of-wodify');?>
			
			</li>
		</ul>
		<h3><?php _e('Examples','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>[wdfylink page="calendar" newtab="true"]Click here to go to the Wodify calendar[/wdfylink]</strong>: <?php _e("Insert text link to the Wodify calendar to open in a new tab",'wp-wdfy-integration-of-wodify');?></li>
		<li><strong>[wdfylink logo="black"/]</strong>: <?php _e('Insert a logo link to the Wodify WOD page.','wp-wdfy-integration-of-wodify');?></li>
		</ul>
		
		<h2>[wdfyevents]</h2>
		<?php _e('Insert a list of upcoming classes of selected programs, including schema.org markup for search engines','wp-wdfy-integration-of-wodify'); ?>
		<h3><?php _e('Attributes','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>includeprograms</strong>: <?php _e('Comma seperated list of programs to include classes from. If empty, all active programs (see classes settings tab) are used.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>excludeprograms</strong>: <?php _e('Comma seperated list of programs NOT to include from all active programs (see Classes settings tab).','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>location</strong>: <?php _e('Comma seperated list of locations. Uses default location (General Options tab) if not specified','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>numdays</strong>: <?php _e('Number of days to find classes in the future. Defaults to 7 (one week)','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>nummonths</strong>: <?php _e('If specified will be used instead of numdays. How many calendar months to display.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>startperiod</strong>: <?php _e('Use "nextmonth" to list event from 1st of next month. Otherwise events beginning from the current date will be displayed.','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>columns</strong>: <?php _e('Comma seperated list of the following values that defines the columns to display and their order','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>date</strong>: <?php _e('Date of class','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>time</strong>: <?php _e('Time of class','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>program</strong>: <?php _e('Name of Wodify program (Event name)','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>name</strong>: <?php _e('Name of Wodify class','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>coach</strong>: <?php _e('Coach of the class','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>location</strong>: <?php _e('Location name from Wodify','wp-wdfy-integration-of-wodify');?>
		<br>&nbsp;&nbsp;&nbsp;<strong>address</strong>: <?php _e('Location address from Wodify','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong>schemaorg</strong>: <?php _e('If set to "false" a schema.org event markup for each listed class will not be included in the page','wp-wdfy-integration-of-wodify');?>
		<?php _e('If you intend to use schema.org markup, please make sure to setup the values for various attributes in the plugin general and program settings.','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong>showheader</strong>: <?php _e('If set to "true" a table header will be included.','wp-wdfy-integration-of-wodify');?>
		</li>
		<li><strong>dateformat</strong>: <?php _e('Use custom date format.','wp-wdfy-integration-of-wodify');?>
		</li>
		
		</ul>
		<h3><?php _e('Examples','wp-wdfy-integration-of-wodify'); ?></h3>
		<ul>
		<li><strong>[wdfyevents includeprograms="Introduction to CrossFit"]</strong>: <?php _e('Show a list of classes for the selected program with default settings','wp-wdfy-integration-of-wodify');?></li>
		<li><strong>[wdfyevents includeprograms="Introduction to CrossFit" columns="date,time,program" dateformat="d.m.y" showheader=true]</strong>: <?php _e('Show only specific columns, a custom date format, and include headers in the table','wp-wdfy-integration-of-wodify');?></li>
		</ul>
	
		</section>
		<?php
		submit_button(__("Save all settings",'wp-wdfy-integration-of-wodify')); 
		
		?>
	</form>

	</div><?php
	}
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
global $soswodify;
$soswodify = new SOS_Wodify_Plugin;