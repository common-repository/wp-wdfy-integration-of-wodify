<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if ( ! class_exists( 'SOS_Wodify_Classes_Widget' ) ):

class SOS_Wodify_Classes_Widget extends WP_Widget {

	function __construct(){
		$widget_ops  = array( 'description' => __( "Display upcoming classes from Wodify", 'wp-wdfy-integration-of-wodify' ) );
		$control_ops = array(/*'width' => 300, 'height' => 400*/ );
		parent::__construct( 'SOS_Wodify_Classes_Widget', $name = __( "Wodify Upcoming Classes", 'wp-wdfy-integration-of-wodify' ), $widget_ops, $control_ops );
	}

	public function widget( $args, $instance ) {
		global $wp_locale;
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}
		
		wp_enqueue_script("wdfy-classesscroll", plugins_url( '../js/classesscroll.js', __FILE__), array(), wdfy_plugin_get_version(), true);
		wp_enqueue_script("jquery-carouFredSel", plugins_url('../js/jquery.carouFredSel-6.2.1-packed.js', __FILE__), array("jquery"), '6.2.1', true);
		
		if (!is_array($instance))
			$instance = array();
		
		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( "Upcoming classes",'wp-wdfy-integration-of-wodify' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		
		$location = ( ! empty( $instance['location'] ) ) ? $instance['location'] : get_option('wodify_location');
		if ( ! $location )
			$location = get_option('wodify_location');
		
		$locationid = ( ! empty( $instance['locationid'] ) ) ? $instance['locationid'] : SOS_Wodify_API::getLocationId($location);

		$showreservations = true;
		
		$numdays = ( ! empty( $instance['numdays'] ) ) ? $instance['numdays'] : 2;
		$numclasses			= isset( $instance['numclasses'] ) ? absint( $instance['numclasses'] ) : 3;
		$boxheight			= isset( $instance['boxheight'] ) ? absint( $instance['boxheight'] ) : 40;
		$futuredays = $numdays;
	
		$lines			= isset( $instance['lines'] ) ? $instance['lines'] : array('program','time','empty','reservation','classname','coach');
		$excludecomponents = ( ! empty( $instance['excludecomponents'] ) ) ? $instance['excludecomponents'] : '';
		if ( ! $excludecomponents )
			$excludecomponents = '';

		$includecomponents = ( ! empty( $instance['includecomponents'] ) ) ? $instance['includecomponents'] : '';
		if ( ! $includecomponents )
			$includecomponents = '';
		
		$filtercoach = ( ! empty( $instance['coach'] ) ) ? $instance['coach'] : '';
		if ( ! $filtercoach )
			$filtercoach = '';
		
		$inclcomp =array_filter(array_map('trim',explode(',',$includecomponents)));
		$exclcomp =array_filter(array_map('trim',explode(',',$excludecomponents)));
		
		if (get_option('wdfy_classes_cron')=='none')
		{
			$usecache=0;
		}
		else
		{
			if (array_key_exists('usecache',$instance))
				$usecache = $instance['usecache'];
			else
				$usecache = 1; 
		
			if ( $usecache!==0 && $usecache !==1)
				$usecache = 1;		
		}
		
		if (array_key_exists('autoscroll',$instance))
			$autoscroll = $instance['autoscroll'];
		else
			$autoscroll = 1;
		if ( $autoscroll!==0 && $autoscroll !==1)
			$autoscroll = 1;		
		
		$prg_bgcolors=get_option('wdfy_prg_bgcolor');
		$prg_inactive=get_option('wdfy_prg_inactive');
		$prg_url=get_option('wdfy_prg_url');
		if (!$prg_url)
			$prg_url = array();
		
		if (!$prg_bgcolors)
			$prg_bgcolors = array();
		if (!$prg_inactive)
			$prg_inactive = array();
	
		$coach_url=get_option('wdfy_coach_url');
		if (!$coach_url)
			$coach_url = array();
		
		echo $args['before_widget']; 
	
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} 		
		$output="";
		$output .= '<div class="wdfy_upcoming_classes_wrapper">';
		$output.='<ul class="wdfy_upcoming_classes clearfix autoscroll-'.$autoscroll;
		$output.=' wdfynumclasses-'.$numclasses;
		$output.= '">';
		$countrows = 0;
		$datetime = new DateTime();
		$datetime->setTimeZone(new DateTimeZone(wpwdfy_get_timezone_string()));
		do
		{	
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
				
				if (array_key_exists(sanitize_title_with_dashes($class->program_name),$prg_url))
				{
					$programurl=$prg_url[sanitize_title_with_dashes($class->program_name)];
				}
				else
				{
					$programurl='';
				}		
							
				
				if (!$programinactive)
				{
					$docoaches = true;
					if (''!=$filtercoach)
					{
						$docoaches = false;
						if ($class->coaches)
						{
							
							$coaches = $class->coaches;
							if (!is_array($coaches))
							{
								$coaches = null;
								$coaches[] = $class->coaches;
							}
							foreach ($coaches as $coach) {
								if ($coach->coach == $filtercoach)
								{
									$docoaches = true;
									break 1;
								}	
							}
						}
					}
					$enddatetime = new DateTime($class->end_date_time, new DateTimeZone(get_option('wodify_timezone')));
					$startdatetime = new DateTime($class->start_date_time, new DateTimeZone(get_option('wodify_timezone')));
			
					if ($docoaches && $enddatetime->diff(new DateTime())->invert)
					{
								$output .= '<li>';
								if (array_key_exists(sanitize_title_with_dashes($class->program_name),$prg_bgcolors))
									$classcolor=$prg_bgcolors[sanitize_title_with_dashes($class->program_name)];
								else
									$classcolor = null;
								if (!$classcolor)
									$classcolor = '#'.$class->calendar_color; 
							
								$classtextcolor = '343434'; //TODO customize color
								$classbackground = 'ffffff'; //TODO customize color

								if (get_option('wodify_debugmode')==true)
									$output.= '<!-- WDFY-DBG: '.print_r($class,true). '-->';
								$output .= '<span class="wdfy_upcoming_classes_container" style=" height: '.$boxheight.'px; border-left-color: '.$classcolor.'; color: #'.$classtextcolor.';" onmouseover="this.style.backgroundColor=\''.$classcolor.'\'; this.style.borderColor=\''.$classcolor.'\'; this.style.color=\'#'.$classbackground.'\';this.style.height=\'auto\';"  onmouseout="this.style.backgroundColor=\'#'.$classbackground.'\';this.style.borderColor=\'#EFEFEF\';this.style.borderLeftColor=\''.$classcolor.'\';this.style.height=\''.$boxheight.'px\'; this.style.color=\'#'.$classtextcolor.'\';">';
								foreach ($lines as $line)				
								{
									switch ($line)
									{
										case 'program':
											
											if ($programurl)
											{
												$output.='<a href="'.$programurl.'">';
											}
											if ($class->is_cancelled)
											{
												$output .= '<span class="wdfy_upcoming_classes_programname wdfy_cancelled">('.__('Cancelled','wp-wdfy-integration-of-wodify').') ';
											}
											else
											{
												$output .= '<span class="wdfy_upcoming_classes_programname">';
											}
											$output .= $class->program_name;
											$output.= "</span>";
											if ($programurl)
											{
												$output.='</a>';
											}
											break;
										case 'time':
											if ($class->is_cancelled)
											{
												$output .= '<span class="wdfy_calendar_icon"></span><span class="wdfy_upcoming_classes_hours wdfy_clear wdfy_cancelled">';
											}
											else
											{
												$output .= '<span class="wdfy_calendar_icon"></span><span class="wdfy_upcoming_classes_hours wdfy_clear">';
											}
											$weekday = (int) $enddatetime->format('N');
											if (7==$weekday)
													$weekday=0;
											$dayname= $wp_locale->get_weekday($weekday);
											$output .= $dayname. ", ";
											$output .= $startdatetime->format(get_option('time_format'))." - ".$enddatetime->format(get_option('time_format'));
											$output.= "</span>";
											break;
										case 'reservation':
											if ('true'==$showreservations)
											{
												if ($class->is_cancelled)
												{ 
													$output .= '<span class="wdfy_upcoming_classes_reservations wdfy_clear">&nbsp;</span>';
												}
												else
												{
													$output .= '<span class="wdfy_upcoming_classes_reservations wdfy_clear">';
													$available = $class->available;
													if ($available<0)
														$available = 0;
													$classlimit = $class->class_limit;
													if ('0'==$classlimit)
													{
														$output .= __('Open','wp-wdfy-integration-of-wodify');
													}
													else
													{
														$output .= $classlimit-$available.'/'.$class->class_limit. ' '.__('spaces reserved.','wp-wdfy-integration-of-wodify');
													}
													$output.= "</span>";
												}
											}
											break;
										case 'reservationbar':
												if ($class->is_cancelled)
												{ 
													$output .= '<span class="wdfy_upcoming_classes_reservations wdfy_clear">&nbsp;</span>';
												}
												else
												{
													$output .= '<span class="wdfy_upcoming_classes_reservationbar wdfy_clear">';
													$available = $class->available;
													if ($available<0)
														$available = 0;
													$classlimit = $class->class_limit;
													// if cancelled set class limit 0
													if ($class->is_cancelled)
													{
														$classlimit = 0;
													}
													if ('0'==$classlimit)
													{
														$output .= '<div class="wdfy_progress_container">';
														$output .= '<div class="wdfy_progress_bar_green" style="width: 0%;">';
														$output .= '</div>';
														$output .= '</div>';
													}
													else
													{
														$limit = 0 + $class->class_limit;
														$percent = 100-$available/$limit*100;
														$output .= '<div class="wdfy_progress_container">';
														if ($available/$limit >=.5)
															$output .= '<div class="wdfy_progress_bar_green" style="width: '.$percent.'%;">';
														elseif ($available>0)
															$output .= '<div class="wdfy_progress_bar_yellow" style="width: '.$percent.'%;">';
														else
															$output .= '<div class="wdfy_progress_bar_red" style="width: '.$percent.'%;">';
														$output .= '</div>';
														$output .= '</div>';
													}
													$output.= "</span>";
												}
											break;
										case 'classname':								
											// is cancelled?
											if ($class->is_cancelled)
											{
												$output .= '<span class="wdfy_upcoming_classes_classname wdfy_cancelled">('.__('Cancelled','wp-wdfy-integration-of-wodify').') ';
											}
											else
											{
												$output .= '<span class="wdfy_upcoming_classes_classname wdfy_clear">';
											}
											$output .= $class->name;
											$output.= "</span>";
											break;
										case 'coach':
											if ($class->coaches)
											{
												$output .= '<span class="wdfy_upcoming_classes_coach wdfy_clear">';

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
													if ($curl)
														$output.='</a>';
														
													$coachnum++;
												
												}
												
												$output.= "</span>";
											}
										case 'empty':$output.="<br />"; break;
										case 'unused': break;
										default: 
											break;
									}	
								}
								$output.=  '</span>';
								
						$output .= "</li>";
						$countrows++;
					}
				}			
			}}
			$futuredays--;
			$datetime->modify('+1 day');
			
		} while ($futuredays>-1);
		$output.="</ul>";
		$output.='<div class="wdfy_upcoming_classes_controls"><a href="#" id="upcoming_classes_prev" style="display: block;"><span class="wdfy_upcoming_classes_prev_arrow"></span></a><a href="#" id="upcoming_classes_next" style="display: block;"><span class="wdfy_upcoming_classes_next_arrow"></span></a></div>';
		if (!$countrows)
		{
				_e('No upcoming classes.','wp-wdfy-integration-of-wodify');
		}
		$output.="</div>";
		echo $output;
		echo $args['after_widget']; 
	} 
	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['location'] =  sanitize_text_field( $new_instance['location'] );
		$instance['locationid'] =   SOS_Wodify_API::getLocationId($instance['location']);
		$instance['numdays'] =   absint( $new_instance['numdays'] );
		$instance['numclasses'] =   absint( $new_instance['numclasses'] );
		$instance['numclasses']	= isset( $instance['numclasses'] ) ? absint( $instance['numclasses'] ) : 3;
		$instance['boxheight'] =   absint( $new_instance['boxheight'] );
		$instance['boxheight']	= isset( $instance['boxheight'] ) ? absint( $instance['boxheight'] ) : 40;
		$instance['lines'] =  $new_instance['lines'];
		$instance['excludecomponents'] =  sanitize_text_field( $new_instance['excludecomponents'] );
		$instance['includecomponents'] =  sanitize_text_field( $new_instance['includecomponents'] );		
		$instance['usecache'] =  absint($new_instance['usecache']);
		$instance['autoscroll'] =  absint($new_instance['autoscroll']);
		$instance['coach'] =  sanitize_text_field( $new_instance['coach'] );
		return $instance;
	}

	public function form( $instance ) {
		$title     			= isset( $instance['title'] ) ? esc_attr( $instance['title'] ) :  __( "Upcoming classes",'wp-wdfy-integration-of-wodify');
		$location 			= isset( $instance['location'] ) ? esc_attr( $instance['location'] ) : get_option('wodify_location');
		$locationid 		= isset( $instance['locationid'] ) ? esc_attr( $instance['locationid'] ) : SOS_Wodify_API::getLocationId($location);
		$numdays  			= isset( $instance['numdays'] ) ?  esc_attr( $instance['numdays'] ) : '2';
		$numdays 			= absint($numdays);
		$numclasses			= isset( $instance['numclasses'] ) ? absint( $instance['numclasses'] ) : 3;
		$boxheight			= isset( $instance['boxheight'] ) ? absint( $instance['boxheight'] ) : 64;
		$lines				= isset( $instance['lines'] ) ? $instance['lines'] : array('program','time','reservationbar','reservation','classname','coach');
		$excludecomponents  = isset( $instance['excludecomponents'] ) ? esc_attr( $instance['excludecomponents'] ) : '';
		$includecomponents  = isset( $instance['includecomponents'] ) ? esc_attr( $instance['includecomponents'] ) : '';	
		$usecache  			= isset( $instance['usecache'] ) ? esc_attr( $instance['usecache'] ) : 1;		
		$autoscroll			= isset( $instance['autoscroll'] ) ? esc_attr( $instance['autoscroll'] ) : 1;	
		$coach 				= isset( $instance['coach'] ) ? esc_attr( $instance['coach'] ) : '';
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:','wp-wdfy-integration-of-wodify' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<?php wpwdfy_widget_lookupfield_location($this,$location); ?>
		
		<?php wpwdfy_widget_lookupfield_coaches($this,$coach); ?>
		
		<p><label for="<?php echo $this->get_field_id( 'numdays' ); ?>"><?php _e( 'Days to show:','wp-wdfy-integration-of-wodify' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'numdays' ); ?>" name="<?php echo $this->get_field_name( 'numdays' ); ?>">
			<option value="0" 
			<?php 	if (0==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today','wp-wdfy-integration-of-wodify');?>
			</option>
			<option value="1" 
			<?php 	if (1==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today and Tomorrow','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="2" 
			<?php 	if (2==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today +2 days','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="3" 
			<?php 	if (3==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today +3 days','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="4" 
			<?php 	if (4==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today +4 days','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="5" 
			<?php 	if (5==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today +5 days','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="6" 
			<?php 	if (6==$numdays) 
						echo "selected"; 
					echo ">"; 
					_e('Today +6 days','wp-wdfy-integration-of-wodify');?>
			</option>
		</select>
		</p>		
		
		<p><label for="<?php echo $this->get_field_id( 'numclasses' ); ?>"><?php _e( 'Number of classes to display without scrolling:','wp-wdfy-integration-of-wodify' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'numclasses' ); ?>" name="<?php echo $this->get_field_name( 'numclasses' ); ?>" type="text" value="<?php echo $numclasses; ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'autoscroll' ); ?>"><?php _e( 'Scroll classes automatically?','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'autoscroll' ); ?>" name="<?php echo $this->get_field_name( 'autoscroll' ); ?>">
				<option value="0"<?php if ($autoscroll==0) echo " selected"; ?>><?php _e( 'No','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="1"<?php if ($autoscroll==1) echo " selected"; ?>><?php _e( 'Yes','wp-wdfy-integration-of-wodify'); ?></option>
		</select></p>
		
		<p><label for="<?php echo $this->get_field_id( 'boxheight' ); ?>"><?php _e( 'Height (in pixels) of class box without hover:','wp-wdfy-integration-of-wodify' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'boxheight' ); ?>" name="<?php echo $this->get_field_name( 'boxheight' ); ?>" type="text" value="<?php echo $boxheight; ?>" /></p>
		
		<?php
		for ($linenum=0; $linenum<6; $linenum++) { ?>
		<p><label for="<?php echo $this->get_field_id( 'lines['.$linenum.']' ); ?>"><?php echo sprintf(__( 'Content line %d:','wp-wdfy-integration-of-wodify' ),$linenum+1); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'lines['.$linenum.']' ); ?>" name="<?php echo $this->get_field_name( 'lines['.$linenum.']' ); ?>">
			<option value="program" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='program') 
						echo "selected"; 
					echo ">"; 
					_e('Program name','wp-wdfy-integration-of-wodify');?>
			</option>
			<option value="time" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='time') 
						echo "selected"; 
					echo ">"; 
					_e('Time','wp-wdfy-integration-of-wodify');?>
			</option>
			<option value="reservation" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='reservation') 
						echo "selected"; 
					echo ">"; 
					_e('Reservation status','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="reservationbar" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='reservationbar') 
						echo "selected"; 
					echo ">"; 
					_e('Visual reservation status','wp-wdfy-integration-of-wodify');?>
			</option>

			<option value="coach" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='coach') 
						echo "selected"; 
					echo ">"; 
					_e('Coach','wp-wdfy-integration-of-wodify');?>
			</option>

			<option value="classname" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='classname') 
						echo "selected"; 
					echo ">"; 
					_e('Class name','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="empty" 
			<?php 	if (array_key_exists($linenum,$lines) && $lines[$linenum]=='empty') 
						echo "selected"; 
					echo ">"; 
					_e('(empty line)','wp-wdfy-integration-of-wodify');?>
			</option>
			
			<option value="unused" 
			<?php 	if (!array_key_exists($linenum,$lines) || $lines[$linenum]=='unused') 
						echo "selected"; 
					echo ">"; 
					_e('(not used)','wp-wdfy-integration-of-wodify');?>
			</option>
			
		</select>
		</p>
		
		<?php
		}
		?>
		<p><label for="<?php echo $this->get_field_id( 'excludecomponents' ); ?>"><?php _e( 'Do not show the following programs (Separate program names with comma, leave empty to show all):','wp-wdfy-integration-of-wodify' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'excludecomponents' ); ?>" name="<?php echo $this->get_field_name( 'excludecomponents' ); ?>" rows="2"><?php echo $excludecomponents; ?></textarea></p>

		<p><label for="<?php echo $this->get_field_id( 'includecomponents' ); ?>"><?php _e( 'Show only the following programs (Separate program names with comma, leave empty to show all):','wp-wdfy-integration-of-wodify' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'includecomponents' ); ?>" name="<?php echo $this->get_field_name( 'includecomponents' ); ?>" rows="2"><?php echo $includecomponents; ?></textarea></p>
		<p>
		<?php
		if (get_option('wdfy_classes_cron')=='none')
		{
			$settings_link = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=sos_wodify') ) .'">'.__('Wodify Settings','wp-wdfy-integration-of-wodify').'</a>'; 
			echo '<br>Classes caching has been deactivated in '.$settings_link. '. No caching will be used.';
			echo '<input type="hidden" id="'.$this->get_field_id( 'usecache' ); 
			echo '" name="'.$this->get_field_name( 'usecache' );
			echo 'value="0">';
		}
		else
		{
		?>
		<label for="<?php echo $this->get_field_id( 'usecache' ); ?>"><?php _e( 'Use cached calendar if available','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'usecache' ); ?>" name="<?php echo $this->get_field_name( 'usecache' ); ?>">
				<option value="0"<?php if ($usecache==0) echo " selected"; ?>><?php _e( 'No','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="1"<?php if ($usecache==1) echo " selected"; ?>><?php _e( 'Yes','wp-wdfy-integration-of-wodify'); ?></option>
		</select>
		<?php } ?></p><?php
	}
} // class
 //array('program','time','empty','reservation','classname','coach');
endif; // !class_exists
