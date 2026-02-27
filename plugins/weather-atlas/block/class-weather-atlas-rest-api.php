<?php
	
	class Weather_Atlas_REST_API
	{
		public function __construct()
		{
			// Hook into WordPress's REST API initialization to register custom routes
			add_action( 'rest_api_init', array (
				$this,
				'register_routes'
			) );
		}
		
		public function register_routes()
		{
			// Register a REST API route for fetching weather widgets
			register_rest_route( 'weather-atlas/v1', '/widgets', array (
				'methods'             => 'GET',
				// Allow only GET requests for this route
				'callback'            => array (
					$this,
					'get_weather_widgets'
				),
				// Callback function to handle the route
				'permission_callback' => '__return_true'
				// Open to all users without permission checks
			) );
		}
		
		public function get_weather_widgets()
		{
			global $wpdb; // Access the global WordPress database object
			$prefix = 'weather_atlas_widget_'; // Prefix for widget options stored in the database
			
			// Query to fetch all options with names starting with the prefix
			$query   = $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%' );
			$widgets = $wpdb->get_results( $query ); // Fetch the query results
			
			// Sort the widgets by 'widget_name' in ascending order
			usort( $widgets, function( $a, $b ) {
				$a_data = maybe_unserialize( $a->option_value ); // Safely unserialize option value for widget A
				$b_data = maybe_unserialize( $b->option_value ); // Safely unserialize option value for widget B
				
				// Sanitize 'widget_name' for sorting and provide fallback values
				return strcmp( sanitize_text_field( $a_data[ "widget_name" ] ?? '' ), // Default to an empty string if undefined
				               sanitize_text_field( $b_data[ "widget_name" ] ?? 'Unnamed Widget' ) // Default to 'Unnamed Widget' if undefined
				);
			} );
			
			$formatted_widgets = []; // Initialize an array to store formatted widgets
			
			foreach ( $widgets as $widget )
			{
				$widget_data = maybe_unserialize( $widget->option_value ); // Safely unserialize the option value
				
				// Format each widget with sanitized and escaped data
				$formatted_widgets[] = array (
					'id'          => esc_html( str_replace( 'weather_atlas_widget_', '', $widget->option_name ) ),
					// Remove prefix and escape
					'widget_name' => esc_html( $widget_data[ 'widget_name' ] ?? '' )
					// Escape and provide a fallback for widget name
				);
			}
			
			return $formatted_widgets; // Return the formatted widgets as a response
		}
	}