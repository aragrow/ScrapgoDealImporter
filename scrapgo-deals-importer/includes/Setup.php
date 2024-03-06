<?php

class ScrapGoDealsSetup Extends ScrapGoDealsUtilities {
    
    // Static flag to track whether the class has been instantiated
    private static $instance;
    private $customtype = '';

    // Private constructor to prevent direct instantiation
    private function __construct() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Other constructor code...

        // Register activation hook within the class constructor.
        register_activation_hook(SCRAPGO_WITH_CLASSES_FILE, [$this, 'activate']);
        
        // Register deactivation hook within the class constructor.
        register_deactivation_hook(SCRAPGO_WITH_CLASSES_FILE, [$this, 'deactivate']);

        // Add a menu item under the 'Settings' menu
        add_action('admin_menu', [$this, 'create_menu']);

        // Register and initialize settings
        add_action('admin_init', [$this, 'initialize_settings']);

        add_action('add_meta_boxes', [$this, 'custom_meta_box']);

        add_action('wp_ajax_nopriv_scrapgo_run_import', [$this, 'import_manually']);

    }

    // Method to get the instance of the class
    public static function get_instance() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function activate() {
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Activation tasks, if any

    }

    public function deactivate() {
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Activation tasks, if any

    }

    public function create_menu() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        // Add main menu page.
        add_menu_page(
            __('Scrapgo Settings', 'scrapgo'),
            __('Scrapgo', 'scrapgo'),
            'scrapgo_admin',
            'scrapgo',
            [$this, 'settings_page'],
            '',
            90
        );
        
        // Add submenu page.
        add_submenu_page(
            'scrapgo',
            __('Test API', 'scrapgo'),
            __('Test API', 'scrapgo'),
            'scrapgo_admin',
            'run-manual',
            [$this, 'test_api_page']
        );

    }

    // Function to display the settings page
    public function settings_page() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        ?>
        <div class="wrap">
            <h2>Scrapgo Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('scrapgo_settings_group');
                do_settings_sections('scrapgo-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
   
   // Function to display the settings page
   public function test_api_page() {
        
    if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

    ?>
    <div class="wrap">
        <h2>Test API</h2>
        <form method="post" action="options.php">
            <?php
            $url = $this->sanitize_url(get_option('scrapgo_api_url'));
    
            $content_type =$this->sanitize_string(get_option('scrapgo_content_type'));
            if (isset($content_type) && empty($content_type)) $args['Header'][] = ['Content-Type' => $content_type];
    
            $token =$this->sanitize_string(get_option('scrapgo_authorization_token'));
            if (isset($token) && empty($token)) $args['Header'][] = ['Bearer' => $token];
    
    
            //$url = 'https://scrapgoapp.com/api/1.1/obj/Deal';
            $response = wp_remote_get($url, $args);
            if (!is_wp_error($response)) 
                var_dump($response["body"]);
            else
                var_dump($response);
            ?>
        </form>
    </div>
    <?php
}

    public function initialize_settings() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        // Register settings group
        register_setting(
            'scrapgo_settings_group',       // Option group
            'scrapgo_debug',                // Option name
            [$this, 'sanitize_checkbox']    // Option sanitize
        );
        
        register_setting(
            'scrapgo_settings_group',   // Option group
            'scrapgo_api_url',          // Option name
            [$this, 'sanitize_url']     // Option sanitize
        );
        
        register_setting(
            'scrapgo_settings_group',  // Option group
            'scrapgo_authorization_token',              // Option name
            [$this, 'sanitize_string']         // Option sanitize
        );

        
        register_setting(
            'scrapgo_settings_group',  // Option group
            'scrapgo_custom_type',              // Option name
            [$this, 'sanitize_string']         // Option sanitize
        );

        register_setting(
            'scrapgo_settings_group',  // Option group
            'scrapgo_content_type',              // Option name
            [$this, 'sanitize_string']         // Option sanitize
        );

        // Add settings section
        add_settings_section(
            'scrapgo_settings_section',             // ID
            'Settings',                             // Title
            [$this,'settings_section_callback'],    // Callback
            'scrapgo-settings'                      // Page
        );
    
        // Add settings field
        add_settings_field(
            'scrapgo_debug',                    // ID
            'Enable Debug Mode',                // Title
            [$this,'debug_checkbox_callback'],  // Callback
            'scrapgo-settings',                 // Page
            'scrapgo_settings_section'          // Section
        );

        // Add settings field
        add_settings_field(
            'scrapgo_api_url',                    // ID
            'API URL',                          // Title
            [$this,'api_url_callback'],         // Callback
            'scrapgo-settings',                 // Page
            'scrapgo_settings_section'          // Section
        );

        // Add settings field
        add_settings_field(
            'scrapgo_content_type',             // ID
            'API Content Type',                        // Title
            [$this,'content_type_callback'],    // Callback
            'scrapgo-settings',                 // Page
            'scrapgo_settings_section'          // Section
        );

        // Add settings field
        add_settings_field(
            'scrapgo_authorization_token',          // ID
            'API Token',                            // Title
            [$this,'authorization_token_callback'], // Callback
            'scrapgo-settings',                     // Page
            'scrapgo_settings_section'              // Section
        );

        // Add settings field
        add_settings_field(
            'scrapgo_custom_type',          // ID
            'Custom Type',                            // Title
            [$this,'custom_type_callback'], // Callback
            'scrapgo-settings',                     // Page
            'scrapgo_settings_section'              // Section
        );
    }
    
    // Section callback function
    public function settings_section_callback() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        echo '<p>Enable or disable debug mode for Scrapgo.</p>';
    }
    
    // Field callback function
    public function debug_checkbox_callback() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        $debug_option = $this->sanitize_checkbox(get_option('scrapgo_debug'));
        echo '<input type="checkbox" id="scrapgo_debug" name="scrapgo_debug" ' . checked(1, $debug_option, false) . 'value="1">';
    }

    // Field callback function
    public function api_url_callback() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $debug_option = $this->sanitize_url(get_option('scrapgo_api_url'));
        $placeholder = __('Enter Authorization Token', 'scrapgo');
        echo "<input type='text' class='regular-text' id='scrapgo_api_url' name='scrapgo_api_url' value='$debug_option' placeholer='$placeholder' required>";
    }
    
    // Field callback function
    public function authorization_token_callback() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $debug_option = $this->sanitize_string(get_option('scrapgo_authorization_token'));
        $placeholder = __('Enter Authorization Token', 'scrapgo');
        echo "<input type='text' class='regular-text' id='scrapgo_authorization_token' name='scrapgo_authorization_token' value='$debug_option' placeholer='$placeholder'>";
    }

    // Field callback function
    public function content_type_callback() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $option = $this->sanitize_string(get_option('scrapgo_content_type'));
        
        $option2sel = ($option == 'text/json')?'selected':'';
        echo '<select id="scrapgo_content_type" name="scrapgo_content_type">';
        echo '    <option value="application/json" selected>application/json</option>';
        echo '    <option value="text/json" '.$option2sel.'>text/json</option>';
        echo '</select>';
    
    }


    // Field callback function
    public function custom_type_callback() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $option = $this->sanitize_string(get_option('scrapgo_custom_type'));
        $placeholder = __('Select Custom Type', 'scrapgo');
        // Get all post types
        $post_types = get_post_types( array( '_builtin' => false ), 'names' );
       
        echo '<select id="scrapgo_custom_type" name="scrapgo_custom_type"  required >';
        echo '<option value="job_listing" selected>Job Listing</option>';
        /*
        echo '<option value="" selected>' . esc_html( $placeholder ) . '</option>';
        // Output options
        foreach ( $post_types as $post_type ) {
            $selected = (esc_attr( $post_type ) == $option) ? 'selected' : '';
            echo '<option value="' . esc_attr( $post_type ) . '" '.$selected.'>' . esc_html( ucfirst($post_type) ) . '</option>';
        }
        */
        echo '</select>';
    }

    // Add meta box to post editor
    public function custom_meta_box() {
        $this->customtype = $this->sanitize_string(get_option('scrapgo_custom_type'));
        add_meta_box(
            'custom-meta-box', // ID of the meta box
            'Attributes', // Title of the meta box
            [$this, 'display_custom_meta_box'], // Callback function to display the meta box content
            $this->customtype, // Post type to which the meta box should be added
            'normal', // Context where the meta box should be displayed
            'default' // Priority of the meta box
        );
    }
    // Callback function to display the content of the meta box

    public function display_custom_meta_box($post) {


        $custom_fields = get_post_custom($post->ID);
 
        ksort($custom_fields);

        echo '<table colspan="2">
            <tbody>';
      
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $key => $values) {
                echo '<tr>';
                echo "<td><label for='$key'>$key </label><td>";
                foreach ($values as $value) {
                    $value = esc_attr($value);
                    echo "<td><input class='regular-text' type='text' id='$key' name='$key' value='{$value}' readonly></td>";
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="2">No custom fields found for this post.</td></tr>';
        }
      
        echo '</tbody>
                </table>';
       

    }

    public function import_scheduled() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        // Schedule import to run once a day
        if (!wp_next_scheduled('scrapgo_importer_event')) {
            // Get the current time
            $current_time = current_time('timestamp');

            // Add 5 seconds to the current time. I add 5 seconds because I have experiencing 
            //  some instances of the schedule being missed when using just time();
            $new_time = $current_time + 5;

            wp_schedule_event($new_time, 'hourly', 'scrapgo_importer_event');
        }
    }
    
}

$singleton_instance = ScrapGoDealsSetup::get_instance();
