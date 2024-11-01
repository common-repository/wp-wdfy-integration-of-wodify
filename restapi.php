<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); 

if ( get_option('wdfy_apiactive'))
{
  add_action( 'rest_api_init', function () {
  register_rest_route( 'wp-integration-of-wodify/v1', '/wod-alexa/date=(?P<date>[a-zA-Z0-9-]+)/program=(?P<program>[a-zA-Z0-9-]+)/speakmode=(?P<speakmode>[01])/password=(?P<password>[0-9]+)', array(
    'methods' => 'GET,POST',
    'callback' => 'wpwdfy_restWOD_Alexa',
	'args' => array(
      'date' => array( ),
	  'program' => array(), 
	   'speakmode' => array(),
	   'password' => array(),
	   ),
	'permission_callback' => function () {return (true);}
  ) );
} );
}

function wpwdfy_restWOD($args)
{	
	return(strip_tags(do_shortcode('[wdfywod excludecomponents="Header"]')));
}

function wpwdfy_restWOD_Tomorrow($args)
{	
	return(strip_tags(do_shortcode('[wdfywod date="+1" excludecomponents="Header"]')));
}

function wpwdfy_restWOD_Alexa($args)
// date in ISO8601 format, e.g. 2021-01-21, program: string as defined in settings, speakmode: 0= Alexa, 1=Siri
{	
	$date = $args['date'];
	$program = $args['program'];
	$speakmode = $args['speakmode'];
	$password = $args['password'];
	
	$useragent = $_ENV['HTTP_USER_AGENT'];
	$logfile=fopen(WP_PLUGIN_DIR.'/wp-wdfy_api_access.log','a');
	$logoutput= date('d.m.Y H:i:s').';'.$useragent.';'.$date.';'.$program.';'.$speakmode.';'.$password."\r\n";
	fwrite($logfile,$logoutput);
	fclose($logfile);
	
	$wod = SOS_Wodify_API::speakWOD($date,$program,$password,$speakmode);
	$wod["speakOut"]=$wod["speakOut"];
	return($wod);
}