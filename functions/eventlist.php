<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function wpwdfy_get_event_list($locations,$inclcomp,$exclcomp,$columns,$schemaorg,$numdays,$dateformat,$showheader,$startperiod,$nummonths) {
	global $wp_locale;
	$docoaches=true;
	if (!$numdays)
		$numdays=1;
	$text="";
	   
		   
	if (get_option('wdfy_classes_cron')=='none')
	{
		$usecache=0;
	}
	else
	{
		$usecache = 1; 
	}

	$lines			= isset( $columns) ? $columns : array('program','date','coach');
	$futuredays = $numdays;
	if ($nummonths==0)
		$nummonths = -1;
	$futuremonths=$nummonths;
	if ($futuremonths>0)
		$futuredays=-1;

	$output="";
	$output .= '<div class="wdfy_upcoming_events">';
	$output.='<table class="wdfy_upcoming_events">';
	if ($showheader)
	{
		$output.='<tr>';
		foreach ($lines as $line)
		{
			switch ($line)
			{
				case 'location':
					$output.= '<th class="wdfy_eventlisthead_locationname">';
					$output.= __('Location','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
				case 'address':	
					$output.= '<th class="wdfy_eventlisthead_locationaddress">';
					$output.= __('Address','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
				case 'name':	
						$output.= '<th class="wdfy_eventlisthead_name">';
						$output.= __('Name','wp-wdfy-integration-of-wodify');
						$output.='</th>';
						break;	
				case 'program':	
					$output.= '<th class="wdfy_eventlisthead_program">';
					$output.= __('Event','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
				case 'date':
					$output.= '<th class="wdfy_eventlisthead_date">';
					$output.= __('Date','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
				case 'time':
					$output.= '<th class="wdfy_eventlisthead_time">';
					$output.= __('Time','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
				case 'coach':			
					$output.= '<th class="wdfy_eventlisthead_coach">';
					$output.= __('Coach','wp-wdfy-integration-of-wodify');
					$output.='</th>';
					break;
			}
		}
		$output.='</tr>';
	}
	$countrows = 0;
	$datetime = new DateTime();  
	if ($startperiod)
		$datetime->modify('first day of next month');
	$datetime->setTimeZone(new DateTimeZone(wpwdfy_get_timezone_string()));
	$prg_inactive=get_option('wdfy_prg_inactive');
	$coach_url=get_option('wdfy_coach_url');
	$prg_images = get_option('wdfy_prg_image');
	if (!$coach_url)
		$coach_url = array();
	
	
	if (!$prg_inactive)
			$prg_inactive = array();
	do
	{
		foreach ($locations as $location)
		
		{
			$alllocs=SOS_Wodify_API::wodifyLocations();
			if (is_array($alllocs))
			{
				foreach ($alllocs as $loc)
				{
					if ($loc->name == $location )
						break(1);
				}
			}
			else
			{
				$loc=$alllocs;			
			}
			$locationid=$loc->id;
			
			$classes =SOS_Wodify_API::wodifyClasses( $locationid, $datetime->format('Y/m/d') ,null,$usecache);
			
			if (is_array($classes))
			{
			foreach ($classes as $class)
			{
				if (array_key_exists(sanitize_title_with_dashes($class->program_name),$prg_inactive))
				{
					$programinactive=$prg_inactive[sanitize_title_with_dashes($class->program_name)];
				}
				else
				{
					$programinactive=false;
				}
				
				if (is_array($exclcomp) && (in_array($class->program_name,$exclcomp)))
				{
					$programinactive = true;
				}
				if (!empty($inclcomp) && !in_array($class->program_name,$inclcomp))
				{
					$programinactive = true;
				}
				
						
				if (!$programinactive)
				{
					$docoaches = true;
					$coachesstr="";
					
					$enddatetime = new DateTime($class->end_date_time, new DateTimeZone(get_option('wodify_timezone')));
					$startdatetime = new DateTime($class->start_date_time, new DateTimeZone(get_option('wodify_timezone')));

					if ($docoaches && $enddatetime->diff(new DateTime())->invert)
					{
						$output .= '<tr class="wdfy_evenlist_container">';			
						foreach ($lines as $line)	
						{
							switch ($line)
							{
								case 'location':			
									$output .= '<td class="wdfy_eventlist_locationname">';
									$output .= $loc->name;
									$output.= "</td>";								
									break;
								case 'address':			
									$output .= '<td class="wdfy_eventlist_locationaddress">';
									if ($loc->street_address_1)
										$output .= $loc->street_address_1.", ";
									if ($loc->street_address_2)
										$output .= $loc->street_address_2.", ";
									$output .= $loc->city;
									$output.= "</td>";								
									break;
								case 'name':			
										$output .= '<td class="wdfy_eventlist_name">';
										$output .= $class->name;
										$output.= "</td>";								
										break;
								case 'program':			
									$output .= '<td class="wdfy_eventlist_programname">';
									$output .= $class->program_name;
									$output.= "</td>";								
									break;
								case 'date':
									$output .= '<td class="wdfy_eventlist_date">';
									$output .= $startdatetime->format($dateformat);
									$output.= '</td>';
									break;
								case 'time':
									$output.= '<td class="wdfy_eventlist_hours">';
									$output .= $startdatetime->format(get_option('time_format'))."-".$enddatetime->format(get_option('time_format'));
									$output.= "</td>";
									break;
								case 'coach':
									$output .= '<td class="wdfy_eventlist_coach">';
									if ($class->coaches)
									{
										$coachesstr="";
										$coaches = $class->coaches;
										if (!is_array($coaches))
										{
											$coaches = null;
											$coaches[] = $class->coaches;
										}
										$coachnum = 0;
										foreach ($coaches as $coach)
										{													
											if ($coachnum) 
											{
												$output.=", ";
												$coachesstr.=", ";
											}										
											if (array_key_exists(sanitize_title_with_dashes($coach->coach),$coach_url))
											{
												$curl=$coach_url[sanitize_title_with_dashes($coach->coach)];
											}
											else
											{
												$curl='';
											}	
											if ($curl)										
												$output.='<a href="'.$curl.'">';
											$output.= $coach->coach;
											$coachesstr.=$coach->coach;
											if ($curl)
												$output.='</a>';
											$coachnum++;
										}	
									}
									$output.= "</td>";
									default: 
										break;
							}
							$output.=" ";
							
						}
						
						//schema.org
						if ($schemaorg)
						{
							$script='<script type="application/ld+json">{"@context": "http://schema.org/","@type": "Event","name": "';
							$script.= $class->program_name;
							$script.='","startDate": "';
							$script.=$startdatetime->format(DATE_ATOM);
							$script.='",';
							$script.='"endDate": "';
							$script.=$enddatetime->format(DATE_ATOM);
							$script.='","url": "';
							global $wp;
							$script.=home_url(add_query_arg(array(), $wp->request));
							$script.='",';
														
							if ($coachesstr)
							{
								$script.='"performer": {"@type": "Person","name": "';
								$script.=$coachesstr.'"},';
							}
							
											
							//Start Location
							$script.='"location": {"@type": "ExerciseGym","name": "';
							$script.=$loc->name;
							
							$script.='","address": "';
							if ($loc->street_address_1)
								$script.= $loc->street_address_1.", ";
							if ($loc->street_address_2)
								$script .= $loc->street_address_2.", ";
							$script .= $loc->city;
							$script.=', ';
							//TODO
							$script.=$loc->country_id;
							
							$script.='","image": "';
							$prg_image='';
							
							if (!$prg_image)
								$prg_image = get_option('wdfy_schema_siteimage');
							if (!$prg_image)
								$prg_image = get_custom_logo();
							if (!$prg_image)
								$prg_image.=plugins_url('../img/calendar.png', __FILE__);
							$script.= $prg_image.'"';
							
							$script.=',"priceRange": "';
							$script.= get_option('wdfy_schema_pricerange').'"';		
							
							$script.=',"telephone": "';
							$script.= get_option('wdfy_schema_phone').'"';	
							
							$script.='}';
							// End Location
							
							$script.=',"description": "';
							$script.=$class->program_name;
							$script.='"';
							
						
						
						
							$script.=',"image": "';
							
							$prg_image='';
							if (array_key_exists(sanitize_title_with_dashes($class->program_name),$prg_images))
								$prg_image=$prg_images[sanitize_title_with_dashes($class->program_name)];							
							if (!$prg_image)
								$prg_image = get_option('wdfy_schema_siteimage');
							if (!$prg_image)
								$prg_image = get_custom_logo();
							if (!$prg_image)
								$prg_image.=plugins_url('../img/calendar.png', __FILE__);
							$script.= $prg_image.'"';				
						
							// Finally custom json
							if (get_option('wdfy_schema_addjson'))
								$script.=", ".get_option('wdfy_schema_addjson');	
							
							$script.="}</script>\n";
							wpwdfy_require_script($script);
						}
							
						$output .= "</tr>";
						$countrows++;
					}
				}			
			}}
	
			$futuredays--;
			$datetime->modify('+1 day');
			if ($futuremonths && $datetime->format('j')==1)
				$futuremonths--;
			
		}		
	} while ($futuredays>-1 || $futuremonths>0);
	$output.="</table>";
	
	if (!$countrows)
	{
			$output.=__('No upcoming events.','wp-wdfy-integration-of-wodify');
	}
	$output.="</div>";
	
	
	return $output;
	
}
	
?>