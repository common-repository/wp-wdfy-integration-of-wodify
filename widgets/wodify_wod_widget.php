<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
if ( ! class_exists( 'SOS_Wodify_WOD_Widget' ) ):

class SOS_Wodify_WOD_Widget extends WP_Widget {

	function __construct(){
		$widget_ops  = array( 'description' => __( "Displays a WOD from Wodify", 'wp-wdfy-integration-of-wodify' ) );
		$control_ops = array(/*'width' => 300, 'height' => 400*/ );
		parent::__construct( 'SOS_Wodify_WOD_Widget', $name = __( "Wodify WOD", 'wp-wdfy-integration-of-wodify' ), $widget_ops, $control_ops );
	}

	public function widget( $args, $instance ) {
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( "WOD",'wp-wdfy-integration-of-wodify' );

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$location = ( ! empty( $instance['location'] ) ) ? $instance['location'] : get_option('wodify_location');
		if ( ! $location )
			$location = get_option('wodify_location');
	
		$program = ( ! empty( $instance['program'] ) ) ? $instance['program'] : get_option('wodify_program');
		if ( ! $program )
			$program = get_option('wodify_program');
		
		//since 0.4.3
		if (array_key_exists('ignorepublishdate',$instance))
			$ignorepublishdate = $instance['ignorepublishdate'];
		else
			$ignorepublishdate = 0;
		
		$date = '';
		if (array_key_exists('date',$instance))
			$date = ( ! empty( $instance['date'] ) ) ? $instance['date'] : '+0';
		if ( ! $date )
			$date = '+0';
		
		$cachewod = $instance['cachewod'];
	
		if ( $cachewod!==0 && $cachewod !==1)
			$cachewod = 1;
		
		$publishoffset  	= isset( $instance['publishoffset'] ) ? ( $instance['publishoffset'] +0.0 ) : get_option('wdfy_publishoffset');

		$excludecomponents = ( ! empty( $instance['excludecomponents'] ) ) ? $instance['excludecomponents'] : '';
		if ( ! $excludecomponents )
			$excludecomponents = '';

		$includecomponents = ( ! empty( $instance['includecomponents'] ) ) ? $instance['includecomponents'] : '';
		if ( ! $includecomponents )
			$includecomponents = '';
		
		if (array_key_exists('show_wodimages',$instance))
			$show_wodimages = $instance['show_wodimages'];
		else	
			$show_wodimages = get_option('wdfy_show_wodimages');
	
		if ( $show_wodimages!=='false' && $show_wodimages !=='true')
			$show_wodimages = false;
		
		if ($show_wodimages=='true')
			$show_wodimages = true;
		else
			$show_wodimages = false;
		
		echo $args['before_widget']; 
	
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} 		
		
		$inclcomp =array_filter(array_map('trim',explode(',',$includecomponents)));
		$exclcomp =array_filter(array_map('trim',explode(',',$excludecomponents)));
		$output = SOS_Wodify_API::wodifyFormatedWOD($location,$program,$date,$ignorepublishdate,$inclcomp,$exclcomp,1==$cachewod,$publishoffset,$show_wodimages);
		echo $output;
		echo $args['after_widget']; 
	} 
	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['location'] =  sanitize_text_field( $new_instance['location'] );
		$instance['program'] =  sanitize_text_field( $new_instance['program'] );
		$instance['ignorepublishdate'] =  absint($new_instance['ignorepublishdate']);
		$instance['date'] =  sanitize_text_field( $new_instance['date'] );
		$instance['cachewod'] =  absint($new_instance['cachewod']);
		$instance['excludecomponents'] =  sanitize_text_field( $new_instance['excludecomponents'] );
		$instance['includecomponents'] =  sanitize_text_field( $new_instance['includecomponents'] );
		$instance['publishoffset'] =  $new_instance['publishoffset']+0.0;
		$instance['show_wodimages'] =  sanitize_text_field($new_instance['show_wodimages']);
		return $instance;
	}

	public function form( $instance ) {
		$title     			= isset( $instance['title'] ) ? esc_attr( $instance['title'] ) :  __( "WOD",'wp-wdfy-integration-of-wodify');
		$location 			= isset( $instance['location'] ) ? esc_attr( $instance['location'] ) : get_option('wodify_location');
		$program  			= isset( $instance['program'] ) ? esc_attr( $instance['program'] ) : get_option('wodify_program');
		$ignorepublishdate  = isset( $instance['ignorepublishdate'] ) ? esc_attr( $instance['ignorepublishdate'] ) : 0;
		$date  				= isset( $instance['date'] ) ? esc_attr( $instance['date'] ) : '+0';
		$cachewod  			= isset( $instance['cachewod'] ) ? esc_attr( $instance['cachewod'] ) : 1;
		$excludecomponents  = isset( $instance['excludecomponents'] ) ? esc_attr( $instance['excludecomponents'] ) : '';
		$includecomponents  = isset( $instance['includecomponents'] ) ? esc_attr( $instance['includecomponents'] ) : '';
		$publishoffset  	= isset( $instance['publishoffset'] ) ? ( $instance['publishoffset'] +0.0 ) : get_option('wdfy_publishoffset');
		$show_wodimages		= isset( $instance['show_wodimages'] ) ? esc_attr( $instance['show_wodimages'] ) : get_option('wdfy_show_wodimages');
		
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		
		<?php wpwdfy_widget_lookupfield_location($this,$location); 
		
		wpwdfy_lookupfield_program($this->get_field_name( 'program' ),$this->get_field_id( 'program' ), __( 'Program (Wodify):','wp-wdfy-integration-of-wodify'),$program,__( '(Please select)','wp-wdfy-integration-of-wodify'));
		?>
		
			
		<p><label for="<?php echo $this->get_field_id( 'date' ); ?>"><?php _e( 'WOD day:','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'date' ); ?>" name="<?php echo $this->get_field_name( 'date' ); ?>">
				<option value="-7"<?php if ('-7'==$date) echo " selected"; ?>><?php _e( 'Last week','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-6"<?php if ('-6'==$date) echo " selected"; ?>><?php _e( 'Today - 6 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-5"<?php if ('-5'==$date) echo " selected"; ?>><?php _e( 'Today - 5 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-4"<?php if ('-4'==$date) echo " selected"; ?>><?php _e( 'Today - 4 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-3"<?php if ('-3'==$date) echo " selected"; ?>><?php _e( 'Today - 3 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-2"<?php if ('-2'==$date) echo " selected"; ?>><?php _e( 'Today - 2 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="-1"<?php if ('-1'==$date) echo " selected"; ?>><?php _e( 'Yesterday','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+0"<?php if ('+0'==$date) echo " selected"; ?>><?php _e( 'Today','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+1"<?php if ('+1'==$date) echo " selected"; ?>><?php _e( 'Tomorrow','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+2"<?php if ('+2'==$date) echo " selected"; ?>><?php _e( 'Today + 2 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+3"<?php if ('+3'==$date) echo " selected"; ?>><?php _e( 'Today + 3 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+4"<?php if ('+4'==$date) echo " selected"; ?>><?php _e( 'Today + 4 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+5"<?php if ('+5'==$date) echo " selected"; ?>><?php _e( 'Today + 5 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+6"<?php if ('+6'==$date) echo " selected"; ?>><?php _e( 'Today + 6 days','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="+7"<?php if ('+7'==$date) echo " selected"; ?>><?php _e( 'Next week','wp-wdfy-integration-of-wodify'); ?></option>
		</select>
		</p>
		
		<p><label for="<?php echo $this->get_field_id( 'publishoffset' ); ?>"><?php _e( 'WOD Publish offset in hours (Show WOD earlier or later than Wodify publish date, negative values vor earlier):','wp-wdfy-integration-of-wodify'); ?></label>
		<input type="number" size="3" id="<?php echo $this->get_field_id( 'publishoffset' ); ?>" name="<?php echo $this->get_field_name( 'publishoffset' ); ?>" value="<?php echo $publishoffset; ?>">
		</p>
		
		
		<p><label for="<?php echo $this->get_field_id( 'ignorepublishdate' ); ?>"><?php _e( 'Ignore Wodify WOD publish date:','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'ignorepublishdate' ); ?>" name="<?php echo $this->get_field_name( 'ignorepublishdate' ); ?>">
				<option value="0"<?php if ($ignorepublishdate==0) echo " selected"; ?>><?php _e( 'No','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="1"<?php if ($ignorepublishdate==1) echo " selected"; ?>><?php _e( 'Yes','wp-wdfy-integration-of-wodify'); ?></option>
		</select>
		</p>
		<p><label for="<?php echo $this->get_field_id( 'cachewod' ); ?>"><?php _e( 'Cache Wodify WOD?','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'cachewod' ); ?>" name="<?php echo $this->get_field_name( 'cachewod' ); ?>">
				<option value="0"<?php if ($cachewod==0) echo " selected"; ?>><?php _e( 'No','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="1"<?php if ($cachewod==1) echo " selected"; ?>><?php _e( 'Yes','wp-wdfy-integration-of-wodify'); ?></option>
		</select>
		</p>
		
		<p><label for="<?php echo $this->get_field_id( 'excludecomponents' ); ?>"><?php _e( 'Do not show the following WOD components (Separate component names with comma, leave empty to show all):','wp-wdfy-integration-of-wodify' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'excludecomponents' ); ?>" name="<?php echo $this->get_field_name( 'excludecomponents' ); ?>" rows="2"><?php echo $excludecomponents; ?></textarea></p>

		<p><label for="<?php echo $this->get_field_id( 'includecomponents' ); ?>"><?php _e( 'Show only following WOD components (Separate component names with comma, leave empty to show all):' ,'wp-wdfy-integration-of-wodify' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'includecomponents' ); ?>" name="<?php echo $this->get_field_name( 'includecomponents' ); ?>" rows="2"><?php echo $includecomponents; ?></textarea></p>
		
		<p><label for="<?php echo $this->get_field_id( 'show_wodimages' ); ?>"><?php _e( 'Show wod images?','wp-wdfy-integration-of-wodify'); ?></label>
		<select class="select" id="<?php echo $this->get_field_id( 'show_wodimages' ); ?>" name="<?php echo $this->get_field_name( 'show_wodimages' ); ?>">
				<option value="false"<?php if ($show_wodimages=='false') echo " selected"; ?>><?php _e( 'No','wp-wdfy-integration-of-wodify'); ?></option>
				<option value="true"<?php if ($show_wodimages=='true') echo " selected"; ?>><?php _e( 'Yes','wp-wdfy-integration-of-wodify'); ?></option>
		</select>
		</p>
<?php
	}
} // class
endif; // !class_exists
