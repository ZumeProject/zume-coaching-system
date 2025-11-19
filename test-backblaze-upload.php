<?php
/**
 * Test script for Backblaze B2 upload functionality
 * 
 * Usage: Run this from WordPress context or with proper bootstrap
 * php test-backblaze-upload.php
 */

// Load WordPress if not already loaded
if ( !defined( 'ABSPATH' ) ) {
    // Try to find WordPress
    $wp_load = dirname( __FILE__ ) . '/../../../../wp-load.php';
    if ( file_exists( $wp_load ) ) {
        require_once $wp_load;
    } else {
        // Try alternative path
        $wp_load = dirname( __FILE__ ) . '/../../../wp-load.php';
        if ( file_exists( $wp_load ) ) {
            require_once $wp_load;
        } else {
            die( "Error: Could not find WordPress. Please run this from WordPress context.\n" );
        }
    }
}

// Load the storage class
require_once dirname( __FILE__ ) . '/shared/zume-backblaze-storage.php';

// Test file path
$test_file = '/Users/chris/Pictures/beardyguy.jpg';

echo "=== Backblaze B2 Upload Test ===\n\n";

// Check if file exists
if ( !file_exists( $test_file ) ) {
    die( "Error: Test file not found: $test_file\n" );
}

echo "✓ Test file found: $test_file\n";
echo "  File size: " . filesize( $test_file ) . " bytes\n";
echo "  File type: " . mime_content_type( $test_file ) . "\n\n";

// Test 1: Check if storage is enabled
echo "Test 1: Checking if storage is enabled...\n";
$is_enabled = Zume_Backblaze_Storage::is_enabled();
if ( $is_enabled ) {
    echo "✓ Storage is enabled\n\n";
} else {
    echo "✗ Storage is NOT enabled or not configured\n";
    echo "  Please check your dt_storage_connection settings in zume_3_options table\n\n";
    exit( 1 );
}

// Test 2: Get connection settings
echo "Test 2: Retrieving connection settings...\n";
$connection = Zume_Backblaze_Storage::get_connection_settings();
if ( $connection ) {
    echo "✓ Connection settings retrieved\n";
    echo "  Bucket: " . ( $connection['bucket'] ?? 'not set' ) . "\n";
    echo "  Region: " . ( $connection['region'] ?? 'not set' ) . "\n";
    echo "  Endpoint: " . ( $connection['endpoint'] ?? 'not set' ) . "\n";
    echo "  Site ID: " . ( $connection['site_id'] ?? 'not set' ) . "\n";
    echo "  Access Key: " . ( !empty( $connection['access_key'] ) ? substr( $connection['access_key'], 0, 8 ) . '...' : 'not set' ) . "\n\n";
} else {
    echo "✗ Failed to retrieve connection settings\n\n";
    exit( 1 );
}

// Test 3: Prepare file array (simulating $_FILES)
echo "Test 3: Preparing file upload array...\n";
$file_info = [
    'name' => 'beardyguy.jpg',
    'full_path' => 'beardyguy.jpg',
    'type' => mime_content_type( $test_file ),
    'tmp_name' => $test_file, // Using actual file path for testing
    'error' => 0,
    'size' => filesize( $test_file )
];

echo "✓ File array prepared:\n";
echo "  Name: " . $file_info['name'] . "\n";
echo "  Type: " . $file_info['type'] . "\n";
echo "  Size: " . $file_info['size'] . " bytes\n\n";

// Test 4: Test key generation (without actual upload)
echo "Test 4: Testing key generation logic...\n";
$test_user_id = 123;
$key_prefix = 'users';
$site_id = $connection['site_id'] ?? '';

// Simulate key generation
$key_parts = [];
if ( !empty( $site_id ) ) {
    $key_parts[] = $site_id;
}
if ( !empty( $key_prefix ) ) {
    $key_parts[] = $key_prefix;
}
if ( !empty( $test_user_id ) ) {
    $key_parts[] = $test_user_id;
}
$key_parts[] = 'test_random_string_12345';
$generated_key = implode( '/', $key_parts ) . '.jpg';

echo "✓ Generated key structure: $generated_key\n";
echo "  Expected pattern: {site_id}/{key_prefix}/{user_id}/{random}.{ext}\n\n";

// Test 5: Test URL generation
echo "Test 5: Testing URL generation...\n";
$test_url = Zume_Backblaze_Storage::get_file_url( $generated_key );
if ( !empty( $test_url ) ) {
    echo "✓ URL generated: $test_url\n";
    echo "  Expected pattern: https://{bucket}.{endpoint}/{key}\n\n";
} else {
    echo "✗ Failed to generate URL\n\n";
}

// Test 6: Attempt actual upload (if all checks pass)
echo "Test 6: Attempting actual upload...\n";
echo "  This will upload the file to Backblaze B2\n";
echo "  Key prefix: $key_prefix\n";
echo "  User ID: $test_user_id\n\n";

try {
    $upload_result = Zume_Backblaze_Storage::upload_file( 
        $key_prefix, 
        $file_info, 
        '', // No existing key
        $test_user_id 
    );
    
    if ( is_wp_error( $upload_result ) ) {
        echo "✗ Upload failed with error:\n";
        echo "  Code: " . $upload_result->get_error_code() . "\n";
        echo "  Message: " . $upload_result->get_error_message() . "\n";
        if ( $upload_result->get_error_data() ) {
            echo "  Data: " . print_r( $upload_result->get_error_data(), true ) . "\n";
        }
    } elseif ( is_array( $upload_result ) && !empty( $upload_result['uploaded'] ) ) {
        echo "✓ Upload successful!\n";
        echo "  Uploaded key: " . ( $upload_result['uploaded_key'] ?? 'not set' ) . "\n";
        
        // Generate URL for uploaded file
        if ( !empty( $upload_result['uploaded_key'] ) ) {
            $uploaded_url = Zume_Backblaze_Storage::get_file_url( $upload_result['uploaded_key'] );
            echo "  Uploaded URL: $uploaded_url\n";
        }
    } else {
        echo "✗ Upload returned unexpected result:\n";
        echo print_r( $upload_result, true ) . "\n";
    }
} catch ( Exception $e ) {
    echo "✗ Upload failed with exception:\n";
    echo "  " . get_class( $e ) . ": " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";

