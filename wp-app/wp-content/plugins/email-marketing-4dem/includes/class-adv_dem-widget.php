<?php
/**
 * Adds Adv_dem_Widget widget.
 */
class Adv_dem_Widget extends WP_Widget {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'adv_dem_widget', // Base ID
			'Newsletter Widget 2.0',
			array( 'description' => esc_html__('Permette ai visitatori di iscriversi alla newsletter.', 'adv_dem')) // Args
		);
		$this->plugin_name = 'adv_dem';
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see Adv_dem_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		$label_email    = $instance['label_email'];
		$label_btn      = $instance['label_btn'];
		$description    = $instance['description'];
		$placeholder    = $instance['placeholder'];
        $dest_recipientId = $instance[ 'dest_recipientId' ];
		$instanceId 	= $instance[ 'instanceId' ];

 		$form = '<form method="POST" action="#" id="adv_dem_widget_form_' . $instanceId . '">
					<div class="adv_error_container" id="errorcontainer' . $instanceId . '" style="display:none;"><strong><p class="adv_error" id="error' . $instanceId . '"></p></strong></div>
					<div class="adv_success_container" id="successcontainer' . $instanceId . '" style="display:none;"><strong><p class="adv_success" id="success' . $instanceId . '"></p></strong></div>
					<p>
						<label class="adv_label" for="'.$label_email.'">'.$label_email.'</label>
						<input class="adv_input" type="text" name="email" id="email" value="" placeholder="' . $placeholder . '" /><br />
						<span class="adv_description">'.$description.'</span>
					</p>
					<button type="submit">' . $label_btn . '</button>
				</form>
				<script>
				jQuery("#adv_dem_widget_form_' . $instanceId . '").on("submit", function(event) {
					event.preventDefault();
					jQuery("div#errorcontainer' . $instanceId . '").hide();
					jQuery("div#successcontainer' . $instanceId . '").hide();

					var email = jQuery(this).find("input[name=\"email\"]");
					if (!/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]{2,24}$/.test(email.val())){
						jQuery("p#error' . $instanceId . '").text("' . esc_html__("Inserisci un indirizzo email valido." , "adv_dem") . '")
						jQuery("div#errorcontainer' . $instanceId . '").show();
					} else {
						jQuery("p#error' . $instanceId . '").text();
						jQuery("div#errorcontainer' . $instanceId . '").hide();
						var dataToPost = {
							"action"    : "adv_dem_widget_subscribe",
							"data"      :  { "email": email.val() , "recipientId" :  "' . $dest_recipientId . '"}
						};

						jQuery.ajax({
							type : "post",
							dataType : "json",
							url : adv_ajax_object.ajaxurl,
							data : dataToPost,
							success: function(response) {
								    console.log(response);
									if(!response.hasOwnProperty("error")){
										jQuery("p#success' . $instanceId . '").text("' . esc_html__("Iscrizione avvenuta con successo." , "adv_dem") . '");
										jQuery("div#successcontainer' . $instanceId . '").show();
									} else if (response.status == 400) {
										jQuery("p#error' . $instanceId . '").text("' . esc_html__("Indirizzo email già registrato." , "adv_dem") . '")
										jQuery("div#errorcontainer' . $instanceId . '").show();
									}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								jQuery("p#error' . $instanceId . '").text(textStatus)
								jQuery("div#errorcontainer' . $instanceId . '").show();
							}
						})
					}
				})
				</script>
				';
		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'] . $form . $args['after_widget'];

	}

	/**
	 * Back-end widget form configuration procedure.
	 *
	 * @see Adv_dem_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		$title = esc_html__("Iscriviti alla newsletter", "adv_dem");
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		$label_email = "";
		if ( isset( $instance[ 'label_email' ] ) ) {
			$label_email = $instance[ 'label_email' ];
		}
		$description = "";
		if ( isset( $instance[ 'description' ] ) ) {
			$description = $instance[ 'description' ];
		}

		$placeholder = "";
		if ( isset( $instance[ 'placeholder' ] ) ) {
			$placeholder = $instance[ 'placeholder' ];
		}

		$label_btn = "";
		if ( isset( $instance[ 'label_btn' ] ) ) {
			$label_btn = $instance[ 'label_btn' ];
		}

		// Gestione delle liste disponibili sulla console
        $dest_recipientId = "";
		if ( isset( $instance[ 'dest_recipientId' ] ) ) {
			$dest_recipientId = $instance[ 'dest_recipientId' ];
		}

		$api4mOptions = get_option( $this->plugin_name);
		$api4m = new Adv_dem_InterfaceAPI($api4mOptions['apikey'], $api4mOptions['entrypoint'] );
		if ($api4m->getRequestSuccessful()) {
			$recipientList = $api4m->getRecipients();
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'dest_recipientId' ); ?>"><?php echo esc_html__("Lista di destinazione" , "adv_dem") ?></label>
	            <select class="widefat"  id="<?php echo esc_attr($this->get_field_id('dest_recipientId')); ?>" name="<?php echo esc_attr($this->get_field_name('dest_recipientId')); ?>">
					<option value=""><?php echo esc_html__("NESSUNA LISTA SELEZIONATA" , "adv_dem") ?></option>
					<?php
						foreach ($recipientList['data'] as $recipient) {
							$recipientCustomFields = $api4m->getRecipientCustomFields($recipient["id"]);
							$emailCfType = array_filter($recipientCustomFields['data'], function ($cf) { 
								if( $cf['type'] === 'Email' ) return $cf;
								
							});
						
							if(!empty($emailCfType))	{
						?>					 .
	                    <option value="<?php echo esc_attr($recipient["id"]); ?>" <?php echo ($dest_recipientId == $recipient["id"]) ? "selected" : "" ?>><?php echo esc_html($recipient["name"]); ?></option>
					<?php }} ?>
				</select>
			</p>
			<p>
	            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html__("Titolo", "adv_dem") ?></label>
	            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
			</p>
			<p>
	            <label for="<?php echo esc_attr($this->get_field_id('label_email')); ?>"><?php echo esc_html__("Label E-mail", "adv_dem") ?></label>
	            <input class="widefat"  id="<?php echo esc_attr($this->get_field_id('label_email')); ?>" name="<?php echo esc_attr($this->get_field_name('label_email')); ?>" type="text" value="<?php echo esc_attr($label_email); ?>" />
			</p>
			<p>
	            <label for="<?php echo esc_attr($this->get_field_id('placeholder')); ?>"><?php echo esc_html__("Placeholder", "adv_dem") ?></label>
	            <input class="widefat"  id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>" name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>" />
			</p>
			<p>
	            <label for="<?php echo esc_attr($this->get_field_id('description')); ?>"><?php echo esc_html__("Testo descrittivo", "adv_dem") ?></label>
	            <input class="widefat"  id="<?php echo esc_attr($this->get_field_id('description')); ?>" name="<?php echo esc_attr($this->get_field_name('description')); ?>" type="text" value="<?php echo esc_attr($description); ?>" />
			</p>
			<p>
	            <label for="<?php echo esc_attr($this->get_field_id('label_btn')); ?>"><?php echo esc_html__("Testo pulsante", "adv_dem") ?></label>
	            <input class="widefat"  id="<?php echo esc_attr($this->get_field_id('label_btn')); ?>" name="<?php echo esc_attr($this->get_field_name('label_btn')); ?>" type="text" value="<?php echo esc_attr($label_btn); ?>" />
			</p>
		<?php
		} else {
		?>
			<h2><?php echo esc_html__("Configura la tua Apikey!" , "adv_dem") ?></h2>
			<p><?php echo esc_html__("Prima di poter procedere con la configurazione del widget devi configurare correttamente il plugin di iscrizione alla newsletter." , "adv_dem") ?></p>
		<?php
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see Adv_dem_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['label_email'] = ( ! empty( $new_instance['label_email'] ) ) ? strip_tags( $new_instance['label_email'] ) : esc_html__("Email" , "adv_dem");
		$instance['label_btn'] = ( ! empty( $new_instance['label_btn'] ) ) ? strip_tags( $new_instance['label_btn'] ) : esc_html__("Iscriviti" , "adv_dem");
		$instance['description'] = ( ! empty( $new_instance['description'] ) ) ? strip_tags( $new_instance['description'] ) : '';
		$instance[ 'placeholder' ] = ( ! empty( $new_instance['placeholder'] ) ) ? strip_tags( $new_instance['placeholder'] ) : '';
        $instance['dest_recipientId'] = ( ! empty( $new_instance['dest_recipientId'] ) ) ? strip_tags( $new_instance['dest_recipientId'] ) : '';
        $instance['instanceId'] = ( ! empty( $old_instance['instanceId'] ) ) ?$old_instance['instanceId'] : md5(time());

		return $instance;
	}

} // class Adv_dem_Widget
?>
