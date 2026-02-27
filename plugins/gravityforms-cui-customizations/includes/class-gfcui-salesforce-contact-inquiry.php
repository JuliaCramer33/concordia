<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GFCUI_Salesforce_Contact_Inquiry
 *
 * Links Contact and Inquiry objects in Salesforce by capturing the Contact ID
 * from the Contact Feed and injecting it into the Inquiry Feed's TargetX_SRMb__Contact__c field.
 *
 * How it works:
 * 1. When Contact Feed completes, stores Contact ID in entry meta
 * 2. Before Inquiry Feed processes, injects Contact ID into the field mapping
 * 3. Inquiry is created with proper Contact relationship
 *
 * @since 1.2.0
 */
class GFCUI_Salesforce_Contact_Inquiry {

    /**
     * Entry meta key for storing Contact ID
     */
    const META_KEY_CONTACT_ID = '_gfcui_salesforce_contact_id';

    /**
     * Feed name identifiers
     */
    const FEED_NAME_CONTACT = 'Contact Feed';
    const FEED_NAME_INQUIRY = 'Inquiry Feed';

    /**
     * Salesforce object types
     */
    const OBJECT_TYPE_CONTACT = 'Contact';
    const OBJECT_TYPE_INQUIRY = 'TargetX_SRMb__Inquiry__c';

    /**
     * Salesforce Inquiry lookup field for Contact
     */
    const INQUIRY_CONTACT_FIELD = 'TargetX_SRMb__Contact__c';

    /**
     * Temporary storage for Contact ID during feed processing
     */
    private $current_contact_id = null;

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // Check if Salesforce add-on is active
        if ( ! class_exists( 'GF_Salesforce' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_salesforce_missing' ) );
            return;
        }

        // Capture Contact ID after Contact feed processes (before API call)
        add_filter( 'gform_salesforce_object_data', array( $this, 'capture_and_inject_contact_id' ), 5, 4 );

        // Store Contact ID after successful API response
        add_action( 'gform_salesforce_post_request', array( $this, 'store_contact_id_in_meta' ), 10, 6 );
    }

    public function admin_notice_salesforce_missing() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>GravityForms CUI Customizations (Salesforce Contact-Inquiry):</strong> Gravity Forms Salesforce Add-On is not active. Contact-Inquiry linking will not work until the add-on is installed and activated.</p>';
        echo '</div>';
    }

    /**
     * Capture Contact ID and inject it into Inquiry feed.
     * This runs BEFORE the API call for both feeds, allowing us to inject the Contact ID
     * into the Inquiry feed if we've already processed the Contact feed.
     *
     * @param array $body  Array of mapped fields and values
     * @param array $form  The form object
     * @param array $entry The entry object
     * @param array $feed  The feed object
     *
     * @return array Modified body with Contact ID injected (for Inquiry feeds)
     */
    public function capture_and_inject_contact_id( $body, $form, $entry, $feed ) {
        // If this is the Inquiry feed and we have a stored Contact ID, inject it
        if ( $this->is_inquiry_feed( $feed ) ) {
            // First check if we have a Contact ID in temporary storage (from same request)
            if ( ! empty( $this->current_contact_id ) ) {
                $body[ self::INQUIRY_CONTACT_FIELD ] = $this->current_contact_id;
                $this->log_debug( __METHOD__ . sprintf( '(): Injected Contact ID (%s) from temporary storage into Inquiry feed for entry %d.', $this->current_contact_id, $entry['id'] ) );
            } else {
                // Fallback: check entry meta (shouldn't normally hit this in same request)
                $contact_id = gform_get_meta( $entry['id'], self::META_KEY_CONTACT_ID );
                if ( ! empty( $contact_id ) ) {
                    $body[ self::INQUIRY_CONTACT_FIELD ] = $contact_id;
                    $this->log_debug( __METHOD__ . sprintf( '(): Injected Contact ID (%s) from entry meta into Inquiry feed for entry %d.', $contact_id, $entry['id'] ) );
                } else {
                    $this->log_debug( __METHOD__ . sprintf( '(): No Contact ID found for entry %d. Inquiry will be created without Contact link.', $entry['id'] ) );
                }
            }
        }

        return $body;
    }

    /**
     * Store Contact ID in entry meta and temporary storage after successful creation.
     *
     * @param array|WP_Error $response     The response from Salesforce API
     * @param string         $request_url  The URL of the request
     * @param array          $request_args The arguments of the request
     * @param array          $entry        The entry currently being processed
     * @param array          $form         The form currently being processed
     * @param array          $feed         The feed currently being processed
     */
    public function store_contact_id_in_meta( $response, $request_url, $request_args, $entry, $form, $feed ) {
        // Only proceed if this is the Contact feed
        if ( ! $this->is_contact_feed( $feed ) ) {
            return;
        }

        // Check if response is valid
        if ( is_wp_error( $response ) ) {
            $this->log_debug( __METHOD__ . '(): Contact creation failed. Error: ' . $response->get_error_message() );
            return;
        }

        // The response body should be JSON, decode it
        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            $this->log_debug( __METHOD__ . '(): Empty response body from Salesforce.' );
            return;
        }

        $data = json_decode( $body, true );
        if ( empty( $data ) ) {
            $this->log_debug( __METHOD__ . '(): Could not decode Salesforce response: ' . $body );
            return;
        }

        // Extract Contact ID from response
        $contact_id = rgar( $data, 'id' );

        if ( empty( $contact_id ) ) {
            $this->log_debug( __METHOD__ . '(): No Contact ID found in Salesforce response. Response: ' . wp_json_encode( $data ) );
            return;
        }

        // Store in temporary property for immediate use by Inquiry feed
        $this->current_contact_id = $contact_id;

        // Also store in entry meta for persistence
        gform_update_meta( $entry['id'], self::META_KEY_CONTACT_ID, $contact_id );

        $this->log_debug( __METHOD__ . sprintf( '(): Stored Contact ID (%s) for entry %d.', $contact_id, $entry['id'] ) );
    }

    /**
     * Check if the feed is a Contact feed.
     *
     * @param array $feed The feed object
     *
     * @return bool True if Contact feed, false otherwise
     */
    protected function is_contact_feed( $feed ) {
        $feed_name   = rgar( $feed, 'meta' ) ? rgar( $feed['meta'], 'feedName' ) : '';
        $object_type = rgar( $feed, 'meta' ) ? rgar( $feed['meta'], 'objectType' ) : '';

        // Match by feed name OR object type
        return ( $feed_name === self::FEED_NAME_CONTACT || $object_type === self::OBJECT_TYPE_CONTACT );
    }

    /**
     * Check if the feed is an Inquiry feed.
     *
     * @param array $feed The feed object
     *
     * @return bool True if Inquiry feed, false otherwise
     */
    protected function is_inquiry_feed( $feed ) {
        $feed_name   = rgar( $feed, 'meta' ) ? rgar( $feed['meta'], 'feedName' ) : '';
        $object_type = rgar( $feed, 'meta' ) ? rgar( $feed['meta'], 'objectType' ) : '';

        // Match by feed name OR object type
        return ( $feed_name === self::FEED_NAME_INQUIRY || $object_type === self::OBJECT_TYPE_INQUIRY );
    }

    /**
     * Log debug messages if Gravity Forms logging is enabled.
     *
     * @param string $message The message to log
     */
    protected function log_debug( $message ) {
        if ( class_exists( 'GFCommon' ) && method_exists( 'GFCommon', 'log_debug' ) ) {
            GFCommon::log_debug( $message );
        }
    }
}
