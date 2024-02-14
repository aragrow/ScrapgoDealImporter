<?php

class ScrapGoDealsUtilities {
    
    // Static flag to track whether the class has been instantiated
    private static $instance;
    
    // Private constructor to prevent direct instantiation
    private function __construct() {

    }

    public function sanitize_url( $input ) {
        // Sanitize the URL using WordPress's built-in function
        $sanitized_url = esc_url_raw( $input );
    
        // Return the sanitized URL
        return $sanitized_url;
    }
    
    public function sanitize_string( $input ) {
        // Sanitize the URL using WordPress's built-in function
        $sanitized_string = sanitize_text_field($input);
    
        // Return the sanitized URL
        return $sanitized_string;
    }

    function sanitize_checkbox( $input ) {
        // Sanitize the checkbox input
        $sanitized_checkbox = ( $input == 1 ) ? 1 : 0;
    
        // Return the sanitized checkbox value
        return $sanitized_checkbox;
    }

}