<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://newearthart.tech
 * @since      1.0.0
 *
 * @package    AcaiBowl
 * @subpackage AcaiBowl/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    AcaiBowl
 * @subpackage AcaiBowl/admin
 * @author     new earth art @ tech <info@newearthart.tech>
 */
class AcaiBowl_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


	function register_settings() {

		register_setting( 'acaibowl_plugin_options', 'acaibowl_plugin_options', 
			array($this,'options_validate') 
		);

		add_settings_section( 'api_settings', 'AcaiBowl Settings', array($this,'section_text'), 'acaibowl_plugin' );

		add_settings_field( 'ne_minigate_plugin_setting_userid', 'User ID',  array($this,'setting_userid'), 'acaibowl_plugin', 'api_settings' );
		add_settings_field( 'ne_minigate_plugin_setting_api_key', 'API Token', array($this,'setting_api_key'), 'acaibowl_plugin', 'api_settings' );
	}

	
	function options_validate( $input ) {

		/*
		$newinput['api_key'] = trim( $input['api_key'] );
		if ( ! preg_match( '/^[a-z0-9]{32}$/i', $newinput['api_key'] ) ) {
			$newinput['api_key'] = '';
		}
		return $newinput;
		*/
	
		return $input;
		
	}

	function section_text() {
		?>
			<strong>Copy your ceredentials from <a href="https://www.mintgate.app/token_api" target="_blank"> https://www.mintgate.app/token_api </a> </strong>
		<?php
	}
	
	function setting_api_key() {
		$options = get_option( 'acaibowl_plugin_options' );
		echo "<textarea rows=\"5\" id='ne_minigate_plugin_setting_api_key' name='acaibowl_plugin_options[api_key]' >" . esc_attr( $options['api_key'] ) . "</textarea>";
	}
	
	function setting_userid() {
		$options = get_option( 'acaibowl_plugin_options' );
		echo "<input id='ne_minigate_plugin_setting_userid' name='acaibowl_plugin_options[userid]' type='text' value='" . esc_attr( $options['userid'] ) . "' />";
	}

	/**
     * Registers a new settings page under Settings.
     */
    function admin_menu() {
        add_options_page(
            __( 'AcaiBowl', 'textdomain' ),
            __( 'AcaiBowl', 'textdomain' ),
            'manage_options',
            'acaibowl_plugin',
            array(
                $this,
                'settings_page'
            )
        );
    }


 
    /**
     * Settings page display callback.
     */
    function settings_page() {
    ?>
		<form action="options.php" method="post" class="ne_minigate_settings">
			<?php 
			settings_fields( 'acaibowl_plugin_options' );
			
			do_settings_sections( 'acaibowl_plugin' ); ?>
			<div class="settings_footer">
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
			</div>
		</form>
    <?php
    }

	/**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save( $post_id ) {
 
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */
 
//		xdebug_break();
		
		// Check if our nonce is set.
        if ( ! isset( $_POST['mintgate_inner_custom_box_nonce'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['mintgate_inner_custom_box_nonce'];
 
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'mintgate_inner_custom_box' ) ) {
            return $post_id;
        }
 
        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
 
        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
 
        /* OK, it's safe for us to save the data now. */
 
        // Sanitize the user input.
        $mydata = sanitize_text_field( $_POST['mintgate_new_field'] );
 
        // Update the meta field.
        update_post_meta( $post_id, 'mintgate_meta_link_value_key', $mydata );
    }
 

	/**
     * Adds the meta box.
     */
	public function meta_box_add($post_type) {

		$post_types = array( 'post', 'page' );
 
        if ( in_array( $post_type, $post_types ) ) {
            add_meta_box(
                'mintgate_meta_box',
                __( 'Mintgate gated access', 'textdomain' ),
                array( $this, 'render_meta_box_content' ),
                $post_type,
                'side',
                'high'
            );
        }
	}

	/**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content( $post ) {
 
        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'mintgate_inner_custom_box', 'mintgate_inner_custom_box_nonce' );
 
        // Use get_post_meta to retrieve an existing value from the database.
        $value = get_post_meta( $post->ID, 'mintgate_meta_link_value_key', true );
 
        // Display the form, using the current value.
        ?>
        <label for="mintgate_new_field">
            <?php _e( 'Gated Token:', 'textdomain' ); ?>
        </label>

        <input type="text" id="mintgate_new_field" name="mintgate_new_field"
				placeholder="something like : MXU6DPSWB6EP"
				 value="<?php echo esc_attr( $value ); ?>" size="25" />

		<p><small>If this is not empty, this content will be gated using acaiBowl

		<?php 

		$mintInfo = get_post_meta( $post->ID, 'acaibowl_post_mintinfo', true );
		
//		global $current_user;
		//echo $current_user->ID;
		//echo $mintInfo;

		$attimages = get_attached_media('image', $post->ID);
		$imgArray = array();
		foreach ($attimages as $image) {
			//echo wp_get_attachment_url($image->ID).' ';
			$imgObject = new stdClass();
			$imgObject->id = $image->ID;
			$imgObject->url = wp_get_attachment_url($image->ID);
			$imgArray[] = $imgObject;
		}
		
		?>


		</small></p>

		<div 
			id="acaibowl-pageadmin" 
			postId="<?php echo  esc_attr($post->ID)?>"
			rest_auth_nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>"
			images="<?php echo esc_attr(json_encode( $imgArray  )) ?>"
			mintInfo="<?php echo esc_attr($mintInfo) ?>"
		>
		</div>
        <?php
    }




	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in AcaiBowl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The AcaiBowl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/AcaiBowl-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name.'padmin', plugin_dir_url( __FILE__ ) . 'js/dist/acai-padmin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in AcaiBowl_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The AcaiBowl_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/AcaiBowl-admin.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name.'padmin', plugin_dir_url( __FILE__ ) . 'js/dist/acai-padmin.js', null, $this->version, true );

	}

}
