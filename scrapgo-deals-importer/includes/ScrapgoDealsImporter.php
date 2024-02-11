<?php
/**
 * This PHP class, ScrapGoDealsImporter, handleS importing deals from an external API into WordPress and saving them as posts with associated metadata. 
 * Here's a breakdown of its key functionalities:
 * Constructor: Initializes the class and sets up various WordPress hooks for scheduling imports and handling manual import requests.
 * import_scheduled() Method: Sets up a scheduled event to import deals once a day if it hasn't been scheduled already.
 * import_manually() Method: Handles manual import requests initiated via AJAX. It calls the import_deals() method to perform the import.
 * import_deals() Method: Retrieves deals from an external API, iterates through them, and inserts each deal as a WordPress post. However, 
 *              there's an issue with transaction handling. The code tries to start a transaction but immediately rolls it back without any 
 *              meaningful operation. This part needs correction.
 * insert_post() Method: Inserts a single deal as a WordPress post and saves its metadata using post meta. However, it's not clear where 
 *              $wpdb is defined, which could lead to errors.
 * insert_post_meta() Method: Inserts metadata associated with a deal as post meta. It iterates through predefined meta keys and saves them 
 *              for each deal. However, it also faces issues with transaction handling and the usage of $wpdb.
 */
class ScrapGoDealsImporter {

    // Static flag to track whether the class has been instantiated
    private static $instance;

    private function __construct() {
        
        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->_construct()');
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
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function import_scheduled() {
        
        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->import_scheduled()');
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
        
        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->import_manually()');
        $this->import_deals();
        wp_die(); // Terminate script execution
    }

    public function import_deals() {
        
        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->import_deals()');
        
        global $wpdb;

        $url = 'https://scrapgoapp.com/api/1.1/obj/Deal';
        $response = wp_remote_get($url);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $deals = json_decode($body, true);
   
            $wpdb->query('START TRANSACTION');
            try {

                foreach ($deals['response']['results'] as $deal) {
                    
                    //if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump($deal);echo '</div>';
                    $this->insert_post($deal);

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

    public function insert_post($deal) {

        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->insert_post()');
        
        global $wpdb;
        
        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Source _ID: ');var_dump($deal['_id']);echo '</div>';

            // Construct your SQL query
        $sql = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'scrapgo' and post_name = %s";

        // Execute the query and retrieve the results
        $results = $wpdb->get_results( $wpdb->prepare( $sql, $deal['_id'] ) );
        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump($wpdb->last_query);;echo '</div>';
        
        // Check if there are results
        if ($results) {
            if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Already Imported.  SKIP.: ');echo '</div>';
            return;
        }

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

            $this->manage_post_meta($post, $deal);

        }
        
    }

    public function manage_post_meta($post_id, $deal) {

        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->insert_post_meta()');
        
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
                   // if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Sub Key: ');var_dump($sub_key);echo '</div>';
                    $this->process_post_meta($post_id, $sub_key, $deal[$main_key][$key]);
                   // if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Sub Key:');var_dump($sub_key);var_dump(' Processed');echo '<hr /></div>';
                }

            } else {

              //  if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta Key: ');var_dump($meta_key);echo '</div>';
                // Process simple json item
                if (!isset($deal[$meta_key])) continue;
             //   if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta Key:');var_dump($meta_key);var_dump(' Processed');echo '<hr /></div>';
                $this->process_post_meta($post_id, $meta_key, $deal[$meta_key]);

            }
        
        }
        
    }

    public function process_post_meta($post_id, $meta_key, $meta_value) {

        if(SCRAPGO_DEBUG) error_log('ScrapGoDealsImporter->process_post_meta()');

        global $wpdb;
    
        $meta_value = get_post_meta($post_id, $meta_key, true);

        if (!empty($meta_value)) {
            $meta = update_post_meta( $post_id, $meta_key, $meta_value );
        }
        else {
            $meta = add_post_meta($post_id, $meta_key, $meta_value, true);
        }    
    
        if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta ID: ');var_dump($meta);echo '<hr /></div>';

        // Save deal metadata using post meta
        if (is_wp_error($meta)) {
        
            // Handle error
            $wpdb->query('ROLLBACK');
            die('Error inserting post meta: ' . $meta->get_error_message());
        }

    }

}

ScrapGoDealsImporter::get_instance();