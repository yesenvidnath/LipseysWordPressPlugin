<?php

class Lipseys_Api_Client {

    private $email;
    private $password;

    public function __construct($email, $password) {
        $this->email = $email;
        $this->password = $password;
    }

    public function get_catalog() {
        // Include the necessary vendor files
        require_once LIPSEYS_API_PLUGIN_DIR . 'vendor/autoload.php';

        // Use the LipseysClient to connect to the API
        $client = new \lipseys\ApiIntegration\LipseysClient($this->email, $this->password);

        try {
            return $client->Catalog();
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage());
        }
    }

    public function insert_or_update_product($item, $percentage) {
        global $wpdb;

        // Debugging: Log the item being processed
        echo "Processing item: " . $item['itemNo'] . "\n";
        ob_flush();
        flush();
    
        // Prepare the data
        $msrp = isset($item['msrp']) ? $item['msrp'] : 0;
        $msrp += ($msrp * ($percentage / 100));
    
        // Check if the product exists
        $existing_product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' AND meta_value = %s
        ", $item['itemNo']));
    
        if ($existing_product_id) {
            echo "Updating existing product: " . $item['itemNo'] . "\n";
            ob_flush();
            flush();
    
            // Update existing product
            $product_id = $existing_product_id;
            $update_result = wp_update_post([
                'ID' => $product_id,
                'post_title' => $item['description1'],
                'post_content' => $this->generate_description($item),
            ], true);
    
            if (is_wp_error($update_result)) {
                echo "Error updating product: " . $update_result->get_error_message() . "\n";
                return $update_result; // Return error if update fails
            }
        } else {
            echo "Inserting new product: " . $item['itemNo'] . "\n";
            ob_flush();
            flush();
    
            // Insert new product
            $product_id = wp_insert_post([
                'post_title' => $item['description1'],
                'post_content' => $this->generate_description($item),
                'post_status' => 'publish',
                'post_type' => 'product',
            ], true);
    
            if (is_wp_error($product_id)) {
                echo "Error inserting product: " . $product_id->get_error_message() . "\n";
                return $product_id; // Return error if insert fails
            }
        }
    
        // Additional meta fields
        update_post_meta($product_id, '_sku', $item['itemNo']);
        update_post_meta($product_id, '_price', $item['currentPrice']);
        update_post_meta($product_id, '_regular_price', $msrp);
        update_post_meta($product_id, '_stock', $item['quantity']);
        update_post_meta($product_id, '_manage_stock', 'yes');
        update_post_meta($product_id, '_stock_status', $item['quantity'] > 0 ? 'instock' : 'outofstock');
    
        echo "Successfully processed item: " . $item['itemNo'] . "\n";
        ob_flush();
        flush();
    
        return true;
    }
    

    private function generate_description($item) {
        $description_list = [
            "<div class='product-dict-content-wrapper-imported-via-lipsy'><b>Item No: </b> {$row['itemNo']} <br />",
            "<b>UPC: </b> {$row['upc']} <br />",
            "<b>Manufacturer Model No: </b> {$row['manufacturerModelNo']} <br />",
            "<b>MSRP: </b> {$row['msrp']} <br />",
            "<b>Model: </b> {$row['model']} <br />",
            "<b>Caliber/Gauge: </b> {$row['caliberGauge']} <br />",
            "<b>Manufacturer: </b> {$row['manufacturer']} <br />",
            "<b>Type: </b> {$row['type']} <br />",
            "<b>Action: </b> {$row['action']} <br />",
            "<b>Barrel Length: </b> {$row['barrelLength']} <br />",
            "<b>Capacity: </b> {$row['capacity']} <br />",
            "<b>Finish: </b> {$row['finish']} <br />",
            "<b>Overall Length: </b> {$row['overallLength']} <br />",
            "<b>Receiver: </b> {$row['receiver']} <br />",
            "<b>Safety: </b> {$row['safety']} <br />",
            "<b>Sights: </b> {$row['sights']} <br />",
            "<b>Stock/Frame Grips: </b> {$row['stockFrameGrips']} <br />",
            "<b>Magazine: </b> {$row['magazine']} <br />",
            "<b>Weight: </b> {$row['weight']} <br />",
            "<b>Chamber: </b> {$row['chamber']} <br />",
            "<b>Drilled and Tapped: </b> {$row['drilledAndTapped']} <br />",
            "<b>Rate of Twist: </b> {$row['rateOfTwist']} <br />",
            "<b>Item Type: </b> {$row['itemType']} <br />",
            "<b>Additional Feature 1: </b> {$row['additionalFeature1']} <br />",
            "<b>Additional Feature 2: </b> {$row['additionalFeature2']} <br />",
            "<b>Additional Feature 3: </b> {$row['additionalFeature3']} <br />",
            "<b>Shipping Weight: </b> {$row['shippingWeight']} <br />",
            "<b>Bound Book Manufacturer: </b> {$row['boundBookManufacturer']} <br />",
            "<b>Bound Book Model: </b> {$row['boundBookModel']} <br />",
            "<b>Bound Book Type: </b> {$row['boundBookType']} <br />",
            "<b>NFA Thread Pattern: </b> {$row['nfaThreadPattern']} <br />",
            "<b>NFA Attachment Method: </b> {$row['nfaAttachmentMethod']} <br />",
            "<b>NFA Baffle Type: </b> {$row['nfaBaffleType']} <br />",
            "<b>Silencer Can Be Disassembled: </b> {$row['silencerCanBeDisassembled']} <br />",
            "<b>Silencer Construction Material: </b> {$row['silencerConstructionMaterial']} <br />",
            "<b>NFA dB Reduction: </b> {$row['nfaDbReduction']} <br />",
            "<b>Silencer Outside Diameter: </b> {$row['silencerOutsideDiameter']} <br />",
            "<b>NFA Form 3 Caliber: </b> {$row['nfaForm3Caliber']} <br />",
            "<b>Optic Magnification: </b> {$row['opticMagnification']} <br />",
            "<b>Maintube Size: </b> {$row['maintubeSize']} <br />",
            "<b>Adjustable Objective: </b> {$row['adjustableObjective']} <br />",
            "<b>Objective Size: </b> {$row['objectiveSize']} <br />",
            "<b>Optic Adjustments: </b> {$row['opticAdjustments']} <br />",
            "<b>Illuminated Reticle: </b> {$row['illuminatedReticle']} <br />",
            "<b>Reticle: </b> {$row['reticle']} <br />",
            "<b>Exclusive: </b> {$row['exclusive']} <br />",
            "<b>Quantity: </b> {$row['quantity']} <br />",
            "<b>Allocated: </b> {$row['allocated']} <br />",
            "<b>On Sale: </b> {$row['onSale']} <br />",
            "<b>Current Price: </b> {$row['currentPrice']} <br />",
            "<b>Retail Map: </b> {$row['retailMap']} <br />",
            "<b>FFL Required: </b> {$row['fflRequired']} <br />",
            "<b>SOT Required: </b> {$row['sotRequired']} <br />",
            "<b>Exclusive Type: </b> {$row['exclusiveType']} <br />",
            "<b>Scope Cover Included: </b> {$row['scopeCoverIncluded']} <br />",
            "<b>Special: </b> {$row['special']} <br />",
            "<b>Sights Type: </b> {$row['sightsType']} <br />",
            "<b>Case: </b> {$row['case']} <br />",
            "<b>Choke: </b> {$row['choke']} <br />",
            "<b>dB Reduction: </b> {$row['dbReduction']} <br />",
            "<b>Family: </b> {$row['family']} <br />",
            "<b>Finish Type: </b> {$row['finishType']} <br />",
            "<b>Frame: </b> {$row['frame']} <br />",
            "<b>Grip Type: </b> {$row['gripType']} <br />",
            "<b>Country of Origin: </b> {$row['countryOfOrigin']} <br />",
            "<b>Package Width: </b> {$row['packageWidth']} <br />",
            "<b>Package Height: </b> {$row['packageHeight']} <br />",
            "<b>Item Group: </b> {$row['itemGroup']} <br />",
            "<b class='keephidden'>From Lipsey</b></div>"
        ];
        return implode('', $description_list);
    }

    private function set_product_image($product_id, $image_name) {
        $image_url = 'https://your-image-url-path/' . $image_name;
        $upload_dir = wp_upload_dir();
        $image_data = @file_get_contents($image_url);
    
        if ($image_data === false) {
            return new WP_Error('image_download_failed', 'Failed to download image: ' . $image_name);
        }
    
        $filename = basename($image_url);
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
    
        file_put_contents($file, $image_data);
    
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
    
        $attach_id = wp_insert_attachment($attachment, $file, $product_id);
        if (is_wp_error($attach_id)) {
            return $attach_id; // Return error if attachment fails
        }
    
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($product_id, $attach_id);
    
        return true; // Return success
    }
}
