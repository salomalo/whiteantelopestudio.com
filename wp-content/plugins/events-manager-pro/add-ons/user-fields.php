<?php
class EM_User_Fields {
	static $form;
	
	function init(){
		//Menu/admin page
		add_action('admin_init',array('EM_User_Fields', 'admin_page_actions'),9); //before bookings
		add_action('emp_forms_admin_page',array('EM_User_Fields', 'admin_page'),10);
		add_action('emp_form_user_fields',array('EM_User_Fields', 'emp_booking_user_fields'),1,1); //hook for booking form editor
		//Booking interception
		add_filter('em_form_validate_field_custom', array('EM_User_Fields', 'validate'), 1, 4); //validate object
		$custom_fields = get_option('em_user_fields', array());
		foreach($custom_fields as $field_id => $field){
			add_action('em_form_output_field_custom_'.$field_id, array('EM_User_Fields', 'output_field'), 1, 2); //validate object
		}
		//disable EM user fields and override with our filter
		remove_filter( 'user_contactmethods' , array('EM_People','user_contactmethods'),10,1);
		add_action( 'show_user_profile', array('EM_User_Fields','show_profile_fields'), 1 );
		add_action( 'edit_user_profile', array('EM_User_Fields','show_profile_fields'), 1 );
		add_action( 'personal_options_update', array('EM_User_Fields','save_profile_fields') );
		add_action( 'edit_user_profile_update', array('EM_User_Fields','save_profile_fields') );
		//admin area additions
		add_filter('em_person_display_summary', array('EM_User_Fields','em_person_display_summary'),10,2);
		//Booking Table and CSV Export
		add_filter('em_bookings_table_rows_col', array('EM_User_Fields','em_bookings_table_rows_col'),10,5);
		add_filter('em_bookings_table_cols_template', array('EM_User_Fields','em_bookings_table_cols_template'),10,2);
	}
	
	function get_form(){
		if( empty(self::$form) ){
			self::$form = new EM_Form('em_user_fields');
		}
		return self::$form;
	}
	
	function emp_booking_user_fields( $fields ){
		//just get an array of options here
		$custom_fields = get_option('em_user_fields');
		foreach($custom_fields as $field_id => $field){
			if( !in_array($field_id, $fields) ){
				$fields[$field_id] = $field['label'];
			}
		}
		return $fields;
	}
	
	function validate($result, $field, $value, $form){
		$EM_Form = self::get_form();
		if( array_key_exists($field['fieldid'], $EM_Form->user_fields) ){
			if( !$EM_Form->validate_field($field['fieldid'], $value) ){
				$form->add_error($EM_Form->get_errors());
				return false;
			}
			return $result && true;
		}
		return $result;
	}
	
	function output_field( $field, $post ){
		$EM_Form = self::get_form();
		if( array_key_exists($field['fieldid'], $EM_Form->user_fields) ){
			$real_field = $EM_Form->form_fields[$field['fieldid']];
			$real_field['label'] = $field['label'];
			echo $EM_Form->output_field_input($real_field, $post);
		}
	}
	
	/*
	 * ----------------------------------------------------------
	 * Booking Table and CSV Export
	 * ----------------------------------------------------------
	 */
	function em_bookings_table_rows_col($value, $col, $EM_Booking, $EM_Bookings_Table, $csv){
		$EM_Form = self::get_form();
		if( array_key_exists($col, $EM_Form->form_fields) ){
			$field = $EM_Form->form_fields[$col];
			$value = get_user_meta($EM_Booking->get_person()->ID, $col, true);
			if( empty($value) && !empty($EM_Booking->booking_meta['registration'][$col]) ){
				$value = is_array($EM_Booking->booking_meta['registration'][$col]) ? explode(', ', $EM_Booking->booking_meta['registration'][$col]):$EM_Booking->booking_meta['registration'][$col];
			}elseif( empty($value) ){
				$value = "";			 
			}
			if( is_array($value) ) $value = implode(', ', $value);
		}
		return $value;
	}
	
	function em_bookings_table_cols_template($template, $EM_Bookings_Table){
		$EM_Form = self::get_form();
		foreach($EM_Form->form_fields as $field_id => $field ){
			$template[$field_id] = $field['label'];
		}
		return $template;
	}
	
	
	
	/*
	 * ----------------------------------------------------------
	 * Display Functions
	 * ----------------------------------------------------------
	 */
	
	function em_person_display_summary($summary, $EM_Person){
		global $EM_Booking;
		$EM_Form = self::get_form();
		if( !get_option('dbem_bookings_registration_disable') || $EM_Person->ID != get_option('dbem_bookings_registration_user') || is_object($EM_Booking) ){
			ob_start();
			//a bit of repeated stuff from the original EM_Person::display_summary() function
			?>
			<table>
				<tr>
					<td><?php echo get_avatar($EM_Person->ID); ?></td>
					<td style="padding-left:10px; vertical-align: top;">
						<strong><?php _e('Name','dbem'); ?></strong> : <a href="<?php echo EM_ADMIN_URL ?>&amp;page=events-manager-bookings&amp;person_id=<?php echo $EM_Person->ID; ?>"><?php echo $EM_Person->get_name() ?></a><br /><br />
						<strong><?php _e('Email','dbem'); ?></strong> : <?php echo $EM_Person->user_email; ?><br /><br />
						<?php foreach($EM_Form->form_fields as $field_id => $field): ?>
						<?php
							$value = get_user_meta($EM_Person->ID, $field_id, true);
							if( empty($value) && !empty($EM_Booking->booking_meta['registration'][$field_id]) ){
								$value = $EM_Booking->booking_meta['registration'][$field_id];
							}elseif( empty($value) ){
								$value = "<em>n/a</em>";
							}
							if( is_array($value) ) $value = implode(', ', $value);
						?>
						<strong><?php echo $field['label']; ?></strong> : <?php echo $value; ?><br /><br />	
						<?php endforeach; ?>
					</td>
				</tr>
			</table>
			<?php
			return ob_get_clean();
		}
		return $summary;
	}
	
	/*
	 * ----------------------------------------------------------
	 * ADMIN Functions
	 * ----------------------------------------------------------
	 */
	
	/**
	 * Adds phone number to contact info of users, compatible with previous phone field method
	 * @param $array
	 * @return array
	 */
	function show_profile_fields($user){
		$EM_Form = self::get_form();
		?>
		<h3><?php _e('Further Information','dbem'); ?></h3>
		<table class="form-table">
			<?php 
			foreach($EM_Form->form_fields as $field_id => $field){
				?>
				<tr>
					<th><label for="<?php echo $field_id; ?>"><?php echo $field['label']; ?></label></th>
					<td>
						<?php echo $EM_Form->output_field_input($field, get_user_meta($user->ID, $field_id, true)); ?>
					</td>
				</tr>
				<?php
			}
			?>	
		</table>
		<?php
	}
	
	function save_profile_fields($user_id){
		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;
		$EM_Form = self::get_form();
		foreach($EM_Form->form_fields as $field_id => $field){
			//validate & save
			if( $EM_Form->validate_field($field_id, $_REQUEST[$field_id]) ){
				update_usermeta( $user_id, $field_id, $_REQUEST[$field_id] );
			}
		}
	}
	
	function admin_page_actions() {
		global $EM_Notices;
		$EM_Form = self::get_form();
		if( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-forms-editor' ){
			if( !empty($_REQUEST['form_name']) && $EM_Form->form_name == $_REQUEST['form_name'] ){
				//set up booking form field map and save/retreive previous data
				if( empty($_REQUEST['bookings_form_action']) && $EM_Form->editor_get_post() ){
					//Update Values
					if( count($EM_Form->get_errors()) == 0 ){
						//prefix all with dbem
						$form_fields = array();
						foreach($EM_Form->form_fields as $field_id => $field){
							if( substr($field_id, 0, 5) != 'dbem_' ){
								$field_id = $field['fieldid'] = 'dbem_'.$field_id;
							}
							$form_fields[$field_id] = $field;
						}
						update_option('em_user_fields', $form_fields);
						$EM_Notices->add_confirm(__('Changes Saved','em-pro'));
						self::$form = false; //reset form
						$EM_Form = new EM_Form($form_fields);
					}else{
						$EM_Notices->add_error($EM_Form->get_errors());
					}
				}
			}
		}
		//enable dbem_bookings_tickets_single_form if enabled
	}
	function admin_page() {
		$EM_Form = self::get_form();
		//enable dbem_bookings_tickets_single_form if enabled
		?>
		<a name="user_fields"></a>
		<div id="poststuff" class="metabox-holder">
			<!-- END OF SIDEBAR -->
			<div id="post-body">
				<div id="post-body-content">
					<div id="em-booking-form-editor" class="stuffbox">
						<h3>
							<?php _e ( 'User Fields', 'em-pro' ); ?>
						</h3>
						<div class="inside">
							<p><?php echo sprintf( __('Registration fields are only shown to guest visitors. If you add new fields here and save, they will then be available as custom registrations in your bookings editor, and this information will be accessible and editable on each user <a href="%s">profile page</a>.', 'em-pro' ), 'profile.php'); ?></p>
							<p><?php _e ( '<strong>Important:</strong> When editing this form, to make sure your current user information is displayed, do not change their field names.', 'em-pro' )?></p>
							<?php echo $EM_Form->editor(false, true, false); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	
	private function show_reg_fields(){
		return !is_user_logged_in() && get_option('dbem_bookings_anonymous'); 
	}
}
EM_User_Fields::init();