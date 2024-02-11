jQuery(document).ready(function($) {
    // Add click event listener to the button
    $('#triggerImportManually').on('click', function() {

        // Perform AJAX request
        jQuery.ajax({
            type: 'POST', // or 'GET' depending on your needs
            url: ajaxurl, // WordPress AJAX URL
            data: {
                action: 'scrapgo_run_import' // Action hook to call
            },
            success: function(response) {
                console.log('Import triggered successfully.');
                console.log(response); // Response from the server
                // Open response in a new window
                var newWindow = window.open('Scrapgo Run Manual Import - Response', 'scrapgorunimportresponse');
                newWindow.document.write(response);
                newWindow.document.close();
            },
            error: function(xhr, status, error) {
                console.error('Error occurred while triggering import.');
                console.log(xhr.responseText); // Error response from the server
            }
        });

    });

});