<?php

defined( 'ABSPATH' ) || die();

// Include the Gravity Forms Feed Add-On Framework.
GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Salesforce Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Gravity Forms
 * @copyright Copyright (c) 2023, Gravity Forms
 */
class GF_Salesforce extends GFFeedAddOn {

	const SETTINGS_FIELD_CONSUMER_KEY    = 'consumer_key';
	const SETTINGS_FIELD_CONSUMER_SECRET = 'consumer_secret';
	const SETTINGS_FIELD_DOMAIN          = 'domain';

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @var    GF_Salesforce $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Gravity Forms Salesforce Add-On.
	 *
	 * @since  1.0
	 * @var    string $_version Contains the version.
	 */
	protected $_version = GF_SALESFORCE_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = GF_SALESFORCE_MIN_GF_VERSION;

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformssalesforce';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformssalesforce/salesforce.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @since  1.0
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://gravityforms.com';

	/**
	 * Defines the title of this add-on.
	 *
	 * @since  1.0
	 * @var    string $_title The title of the add-on.
	 */
	protected $_title = 'Gravity Forms Salesforce Add-On';

	/**
	 * Defines the short title of the add-on.
	 *
	 * @since  1.0
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Salesforce';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Enabling background feed processing to prevent performance issues delaying form submission completion.
	 *
	 * @since 1.0
	 *
	 * @var bool
	 */
	protected $_async_feed_processing = true;

	/**
	 * Defines the capabilities needed for the Gravity Forms Salesforce Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_salesforce', 'gravityforms_salesforce_uninstall' );

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_salesforce';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_salesforce';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_salesforce_uninstall';

	/**
	 * The API class.
	 *
	 * @since 1.0
	 *
	 * @var GF_Salesforce_API
	 */
	private $api = null;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since  1.0
	 *
	 * @return GF_Salesforce $_instance An instance of the GF_Salesforce class
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new GF_Salesforce();
		}

		return self::$_instance;

	}

	/**
	 * Register initialization hooks.
	 *
	 * @since  1.0
	 */
	public function init() {
		require_once 'includes/api/class-gf-salesforce-api.php';

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Send data to Salesforce only when payment is received.', 'gravityformssalesforce' ),
			)
		);

		parent::init();
	}

	/**
	 * Add Ajax callback.
	 *
	 * @since 1.0
	 */
	public function init_ajax() {

		parent::init_ajax();

		// Ajax callback to disconnect Salesforce account.
		add_action( 'wp_ajax_gfsalesforce_deauthorize', array( $this, 'ajax_deauthorize' ) );

	}

	/**
	 * Updates the auth token before initializing the settings.
	 *
	 * @since 1.0
	 */
	public function plugin_settings_init() {
		$this->maybe_update_auth_tokens();
		parent::plugin_settings_init();
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @since 1.0
	 *
	 * @return array Scripts to be enqueued.
	 */
	public function scripts() {

		$dev_min = defined( 'GF_SCRIPT_DEBUG' ) && GF_SCRIPT_DEBUG ? '' : '.min';

		$scripts = array(
			array(
				'handle'    => 'gform_salesforce_admin_js',
				'src'       => trailingslashit( $this->get_base_url() ) . "assets/js/dist/scripts-admin{$dev_min}.js",
				'version'   => $this->_version,
				'deps'      => array(),
				'in_footer' => true,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'plugin_settings', 'form_settings' ),
						'tab'        => array( $this->_slug, $this->get_short_title() ),
					),
				),
				'strings'   => array(
					'ajax_nonce' => wp_create_nonce( 'gf_salesforce_ajax' ),
				),
			),
			array(
				'handle'    => 'gform_salesforce_vendor_admin_js',
				'src'       => $this->get_base_url() . "/assets/js/dist/vendor-admin{$dev_min}.js",
				'version'   => $this->_version,
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'plugin_settings', 'form_settings' ),
						'tab'        => $this->_slug,
					),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}


	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Define plugin settings fields.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		$enable_custom_app = apply_filters( 'gform_salesforce_allow_manual_configuration', false );

		if ( $enable_custom_app ) {
			return array(
				array(
					'title'       => __( 'Salesforce Account', 'gravityformssalesforce' ),
					'description' => __( 'Provide the Consumer Key and Consumer Secret from your Salesforce Connected App.', 'gravityformssalesforce' ),
					'fields'      => array(
						array(
							'name'              => self::SETTINGS_FIELD_CONSUMER_KEY,
							'type'              => 'text',
							'label'             => __( 'Consumer Key', 'gravityformssalesforce' ),
							'feedback_callback' => array( $this, 'verify_api_credentials' ),
						),
						array(
							'name'              => self::SETTINGS_FIELD_CONSUMER_SECRET,
							'type'              => 'text',
							'label'             => __( 'Consumer Secret', 'gravityformssalesforce' ),
							'feedback_callback' => array( $this, 'verify_api_credentials' ),
						),
						array(
							'name'              => self::SETTINGS_FIELD_DOMAIN,
							'type'              => 'text',
							'label'             => __( 'Salesforce Domain', 'gravityformssalesforce' ),
							'feedback_callback' => array( $this, 'verify_api_credentials' ),
						),
						array(
							'name'  => 'is_custom_app',
							'type'  => 'hidden',
							'value' => 1,
						),
						array(
							'type' => 'salesforce_validation_message',
						),
					),
				),
			);
		} else {
			return array(
				array(
					'title'       => __( 'Salesforce Account', 'gravityformssalesforce' ),
					/* translators: %1$s is an opening <a> tag with a link to Salesforce.com. %2$s is the closing </a> tag */
					'description' => '<p>' . sprintf( esc_html__( 'Connect your Salesforce account to allow Gravity Forms to send data to Salesforce. If you don\'t have a Salesforce account, you can %1$ssign up for one here.%2$s', 'gravityformssalesforce' ), '<a href="https://www.salesforce.com/" target="_blank">', '</a>' ) . '</p>',
					'fields'      => array(
						array(
							'name'  => 'auth_button',
							'label' => '',
							'type'  => 'oauth_connect_button',
						),
						array(
							'type'  => 'save',
							'class' => 'hidden',
						),
					),
				),
			);
		}

	}

	/**
	 * The markup for the validation message settings field.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_salesforce_validation_message() {
		$display_validation_error = false;
		$settings                 = $this->get_plugin_settings();
		if ( rgar( $settings, 'consumer_key' ) && rgar( $settings, 'consumer_secret' ) && rgar( $settings, 'domain' ) ) {
			$display_validation_error = ! $this->verify_api_credentials();
		}

		if ( $display_validation_error ) {
			echo __( '<div class="alert gforms_note_error">The add-on could not connect using this custom app. The app might be misconfigured or the associated user might not have API access.</div>', 'gravityformssalesforce' );
		}
	}

	/**
	 * Initialize and instance of the API class.
	 *
	 * @since 1.0
	 *
	 * @return GF_Salesforce_API|false
	 */
	public function initialize_api() {

		// Load the API library file if it hasn't been loaded yet.
		if ( ! class_exists( 'GF_Saleforce_API' ) ) {
			require_once 'includes/api/class-gf-salesforce-api.php';
		}

		$settings = $this->get_auth_settings();
		if ( ! $settings ) {
			$this->api = false;
			return false;
		}

		return $this->get_api_instance();
	}

	/**
	 * Generate HTML for the button to start the OAuth process, or to disconnect an Salesforce account.
	 *
	 * @since 1.0
	 *
	 * @param array $field Field settings.
	 * @param bool  $echo  Display field. Defaults to true.
	 *
	 * @return string HTML for the button to start the OAuth process, or to disconnect a Salesforce account.
	 */
	public function settings_oauth_connect_button( $field, $echo = true ) {

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		$enable_custom_app = apply_filters( 'gform_salesforce_allow_manual_configuration', false );

		// Check if Salesforce API is available.
		if ( ! $this->initialize_api() ) {

			$nonce        = wp_create_nonce( $this->get_authentication_state_action() );
			$settings_url = rawurlencode(
				add_query_arg(
					array(
						'page'    => 'gf_settings',
						'subview' => $this->get_slug(),
					),
					admin_url( 'admin.php' )
				)
			);

			$oauth_url = add_query_arg(
				array(
					'redirect_to' => $settings_url,
					'license'     => GFCommon::get_key(),
					'state'       => $nonce,
				),
				GF_Salesforce_API::get_gravity_api_url()
			);

			if ( get_transient( 'gravityapi_request_gravityformssalesforce' ) ) {
				delete_transient( 'gravityapi_request_gravityformssalesforce' );
			}

			set_transient( 'gravityapi_request_gravityformssalesforce', $nonce, 10 * MINUTE_IN_SECONDS );

			$connect_button = esc_html__( 'Connect to Salesforce', 'gravityformsalesforce' );

			$html = sprintf(
				'<a href="%1$s" class="primary button large" id="gform_salesforce_connect_button">%2$s</a>',
				$oauth_url,
				/* translators: SVG button connect Salesforce account */
				$connect_button
			);
		} else {
			$connection_label = sprintf(
				'%1$s : %2$s',
				esc_html__( 'Connected to Salesforce account', 'gravityformssalesforce' ),
				esc_html( rgars( $settings, 'auth_token/domain' ) )
			);
			if ( $this->is_gravityforms_supported( '2.8.8' ) ) {
				$html = '<p>
					<span class="gform-status-indicator gform-status-indicator--size-sm gform-status-indicator--theme-cosmos gform-status--active gform-status--no-icon gform-status--no-hover">
						<span class="gform-status-indicator-status gform-typography--weight-medium gform-typography--size-text-xs">' .
							$connection_label . '
						</span>
					</span>
				</p>';
			} else {
				$html  = sprintf(
				'<p> %1$s </p>',
				$connection_label
				);
			}
			$html .= sprintf(
				'<a href="#" class="button" id="gform_salesforce_disconnect_button">%1$s</a>',
				esc_html__( 'Disconnect from Salesforce', 'gravityformssalesforce' )
			);
		}

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Get action name for authentication state.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_authentication_state_action() {

		return 'gform_salesforce_authentication_state';

	}

	/**
	 * Attempt to update auth tokens if we have a payload.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function maybe_update_auth_tokens() {
		if ( ! $this->is_plugin_settings( $this->_slug ) ) {
			return;
		}

		$payload = $this->get_oauth_payload();
		if ( ! $payload || $this->is_save_postback() ) {
			return;
		}

		// Verify state.
		if ( rgpost( 'state' ) && ! wp_verify_nonce( rgar( $payload, 'state' ), $this->get_authentication_state_action() ) ) {
			$this->add_error_notice( __METHOD__, esc_html__( 'Unable to connect to Salesforce due to mismatched state.', 'gravityformssalesforce' ) );
			return;
		}

		// If error is provided, display message.
		if ( rgpost( 'auth_error' ) || isset( $payload['auth_error'] ) || empty( $payload['auth_payload'] ) ) {
			// Add error message.
			$this->add_error_notice( __METHOD__, esc_html__( 'Unable to connect to your Salesforce account.', 'gravityformssalesforce' ) );
			return;
		}

		$auth_data = json_decode( base64_decode( rgar( $payload, 'auth_payload' ) ), true );
		$auth_code = rgar( $auth_data, 'code' );

		// If auth code is not provided, abort silently.
		if ( ! $auth_code ) {
			return;
		}

		// If access token is already set, we don't need to update it.
		$settings = $this->get_auth_settings();
		if ( ! rgempty( 'access_token', $settings ) ) {
			return;
		}

		// Getting access token based on auth code.
		$token_result = $this->exchange_code_for_access_token( $auth_code );

		// If the token result is an error object, display specific error messages.
		if ( ! $token_result || is_wp_error( $token_result ) ) {

			$error_message = esc_html__( 'Authentication with Salesforce was unsuccessful.', 'gravityformssalesforce' );

			if ( is_wp_error( $token_result ) && $token_result->get_error_code() === 'API_DISABLED_FOR_ORG' ) {
				$error_message .= esc_html__( ' This Salesforce account does not have API access.', 'gravityformssalesforce' );
			}

			// Add error message.
			$this->add_error_notice( __METHOD__, $error_message );
			return;
		}

		$this->log_debug( __METHOD__ . '(): Auth code was exchanged for token successfully.' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'gf_settings',
					'subview' => $this->get_slug(),
				),
				admin_url( 'admin.php' )
			)
		);

		exit();


	}

	/**
	 * Add an error notice to admin if something goes awry. Also logs error to error_log.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method  The method being called.
	 * @param string $message The message to display.
	 *
	 * @return void
	 */
	private function add_error_notice( $method, $message ) {
		add_action( 'admin_notices', function () use ( $message ){
			printf( '<div class="notice below-h1 notice-error gf-notice"><p>%1$s</p></div>', $message );
		} );

		$this->log_error( $method . ': ' . $message );
	}

	/**
	 * Get the payload from the OAuth response.
	 *
	 * @since 1.0
	 *
	 * @return array The auth payload.
	 */
	private function get_oauth_payload() {
		$payload = array_filter(
			array(
				'auth_payload' => rgpost( 'auth_payload' ),
				'auth_error'   => rgpost( 'auth_error' ),
				'state'        => rgpost( 'state' ),
			)
		);

		if ( count( $payload ) === 2 || isset( $payload['auth_error'] ) ) {
			return $payload;
		}

		$payload = get_transient( "gravityapi_response_{$this->_slug}" );

		if ( rgar( $payload, 'state' ) !== get_transient( "gravityapi_request_{$this->_slug}" ) ) {
			return array();
		}

		delete_transient( "gravityapi_response_{$this->_slug}" );

		return is_array( $payload ) ? $payload : array();
	}

	/**
	 * Exchange code for access token and refresh token.
	 *
	 * @since 1.0
	 *
	 * @param string $code code provided by Salesforce API to exchange for access token.
	 *
	 * @return boolean true if tokens successfully saved.
	 */
	private function exchange_code_for_access_token( $code = '' ) {

		// Load the API library file if necessary.
		if ( ! class_exists( 'GF_Salesforce_API' ) ) {
			require_once 'includes/class-gf-salesforce-api.php';
		}

		$redirect_url = GF_Salesforce_API::get_gravity_api_url( '/token' );

		$response = wp_remote_post(
			$redirect_url,
			array( 'body' => array( 'code' => $code ) )
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): Exchange of code for tokens returned error: ' . $response->get_error_message() );

			return false;
		}

		// Save new access token.
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		// No tokens returned. Abort.
		if ( rgempty( 'access_token', $response_data ) || rgempty( 'refresh_token', $response_data ) ) {
			$this->log_error( __METHOD__ . '(): Request to exchange code for tokens returned no tokens.' );

			return false;
		}

		// Error returned. Abort.
		if ( ! rgempty( 'error', $response_data ) ) {
			$this->log_error( __METHOD__ . '(): Request to exchange code for tokens returned error: ' . $response_data['error_description'] );

			return false;
		}

		// Verifying if tokens can be used.
		$api                = new GF_Salesforce_API( $response_data, $this );
		$token_verification = $api->verify_credentials();

		if ( is_wp_error( $token_verification ) ) {
			$this->log_error( __METHOD__ . '(): Access token is not usable. ' . $token_verification->get_error_message() );

			return $token_verification;
		}

		// Everything OK. Save tokens.
		$this->update_auth_data( $response_data );

		return true;
	}

	/**
	 * Updates the stored authentication data with a new set of tokens
	 *
	 * @since 1.0
	 *
	 * @param array $new_auth_data The new tokens.
	 * @param bool  $is_refresh    If the tokens are being refreshed.
	 *
	 * @return array The updated auth data.
	 */
	public function update_auth_data( $new_auth_data, $is_refresh = false ) {
		$settings = $this->get_plugin_settings();

		if ( ! $settings ) {
			$settings = array();
		}

		if ( rgars( $settings, 'auth_token/refresh_token' ) ) {
			$current_refresh_token = $settings['auth_token']['refresh_token'];
		}

		$auth_data = array(
			'access_token'  => rgar( $new_auth_data, 'access_token' ),
			'refresh_token' => $is_refresh ? $current_refresh_token : rgar( $new_auth_data, 'refresh_token' ),
			'domain'        => rgar( $new_auth_data, 'instance_url' ),
			'issued_at'     => rgar( $new_auth_data, 'issued_at' ),
		);

		$settings['auth_token'] = $auth_data;
		// salesforce_state was for one time use only.
		unset( $settings['salesforce_state'] );

		unset( $settings['is_custom_app'] );

		// Save plugin settings.
		$this->update_plugin_settings( $settings );

		return $auth_data;
	}

	/**
	 * Revoke refresh token and remove tokens from Settings. Then send JSON error object or { 'success' => true } .
	 *
	 * @since 1.0
	 */
	public function ajax_deauthorize() {
		check_ajax_referer( 'gf_salesforce_ajax', 'nonce' );

		if ( ! GFCommon::current_user_can_any( $this->_capabilities_settings_page ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Access denied.', 'gravityformssalesforce' ) ) );
		}

		// Try revoking tokens if API is available.
		if ( $this->initialize_api() ) {
			$result = $this->api->revoke_tokens();
			if ( is_wp_error( $result ) ) {
				$this->log_error( __METHOD__ . '(): Unable to revoke refresh token; ' . $result->get_error_message() );
			}
		}

		// Call parent method to prevent adding back of tokens.
		parent::update_plugin_settings( array() );

		wp_send_json_success();
	}

	/**
	 * Add supported notification events.
	 *
	 * @since  1.0
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 */
	public function supported_notification_events( $form ) {

		// If this form does not have a Salesforce feed, return false.
		if ( ! $this->has_feed( $form['id'] ) ) {
			return false;
		}

		// Return Salesforce notification events.
		return array(
			'salesforce_feed_failure' => esc_html__( 'Salesforce Feed Failure', 'gravityformssalesforce' ),
		);

	}

	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Define feed settings fields.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'title'  => __( 'Feed Information', 'gravityformssalesforce' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Feed Name', 'gravityformssalesforce' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => sprintf(
							'<strong>%s</strong>%s',
							esc_html__( 'Name', 'gravityformssalesforce' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformssalesforce' )
						),
					),
					array(
						'name'     => 'objectType',
						'label'    => __( 'Object Type', 'gravityformssalesforce' ),
						'type'     => 'select',
						'onchange' => 'window.reloadFieldMap(this)',
						'choices'  => $this->get_object_type_options(),
						'tooltip'  => sprintf(
							'<strong>%s</strong>%s',
							esc_html__( 'Object Type', 'gravityformssalesforce' ),
							esc_html__( 'Select the object type from your Salesforce account that you want to send data to.', 'gravityformssalesforce' )
						),
					),
				),
			),
			array(
				'title'       => __( 'Field Mapping', 'gravityformssalesforce' ),
				'description' => __( 'Map your Gravity Form fields to the Salesforce fields below.', 'gravityformssalesforce' ),
				'fields'      => array(

					array(
						'name'      => 'fieldMapping',
						'label'     => __( 'Field Mapping', 'gravityformssalesforce' ),
						'type'      => 'field_map',
						'field_map' => $this->get_field_map_options_for_object(),
						'tooltip'   => sprintf(
							'<strong>%s</strong>%s',
							esc_html__( 'Field Mapping', 'gravityformssalesforce' ),
							esc_html__( 'Associate fields from your Salesforce object to the appropriate form fields by selecting the appropriate fields from the dropdown list.', 'gravityformssalesforce' )
						),
					),
					array(
						'name'    => 'updateDuplicates',
						'label'   => __( 'Attempt to update duplicate records', 'gravityformssalesforce' ),
						'type'    => 'toggle',
						'tooltip' => sprintf(
							'<strong>%s</strong>%s',
							esc_html__( 'Update Duplicate Records', 'gravityformssalesforce' ),
							esc_html__( 'If toggled on, the feed will attempt to update records that Salesforce has determined to be duplicates. If toggled off, duplicates will not be updated and if a duplicate is found, an error will be shown in the entry. Duplicate management rules are controlled from your Salesforce account.', 'gravityformssalesforce' )
						),
					),
				),
				'dependency'  => 'objectType',
			),
			array(
				'title'  => __( 'Feed Conditional Logic', 'gravityformssalesforce' ),
				'fields' => array(
					array(
						'name'           => 'feedCondition',
						'type'           => 'feed_condition',
						'label'          => __( 'Conditional Logic', 'gravityformssalesforce' ),
						'checkbox_label' => __( 'Enable', 'gravityformssalesforce' ),
						'tooltip'        => '<strong>' . __( 'Conditional Logic', 'gravityformssalesforce' ) . '</strong>' . __( 'When conditional logic is enabled, form submissions will only be sent to Salesforce when the condition is met. When disabled, all form submissions will be sent.', 'gravityformssalesforce' ),
					),
				),
			),
		);

	}

	/**
	 * Enable feed creation.
	 *
	 * @since  1.0
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->verify_api_credentials();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.0
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return false;

	}


	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Define the feed list table columns.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'   => __( 'Name', 'gravityformssalesforce' ),
			'objectType' => __( 'Object Type', 'gravityformssalesforce' ),
		);

	}


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process feed.
	 *
	 * @since  1.0
	 * @since  1.1 Updated return value for consistency with other add-ons, so the framework can save the feed status to the entry meta.
	 *
	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return array|WP_Error
	 */
	public function process_feed( $feed, $entry, $form ) {
		$body        = array();
		$meta        = rgar( $feed, 'meta' );
		$object_type = rgar( $meta, 'objectType' );

		foreach ( $meta as $meta_key => $meta_value ) {
			if ( strpos( $meta_key, 'fieldMapping' ) === false ) {
				continue;
			}

			$field_name  = str_replace( 'fieldMapping_', '', $meta_key );
			$field_value = $this->get_field_value( $form, $entry, $meta_value );

			if ( empty( $field_value ) ) {
				continue;
			}

			$body[ $field_name ] = $field_value;
		}

		/**
		 * Modify the body of the request before it is executed.
		 *
		 * @since 1.0.0
		 *
		 * @param array $body Array of mapped fields and values.
		 * @param array $form The form object.
		 * @param array $entry The entry object.
		 * @param array $feed The feed object.
		 */
		$body = gf_apply_filters( array( 'gform_salesforce_object_data', $form['id'] ), $body, $form, $entry, $feed );

		$this->log_debug( __METHOD__ . sprintf( '(): Creating %s record with the following data: ', $object_type ) . wp_json_encode( $body ) );
		$created = $this->get_api_instance()->create_new_record( $object_type, $body );
		$this->post_request_action( $entry, $form, $feed );

		if ( is_wp_error( $created ) ) {
			/* translators: %s is a string containing the error response from the API. */
			$this->add_feed_error( sprintf( __( 'Error creating object in Salesforce: %s.', 'gravityformssalesforce' ), $created->get_error_message() ), $feed, $entry, $form );
			GFAPI::send_notifications( $form, $entry, 'salesforce_feed_failure' );

			return $created;
		}

		if ( rgars( $created, '0/errorCode' ) === 'DUPLICATES_DETECTED' ) {
			$existing_record = $this->get_record_to_update( $created );

			if ( $existing_record && rgar( $meta, 'updateDuplicates' ) === '1' ) {
				$this->log_debug( __METHOD__ . sprintf( '(): Duplicate record (ID: %s) discovered by Salesforce, attempting to update the record.', $existing_record ) );

				$updated = $this->get_api_instance()->update_record( $existing_record, $object_type, $body );
				$this->post_request_action( $entry, $form, $feed );
				if ( is_wp_error( $updated ) ) {
					/* translators: %s is a string containing the error response from the API. */
					$this->add_feed_error( sprintf( __( 'Error updating object in Salesforce: %s.', 'gravityformssalesforce' ), $updated->get_error_message() ), $feed, $entry, $form );
					GFAPI::send_notifications( $form, $entry, 'salesforce_feed_failure' );

					return $updated;
				}

				$this->log_debug( __METHOD__ . '(): Record updated.' );
				$this->add_note(
					$entry['id'],
					/* translators: %1$s is the object type. %2$s is the ID of the updated record. */
					sprintf( __( 'Updated %1$s object. ID: %2$s.', 'gravityformssalesforce' ), $object_type, $existing_record ),
					'success'
				);

				return $entry;
			} else {
				/* translators: %1$s is the ID of the duplicate record. %2$s is the feed ID. */
				$this->add_feed_error( sprintf( __( 'Salesforce found a duplicate record (ID: %1$s) but updating is disabled in the feed settings (ID: %2$s).', 'gravityformssalesforce' ), $existing_record, rgar( $feed, 'id' ) ), $feed, $entry, $form );
				GFAPI::send_notifications( $form, $entry, 'salesforce_feed_failure' );

				return new WP_Error( 'duplicate_detected', 'Duplicate record found but updating is disabled in the feed settings.', $created );
			}
		}

		$this->log_debug( __METHOD__ . '(): Created new ' . $object_type . ' record. ID: ' . rgar( $created, 'id' ) );
		$this->add_note(
			$entry['id'],
			/* translators: %1$s is the object type. %2$s is the ID of the updated record. */
			sprintf( __( 'Created %1$s object in Salesforce. ID: %2$s.', 'gravityformssalesforce' ), $object_type, rgar( $created, 'id' ) ),
			'success'
		);

		return $entry;
	}

	/**
	 * Fires the post request action.
	 *
	 * @since 1.0
	 *
	 * @param array $entry The current entry being processed.
	 * @param array $form  The current form being processed.
	 * @param array $feed  The current feed being processed.
	 *
	 */
	public function post_request_action( $entry, $form, $feed ) {
		$response     = $this->get_api_instance()->get_last_response();
		$request_url  = $this->get_api_instance()->get_last_request_url();
		$request_args = $this->get_api_instance()->get_last_request_args();
		/**
		 * Fires after a request is made to Salesforce.
		 *
		 * @since 1.0
		 *
		 * @param array|WP_Error $response     The response from the Salesforce API.
		 * @param string         $request_url  The URL of the request.
		 * @param array          $request_args The arguments of the request.
		 * @param array          $entry        The entry currently being processed.
		 * @param array          $form         The form currently being processed.
		 * @param array          $feed         The feed currently being processed.
		 */
		do_action( 'gform_salesforce_post_request', $response, $request_url, $request_args, $entry, $form, $feed );
	}

	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get the formatted field map options for a specific sobject type.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_field_map_options_for_object() {
		$object_type = $this->get_setting( 'objectType' );
		$fields      = $this->get_api_instance()->get_fields_for_object( $object_type );
		$options     = array();

		if ( empty( $fields ) ) {
			return $options;
		}

		foreach ( $fields as $field ) {
			$options[] = array(
				'name'         => $field['name'],
				'label'        => $field['label'],
				'required'     => false,
				'allow_custom' => true,
			);
		}

		return $options;
	}

	/**
	 * Get the formatted options for a specific sobject type.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_field_options_for_object() {
		$object_type = $this->get_setting( 'objectType' );
		$fields      = $this->get_api_instance()->get_fields_for_object( $object_type );
		$options     = array();

		if ( empty( $fields ) ) {
			return $options;
		}

		foreach ( $fields as $field ) {
			$options[] = array(
				'label' => $field['label'],
				'value' => $field['name'],
			);
		}

		return $options;
	}

	/**
	 * Get the formatted choices for object types associated with this account.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_object_type_options() {
		$api   = $this->get_api_instance();
		$types = $api->get_object_types( true );

		if ( empty( $types ) ) {
			return array();
		}

		$choices = array(
			array(
				'label' => __( 'Select an Object Type', 'gravityformssalesforce' ),
				'value' => '',
			),
		);

		foreach ( $types as $type ) {
			$choices[] = array(
				'label' => $type['label'],
				'value' => $type['name'],
			);
		}

		return $choices;
	}

	/**
	 * Verify if the existing credentials are correct.
	 *
	 * @since 1.0
	 *
	 * @return bool | null Returns true if the credentials are correct, false if they are not, and null if the credentials are not set.
	 */
	public function verify_api_credentials() {
		$verified = $this->get_api_instance()->verify_credentials();

		if ( is_wp_error( $verified ) ) {
			$error_msg = $verified->get_error_message();
			$this->add_error_notice( __METHOD__, $error_msg );
			return false;
		}

		return $verified ? true : null;
	}

	public function get_auth_settings() {

		$settings = $this->get_plugin_settings();

		$is_custom_app = rgar( $settings, 'is_custom_app' );
		if ( $is_custom_app ) {
			$auth_settings = array(
				'is_custom_app'   => 1,
				'domain'          => rgar( $settings, 'domain' ),
				'consumer_key'    => rgar( $settings, 'consumer_key' ),
				'consumer_secret' => rgar( $settings, 'consumer_secret' ),
			);
		} else {
			$auth_settings = array(
				'is_custom_app' => 0,
				'domain'        => rgars( $settings, 'auth_token/domain' ),
				'access_token'  => rgars( $settings, 'auth_token/access_token' ),
				'refresh_token' => rgars( $settings, 'auth_token/refresh_token' ),
			);
		}

		$is_custom_app_configured = $is_custom_app && rgar( $auth_settings, 'consumer_key' );
		$is_oauth_connected       = ! $is_custom_app && rgar( $auth_settings, 'access_token' );

		return $is_custom_app_configured || $is_oauth_connected ? $auth_settings : false;
	}

	/**
	 * Get the current instance of the API class.
	 *
	 * @since 1.0
	 *
	 * @return GF_Salesforce_API|null
	 */
	public function get_api_instance() {
		$api = $this->api;

		if ( ! is_null( $this->api ) ) {
			return $this->api;
		}

		$auth_data = $this->get_auth_settings();
		$this->api = new GF_Salesforce_API( $auth_data, $this );

		return $this->api;
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.0
	 *
	 * @return string For newer versions of Gravity Forms, return the CSS class associated with the icon. Otherwise, URL to the local icon file.
	 */
	public function get_menu_icon() {
		return $this->is_gravityforms_supported( '2.7.16.1' ) ? 'gform-icon--salesforce' : file_get_contents( $this->get_base_path() . '/assets/img/salesforce.svg' );
	}

	/**
	 * Get the record ID to update from the response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $response The response from the API.
	 *
	 * @return string The record ID to update.
	 */
	private function get_record_to_update( $response ) {
		$matches          = rgars( $response, '0/duplicateResult/matchResults/0/matchRecords' );
		$existing_record  = '';
		$match_confidence = 0;

		foreach ( $matches as $match ) {
			if ( rgar( $match, 'matchConfidence' ) > $match_confidence ) {
				$existing_record = rgars( $match, 'record/Id' );
			}
		}

		if ( ! $existing_record ) {
			$this->log_error( __METHOD__ . '(): No record ID found in the response.' );
		}

		return $existing_record;
	}
}
