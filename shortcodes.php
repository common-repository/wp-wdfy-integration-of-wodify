<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); 


function wpwdfy_shortcode_events( $atts, $content = null ) {

	$defaults = array(
		'dateformat' =>get_option('date_format'),
		'location' =>get_option('wodify_location'),
		'includeprograms' => '',
		'excludeprograms' => '',
		'columns' => 'date, time, program, coach,location,address',
		'schemaorg' => 'true',     //TODO set option
		'numdays' => 7,
		'showheader' => 'false',
		'startperiod' => 'now',
		'nummonths' => -1,
	);

	extract( shortcode_atts( $defaults, $atts ) );

	$inclcomp =array_filter(array_map('trim',explode(',',$includeprograms)));
	$exclcomp =array_filter(array_map('trim',explode(',',$excludeprograms)));
	$dateformat = sanitize_text_field($dateformat);
	
	$locations =array_filter(array_map('trim',explode(',',$location)));
	$cols =array_filter(array_map('trim',explode(',',$columns)));
	if ($schemaorg=="false")
		$schema=false;
	else
		$schema=true;
	
	if ($showheader=="false")
		$header=false;
	else
		$header=true;
	switch ($startperiod)
	{ 
		case 'nextmonth': $startperiod = 1;break;
		default: $startperiod=0; break;
	}
	$numdays=intval($numdays);
	$nummonths=intval($nummonths);
	if ($nummonths==0)
		$nummonths = -1;
 
	
	
	$output="";
	$text = wpwdfy_get_event_list($locations,$inclcomp,$exclcomp,$cols,$schema,$numdays,$dateformat,$header,$startperiod,$nummonths);
	
	
	if ( isset( $text ) && ! empty( $text ) && ! is_feed() ) {
		return do_shortcode( $text );
	}
}
if ( ! shortcode_exists( 'wdfyevents' ) ) {
	add_shortcode( 'wdfyevents', 'wpwdfy_shortcode_events' );
}



function wpwdfy_shortcode_wod( $atts, $content = null ) {

	$defaults = array(
		'date'      => current_time("Y/m/d"), 
		'location' =>get_option('wodify_location'),
		'program' =>get_option('wodify_program'),
		'ignorepublishdate' => 'false',
		'includecomponents' => '',
		'excludecomponents' => '',
		'cache' => 'true',
		'publishoffset' => get_option('wdfy_publishoffset'),
		'showimages' => get_option('wdfy_show_wodimages')
	);

	extract( shortcode_atts( $defaults, $atts ) );

	$inclcomp =array_filter(array_map('trim',explode(',',$includecomponents)));
	$exclcomp =array_filter(array_map('trim',explode(',',$excludecomponents)));
	$output="";
	$publish = null;

	if ($date=="+undefined")
		$date = "+0";

	if (substr($date,0,1)=='+'||substr($date,0,1)=='-')
	{
		$datetime = new DateTime();
		$datetime->setTimeZone(new DateTimeZone(wpwdfy_get_timezone_string()));
		
		$modify=$date.' day';
		$datetime->modify($modify);
		
		$date = $datetime->format('Y/m/d');
		
	}
	
	
	switch (substr($date,0,3))
	{
		case 'MON': $date = wpwdfy_nextweekdaydate(1); break;
		case 'TUE': $date = wpwdfy_nextweekdaydate(2); break;
		case 'WED': $date = wpwdfy_nextweekdaydate(3); break;
		case 'THU': $date = wpwdfy_nextweekdaydate(4); break;
		case 'FRI': $date = wpwdfy_nextweekdaydate(5); break;
		case 'SAT': $date = wpwdfy_nextweekdaydate(6); break;
		case 'SUN': $date = wpwdfy_nextweekdaydate(0); break;
	
	}
	
	if (!$date)
			$date = current_time("Y/m/d");
	
	if ($showimages=="true")
		$images=true;
	else
		$images=false;
		
	
	$text= SOS_Wodify_API::wodifyFormatedWOD($location,$program,$date,'true'==$ignorepublishdate,$inclcomp,$exclcomp,'false'!=$cache,$publishoffset,$images); 
	if ( isset( $text ) && ! empty( $text ) && ! is_feed() ) {
		return do_shortcode( $text );
	}
}
if ( ! shortcode_exists( 'wdfywod' ) ) {
	add_shortcode( 'wdfywod', 'wpwdfy_shortcode_wod' );
}

/**
 * Create the shortcode 'wdfylink' attributes see readme.txt
 */ 
function  wpwdfy_get_logo_image($logo,$content)
{
	$output ='';
	switch ($logo)
	{
		case 'black': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_White_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'black2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_White_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;
		case 'white': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_Black_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'white2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_Black_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;
		case 'color': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_color_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'color2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Horizontal_color_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;
		
		case 'vblack': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_White_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'vblack2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_White_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;
		case 'vwhite': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_Black_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'vwhite2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_Black_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;
		case 'vcolor': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_Color_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo_small">';
			break;
		case 'vcolor2': 
			$output.= '<img src="'.plugins_url('img/Wodify_Vertical_Color_256.png', __FILE__).'" alt="Wodify: Results in a box" class="wdfylogo">';
			break;		
		default: 
			if ($content)
			{	
				$output.= $content;
			}
			else {
				$output.=__('Wodify','wp-wdfy-integration-of-wodify');
			}
			break;
	}
	return $output;
}
 
function wpwdfy_shortcode_link( $atts, $content = null ) {

	$defaults = array(
		'device'      => 'auto', 
		'page' => 'wod',
		'newtab' => 'false',
		'logo' => 'none',
	);

	extract( shortcode_atts( $defaults, $atts ) );
	$output="";

	//Link to mobile
	if ("mobile"==$device || "auto"== $device)
	{
		if ("auto"==$device)
		{
				$output.='<span class="soswodify_mobile">';
		}
		$output.='<a href="https://app.wodify.com/Mobile/';
		
		switch ($page)
		{
			case 'calendar': $output.= "Class_Schedule.aspx";break;
			case 'whiteboard': $output.= "Whiteboard_Mobile.aspx";break;
			case 'wod': $output.="WOD.aspx";break;
			default: break;
		}
		
		$output.= '" ';
		
		if ("true"==$newtab)
			$output.= 'target="_blank" rel="noopener noreferrer" >';
		else
			$output.= ">";
		
		$output.= wpwdfy_get_logo_image($logo,$content);
		
		$output.= "</a>";
		
		if ("auto"==$device)
		{
				$output.='</span>';
		}
	}

	//link to desktop
	if ("desktop"==$device || "auto"== $device)
	{
		if ("auto"==$device)
		{
				$output.='<span class="soswodify_desktop">';
		}
		$output.='<a href="';
		
		switch ($page)
		{
			case 'calendar': $output.= 'https://app.wodify.com/Schedule/CalendarListViewEntry.aspx';break;
			case 'whiteboard': $output.= 'https://app.wodify.com/Performance/WhiteboardEntry.aspx';break;
			case 'wod': $output.='https://app.wodify.com/WOD/WODEntry.aspx';break;
			case 'home': $output.='http://www.wodify.com';break;
			default: break;
		}
		
		$output.= '" ';
		
		if ("true"==$newtab)
			$output.= 'target="_blank" rel="noopener noreferrer" >';
		else
			$output.= ">";
		
		$output.= wpwdfy_get_logo_image($logo,$content);
		
		$output.= "</a>";
		
		if ("auto"==$device)
		{
				$output.='</span>';
		}
	}
	
	$text = $output;
	 
	if ( isset( $text ) && ! empty( $text ) && ! is_feed() ) {
		// The do_shortcode function is necessary to let WordPress execute another nested shortcode.
		return do_shortcode( $text );
	}
}
if ( ! shortcode_exists( 'wdflink' ) ) {
	add_shortcode( 'wdfylink', 'wpwdfy_shortcode_link' );
}

/*
function wpwdfy_shortcode_calendar( $atts, $content = null ) {
	$defaults = array(
		'date'      => date("Y/m/d"), 
		'location' =>get_option('wodify_location'),
		'includeprograms' => '',
		'excludeprograms' => '',
		'lines' => 'classname, time, reservation, coach'
		
	);

	extract( shortcode_atts( $defaults, $atts ) );

	$inclcomp =array_filter(array_map('trim',explode(',',$includeprograms)));
	$exclcomp =array_filter(array_map('trim',explode(',',$excludeprograms)));
	$output="";
	$publish = null;
	$text="";
	
	$datetime = new DateTime($date);
	while ($datetime->format('w')!=get_option('start_of_week'))
	{
		$datetime->modify('-1 day');
	}

	if ( isset( $text ) && ! empty( $text ) && ! is_feed() ) {
		return do_shortcode( $text );
	}
}
if ( ! shortcode_exists( 'wdfycalendar' ) ) {
	add_shortcode( 'wdfycalendar', 'wpwdfy_shortcode_calendar' );
}
*/




