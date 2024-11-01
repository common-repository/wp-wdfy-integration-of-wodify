<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


function wpwdfy_require_script($code) {
  global $wpwdfy_scripts;
  $wpwdfy_scripts[] = $code; // store code snippet for later injection
}

function wpwdfy_inject_scripts() {
	global $wpwdfy_scripts;

	if (is_array($wpwdfy_scripts))
	{
		foreach($wpwdfy_scripts as $script)
			echo $script;
	}
	  
	
}
add_action('wp_footer', 'wpwdfy_inject_scripts');

function wpwdfy_get_timezone_string() {
	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) )
	return $timezone;

	// get UTC offset, if it isn't set then return UTC
	if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
	return 'UTC';

	// adjust UTC offset from hours to seconds
	$utc_offset *= 3600;

	// attempt to guess the timezone string from the UTC offset
	if ( $timezone = timezone_name_from_abbr('', $utc_offset, 0 ) ) {
		return $timezone;
	}
	// last try, guess timezone string manually
	$is_dst = date( 'I' );

	foreach ( timezone_abbreviations_list() as $abbr ) {
		foreach ( $abbr as $city ) {
			if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
				return $city['timezone_id'];
	}
}

// fallback to UTC
return 'UTC';
}
function wpwdfy_nextweekdaydate(int $day)   
{
		$datetime = new DateTime();
		$datetime->setTimeZone(new DateTimeZone(wpwdfy_get_timezone_string()));
		$wday 		= date_format($datetime,'w');
		
		if ($wday<=$day)
		{
			$modify='+'.($day-$wday).' day';
			$datetime->modify($modify);
		}
		else
		{
			$modify='+'.(7-$wday+$day).' day';
			$datetime->modify($modify);
		}

		return ($datetime->format('Y/m/d'));
}
	
	
	function wpwdfy_lookupfield_program($fieldname,$fieldid,$title,$program='',$noselecttext='')
	{?>
	<p><label for="<?php echo $fieldid; ?>"><?php echo $title; ?></label><?php
		$programs=SOS_Wodify_API::wodifyPrograms();
		if ($programs)
		{
			?><select class="select" id="<?php echo $fieldid; ?>" name="<?php echo $fieldname; ?>"><option value=""<?php if ($program=="") echo " selected"; ?>><?php echo $noselecttext; ?></option>			<?php
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
			echo __("Error accessing Wodify","wp-wdfy-integration-of-wodify");
		}
	}
	
	function wpwdfy_widget_lookupfield_location($widget,$location)
	{
		wpwdfy_lookupfield_location($widget->get_field_name( 'location' ), $widget->get_field_id( 'location' ),__( 'Box Location (Wodify):','wp-wdfy-integration-of-wodify'),$location,__( '(Please select)','wp-wdfy-integration-of-wodify'));
	}
	
	function wpwdfy_lookupfield_location($fieldname,$fieldid,$title,$location,$noselecttext)
	{
		?><p><label for="<?php echo $fieldid; ?>"><?php echo $title ?></label><?php
		$locations=SOS_Wodify_API::wodifyLocations();
		if ($locations)
		{
			?><select class="select" id="<?php echo $fieldid; ?>" name="<?php echo $fieldname; ?>">
			<option value=""<?php if ($location=="") echo " selected"; ?>><?php echo $noselecttext; ?></option><?php
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
			echo __("Error accessing Wodify","wp-wdfy-integration-of-wodify");
		}
		?></p><?php
	}
	
	function wpwdfy_widget_lookupfield_coaches($widget,$coach)
	{
		?>
		<p><label for="<?php echo $widget->get_field_id( 'coach' ); ?>"><?php _e( 'Coach:','wp-wdfy-integration-of-wodify'); ?></label>
		<?php

		$coaches=SOS_Wodify_API::wodifyCoaches();
		if ($coaches)
		{
			?>
			<select class="select" id="<?php echo $widget->get_field_id( 'coach' ); ?>" name="<?php echo $widget->get_field_name( 'coach' ); ?>">
			<option value=""<?php if ($coach=="") echo " selected"; ?>><?php _e( 'All','wp-wdfy-integration-of-wodify'); ?></option>
			<?php
				
			foreach ($coaches as $loc)
			{
				echo '<option value="'.$loc->first_name.' '.$loc->last_name.'" ';
				if ($coach==esc_attr($loc->first_name.' '.$loc->last_name))
				{
					echo "selected";
				}
				echo '>'.$loc->first_name.' '.$loc->last_name."</option>";
			}
				
				echo "</select>";
					
		}
		else
		{
			echo __("Error accessing Wodify","wp-wdfy-integration-of-wodify");
		}
		?></p><?php
	}
	
	function wdfy_checkCreateWODPost()
	{
		$locations 	= get_option('wdfy_wpub_location');
		$programs  	= get_option('wdfy_wpub_program');
		$numdays 	= get_option('wdfy_wodpublish_days');
		$autocreate = get_option('wdfy_wpub_autocreate');
		$titletplt	= get_option('wdfy_wpub_title');
		$publish    = get_option('wdfy_wpub_publish');
		$incomp    	= get_option('wdfy_wpub_incomp');
		$excomp    	= get_option('wdfy_wpub_excomp');
		$categories = get_option('wdfy_wpub_category');
		//version 2.2
		$posttypes = get_option('wdfy_wpub_posttype');
		$authorname	= get_option('wdfy_wpub_author');
		$images      = get_option('wdfy_wpub_images');
		$thumb      = get_option('wdfy_wpub_thumb');
		
		if (!is_array($locations))
		{
			return;
		}
		foreach ($locations as $key => $location)
		{
			if ($location && array_key_exists($key,$autocreate) && $autocreate[$key]!='false')
			{
				$program = $programs[$key];

				$daycount = 0;
				
				$datetime = new DateTime();
				if (!array_key_exists($key,$incomp))
				{
						$incomp['key']='';				
				}
				if (!array_key_exists($key,$excomp))
				{
						$excomp['key']='';				
				}
				$inclcomp =array_filter(array_map('trim',explode(',',$incomp[$key])));
				$exclcomp =array_filter(array_map('trim',explode(',',$excomp[$key])));
				if(!array_key_exists($key,$titletplt) || !$titletplt[$key])
				{
					$titletplt[$key] = '%1 WOD, %2';
				}
				if (!array_key_exists($key,$publish))
				{
						$publish['key']='draft';				
				}
				
				if (!array_key_exists($key,$categories))
				{
						$categories['key']='';				
				}
				//version 2.2
				if (!array_key_exists($key,$posttypes))
				{
						$posttypes['key']='post';				
				}
				
				
				$cats =array_filter(array_map('trim',explode(',',$categories[$key])));
				$catids = array();
				foreach ($cats as $cat)
				{
					$catids[]= get_cat_ID($cat);
				}
				$author = null;
				if (array_key_exists($key,$authorname)&&$authorname[$key])
				{
					$user = get_user_by('login',$authorname[$key]);
					if ($user)
						$author = $user->ID;
				}
				if (!$author)
				{
					$author=1;
				}
				
				do {
					$date = $datetime->format('Y/m/d');
					$postexists = get_option('wdfy_wod_posted-'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($location).sanitize_title_with_dashes($program));
					if ( !$postexists) 
					{
						$wod = 	SOS_Wodify_API::wodifyWOD($location,$program,$date);
						if ($wod) {
							//$lastedit = new DateTime($wod->APIWod->WodHeader->LastEditDateTime, new DateTimeZone(get_option('wodify_timezone')));
							$lastedit = new DateTime($wod->APIWod->WodHeader->LastEditDateTime, new DateTimeZone('America/New_York'));
							$intervalcreated = $lastedit->diff(new DateTime());
						
							if (0==$intervalcreated->d && 0 == $intervalcreated->h) // if wod modified less than an hour ago it might still change
							{
								$wod=null; // to fresh, don't post just yet
							}					
						}
						if ($wod) 
						{
							if ('yes'== $images[$key])
							{
								$doimages = true; 
							}
							else
							{
								$doimages = false;
							}
							
							if ('yes'== $thumb[$key])
							{
								$dothumb = true; 
							}
							else
							{
								$dothumb = false;
							}
									
							$result = SOS_Wodify_API::formatWOD($wod,$date,true,$inclcomp,$exclcomp,'',$doimages,true);
							$firstimage = $result[0];
							$text = $result[1];
							
							$post = array();
							$post['ID']=0;
							$post['post_content'] 	= $text;
							
							$title = $titletplt[$key];
							$title = str_replace('%1',$program,$title);
							$title = str_replace('%2',date_i18n( get_option( 'date_format' ), strtotime($datetime->format('m/d-Y'))) ,$title);
							
							$title = str_replace('%3',$wod->APIWod->WodHeader->Name,$title);
							
							$post['post_title'] 	= $title;
							
							
							if ('internalpublishdate'==get_option('wdfy_publishdatesetting')){
								$blogpublish = new DateTime($wod->APIWod->WodHeader->InternalPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
							}
							else {
								$blogpublish = new DateTime($wod->APIWod->WodHeader->BlogPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
							}
							$publishoffset = get_option('wdfy_publishoffset');
							$publishoffset +=0.0;
							if ($publishoffset)
							{
								$blogpublish->modify($publishoffset.' hour');
							}
										
							switch ($publish[$key])
							{
								case 'date':
									$post['post_status'] 	= 'publish'; 
									$post['post_date'] =  $blogpublish->format('Y-m-d H:i:s');	
									break;
								case 'immediate':
									$post['post_status'] 	= 'publish'; 
									unset($post['post_date']);
									break;
								default:
									$post['post_status'] 	= 'draft'; 
									$post['post_date'] =  $blogpublish->format('Y-m-d H:i:s');	
									break;
								
							}
							
							$post['post_author'] 	= $author;
							$post['post_category']	= $catids;
							//version 2.2
							$post['post_type'] 		= $posttypes[$key];
							$postid = wp_insert_post($post);
							if ($postid)
							{
								update_option('wdfy_wod_posted-'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($location).sanitize_title_with_dashes($program),$postid);
								add_post_meta($postid, '_wdfy_date',$date, true);	
								add_post_meta($postid, '_wdfy_program',$program, true);	
								add_post_meta($postid, '_wdfy_location',$location, true);		

								if ($firstimage && $dothumb)
								{
									wp_update_post(	array(
													'ID' => $firstimage, 
													'post_parent' => $postid
													)
									);
									set_post_thumbnail( $postid, $firstimage );
								}

								
							}
						}
					}					
					$datetime->modify('+1 day');
					$daycount++;
				} while ($daycount < $numdays);
			}
		}
	}
	
	add_action( 'before_delete_post', 'wdfy_delete_wod_posts' );
	function wdfy_delete_wod_posts( $postid ){
			$date = get_post_meta( $postid, '_wdfy_date', true );
			if ($date)
			{
				$program = get_post_meta( $postid, '_wdfy_program', true );
				$location = get_post_meta( $postid, '_wdfy_location', true );	
				delete_option('wdfy_wod_posted-'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($location).sanitize_title_with_dashes($program));
			}
	}	
	
	function wdfy_copy_photo( $photo_url) {
		
		if( !class_exists( 'WP_Http' ) )
		  include_once( ABSPATH . WPINC. '/class-http.php' );

		$photo = new WP_Http();
		$photo = $photo->request( $photo_url);
		if( !is_array($photo) || $photo['response']['code'] != 200 )
			return false;

		$attachment = wp_upload_bits('wpwdfy.jpg', null, $photo['body'], date("Y-m", strtotime( $photo['headers']['last-modified'] ) ) );
		if( !empty( $attachment['error'] ) )
			return false;

		$filetype = wp_check_filetype( basename( $attachment['file'] ), null );

		$postinfo = array(
			'post_mime_type'	=> $filetype['type'],
			'post_title'		=> 'WOD image',
			'post_content'		=> '',
			'post_status'		=> 'inherit',
		);
		$filename = $attachment['file'];
		$attach_id = wp_insert_attachment( $postinfo, $filename);

		if( !function_exists( 'wp_generate_attachment_data' ) )
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id,  $attach_data );
		return $attach_id;
}
	function wdfy_refresh_lookupdata()
	{
		$locations=SOS_Wodify_API::wodifyLocations(false);
		$programs=SOS_Wodify_API::wodifyPrograms(false);
		$coaches=SOS_Wodify_API::wodifyCoaches(false);
		if (is_array($locations)&&is_array($programs)&&is_array($coaches))
			return true;
		else
			return false;
	}

	function wdfy_format_plain_or_html($input)
	{
		
	
		$input= str_replace("<p> </p>","", $input);
		if (preg_match('/<\s?[^\>]*\/?\s?>/i', $input))
		{
			//filter all but basic html tags
			$original=$input;
			
			$input = strip_tags($input, '<a><p><b><i><u><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><div><table><tr><td><th>');
			$input = preg_replace('/<p\s+style="[^"]*"\s*>/i', '<p>', $input);
			$input = preg_replace('/<p\s+style=\'[^\']*\'\s*>/i', '<p>', $input);
			$input = preg_replace('/<p\s+align="[^"]*"\s*>/i', '<p>', $input);
			$input = preg_replace('/<p\s+align=\'[^\']*\'\s*>/i', '<p>', $input);
			$input = preg_replace('/<p\s+class="[^"]*"\s*>/i', '<p>', $input);
			$input = preg_replace('/<p\s+class=\'[^\']*\'\s*>/i', '<p>', $input);

			$input=nl2br($input);
			return $input;
		}
		else
		return nl2br(htmlentities($input));

		 
	}
	
?>