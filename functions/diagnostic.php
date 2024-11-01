<?php
 
require_once('../../../../wp-load.php');
global $wpdb; 
global $wdfydebug;
$wdfydebug=true;
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if (!current_user_can('manage_options'))
{
	echo "Get out of here!";
	
}
else
{
	$today = date( "m-d-y" );
	$filename='WP-WDFY-Diagnostic-' . $today . '.txt';
			ob_start();
			header( "Content-Description: File Transfer" );

		
			header( "Content-Disposition: attachment; filename=" . $filename ); 
			header( "Content-Type: text/plain; charset=" . get_option( 'blog_charset' ), true );
			echo "\xEF\xBB\xBF"; // UTF-8 BOM
	
			echo "WP-WDFY Diagnostic Information\n";
			echo "WPDFY Version: ".wdfy_plugin_get_version();
			echo "\nSite: ".home_url();
			echo "\n";
			
			echo "\nOption wodify_program: ".get_option('wodify_program');
			echo "\nOption wodify_location: ".get_option('wodify_location');
			echo "\nOption wodify_timezone: ".get_option('wodify_timezone');
			echo "\nOption wdfy_prg_inactive: ".print_r(get_option('wdfy_prg_inactive'),true);
			echo "\nOption wdfy_publishdatesetting: ".get_option('wdfy_publishdatesetting');
			echo "\nOption wdfy_publishoffset: ".get_option('wdfy_publishoffset');
			echo "\nOption wdfy_classes_cron: ".get_option('wdfy_classes_cron');
			echo "\nOption wdfy_wodpublish_cron: ".get_option('wdfy_wodpublish_cron');
			echo "\nOption wdfy_wodpublish_days: ".get_option('wdfy_wodpublish_days');
			echo "\nOption wdfy_show_wodimages: ".get_option('wdfy_show_wodimages');
			echo "\nOption wdfy_local_images: ".get_option('wdfy_local_images');

			
		/*
		gister_setting( 'wodify_group', 'wdfy_wpub_author');
		register_setting( 'wodify_group', 'wdfy_wpub_location');
		register_setting( 'wodify_group', 'wdfy_wpub_program');
		register_setting( 'wodify_group', 'wdfy_wpub_category');
		register_setting( 'wodify_group', 'wdfy_wpub_publish');
		register_setting( 'wodify_group', 'wdfy_wpub_images');
		register_setting( 'wodify_group', 'wdfy_wpub_thumb');
		register_setting( 'wodify_group', 'wdfy_wpub_title');
		register_setting( 'wodify_group', 'wdfy_wpub_incomp');
		register_setting( 'wodify_group', 'wdfy_wpub_excomp');*/
			
			
			$timenow = new DateTime();
			echo "\nCurrent Server-Date:". print_r($timenow,true);
			$locations=SOS_Wodify_API::wodifyLocations(false);
			echo "\n\nLocations:\n\n";
			print_r( $locations);
			
			$results = $GLOBALS['wpdb']->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE (post_status='publish'||post_status='private') and post_content like '%wdfywod%'", OBJECT );
			foreach ($results as $mypost)
			{
				
			
				$sc = explode ("wdfywo",$mypost->post_content);
				
				
				$first=true;
				$wdfydebug=true;
				foreach ($sc as $code)
				{
					if (!$first)
					{
						$shortcode ="[wdfywod". substr($code,1,strpos($code,"]"));
						echo "\n\n-----Shortcode: ".$shortcode." in ".$mypost->guid ;
						echo "\n\n";
						$scresult=do_shortcode($shortcode);
						
					}
					$first=false;
				}
				
			}
			
			$wodwidgets=get_option("widget_sos_wodify_wod_widget");
			echo "\n\n----- WODWidgets:\n";
			print_r($wodwidgets);
		
			
			
			
			
			
	
}

 
?>