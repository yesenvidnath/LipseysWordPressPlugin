<?php
/*
Plugin Name: Lipseys API Plugin
Description: Connects to the Lipseys API and imports product data into WooCommerce.
Version: 1.0.0
Author: Yesen Kandalama
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define the plugin directory path
if (!defined('LIPSEYS_API_PLUGIN_DIR')) {
    define('LIPSEYS_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Include necessary files
require_once LIPSEYS_API_PLUGIN_DIR . 'includes/class-lipseys-api-client.php';
require_once LIPSEYS_API_PLUGIN_DIR . 'includes/class-lipseys-api-admin.php';

// Initialize the plugin
add_action('plugins_loaded', 'lipseys_api_plugin_init');

function lipseys_api_plugin_init() {
    // Any initialization code can go here
}

// Register hooks and actions
add_action('admin_menu', 'lipseys_api_add_admin_menu');
function lipseys_api_add_admin_menu() {
    add_menu_page(
        'Lipsey\'s API',
        'Lipsey\'s API',
        'manage_options',
        'lipseys-api',
        'lipseys_api_admin_page',
        'dashicons-admin-network'
    );

    // Add submenu for displaying products added by the plugin
    add_submenu_page(
        'lipseys-api',                // Parent slug
        'Lipsey\'s Products',         // Page title
        'Lipsey\'s Products',         // Menu title
        'manage_options',             // Capability
        'lipseys-products',           // Menu slug
        'lipseys_api_products_page'   // Callback function
    );
}

// The admin page content
function lipseys_api_admin_page() {
    $admin = new Lipseys_Api_Admin();
    $admin->admin_page();
}

// The products page content
function lipseys_api_products_page() {
    global $wpdb;

    // Handle search
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

    // Base query to get all published products
    $query = "SELECT p.ID, p.post_title, p.post_content, pm.meta_value AS sku, pm_price.meta_value AS price, pm_stock.meta_value AS stock
              FROM {$wpdb->posts} p
              INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
              INNER JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
              INNER JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
              WHERE p.post_type = 'product' AND p.post_status = 'publish'
              AND p.post_content LIKE %s"; // Filter for 'From Lipsey' in description

    // Prepare the LIKE parameter
    $like_search = '%From Lipsey%';

    // Add search condition if a search query is provided
    if (!empty($search_query)) {
        // Use placeholders for both LIKE and search query
        $query = $wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_content, pm.meta_value AS sku, pm_price.meta_value AS price, pm_stock.meta_value AS stock
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
             INNER JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
             INNER JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             AND p.post_content LIKE %s
             AND p.post_title LIKE %s",
            $like_search,
            '%' . $wpdb->esc_like($search_query) . '%'
        );
    } else {
        // Prepare the query without additional search
        $query = $wpdb->prepare(
            $query,
            $like_search
        );
    }

    // Fetch the products
    $products = $wpdb->get_results($query);

    ?>
    <div class="wrap">
        <h1>Lipsey's Products</h1>
        <form method="post" action="">
            <input type="text" name="search_query" placeholder="Search products by name" value="<?php echo esc_attr($search_query); ?>" />
            <input type="submit" class="button button-primary" value="Search" />
        </form>
        <br>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="5%">Product ID</th>
                    <th width="20%">Product Name</th>
                    <th width="25%">Description</th>
                    <th width="10%">SKU</th>
                    <th width="10%">Price</th>
                    <th width="10%">Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)) : ?>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><?php echo esc_html($product->ID); ?></td>
                            <td><?php echo esc_html($product->post_title); ?></td>
                            <td><?php echo esc_html(wp_trim_words($product->post_content, 20)); ?></td>
                            <td><?php echo esc_html($product->sku); ?></td>
                            <td><?php echo esc_html($product->price); ?></td>
                            <td><?php echo esc_html($product->stock); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">No products found containing "From Lipsey" in their description.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Add your AJAX handler here
add_action('wp_ajax_lipseys_api_fetch', 'handle_lipseys_api_fetch');

function handle_lipseys_api_fetch() {
    // Check for required POST parameters
    if (
        !isset($_POST['lipseys_api_email']) ||
        !isset($_POST['lipseys_api_password']) ||
        !isset($_POST['lipseys_api_percentage'])
    ) {
        echo "Error: Missing parameters.";
        wp_die(); // End the AJAX call
    }

    // Sanitize input
    $email = sanitize_email($_POST['lipseys_api_email']);
    $password = sanitize_text_field($_POST['lipseys_api_password']);
    $percentage = floatval($_POST['lipseys_api_percentage']);

    echo "Parameters received: \n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Percentage: $percentage\n";
    ob_flush();
    flush();

    echo "Connecting to the Lipseys API...\n";
    ob_flush();
    flush();

    // Initialize the API client
    $client = new Lipseys_Api_Client($email, $password);
    $response = $client->get_catalog();

    // Check if there was an error with the API call
    if (is_wp_error($response)) {
        echo "Error: " . esc_html($response->get_error_message()) . "\n";
        wp_die();
    }

    // Check if the response contains data
    if (!isset($response['data']) || !is_array($response['data'])) {
        echo "Error: API response did not contain valid data.\n";
        wp_die();
    }

    $total_items = count($response['data']);
    echo "Data fetched successfully. Total items: $total_items\n";
    ob_flush();
    flush();

    echo "Processing and storing items...\n";
    ob_flush();
    flush();

    // Loop through each item and store it
    foreach ($response['data'] as $index => $item) {
        $result = $client->insert_or_update_product($item, $percentage);
        if (is_wp_error($result)) {
            echo "Error processing item #$index: " . $item['itemNo'] . " - " . $result->get_error_message() . "\n";
        } else {
            echo "Stored item #$index: " . $item['itemNo'] . " - " . $item['description1'] . "\n";
        }
        ob_flush();
        flush();
    }

    echo "Data processing complete.\n";
    wp_die(); // End the AJAX call
}
