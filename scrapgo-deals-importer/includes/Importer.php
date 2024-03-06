<?php

class ScrapGoDealsImporter Extends ScrapGoDealsUtilities{

    // Static flag to track whether the class has been instantiated
    private static $instance;

    private $keys = [];
    private $uniqueKeys = [];
    private $customtype = '';

    private function __construct() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        // Other constructor code...

        add_action('init', [$this, 'import_scheduled']);
        add_action('scrapgo_event', [$this, 'import_deals']);
        add_action('wp_ajax_scrapgo_run_import', [$this, 'import_manually']);
        
         /**
         * Coment out when debuging manual run.
         */
        if(isset($_GET['run'])) 
            if(SCRAPGO_DEBUG) add_action('plugins_loaded', [$this, 'import_deals']);

    }

    // Method to get the instance of the class
    public static function get_instance() {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));

        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function import_scheduled() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        // Schedule import to run once a day
        if (!wp_next_scheduled('scrapgo_event')) {
            // Get the current time
            $current_time = current_time('timestamp');

            // Add 5 seconds to the current time. I add 5 seconds because I have experiencing 
            //  some instances of the schedule being missed when using just time();
            $new_time = $current_time + 5;

            wp_schedule_event($new_time, 'hourly', 'scrapgo_event');
        }
    }
    
    function import_manually() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        $this->import_deals(false);
        wp_die(); // Terminate script execution
    }

    public function import_deals() {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        
        // Start time
        $start_time = microtime(true);

        global $wpdb;
        $args = ['Header'=>[]];
        $url =$this->sanitize_url(get_option('scrapgo_api_url'));
        $this->customtype = $this->sanitize_string(get_option('scrapgo_custom_type'));

        $content_type =$this->sanitize_string(get_option('scrapgo_content_type'));
        if (isset($content_type) && empty($content_type)) $args['Header'][] = ['Content-Type' => $content_type];

        $token =$this->sanitize_string(get_option('scrapgo_authorization_token'));
        if (isset($token) && empty($token)) $args['Header'][] = ['Bearer' => $token];

        $response = wp_remote_get($url, $args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $deals = json_decode($body, true);

            $this->findJsonKeys($deals['response']['results']);
   
            $wpdb->query('START TRANSACTION');
            try {

                foreach ($deals['response']['results'] as $deal) {
                    
                    
                    //if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;">';var_dump($deal);echo '</div>';}
                    $post_name = $deal['_id'];
                    // Get the post object by post_name

                    if (isset($deal['Company_name'])) {
                        if (isset($deal['d_material_type_label'])) 
                            $deal['post_title'] = $deal['Company_name']. ' - '.$deal['d_material_type_label'];
                        elseif (isset($deal['description'])) 
                            $deal['post_title'] = $deal['Company_name']. ' - '.$deal['description'];
                        else 
                            $deal['post_title'] = $deal['Company_name']. ' - '.$deal['_id'];
                    } else
                        $deal['post_title'] = $deal['_id'];

                  // var_dump($deal['post_title']);
                    // Create post_name based on the post title
                     $deal['post_name'] = sanitize_title($deal['post_title']);
                    if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h1>Post Name: ';var_dump($deal['post_name']);echo '</h1></div>';}
                    // Check if the same post_name exists
                    $post = get_page_by_path($deal['post_name'], OBJECT, $this->customtype);
                      if ($post instanceof WP_Post) {
                        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Post Found - Source _ID: ');var_dump($deal['_id']);echo '</h2></div>';}
                       // If post_name exists, then check to make sure it is the same _id.
                       $post_id = $post->ID;
                        /**
                        * $post_id: The ID of the post you want to retrieve the _id meta value for.
                        *'_id': The name of the meta field you want to retrieve.
                        * true: Specifies that you want to return a single value. I
                        */
                       if( $deal['_id'] == get_post_meta($post_id, '_id', true) ) {
                            $found = true;
                       } else {
                             $found = false;
                       }

                    } else {
                        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Post Object NOT Found');echo '</h2></div>';}     
                        // If post_name does not exists.
                        $found = false;
                    }
                 

                    // Check if the post object exists
                    if ($found) {
                      
                      // If the modified date is the same then same version, skip.
                      if ( get_post_meta($post_id, 'Modified Date', true) <>  $deal['Modified Date'] )
                        $this->update_post($post_id, $deal);
                      else
                        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Post NOT updated - Save Version');echo '</h2></div>';}     

                    } else {

                        $this->insert_post($deal);

                    }

                }
                
                $wpdb->query('COMMIT');
      
               
            } catch (Exception $e) {

                // Rollback the transaction if an error occurred
                $wpdb->query('ROLLBACK');
                // Optionally, you can log the error or handle it in another way
                var_dump('Error: ' . $e->getMessage());

            }   
        }
        // End time
        $end_time = microtime(true);

        // Calculate execution time
        $execution_time = number_format($end_time - $start_time,2);

        // Display execution time
        echo "<div style='margin-left: 200px;'><h2>ScrapGo Import Completed. Import executed in $execution_time seconds</h2></div>";
    }

    public function findJsonKeys($input) {
        
        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));

        // Loop throught the input and extract keys.
        foreach($input as $item) {
            
               //Extract Keys
            $keys = array_keys($item);

            // Merge keys into the uniqueKeys array
            $this->keys = array_merge($this->keys, $keys);

        }

        // Remove duplicate keys and reindex the array
        $this->uniqueKeys = array_values(array_unique($this->keys));

        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;">';var_dump('Unique Keys: ');var_dump($this->uniqueKeys);echo '</div>';}

    }

    public function update_post($post_id, $deal)  {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        global $wpdb;

        // Array to sanitize values
        $format = [
            '%d',
            '%s',
            '%s',
            '%s'
        ];

        $where_format = [ '%d' ]; 
        
        $where = [ 'ID' => $post_id ];

        if (isset($deal['Description']))
            $post_content_blocks = "<!-- wp:paragraph --><p>{$deal['Description']}</p><!-- /wp:paragraph -->";
        else
            $post_content_blocks = "";

        // Insert each deal into the post table
        $post_data = [
            'ID' => $post_id,
            'post_title' => $deal['post_title'],
            'post_content' => $post_content_blocks,
            'post_status' => $this->set_post_status($deal['Availability_label']),
        ];


        $post = wp_update_post($post_data, $format);
        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Post ID Updated: ');var_dump($post_id);;echo '</h2></div>';}

        $this->manage_post_meta($post_id, $deal);
        if (isset($deal['images']) ) $this->manage_post_media($post_id, $deal['images']);

    }

    public function insert_post($deal) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));
        
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
        
        if (isset($deal['Description']))
            $post_content_blocks = "<!-- wp:paragraph --><p>{$deal['Description']}</p><!-- /wp:paragraph -->";
        else
            $post_content_blocks = "";
        
        // Insert each deal into the post table
        $post_data = array(
            'post_title' => $deal['post_title'],
            'post_name' => $deal['post_name'],
            'post_content' => $post_content_blocks,
            'post_status' => $this->set_post_status($deal['Availability_label']),
            'post_type' => $this->customtype,
            'post_date' => $deal['Created Date'],
        );

        $post = wp_insert_post($post_data, $format);
        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Post ID after Insert: ');var_dump($post);echo '</h2></div>';}

         // Save deal metadata using post meta
         if (is_wp_error($post)) {
            // Handle error

            $wpdb->query('ROLLBACK');
            die('Error inserting post: ' . $post->get_error_message());

        } else {
            

            $this->manage_post_meta($post, $deal);
            if (isset($deal['images']) ) $this->manage_post_media($post, $deal['images']);

        }
        
    }

    public function set_post_status($availability) {
        if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;"><h2>';var_dump('Availability_label: ');var_dump($availability);echo '</h2></div>';}

        switch ($availability)
        {
            case 'Ongoing':
                $post_status = 'publish';
                break;
            default:
                $post_status = 'draft';
        }
        return $post_status;
    }


    public function manage_post_media($post_id, $images) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));

        global $wpdb;

        delete_post_meta( $post_id, 'images' );
        $this->process_post_meta($post_id, 'images', implode('|',$images));

    }

    public function manage_post_meta($post_id, $deal) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));

        // Insert each deal into the post table
        
        foreach ($this->uniqueKeys as $meta_key) {

            if ($meta_key == 'images') continue;                //If images skip
            if (!array_key_exists($meta_key, $deal)) continue;  //If key does not exist skip

            if (is_array($deal[$meta_key])) {       // If the value is an array, then look thru array.

                // extract the keys.
                $subkeys = array_keys($deal[$meta_key]);

                foreach($subkeys as $key){
                  $sub_key = $meta_key.' - '.$key;
                  if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;">';var_dump('Sub Key: ');var_dump($sub_key);var_dump(' - Meta Value:');var_dump($deal[$meta_key][$key]);echo '</div>';}
                  $this->process_post_meta($post_id, $sub_key, $deal[$meta_key][$key]);
                }

            } else {

              //  if(SCRAPGO_DEBUG) echo '<div style="margin-left: 200px;">';var_dump('Meta Key: ');var_dump($meta_key);echo '</div>';
                // Process simple json item
                if (!isset($deal[$meta_key])) continue;
                if(SCRAPGO_DEBUG) {echo '<div style="margin-left: 200px;">';var_dump('Meta Key:');var_dump($meta_key);var_dump(' - Meta Value:');var_dump($deal[$meta_key]);echo '</div>';}
                $this->process_post_meta($post_id, $meta_key, $deal[$meta_key]);
            }
        
        }
        
    }

    public function process_post_meta($post_id, $meta_key, $meta_value) {

        if(SCRAPGO_DEBUG) error_log(__CLASS__.'::'.__FUNCTION__.'() - '.date('H:i:s'));

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