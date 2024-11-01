<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
global $wdfy_debug_output;
$wdfy_debug_output = '';
class SOS_Wodify_API
{	
	public static function wodifyClasses($location, $date, $programid="",$usecachedresult=true,$enddate=null){
			
		$result=null;
				
		global $wdfydebug;
		global $wdfy_debug_output;
		
		if (isset($wdfydebug)&&(true==$wdfydebug))
			$usecachedresult =false;
		
		if ($usecachedresult && !$programid) // cache only if all classes are requested		
		{
			$result = get_transient('wdfyclasses_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($location));
			if (!is_array($result))
			{
				$result=null;
			}
			// cache verwerfen wenn aus alter API
			if (!isset($result[0]->program_name))
			{
				$result=null;
			}
		}
		
		if (!$result)	
		{
			//new API
			$query = sprintf("%s|%s|%s;",'start_date' , 'eq',$date);
			$query .= sprintf("%s|%s|%s;",'location_id' , 'eq',$location);
			if ($programid) 
				$query .= sprintf("%s|%s|%s;",'program_id' , 'eq',$programid);
			
			$query = urlencode($query);


			$url = "https://api.wodify.com/v1/classes/search?q=".$query."&sort=start_date_time";  
			
			
			$args = array(                 
				"method" => 'GET',
				"headers" => array(
				"accept" => 'application/json',
				"x-api-key" => get_option('wodify_apikey'),
			));
			$result=wp_remote_request( $url, $args);
			if ( is_array( $result ) ) {
				$result = $result['body']; 
			}
		
			if (is_object($result))
			{
				return false;
			}
			$result = json_decode($result);
			
			if (!isset($result->classes))
			{
				return false;	  
			}
			$result = $result->classes;
		
			//debug
			if (isset($wdfydebug)&&(true==$wdfydebug))
				print_r($result);
			
			if (get_option('wdfy_classes_cron')!='none')
			{
				set_transient('wdfyclasses_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($location),$result,3600*24); //cache classes for one day to overcome Wodify outages cache renew will be done via cron
			}
		}
		if (!is_array($result))
		{
			$resultarray[]=$result;
			return $resultarray;
		}
		return $result; 
	}
	
	public static function getLocationId($locationname)
	{
		$locations=SOS_Wodify_API::wodifyLocations();
		
		if (is_array($locations))
		{
			foreach ($locations as $loc)
			{
				if ($loc->name == $locationname)
					return $loc->id;
			}
		}
		else
		{
			if ($locationname == $locations->name)
				return $locations->id;
			else
				return false;
		}
		return false;
	}
	
	
	public static function wodifyLocations($cache=true){
	
		$cached = get_transient('wdfy_cachedlocation');	//Cache still current -> Use cache if thats ok
		if ($cached && $cache) 
		{
			$result = get_option('wdfy_locations');
			if (is_array($result) && isset($result[0]->Name))
			{
				// Cache is for old API, force reload
				$result = false;
			}
			if ($result)
			{
				return $result;		
			}
		}
	
		//new API
		$url= 'https://api.wodify.com/v1/customers/locations';
		$args = array(                 
			"method" => 'GET',
			"headers" => array(
				"accept" => 'application/json',
				"x-api-key" => get_option('wodify_apikey')
		));
		$result=wp_remote_request( $url, $args);
		if ( is_array( $result ) ) {
			$result = $result['body']; 
		}

		if (!is_object($result))
		{
			$htmlbody = $result;
			$result = json_decode($result);
			if (isset($result->locations))  // Wodify returned correct result, update cache and return result;
			{
				$result = $result->locations;
				if (!is_array($result))
				{
					$resultarray[]=$result;
					$result = null;
					$result = $resultarray;
				}
				update_option('wdfy_locations',$result);
				set_transient('wdfy_cachedlocation',true,3600*24); // cache location list for one day		
				return $result;
			}
			else
			{
				error_log( "WODIFY API request failed: Unexpected query result code 1: ".print_r($result,true));
				if ((!$result) && $htmlbody)
				{
					error_log( "WODIFY API html response ".print_r($htmlbody,true));
				}
			}
		}
		else
		{
			error_log( "WODIFY API request failed: Unexpected query result code 2: ".print_r($result,true));
		}
			
		$result = get_option('wdfy_locations');
		if ($result && $cache)
			return $result;
		else
			return false;
		   
    }    

	public static function wodifyPrograms($cache=true){
		$cached = get_transient('wdfy_cachedprogram');	//Cache still current -> Use cache if thats ok
		
		if ($cached && $cache)
		{
			$result = get_option('wdfy_programs');
			if (is_array($result) && isset($result[0]->Name))
			{
				// Cache is for old API, force reload
				$result = false;
			}
			if (is_array($result)&&$result[0])
			{
				return $result;	
			}
		}
		
		$url= 'https://api.wodify.com/v1/programs';
		$args = array(                 
			"method" => 'GET',
			"headers" => array(
				"accept" => 'application/json',
				"x-api-key" => get_option('wodify_apikey')
		));
		$result=wp_remote_request( $url, $args);
		if ( is_array( $result ) ) {
			$result = $result['body']; 
		}
		
		if (!is_object($result))
		{
			$result = json_decode($result);
			if (isset($result->programs))  // Wodify returned correct result, update cache and return result;
			{
				$result = $result->programs;
				if (!is_array($result))
				{
					$resultarray[]=$result;
					$result = null;
					$result = $resultarray;
				}

				foreach ($result as $prog)
				{
					// if program is not active, remove it from the list
					if (!$prog->is_active)
					{
						$key = array_search($prog,$result);
						unset($result[$key]);
					}
				}

				update_option('wdfy_programs',$result);
				set_transient('wdfy_cachedprogram',true,3600*24); // cache location list for one day	

				return $result;
			}	
		}
		else // no proper result returned, check if we save an earlier result
		{
			//error_log( "WODIFY program API request failed: Unexpected query result: ".print_r($result,true));
			$result = get_option('wdfy_programs');
			if ($result && $cache)
				return $result;
			else
				return false;
		}   
    }    
	
	public static function wodifyCoaches($cache=true){
      	  
		$cached = get_transient('wdfy_cachedcoach');	//Cache still current -> Use cache if thats ok
		if ($cached && $cache)
		{
			$result = get_option('wdfy_coaches');
			if (is_array($result) && isset($result[0]->Name))
			{
				// Cache is for old API, force reload
				$result = false;
			}
			if ($result)
				return $result;		
		}
		  
		$url= 'https://api.wodify.com/v1/customers/employees';
		$args = array(                 
			"method" => 'GET',
			"headers" => array(
				"accept" => 'application/json',
				"x-api-key" => get_option('wodify_apikey')
		));
		$result=wp_remote_request( $url, $args);
		if ( is_array( $result ) ) {
			$result = $result['body']; 
		}

	
		$result = json_decode($result);
		if (isset($result->Employees))
		{
			$result = $result->Employees;
			if (!is_array($result))
			{
				$resultarray[]=$result;
				$result = null;
				$result = $resultarray;
			}
			update_option('wdfy_coaches',$result);
			set_transient('wdfy_cachedcoach',true,3600*24); // cache program list for one day

			return $result;
			
		}
		else // no proper result returned, check if we save an earlier result
		{
			$result = get_option('wdfy_coaches');
			if ($result && $cache)
				return $result;
			else
				return false;
		}         
    }    	
	
	public static function wodifyWOD($locationname,$programname,$date,$cachewod = true){
		
		$dateformat = "Y/m/d";
		
		$dateobj = DateTime::createFromFormat($dateformat, $date);
		
		if (!$dateobj)
			return false;
		$wod = null;
		global $wdfydebug;
		if (isset($wdfydebug)&&(true==$wdfydebug))
			$cachewod =false;
		
		$date = str_replace('/','-',$date);			
		if (!isset($_GET['updatewodcache']) && $cachewod)
		{
			$wod = get_transient('wdfywodn1_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($locationname).sanitize_title_with_dashes($programname));
		}

		if (!$wod)	
		{			
			$url= 'https://api.wodify.com/v1/workouts/formattedworkout?date='.urlencode($date).'&program='.urlencode($programname).'&location='.urlencode($locationname);
	
			$args = array(                 
				"method" => 'GET',
				"headers" => array(
				"accept" => 'application/json',
				"x-api-key" => get_option('wodify_apikey')
			));
			$result=wp_remote_request( $url, $args);
			
			if ( is_array( $result ) ) {
			$result = $result['body']; 
			}
				
			if (isset($wdfydebug)&&(true==$wdfydebug))
			{
				echo "$locationname,$programname,$date\n";
				print_r ($result);
			}
			
			if (is_object($result))
			{
				$wod=false;
				error_log( "WODIFY API request failed: Unexpected query result: ".print_r($result,true));
			}
			else
			{
				$result = json_decode($result);   
				if (!isset($result->APIWod))
				{
					$wod = false;	  
					//error_log( "WODIFY API request (Program: ".$programname. ", Location: ".$locationname. ", Date: ".$date. ") failed: ".$result->UserMessage);
					//error_log( 'Wodify API result: '.print_r($result,true));
				}
				else
				{
					$wod = $result;   				
				}
			}
			
			if ($cachewod || isset($_GET['updatewodcache']))
			{
				$intervalwodday = $dateobj->diff(new DateTime()); // for what day
				if ($wod)
				{
					// wod chaching
					// account for Wodify's error last edited time returned as New York time.
					//$lastedit = new DateTime($wod->APIWod->WodHeader->LastEditDateTime, new DateTimeZone(get_option('wodify_timezone')));
					$lastedit = new DateTime($wod->APIWod->WodHeader->LastEditDateTime, new DateTimeZone('America/New_York'));
					
					$intervalcreated = $lastedit->diff(new DateTime());
					
					 //created how long ago;
					if (0==$intervalcreated->d && 0 == $intervalcreated->h) // if wod modified less than an hour ago it might still change
					{
						$cachetime = -1; // 20mins		
					}
					elseif ($date==date("Y-m-d")) // todays wod
					{
						$cachetime = 60*60*24;
						
					}
					elseif ($intervalwodday->invert) // wod is for a future day, don't cache too long, might still change
					{
						$cachetime =60*60*8; //8hours
					}
					elseif (7>=$intervalwodday->d) // cache older wods only up to 7 days
					{
						 $cachetime = (8-$intervalwodday->d) * 60*60*24;
					}
					else
					{
						$cachetime = -1; //do not cache older wods
					}

					if ($cachetime>0)
						set_transient('wdfywodn1_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($locationname).sanitize_title_with_dashes($programname),$wod,$cachetime);
					else
						delete_transient('wdfywodn1_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($locationname).sanitize_title_with_dashes($programname));
				}
				else
				{
					//nowod caching					
					if ($date==date("Y-m-d")) // // no wod for today yet? look again soon
					{
						$cachetime =20*60; //20min
						
					}
					elseif ($intervalwodday->invert && 7>=$intervalwodday->d) // future day wod, will hopefully show up soon.
					{			
						if ($intervalwodday)
						$cachetime = ($intervalwodday->d ) *4 *60*60 +7200; // 4hours for each day in the future
					}
					elseif (7>=$intervalwodday->d)// past wod - / cache older wods only up to 7 days
					{
						$cachetime = (8-$intervalwodday->d) * 60*60*24;
					}
					else
					{
						$cachetime=-1;
					}
					$nowod='nowod';
					//error_log('caching nowod on day '.$date.' for '.$cachetime.' seconds');
					set_transient('wdfywodn1_'.sanitize_title_with_dashes($date).sanitize_title_with_dashes($locationname).sanitize_title_with_dashes($programname),$nowod,$cachetime); // 
				}
			}
		}		
		if ('nowod'==$wod)
			$wod =false;
		return $wod;
	}
	
	//get Wod from Wodify and format
	public static function wodifyFormatedWOD($locationname,$programname,$date,$ignorepublishdate=false,$includecomponent=array(),$excludecomponent=array(),$cachewod=true,$publishoffset='',$showimages=false){
		if (''===$publishoffset)
			$publishoffset = get_option('wdfy_publishoffset');
		if (substr($date,0,1)=='+'||substr($date,0,1)=='-')
		{
			$datetime = new DateTime();
			
			$datetime->setTimeZone(new DateTimeZone(wpwdfy_get_timezone_string()));
			$modify=$date.' day';
			$datetime->modify($modify);			
			$date = $datetime->format('Y/m/d');
		}
		$wod = 	SOS_Wodify_API::wodifyWOD($locationname,$programname,$date,$cachewod);
		
		return (SOS_Wodify_API::formatWOD($wod,$date,$ignorepublishdate,$includecomponent,$excludecomponent,$publishoffset,$showimages));
		
	}
	
	// Format Wod returned by Wodify API
	public static function formatWOD($wod,$date='',$ignorepublishdate=false,$includecomponent=array(),$excludecomponent=array(),$publishoffset='',$showimages=false, $dothumb =false){
		$output = '';
		$publish = null;
		if (''===$publishoffset)
			$publishoffset = get_option('wdfy_publishoffset');
		
		
		if ($wod)
		{			
			
			if ('internalpublishdate'==get_option('wdfy_publishdatesetting')){
				$blogpublish = new DateTime($wod->APIWod->WodHeader->InternalPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
			}
			else {
				$blogpublish = new DateTime($wod->APIWod->WodHeader->BlogPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
			}
			$publishoffset +=0.0;
			if ($publishoffset)
			{
				$blogpublish->modify($publishoffset.' hour');
			}
			
			$publish = $blogpublish->diff(new DateTime());
			/*
			$debug1='<!-- 
				apiinternaltime: '.print_r($wod->APIWod->WodHeader->InternalPublishDateTime,true).'
				blogpublish: '.print_r($blogpublish,true).'
				current: '.print_r (new DateTime(),true).'
				diff: '.print_r($publish,true).' -->';
				
			$output.=str_replace("=>",":",$debug1);
			*/
			if (!$ignorepublishdate && $publish && $publish->invert)
			{
				$output.= '<div class="soswodify_wod_notavailable">';
				$output.= __('WOD not yet available. Please come back later!','wp-wdfy-integration-of-wodify');
				$output.='</div>';
				return  $output;
			}
		}
		else {
			$output.= '<div class="soswodify_wod_notavailable">';
			$output.= __('No WOD available','wp-wdfy-integration-of-wodify');
			$output.='</div>';
			return $output;
		}

		// Filter WOD
		$output .= '<div class="soswodify_wod_wrapper">';
		
		// WOD Header
		$printsection =true;
		$wodprinted=false;
		if (is_array($excludecomponent) && (in_array('Header',$excludecomponent)))
		{
				$printsection = false;
		}
		if (!empty($includecomponent) && !in_array('Header',$includecomponent))
		{
				$printsection = false;
		}
		
		if ($printsection) {
			$wodprinted=true;
			$output .= '<div class="soswodify_wod_header">';
			$output .= $wod->APIWod->WodHeader->Name;
			$output .= '</div>'; // wod_header
			$output .= '<div class="soswodify_wod_comment" ';
			
			if ($wod->APIWod->WodHeader->Comments)
			{	
				$output.= ">";
				$output.= wdfy_format_plain_or_html($wod->APIWod->WodHeader->Comments);
			}
			else
			{
				$output .= 'style="display:none"><br>';
			}
			$output.= '</div>';// wod_comment
		}
		
		//Announcements
		$printsection =true;
		$wodprinted=false;
		if (is_array($excludecomponent) && (in_array('Announcements',$excludecomponent)))
		{
				$printsection = false;
		}
		if (!empty($includecomponent) && !in_array('Announcements',$includecomponent))
		{
				$printsection = false;
		}
		
		if ($printsection) {
			$wodprinted=true;
			$output .= '<div class="soswodify_announcements" ';
					
			if (($wod->APIWod->Announcements) && ($wod->APIWod->Announcements!=""))
			{	
				$output.= ">";
				
				$announcements= $wod->APIWod->Announcements;
				if (!is_array($announcements))
				{
					$annarray[]=$announcements;
						$announcements = null;
						$announcements = $annarray;
				}
				
				foreach ($announcements as $announcement)
				{
					if ("True"== $announcement->IsActive)
					{
						$announcefromdate = new DateTime($announcement->FromDate);
						$announcetodate = new DateTime($announcement->ToDate);
						$announcetodate->modify("+1 day");		
						$anntoday =  new DateTime($wod->APIWod->WodHeader->Date);
	
						if (($anntoday>=$announcefromdate) && ($announcetodate>= $anntoday ))
						{
							$output.= $announcement->LinkifiedMessage;
							$wodprinted =true;
						}
					}
				}
			}
			else
			{
				$output .= 'style="display:none"><br>';
			}
			$output.= '</div>';// announcements
		}
	
		$output .= '<span class="soswodify_ListRecords">';
		
		$printsection =true;
		$sectionopened= false;
		$firstimage=false;
		
		if (is_array($wod->APIWod->Components))
		{
		$components= $wod->APIWod->Components;
	
		if (!is_array($components))
		{
			$comparray[]=$components;
				$components = null;
				$components = $comparray;
		}
		
		

		foreach ($components as $componentobject)
		{
			$component = $componentobject->Component;
			if ('True'==$component->IsSection)
			{
				$printsection=true;
				if (is_array($excludecomponent) && (in_array($component->Name,$excludecomponent)))
				{
						$printsection = false;
						$output.='<!-- skipping excl. section '.sanitize_title_with_dashes($component->Name).' -->';
				}
				if (!empty($includecomponent) && !in_array($component->Name,$includecomponent))
				{
					$printsection = false;
					$output.='<!-- skipping not incl. section '.sanitize_title_with_dashes($component->Name).' -->';
				}
				if ($printsection) {
					if ($sectionopened) {
						$output.= "</div><!-- /section -->";
						$sectionopened = false;
					}
					$output.= '<div class="sos_wodify_section_'.sanitize_title_with_dashes($component->Name).'"><!-- WOD Component ID '. $component->WODComponentId .'--><div class="soswodify_section_title">';
					$sectionopened = true;
					$output.= $component->Name;
					$output.= '</div>';
					
					$output .= '<div class="soswodify_section_comment"';
		
					if ($component->Comments)
					{
						$output.=">";
						
						$output.= wdfy_format_plain_or_html($component->Comments);
						
						
						
					}
					else
					{
						$output .= 'style="display:none"><br>';
					}
					$output.= '</div>';// wod_comment
				
					$wodprinted=true;
				}
			}
			else
			{
				// include featured images
				if ('Image'== $component->Name)
				{
					if($showimages || $dothumb)
					{
						$wodprinted=true;
						$attachid = get_option('wdfy_image_attachid'.$component->WODComponentId);					
						$image_url="";
						
						if ($attachid)
						{
							$image_url = wp_get_attachment_url( $attachid );
						}
						
						if (!$image_url  && (($dothumb && !$firstimage) || 'local' == get_option('wdfy_local_images')))
						{							
							$attachid=wdfy_copy_photo($component->ImageURL);
							update_option('wdfy_image_attachid'.$component->WODComponentId,$attachid);
							$image_url = wp_get_attachment_url( $attachid );
						}
						$firstimage = $attachid;
						
						if (!$image_url)
						{
							$image_url=$component->ImageURL;
							$attachid = $component->WODComponentId;
						}
						
						if ($showimages)
						{
							$output.='<figure class="wp-caption-text"><a href="'.$image_url.'">';
							$output.='<img class="soswodify_image size-full wp-image-'.$attachid.'" src="'.$image_url.'"></a>';
							if ($component->Comments)
							{
								$output .= '<figcaption soswodify_image_caption wp-caption-text>'.$component->Comments.'</figcaption>';
							}
							$output.='</figure>';		
						}
					}
				}
				elseif ($printsection)
				{
					$wodprinted=true;
					$output .= '<div class="soswodify_component_show_wrapper"><div class="soswodify_component_name">';
					$output .= $component->Name;					
					
					if (isset($component->PerformanceResultTypeName))
						$resulttype= $component->PerformanceResultTypeName;
					elseif (isset($component->ResultTypeName))
						$resulttype= $component->ResultTypeName;
					else
						$resulttype=null;
					
					if ($component->RepScheme)
					{
						$output .=" (".$component->RepScheme.")";
					}
					elseif ($resulttype)
					{
						if ("No Measure" == $resulttype)
						{
							
						}
						elseif ("Each Round" == $resulttype && absint($component->Rounds)>0 ) {
							$output .= " (".$component->Rounds." Rounds for reps)";
						}
						else	{		
							$output .=" (".$resulttype.")";
						}
					}
					
					$output .= '</div>'; // component name
					$output .= '<div class="soswodify_component_wrapper">';
					
					$text = wdfy_format_plain_or_html($component->Description);
					/*
					$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
					$text = preg_replace($pattern, "", $text);//$1 for url filtered
					*/
					$output .= $text;
					
					if ($component->Comments)
					{
						$output .= '<div class="soswodify_component_comment">';
						$text = wdfy_format_plain_or_html($component->Comments);
						/*
						$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
						$text = preg_replace($pattern, "", $text);
						*/
						$output .= $text;
						$output .= '</div>';
					}
					$output .= '</div>'; // component wrapper
					$output .= '</div>'; // component show wrapper
				}
			}
		}
		if ($sectionopened) {
			$output.= "</div><!-- /section -->";
			$sectionopened = false;
		}	
		}
		if (!$wodprinted)
		{
			$output.= __('No WOD available.','wp-wdfy-integration-of-wodify');
		}
		$output .= '</span>'; //ListRecords
		$output .= '</div>'; // sos_wodify_wod_wrapper
		if ($dothumb)
			return(array($firstimage,$output));
		else
			return $output;
  
	  
    }

	//RETURN WOD Output for ALEXA
	public static function speakWOD($dateparam, $program,$password,$speakmode){
		$output = '';
		$publish = null;
		$ignorepublishdate=false;
		$includecomponent=array();
		$excludecomponent=array();
		$publishoffset='';
		
		$wdfy_alexa_magicnumber  = get_option('wdfy_alexa_magicnumber');
		
		if ( 0!=$wdfy_alexa_magicnumber && '0' != $wdfy_alexa_magicnumber && $wdfy_alexa_magicnumber != $password) {
			
			$output.= __('Sorry, the magical number is wrong..','wp-wdfy-integration-of-wodify');
			if ($speakmode == 0) // convert line breaks to pauses for Alexa
			{
				$output = preg_replace('/\s\s+/', '<break time="200ms"/>',$output);
				$output = str_replace('\n', '<break time="200ms"/>',$output);
			}
			return  array ('speakOut' => $output);
		}
				
		$date=str_replace('-','/',$dateparam);

		$programname="";
		
		if (!$program)
		{
			$programname = get_option('wodify_program');
			$output.=sprintf(__('Sorry, I could not find a suitable program, but I will try %s for you','wp-wdfy-integration-of-wodify'),$programname).".\n\n";
		}
		else
		{
			$wdfy_api_program_short   	= get_option('wdfy_api_program_short');
			if (!$wdfy_api_program_short)
				$wdfy_api_program_short = array();
			 
			$wdfy_api_program   	= get_option('wdfy_api_program');
			if (!$wdfy_api_program)
				$wdfy_api_program = array();
				
			$programname ='';
			
			foreach ($wdfy_api_program_short as $program_key => $program_short)
			{
				if ($program_short == $program)
				{
					$programname = $wdfy_api_program[$program_key];
				}
			}
		}
		
		if ( !$programname) {
			
			$programname = get_option('wodify_program');
			$output.=sprintf(__('Sorry, I could not find a suitable program, but I will try %s for you','wp-wdfy-integration-of-wodify'),$programname).".\n\n";
		}
		
			
		$publishoffset = get_option('wdfy_publishoffset');
		$locationname = get_option('wodify_location');
		$wod = 	SOS_Wodify_API::wodifyWOD($locationname,$programname,$date,true);
			
				
		if ($wod)
		{			
			if ('internalpublishdate'==get_option('wdfy_publishdatesetting')){
				$blogpublish = new DateTime($wod->APIWod->WodHeader->InternalPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
			}
			else {
				$blogpublish = new DateTime($wod->APIWod->WodHeader->BlogPublishDateTime, new DateTimeZone(get_option('wodify_timezone')));
			}
			$publishoffset +=0.0;
			if ($publishoffset)
			{
				$blogpublish->modify($publishoffset.' hour');
			}
			
			$publish = $blogpublish->diff(new DateTime());
		
			
			if (!$ignorepublishdate && $publish && $publish->invert)
			{
				$output.= __('The workout is not yet available. Please ask me again later.','wp-wdfy-integration-of-wodify');
				if ($speakmode == 0) // convert line breaks to pauses for Alexa
				{
					$output = preg_replace('/\s\s+/', '<break time="200ms"/>',$output);
					$output = str_replace('\n', '<break time="200ms"/>',$output);
				}
				return  array ('speakOut' => $output);
			}
		}
		else {
			
			$output.= __('Sorry, no workout is available for that day.','wp-wdfy-integration-of-wodify');
			if ($speakmode == 0) // convert line breaks to pauses for Alexa
			{
				$output = preg_replace('/\s\s+/', '<break time="200ms"/>',$output);
				$output = str_replace('\n', '<break time="200ms"/>',$output);
			}
			return  array ('speakOut' => $output);
		}

		
		// WOD Header
		//TODO printsection "header" Konfigurieren und sprache anpassen
		/*
		$printsection =true;
		$wodprinted=false;
		if (is_array($excludecomponent) && (in_array('Header',$excludecomponent)))
		{
				$printsection = false;
		}
		if (!empty($includecomponent) && !in_array('Header',$includecomponent))
		{
				$printsection = false;
		}
		$printsection=true;
		if ($printsection) {
		
			$output .= $wod->APIWod->WodHeader->Name;
				
			if ($wod->APIWod->WodHeader->Comments)
			{	
				$output.= $wod->APIWod->WodHeader->Comments;
			}
			else
			{
				$output .= '';
			}
		}
		*/
		
		//Announcements
		//TODO Announcement konfigurieren (eigener Alexa-Intent!) und anpssen
		/*
		$printsection =false;
		$wodprinted=false;
		$hasannouncement=false;
		
		if (is_array($excludecomponent) && (in_array('Announcements',$excludecomponent)))
		{
				$printsection = false;
		}
		if (!empty($includecomponent) && !in_array('Announcements',$includecomponent))
		{
				$printsection = false;
		}	
		if ($printsection) {	
			if (($wod->APIWod->Announcements) && ($wod->APIWod->Announcements!=""))
			{					
				$announcements= $wod->APIWod->Announcements->Announcement;
				if (!is_array($announcements))
				{
					$annarray[]=$announcements;
						$announcements = null;
						$announcements = $annarray;
				}
				
				foreach ($announcements as $announcement)
				{
					if ("True"== $announcement->IsActive)
					{
						$announcefromdate = new DateTime($announcement->FromDate);
						$announcetodate = new DateTime($announcement->ToDate);
						$announcetodate->modify("+1 day");		
						$anntoday =  new DateTime($wod->APIWod->WodHeader->Date);
	
						if (($anntoday>=$announcefromdate) && ($announcetodate>= $anntoday ))
						{
							$output.= $announcement->LinkifiedMessage;
							$wodprinted =true;
						}
					}
				}
			}
		}
		elseif (($wod->APIWod->Announcements) && ($wod->APIWod->Announcements!=""))
		{
			$hasannouncement=true;
		}
		*/
		
		//WOD Components		
		$printsection =true;
		$sectionopened= false;
		$componentcount=0;
		$components= $wod->APIWod->Components->Component;
		if (!is_array($components))
		{
			$comparray[]=$components;
				$components = null;
				$components = $comparray;
		}
		 
		foreach ($components as $component)
		{
			if ('True'==$component->IsSection)
			{
				$printsection=true;
				if (is_array($excludecomponent) && (in_array($component->Name,$excludecomponent)))
				{
						$printsection = false;
				}
				if (!empty($includecomponent) && !in_array($component->Name,$includecomponent))
				{
					$printsection = false;
				}
				if ($printsection) {
					if ($sectionopened) {
						$sectionopened = false;
					}
					$sectionopened = true;
					
					$output .="\n";
					$output.= $component->Name;
					$output .=":\n";
					if ($component->Comments)
					{
						$output .="\n"; 
						$output.= $component->Comments;
						$output .="\n";
					}
					$wodprinted=true;
				}
			}
			else
			{
				if ($printsection)
				{
					$wodprinted=true;
					$output .="\n\n";
					$output .= $component->Name;
					$output .=' ';
					
					if (isset($component->PerformanceResultTypeName))
						$resulttype= $component->PerformanceResultTypeName;
					elseif (isset($component->ResultTypeName))
						$resulttype= $component->ResultTypeName;
					else
						$resulttype=null;
					
					if ($component->RepScheme)
					{
						$output .= $component->RepScheme.". ";
					}
					elseif ($resulttype)
					{
						if ("No Measure" == $resulttype)
						{
							$output.= __('No measure. ','wp-wdfy-integration-of-wodify');
							$output .="\n";
						}
						elseif ("Each Round" == $resulttype && absint($component->Rounds)>0 ) {
							$output .= sprintf(__('%s Rounds for Reps','wp-wdfy-integration-of-wodify'),$component->Rounds);
						}
						else	{		
							$output .=sprintf(__("For %s",'wp-wdfy-integration-of-wodify',"for example for time, for max reps,..."),$resulttype);
						}
					}
					
					$output .="\n";
					$text = $component->Description;
					$output .= $text;
					
					if ($component->Comments)
					{	
						$text = $component->Comments;
						$output .="\n"; 
						$output .= $text;
						$output .="\n"; 
						
					}
				}
			}
		}
		if ($sectionopened) {
			$sectionopened = false;
		}		
		if (!$wodprinted)
		{
			$output.= __('Sorry, no workout is available for that day.','wp-wdfy-integration-of-wodify');
		}
		
		/*TODO
		if ($hasannouncement)
		{
			$output .='\n'; 
			$output.="Es gibt übrigens Neuigkeiten.";
		}
		*/	
		
		$output = str_replace('<p>',' ',$output);
		$output = str_replace('</p>','\n',$output);

		if ($speakmode == 0) // convert line breaks to pauses for Alexa
		{
			$output = preg_replace('/\s\s+/', '<break time="200ms"/>',$output);
		    $output = str_replace('\n', '<break time="200ms"/>',$output);
			
			
			$output = str_replace(':-)',' ',$output);
			$output = str_replace(';-)',' ',$output);
			$output = str_replace('-',' ',$output);
			
			$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
			$output = preg_replace($pattern, "", $output);//$1 for url filtered
		}
		else
		{
			$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
			$output = preg_replace($pattern, "", $output);//$1 for url filtered
			
			$output = str_replace(':-)',' ',$output);
			$output = str_replace(';-)',' ',$output);
			$output = str_replace('-',' ',$output);			
		}
		
		$result = array ('speakOut' => $output); 
		return $result;
  
	  
    }   	
	




   	
}