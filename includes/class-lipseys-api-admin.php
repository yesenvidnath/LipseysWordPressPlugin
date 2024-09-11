<?php

class Lipseys_Api_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_menu() {
        add_menu_page(
            'Lipsey\'s API', 
            'Lipsey\'s API', 
            'manage_options', 
            'lipseys-api', 
            array($this, 'admin_page'), 
            'dashicons-admin-network'
        );
    }

    public function admin_page() {
        if (isset($_POST['lipseys_api_fetch'])) {
            $this->fetch_and_store_data();
        }
        include LIPSEYS_API_PLUGIN_DIR . 'templates/admin-page.php';
    }

 
    private function fetch_and_store_data() {
        $email = sanitize_email($_POST['lipseys_api_email']);
        $password = sanitize_text_field($_POST['lipseys_api_password']);
        $percentage = floatval($_POST['lipseys_api_percentage']);
    
        // Start output buffering
        echo '<pre class="cmd-output">';
        echo "Starting data fetch...\n";
        ob_flush(); // Flush the output buffer to the browser
        flush();
    
        $client = new Lipseys_Api_Client($email, $password);
        $response = $client->get_catalog();
    
        if (is_wp_error($response)) {
            echo "Error: " . esc_html($response->get_error_message()) . "\n";
            echo '</pre>';
            ob_flush();
            flush();
            return;
        }
    
        if (!isset($response['data']) || !is_array($response['data'])) {
            echo "Error: API response did not contain valid data.\n";
            echo '</pre>';
            ob_flush();
            flush();
            return;
        }
    
        echo "Data fetched successfully. Processing and storing items...\n";
        ob_flush();
        flush();
    
        foreach ($response['data'] as $item) {
            $result = $this->insert_or_update_product($item, $percentage);
            if (is_wp_error($result)) {
                echo "Error processing item: " . $item['itemNo'] . " - " . $result->get_error_message() . "\n";
            } else {
                echo "Stored item: " . $item['itemNo'] . " - " . $item['description1'] . "\n";
            }
            ob_flush();
            flush();
        }
    
        echo "Data processing complete.\n";
        echo '</pre>';
        ob_flush();
        flush();
    }
    
    
}
