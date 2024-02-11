##################
##### CLASS ScrapGoDealsSetup
##################

The ScrapGoDealsSetup class in WordPress is responsible for setting up various components of a plugin or theme related to managing deals. Let's break down the key functionalities and methods of this class:

Singleton Pattern:

The class follows the Singleton pattern to ensure that only one instance of the class is created throughout the application's lifecycle. This ensures consistency and prevents redundant instantiations.
Constructor:

The private constructor initializes various actions and hooks required for setting up the plugin or theme. These actions include registering activation and deactivation hooks, registering custom post types and taxonomies, creating admin menus, initializing settings, and enqueueing scripts.
Static Method get_instance():

This method ensures that only one instance of the class is created and returns the instance if it exists. If an instance doesn't exist, it creates a new one.
Activation and Deactivation Hooks:

The activate() and deactivate() methods handle activation and deactivation tasks, respectively. These tasks may include database updates, setting default options, or cleaning up resources.
Custom Post Type Registration:

The custom_post_type() method registers a custom post type named "Scrapgo" with its associated labels, arguments, and capabilities. This custom post type is used to manage deals within the WordPress dashboard.
Custom Taxonomy Registration:

The custom_taxonomy() method registers a custom taxonomy named "Scrapgo Taxonomy" for organizing and categorizing deals. It defines labels, arguments, and settings for the taxonomy.
Admin Menu Creation:

The create_menu() method adds menu items to the WordPress admin dashboard. It adds a main menu page for "Scrapgo Settings" and a submenu page for running manual imports.
Settings Page:

The settings_page() method displays the settings page for the plugin or theme. It includes a form for managing settings related to the Scrapgo plugin, such as enabling debug mode.
Settings Initialization:

The initialize_settings() method registers settings and defines sections and fields for the settings page. It allows users to customize the behavior of the plugin through settings.
Scripts Enqueuing:

The enqueue_admin_scripts() and enqueue_custom_scripts() methods enqueue scripts and stylesheets for the admin and front-end respectively. These scripts enhance the functionality and appearance of the plugin or theme.
Debugging and Logging:

Throughout the class, there are conditional statements (if(SCRAPGO_DEBUG)) used for debugging and logging purposes. These statements help developers debug the plugin by logging messages to the error log when debug mode is enabled.
Initialization:

The last line instantiates the class using the get_instance() method, ensuring that the setup tasks are executed when the class is loaded.
Overall, the ScrapGoDealsSetup class provides a structured approach to setting up and configuring a plugin or theme for managing deals in WordPress. It encapsulates various functionalities and settings required for the smooth operation of the plugin or theme.

##################
##### CLASS ScrapGoDealsImporter
##################

The ScrapGoDealsImporter class is designed to import deals from an external source into WordPress and manage their metadata. Let's break down the main components and functionalities of this class:

Singleton Pattern:

The class follows the Singleton pattern, ensuring that only one instance of the class is created throughout the application's lifecycle. This is achieved using a private static property $instance and a private constructor.
Constructor:

The constructor initializes various actions that the class should perform when instantiated.
It hooks into WordPress actions such as init, wp_ajax_scrapgo_run_import, and wp_ajax_nopriv_scrapgo_run_import.
Static Method get_instance():

This method ensures that only one instance of the class is created.
It returns the instance of the class if it exists; otherwise, it creates a new instance.
Import Methods:

import_scheduled(): This method schedules the import to run periodically using wp_schedule_event. It ensures that the import event is scheduled to run every 5 minutes.
import_manually(): This method is triggered via AJAX to manually initiate the import process. It calls the import_deals() method.
import_deals(): This method retrieves deals from an external API (https://scrapgoapp.com/api/1.1/obj/Deal) using wp_remote_get(). It iterates through the deals, inserts them into the WordPress database using wp_insert_post(), and manages their metadata.
Database Operations:

insert_post(): Inserts a deal as a WordPress post and manages its metadata.
manage_post_meta(): Manages the metadata associated with each deal.
process_post_meta(): Processes and saves individual metadata entries for each deal.
Error Handling:

The class includes error handling mechanisms using try-catch blocks and error logging. If an error occurs during the import process or metadata management, it is logged using error_log().
Debugging:

The class includes debugging statements (if(SCRAPGO_DEBUG)) to log messages and debug information when the SCRAPGO_DEBUG constant is set to true.
Initialization:

Finally, the ScrapGoDealsImporter::get_instance() call ensures that the class is instantiated and its functionalities are initialized.
This class effectively manages the import of deals into WordPress and ensures that the data is correctly inserted and updated in the database along with its associated metadata. It provides flexibility for scheduled and manual imports while handling errors and logging debug information for troubleshooting purposes.

##################
##### JS admin.js
##################

This code snippet sets up a click event listener on a button. When the button is clicked, it triggers an AJAX request to a WordPress endpoint. Upon success, it opens a new window and displays the response from the server. If there's an error, it logs an error message. This code is commonly used in WordPress plugins and themes to perform asynchronous tasks without reloading the entire page.
