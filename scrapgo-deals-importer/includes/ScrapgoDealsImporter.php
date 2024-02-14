<?php
/**
* Description:
*   The ScrapGoDealsImporter class is responsible for importing deals from an external source and saving them as WordPress posts. 
*   It provides methods for scheduled imports, manual imports, and processing deal metadata.
*
* Usage:
*   To utilize the functionality of the ScrapGoDealsImporter class, follow these steps:
*       Ensure the class is included in your WordPress plugin or theme.
*       Instantiate the class using the get_instance() method.
*       The class will automatically hook into WordPress actions and trigger the necessary import processes.
* Methods:
*   __construct(): Constructor method. Initializes necessary WordPress action hooks for scheduled and manual imports.
*   get_instance(): Static method to retrieve the singleton instance of the class.
*   import_scheduled(): Method to schedule the import of deals to run once a day. Hooks into the WordPress init action.
*   import_manually(): Method to trigger manual import of deals. Hooks into the WordPress AJAX action wp_ajax_scrapgo_run_import.
*   import_deals(): Method to fetch deals from an external API endpoint and insert them into the WordPress database. Invoked by scheduled or manual imports.
*   insert_post($deal): Method to insert a deal as a WordPress post. Checks for existing posts to prevent duplicates.
*   manage_post_meta($post_id, $deal): Method to manage post metadata for each deal.
*   process_post_meta($post_id, $meta_key, $meta_value): Method to process and save individual post metadata.
* Dependencies:
*   The class relies on the WordPress database and AJAX functionality to fetch and save deal data.
*/
class ScrapGoDealsImporter Extends ScrapGoDealsUtilities{

    // Static flag to track whether the class has been instantiated
    private static $instance;

    private function __construct() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Other constructor code...

        add_action('init', [$this, 'import_scheduled']);
        add_action('scrapgo_event', [$this, 'import_deals']);
        add_action('wp_ajax_scrapgo_run_import', [$this, 'import_manually']);
        add_action('wp_ajax_nopriv_scrapgo_run_import', [$this, 'import_manually']);
        
        /**
         * Coment out when debuging manual run.
         */
        //if(SCRAPGO_DEBUG) add_action('plugins_loaded', [$this, 'import_deals']);

    }

    // Method to get the instance of the class
    public static function get_instance() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function import_scheduled() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        // Schedule import to run once a day
        if (!wp_next_scheduled('scrapgo_event')) {
            // Get the current time
            $current_time = current_time('timestamp');

            // Add 5 seconds to the current time. I add 5 seconds because I have experiencing 
            //  some instances of the schedule being missed when using just time();
            $new_time = $current_time + 5;

            wp_schedule_event($new_time, '5minutes', 'scrapgo_event');
        }
    }
    
    function import_manually() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        $this->import_deals();
        wp_die(); // Terminate script execution
    }

    public function import_deals() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        
        global $wpdb;
        $args = ['Header'=>[]];
        $url = sanitize_url(get_option('scrapgo_api_url'));

        $content_type = sanitize_string(get_option('scrapgo_content_type'));
        if (isset($content_type) && empty($content_type)) $args['Header'][] = ['Content-Type' => $content_type];

        $token = sanitize_string(get_option('scrapgo_authorization_token'));
        if (isset($token) && empty($token)) $args['Header'][] = ['Bearer' => $token];


        //$url = 'https://scrapgoapp.com/api/1.1/obj/Deal';
        $response = wp_remote_get($url, $args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $deals = json_decode($body, true);
   
            $wpdb->query('START TRANSACTION');
            try {

                foreach ($deals['response']['results'] as $deal) {
                    
                    if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;"><h1>';var_dump('Source _ID: ');var_dump($deal['_id']);echo '</h1></div>';
                    if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump($deal);echo '</div>';
                    
                    // Get the post object by post_name
                    $post = get_page_by_path($post_name, OBJECT, 'post');
                    // Check if the post object exists
                    if ($post instanceof WP_Post) {
                        $post_id = $post->ID;
                        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;"><h1>';var_dump('Post Exists, Post_id: ');var_dump($post_id);echo '</h1></div>';
                        $this->update_post($post_id, $deal);
                    } else {
                        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;"><h1>';var_dump('Post Does not Exists');echo '</h1></div>';
                        $this->insert_post($deal);
                    }

                }
                
                $wpdb->query('COMMIT');
               
            } catch (Exception $e) {

                // Rollback the transaction if an error occurred
                $wpdb->query('ROLLBACK');
                // Optionally, you can log the error or handle it in another way
                error_log('Error: ' . $e->getMessage());

            }   
        }
    }

    public function update_post($deal) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        // IF UPDATE POST IS NEEDED, ADD CODE HERE.

    }

    public function insert_post($deal) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        
        global $wpdb;

        // Array to sanitize values
        $format = [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        ];

        // Insert each deal into the post table
        $post_data = array(
            'post_title' => $deal['d_material_type_label'],
            'post_name' => $deal['_id'],
            'post_content' => $deal['Description'],
            'post_status' => 'publish',
            'post_type' => 'scrapgo',
            'post_date' => $deal['Created Date'],
        );

        $post = wp_insert_post($post_data, $format);
        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Post ID after Insert: ');var_dump($post);echo '<hr /></div>';

         // Save deal metadata using post meta
         if (is_wp_error($post)) {
            // Handle error

            $wpdb->query('ROLLBACK');
            die('Error inserting post: ' . $post->get_error_message());

        } else {
            
            if (isset($deal['images']) ) $this->manage_post_media($post, $deal['images'], $deal['Description']);
            $this->manage_post_meta($post, $deal);

        }
        
    }

    public function manage_post_media($post_id, $images, $descr) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        global $wpdb;

        foreach($images as $image) {
            // Include necessary WordPress core files
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Check if "https" is not found in the string
            if (strpos($image, "https:") === false) {
                // Append "https" to the string
                $image = "https:" . $image;
            }
            if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Media: ');var_dump($image);echo '<hr /></div>';

            // Check if the URL is valid
            if ( ! filter_var($image, FILTER_VALIDATE_URL) ) {

                // Optionally, you can log the error or handle it in another way
                if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Invalid image URL');echo '<hr /></div>';
                continue;
                
            }

            // Download the image to the server
            $media_id = media_sideload_image($image, $post_id, $desc, 'id');

            if (!has_post_thumbnail($post_id)) set_post_thumbnail($post_id, $media_id);
            if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Media Id: ');var_dump($media_id);echo '<hr /></div>';
            if (!is_wp_error($media_id)) {
                // The image was successfully sideloaded and attached to the post
                if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump("Media sideloaded successfully ");echo '<hr /></div>';
            } else {
                // An error occurred during sideloading
                $wpdb->query('ROLLBACK');
                if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump($media_id->get_error_message());echo '<hr /></div>';
                die();
            }

        }

    }

    public function manage_post_meta($post_id, $deal) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');
        
        $post_metas = [
            'Modified Date',
            'Created By',
            'Availability_label',
            'Company_name',
            'Company/Supplier',
            'd_material_type_label',
            'deal_N/F',
            'deal_status_label',
            'Description',
            'd_material_type',
            'Measurement_label',
            'Preparation_label',
            'Quantity',
            'Ready',
            'Target_Price',
            'Pickup_location_city',
            'Pickup_location_country',
            ['Pickup_location_adress',['address','lat','lng']],
            'Pickup_location_state',
            'Pickup_location_street',
            'Pickup_location_zipcode',
            'Quantity',
            'Target_Value',
            '_id'
        ];
        // Insert each deal into the post table
        
        foreach ($post_metas as $meta_key) {
            
            if (is_array($meta_key)) {
                
                // Process complex json item
                if (!isset($deal[$meta_key[0]])) continue;
                $main_key = $meta_key[0];
                foreach($meta_key[1] as $key){
                    $sub_key = $main_key.'-'.$key;
                   if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Sub Key: ');var_dump($sub_key);var_dump(' - Meta Value:');var_dump($deal[$main_key][$key]);echo '</div>';
                    $this->process_post_meta($post_id, $sub_key, $deal[$main_key][$key]);
                }

            } else {

              //  if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta Key: ');var_dump($meta_key);echo '</div>';
                // Process simple json item
                if (!isset($deal[$meta_key])) continue;
                if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta Key:');var_dump($meta_key);var_dump(' - Meta Value:');var_dump($deal[$meta_key]);echo '</div>';
                $this->process_post_meta($post_id, $meta_key, $deal[$meta_key]);
            }
        
        }
        
    }

    public function process_post_meta($post_id, $meta_key, $meta_value) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'()');

        global $wpdb;
    
        $found = get_post_meta($post_id, $meta_key, true);

        if (!empty($found)) {
            $meta = update_post_meta( $post_id, $meta_key, $meta_value );
        }
        else {
            $meta = add_post_meta($post_id, $meta_key, $meta_value, true);
        }    

        // Save deal metadata using post meta
        if (is_wp_error($meta)) {
        
            // Handle error
            $wpdb->query('ROLLBACK');
            die('Error inserting post meta: ' . $meta->get_error_message());
        }

    }

}

ScrapGoDealsImporter::get_instance();