<?php
/**
* Description:
*   The ScrapGoDealsSetup class manages the setup and configuration of the ScrapGo plugin. It handles tasks such as registering custom post types, 
*   taxonomies, menu pages, settings, and script enqueuing.
* Usage:
*   To utilize the functionality provided by the ScrapGoDealsSetup class, follow these steps:
*       Ensure the class is included in your WordPress plugin or theme.
*       Instantiate the class using the get_instance() method.
*
* Methods:
*   __construct(): Constructor method. Initializes necessary WordPress hooks for activation, deactivation, custom post type registration, custom taxonomy registration,
*        menu creation, settings initialization, and script enqueuing.
*   get_instance(): Static method to retrieve the singleton instance of the class.
*   activate(): Method called during plugin activation. Performs activation tasks, if any.
*   deactivate(): Method called during plugin deactivation. Performs deactivation tasks, if any.
*   custom_post_type(): Method to register the custom post type 'scrapgo'.
*   custom_taxonomy(): Method to register the custom taxonomy for the 'scrapgo' post type.
*   create_menu(): Method to add menu pages under the 'Settings' menu.
*   settings_page(): Method to display the settings page.
*   run_manual_page(): Method to display the manual import page.
*   initialize_settings(): Method to register and initialize plugin settings.
*   settings_section_callback(): Callback function for the settings section.
*   debug_checkbox_callback(): Callback function for the debug mode checkbox.
*   enqueue_admin_scripts(): Method to enqueue scripts for the admin dashboard.
*   enqueue_custom_scripts(): Method to enqueue custom scripts.
*
* Dependencies:
*   The class relies on WordPress hooks and functions for plugin activation, deactivation, menu creation, settings registration, script enqueuing, and other administrative tasks.
 */
class ScrapGoDealsSetup Extends ScrapGoDealsUtilities {
    
    // Static flag to track whether the class has been instantiated
    private static $instance;
    
    // Private constructor to prevent direct instantiation
    private function __construct() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Other constructor code...

        // Register activation hook within the class constructor.
        register_activation_hook(SCRAPGO_WITH_CLASSES_FILE, [$this, 'activate']);
        
        // Register deactivation hook within the class constructor.
        register_deactivation_hook(SCRAPGO_WITH_CLASSES_FILE, [$this, 'deactivate']);

        // Register custom post type during WordPress initialization.
        add_action( 'init', [$this, 'custom_post_type'], 0 );

        // Register custom taxonomy during WordPress initialization.
        add_action( 'init', [$this, 'custom_taxonomy'], 0 );

        // Add a menu item under the 'Settings' menu
        add_action('admin_menu', [$this, 'create_menu']);

        // Register and initialize settings
        add_action('admin_init', [$this, 'initialize_settings']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_scripts']);

        add_action('add_meta_boxes', [$this, 'custom_meta_box']);

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

      /**
     * Registers the Scrapgo Custom Post Type
     */
    public function custom_post_type() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $labels = array(
            'name'                  => _x( 'Scrapgo', 'Post Type General Name', 'scrapgo' ),
            'singular_name'         => _x( 'Scrapgo', 'Post Type Singular Name', 'scrapgo' ),
            'menu_name'             => __( 'Scrapgo', 'scrapgo' ),
            'name_admin_bar'        => __( 'Scrapgo', 'scrapgo' ),
            'archives'              => __( 'Scrapgo Archives', 'scrapgo' ),
            'attributes'            => __( 'Scrapgo Attributes', 'scrapgo' ),
            'parent_item_colon'     => __( 'Parent Scrapgo:', 'scrapgo' ),
            'all_items'             => __( 'All Scrapgo', 'scrapgo' ),
            'add_new_item'          => __( 'Add New Scrapgo', 'scrapgo' ),
            'add_new'               => __( 'Add New', 'scrapgo' ),
            'new_item'              => __( 'New Scrapgo', 'scrapgo' ),
            'edit_item'             => __( 'Edit Scrapgo', 'scrapgo' ),
            'update_item'           => __( 'Update Scrapgo', 'scrapgo' ),
            'view_item'             => __( 'View Scrapgo', 'scrapgo' ),
            'view_items'            => __( 'View Scrapgo', 'scrapgo' ),
            'search_items'          => __( 'Search Scrapgo', 'scrapgo' ),
            'not_found'             => __( 'Not found', 'scrapgo' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'scrapgo' ),
            'featured_image'        => __( 'Featured Image', 'scrapgo' ),
            'set_featured_image'    => __( 'Set featured image', 'scrapgo' ),
            'remove_featured_image' => __( 'Remove featured image', 'scrapgo' ),
            'use_featured_image'    => __( 'Use as featured image', 'scrapgo' ),
            'insert_into_item'      => __( 'Insert into Scrapgo', 'scrapgo' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Scrapgo', 'scrapgo' ),
            'items_list'            => __( 'Scrapgo list', 'scrapgo' ),
            'items_list_navigation' => __( 'Scrapgo list navigation', 'scrapgo' ),
            'filter_items_list'     => __( 'Filter Scrapgo list', 'scrapgo' ),
        );
        $args = array(
            'label'                 => __( 'Scrapgo', 'scrapgo' ),
            'description'           => __( 'Post Type Description', 'scrapgo' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        register_post_type( 'scrapgo', $args );

    }

    /**
     * Registers the Scrapgo Taxonomy for the Scrapgopost type 'scrapgo'.
     */
    public function custom_taxonomy() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        $labels = array(
            'name' => _x( 'Scrapgo Taxonomy', 'taxonomy general name' ),
            'singular_name' => _x( 'Scrapgo Taxonomy', 'taxonomy singular name' ),
            'search_items' =>  __( 'Search Scrapgo Taxonomy' ),
            'all_items' => __( 'All Scrapgo Taxonomy' ),
            'parent_item' => __( 'Parent Scrapgo Taxonomy' ),
            'parent_item_colon' => __( 'Parent Scrapgo Taxonomy:' ),
            'edit_item' => __( 'Edit Scrapgo Taxonomy' ),
            'update_item' => __( 'Update Scrapgo Taxonomy' ),
            'add_new_item' => __( 'Add New Scrapgo Taxonomy' ),
            'new_item_name' => __( 'New Scrapgo Taxonomy Name' ),
            'menu_name' => __( 'Scrapgo Taxonomy' ),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'custom-taxonomy' ),
        );

        // Register the Scrapgo Taxonomy
        register_taxonomy( 'custom_taxonomy', array( 'scrapgo' ), $args );

    }

    public function create_menu() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        // Add main menu page.
        add_menu_page(
            __('Scrapgo Settings', 'scrapgo'),
            __('Scrapgo', 'scrapgo'),
            'manage_options',
            'scrapgo',
            [$this, 'settings_page'],
            '',
            90
        );

        // Add submenu page.
        add_submenu_page(
            'scrapgo',
            __('Manual Import', 'scrapgo'),
            __('Run Import', 'scrapgo'),
            'manage_options',
            'run-manual',
            [$this, 'run_manual_page']
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

    public function run_manual_page() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        ?>
        <div class="wrap">
            <h2>Run Import Manually</h2>
                <p><button id="triggerImportManually" class="button">Run Import Manually</button></p>
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

        $debug_option = $this->sanitize_string(get_option('scrapgo_content_type'));
        $placeholder = __('Content Type', 'scrapgo');
        echo "<input type='text' class='regular-text' id='scrapgo_content_type' name='scrapgo_content_type' value='$debug_option' placeholer='$placeholder'>";
        }

    public function enqueue_admin_scripts() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        wp_enqueue_script('scrapgo-admin-script', SCRAPGO_PLUGIN_URL . 'dist/js/admin.js', array('jquery'), null, true);
        //wp_localize_script('scrapgo-admin-script', 'ajaxurl', admin_url('admin-ajax.php'));
    }

    function enqueue_custom_scripts() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
    }

    // Add meta box to post editor
    public function custom_meta_box() {
        add_meta_box(
            'custom-meta-box', // ID of the meta box
            'Attributes', // Title of the meta box
            [$this, 'display_custom_meta_box'], // Callback function to display the meta box content
            'scrapgo', // Post type to which the meta box should be added
            'normal', // Context where the meta box should be displayed
            'default' // Priority of the meta box
        );
    }
    // Callback function to display the content of the meta box

    public function display_custom_meta_box($post) {


        $custom_fields = get_post_custom($post->ID);

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


}

$singleton_instance = ScrapGoDealsSetup::get_instance();
