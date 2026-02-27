<?php

/**
 * GF_Salesforce_API
 *
 * Handles API calls to the Salesforce REST API.
 *
 * @since 1.0
 */
class GF_Salesforce_API {

	protected $verification_result = null;
	protected $token;
	protected $secret;
	protected $domain;
	protected $access_token;
	protected $refresh_token;
	protected $addon;
	protected $is_custom_app;

	/**
	 * The response of the last request made by the API
	 *
	 * @since 1.0
	 * @var array|WP_Error The last response from the API.
	 */
	protected $last_response;

	/**
	 * The request URL of the last request made by the API
	 *
	 * @since 1.0
	 * @var string The last request URL.
	 */
	protected $last_request_url;

	/**
	 * The arguments of the last request made by the API.
	 *
	 * @since 1.0
	* @var array The last request arguments.
	 */
	protected $last_request_args;

	/**
	 * Constructor for GF_Salesforce_API
	 *
	 * @param string        $auth_data The array of auth data.
	 * @param GF_Salesforce $addon     The GF_Salesforce instance.
	 */
	public function __construct( $auth_data, $addon ) {
		$this->addon         = $addon;
		$this->is_custom_app = rgar( $auth_data, 'is_custom_app' );
		$this->domain        = rgar( $auth_data, 'domain' ) ? rgar( $auth_data, 'domain' ) : rgar( $auth_data, 'instance_url' );
		$this->refresh_token = rgar( $auth_data, 'refresh_token' );
		$this->token         = rgar( $auth_data, 'consumer_key' );
		$this->secret        = rgar( $auth_data, 'consumer_secret' );
		$this->access_token  = $this->is_custom_app && empty( rgar( $auth_data, 'access_token' ) ) ? $this->get_access_token() : rgar( $auth_data, 'access_token' );
	}

	/**
	 * Get the correct base URL.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_base_url() {
		return sprintf( '%s/services/data/%s/', $this->domain, GF_SALESFORCE_API_VERSION );
	}

	/**
	 * Get the correct base auth URL.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_url() {
		return sprintf( '%s/services/oauth2/', $this->domain );
	}

	/**
	 * Get Gravity API URL for path.
	 *
	 * @since 1.0
	 *
	 * @param string $path Endpoint path.
	 *
	 * @return string URL for Gravity API endpoint.
	 */
	public static function get_gravity_api_url( $path = '' ) {

		if ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		return defined( 'GRAVITY_API_URL' ) ? GRAVITY_API_URL . '/auth/salesforce' . $path : self::$gravity_api_url . '/auth/salesforce' . $path;

	}

	/**
	 * Get object types for the account.
	 *
	 * @since 1.0
	 *
	 * @param bool $cache - Whether to query the cache first.
	 *
	 * @return array
	 */
	public function get_object_types( $cache = true ) {
		$object_types = false;
		$has_objects  = false;

		$object_request_path = 'sobjects';

		/**
		 * Allows the gf_salesforce_object_request_path filter to modify the object request path and return specific object types.
		 *
		 * @since 1.0
		 *
		 * @param string $object_request_path The object name that'll be passed to query the Object Type dropdown.
		 *
		 * @return string
		 */
		$object_request_path = apply_filters( 'gform_salesforce_object_request_path', $object_request_path );

		if ( $cache ) {
			$cache_key    = sprintf( 'gf_salesforce_object_types_%s_%s', base64_encode( $this->access_token ), $object_request_path );
			$object_types = GFCache::get( $cache_key, $has_objects, true );
		}

		if ( $has_objects ) {
			return $object_types;
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
		);

		$response = $this->make_request_with_possible_retry( $object_request_path, array(), $headers );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data = json_decode( $response, true );

		if ( rgar( $data, 'objectDescribe' ) ) {
			$types = array();
			array_push( $types, rgar( $data, 'objectDescribe' ) );
		} else {
			$types = rgar( $data, 'sobjects' );
		}

		if ( empty( $types ) ) {
			return array();
		}

		$only_updateable = array_filter( $types, function ( $item ) {
			return $item['updateable'] && $item['updateable'] !== 'false';
		} );

		if ( $cache ) {
			GFCache::set( $cache_key, $only_updateable, true, DAY_IN_SECONDS );
		}

		return $only_updateable;
	}

	/**
	 * Get the fields available for a given object type.
	 *
	 * @since 1.0
	 *
	 * @param string $object_type - The object type to query.
	 * @param bool   $cache       - Whether to query cache first.
	 *
	 * @return array|false|mixed|string|null
	 */
	public function get_fields_for_object( $object_type, $cache = true ) {

		// If the object type is empty, abort early returning an empty list of fields.
		if ( empty( $object_type ) ) {
			return array();
		}

		$has_fields = false;

		if ( $cache ) {
			$cache_key = sprintf( 'gf_salesforce_object_type_fields_%s', base64_encode( $this->access_token . $object_type ) );
			$fields    = GFCache::get( $cache_key, $has_fields, true );
		}

		if ( $has_fields ) {
			return $fields;
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
		);

		$path = sprintf( 'sobjects/%s/describe', $object_type );

		$response = $this->make_request_with_possible_retry( $path, array(), $headers );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$data   = json_decode( $response, true );
		$fields = rgar( $data, 'fields' );

		if ( empty( $fields ) ) {
			return array();
		}

		$only_updateable = array_filter( $fields, function ( $item ) {
			return $item['updateable'] && $item['updateable'] !== 'false';
		} );

		if ( $cache ) {
			GFCache::set( $cache_key, $only_updateable, true, DAY_IN_SECONDS );
		}

		return $only_updateable;
	}

	/**
	 * Verify the current credentials.
	 *
	 * @since 1.0.0
	 **
	 * @return bool | WP_Error Returns true if credentials are valid and can be used. Returns false if credentials have not been set. Returns a WP_Error if credentials are invalid.
	 */
	public function verify_credentials() {

		// Auth credentials are not set.
		if ( empty( $this->token ) && empty( $this->access_token ) ) {
			return false;
		}

		// Check memoized response first to avoid multiple calls per server hit.
		if ( $this->verification_result !== null ) {
			return $this->verification_result;
		}

		if ( $this->access_token === null ) {
			$this->verification_result = new WP_Error( 'invalid_access_token', esc_html__( 'Could not retrieve access token.', 'gravityformssalesforce' ) );

			return $this->verification_result;
		}

		$result = $this->make_request_with_possible_retry( 'limits', array(), array( 'Authorization' => 'Bearer ' . $this->access_token ) );

		$log = is_wp_error( $result ) ? ' Error: ' . $result->get_error_message() : 'Valid';
		$this->addon->log_debug( __METHOD__ . '(): Result of verify API credentials check: ' . $log );

		$this->verification_result = is_wp_error( $result ) ? $result : true;

		return $this->verification_result;
	}

	/**
	 * Get the access token for custom apps, either from the cache or the API directly.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function get_access_token( $cache = true ) {

		if ( empty( $this->token ) || empty( $this->secret ) ) {
			return null;
		}

		// Look for custom app cached access token.
		$cache_key    = sprintf( 'gf_salesforce_access_key_%s', base64_encode( $this->token . $this->secret . $this->domain ) );
		$access_token = $cache ? GFCache::get( $cache_key ) : null;

		if ( ! $access_token ) {
			$access_token = $this->request_access_token();
			if ( ! empty( $access_token ) ) {
				GFCache::set( $cache_key, $access_token, true, DAY_IN_SECONDS );
			}
		}

		return $access_token;
	}

	/**
	 * Request an access token from the API using the client_credentials flow.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function request_access_token() {

		if ( empty( $this->token ) || empty( $this->secret ) ) {
			return null;
		}

		$body = array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->token,
			'client_secret' => $this->secret,
		);

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$response = $this->make_request( 'token', $body, $headers, 'POST', true );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return rgar( $data, 'access_token' );
	}

	/**
	 * Refresh the access token.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function attempt_token_refresh() {
		$settings      = $this->addon->get_plugin_settings();
		$refresh_token = rgar( $settings['auth_token'], 'refresh_token' );

		$response = wp_remote_post(
			self::get_gravity_api_url( 'refresh' ),
			array( 'body' => array( 'refresh_token' => $refresh_token ) )
		);

		if ( rgar( $response, 'response' ) ) {
			if ( rgar( $response['response'], 'code' ) === 200 ) {
				$decoded_response = json_decode( $response['body'], true );
				$this->addon->update_auth_data( $decoded_response, true );
				$this->access_token = rgar( $decoded_response, 'access_token' );
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): Exchange of code for tokens returned error: ' . $response->get_error_message() );

			return;
		}

		// Save new access token.
		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );
	}

	/**
	 * Create a new record.
	 *
	 * @since 1.0
	 *
	 * @param string $object_type - The object type to query.
	 * @param array  $body        - The body containing all the info for the record.
	 *
	 * @return array|WP_Error
	 */
	public function create_new_record( $object_type, $body ) {

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Content-Type'  => 'application/json',
		);

		$path = sprintf( 'sobjects/%s', $object_type );

		$response = $this->make_request_with_possible_retry( $path, wp_json_encode( $body ), $headers, 'POST' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( $response, true );

		return $data;
	}

	/**
	 * Update a record with the given values.
	 *
	 * @since 1.0
	 *
	 * @param string $existing_record The ID of the existing record.
	 * @param string $object_type     The object type to query.
	 * @param array  $body            The values to update.
	 *
	 * @return array|mixed|string|WP_Error
	 */
	public function update_record( $existing_record, $object_type, $body ) {

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Content-Type'  => 'application/json',
		);

		$path = sprintf( 'sobjects/%s/%s', $object_type, $existing_record );

		$response = $this->make_request_with_possible_retry( $path, wp_json_encode( $body ), $headers, 'PATCH' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( $response, true );

		return $data;
	}

	/**
	 * Revoke Salesforce refresh token
	 *
	 * @since 1.0
	 *
	 * @return boolean|WP_Error Result of the revoking request, if network request failed return error.
	 */
	public function revoke_tokens() {

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$request_options = array(
			'token' => $this->refresh_token ? $this->refresh_token : $this->access_token,
		);

		return $this->make_request( 'revoke', $request_options, $headers, 'POST', true );

	}

	/**
	 * Wrapper for making a request to the API with an attempt to refresh the access token if the request fails.
	 *
	 * @since 1.0
	 *
	 * @param string $path    - The path for the request.
	 * @param array  $body    - The body to pass.
	 * @param array  $headers - Headers to pass.
	 * @param string $method  - The METHOD type.
	 * @param bool   $is_auth - Whether this is an auth request.
	 *
	 * @return array|string|WP_Error
	 */
	private function make_request_with_possible_retry( $path = '', $body = array(), $headers = array(), $method = 'GET', $is_auth = false ) {

		$response = $this->make_request( $path, $body, $headers, $method, $is_auth );

		$retrieved_response_code = wp_remote_retrieve_response_code( $response );

		if ( (int) $retrieved_response_code === 401 ) {

			if ( $this->is_custom_app ) {
				$this->access_token = $this->get_access_token( false );
			} else {
				$this->attempt_token_refresh();
			}

			if ( $headers['Authorization'] ) {
				$headers['Authorization'] = 'Bearer ' . $this->access_token;
			}

			$response = $this->make_request( $path, $body, $headers, $method, $is_auth );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		return $response_body;
	}

	/**
	 * Make a request to the API.
	 *
	 * @since 1.0
	 *
	 * @param string $path    - The path for the request.
	 * @param array  $body    - The body to pass.
	 * @param array  $headers - Headers to pass.
	 * @param string $method  - The METHOD type.
	 * @param bool   $is_auth - Whether this is an auth request.
	 *
	 * @return array|string|WP_Error
	 */
	private function make_request( $path = '', $body = array(), $headers = array(), $method = 'GET', $is_auth = false ) {
		// Log request.
		gf_salesforce()->log_debug( __METHOD__ . '(): Making request to: ' . $path );

		$request_url = $is_auth ? $this->get_auth_url() . $path : $this->get_base_url() . $path;

		$args = array(
			'method'    => $method,
			/**
			 * Filters if SSL verification should occur.
			 *
			 * @param bool false If the SSL certificate should be verified. Defaults to false.
			 *
			 * @return bool
			 */
			'sslverify' => apply_filters( 'https_local_ssl_verify', false, $request_url ),
			/**
			 * Sets the HTTP timeout, in seconds, for the request.
			 *
			 * @param int 30 The timeout limit, in seconds. Defaults to 30.
			 *
			 * @return int
			 */
			'timeout'   => apply_filters( 'http_request_timeout', 30, $request_url ),
		);

		$args['headers'] = $headers;

		if ( 'GET' === $method || 'POST' === $method || 'PUT' === $method || 'PATCH' === $method ) {
			$args['body'] = empty( $body ) ? '' : $body;
		}

		// Execute request.
		$response = wp_remote_request(
			$request_url,
			$args
		);

		$this->last_response     = $response;
		$this->last_request_args = $args;
		$this->last_request_url  = $request_url;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body           = wp_remote_retrieve_body( $response );
		$retrieved_response_code = wp_remote_retrieve_response_code( $response );

		if ( (int) $retrieved_response_code === 401 ) {
			return $response;
		}

		if ( (int) $retrieved_response_code < 200 || (int) $retrieved_response_code > 299 ) {

			$response_data = json_decode( $response_body, true );
			if ( isset( $response_data[0] ) ) {
				$error_message = empty( $response_data[0]['message'] ) ? '' : $response_data[0]['message'];
				$error_code    = empty( $response_data[0]['errorCode'] ) ? '' : $response_data[0]['errorCode'];
			} else {
				$error_message = empty( $response_data['error_description'] ) ? '' : $response_data['error_description'];
				$error_code    = empty( $response_data['error'] ) ? '' : $response_data['error'];
			}

			if ( $error_code === 'DUPLICATES_DETECTED' ) {
				return $response;
			}

			gf_salesforce()->log_error( __METHOD__ . '(): Unable to validate with the Salesforce API; code: ' . $retrieved_response_code . '; message: ' . $error_message );

			return new WP_Error( $error_code, $error_message, $retrieved_response_code );
		}

		return $response;
	}

	/**
	 * Get the last response from the API.
	 *
	 * @since 1.0
	 *
	 * @return array|WP_Error The last response from the API.
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Get the last request URL.
	 *
	 * @since 1.0
	 *
	 * @return string The last request URL.
	 */
	public function get_last_request_url() {
		return $this->last_request_url;
	}

	/**
	 * Get the last request arguments.
	 *
	 * @since 1.0
	 *
	 * @return array The last request arguments.
	 */
	public function get_last_request_args() {
		unset( $this->last_request_args['headers']['Authorization'] );
		return $this->last_request_args;
	}

}
