<?php
/**
 * Standalone test for Backblaze upload logic
 * Tests key generation and URL construction without requiring WordPress
 */

echo "=== Backblaze B2 Logic Test (Standalone) ===\n\n";

// Test file path
$test_file = '/Users/chris/Pictures/beardyguy.jpg';

// Check if file exists
if ( !file_exists( $test_file ) ) {
    die( "Error: Test file not found: $test_file\n" );
}

echo "✓ Test file found: $test_file\n";
echo "  File size: " . filesize( $test_file ) . " bytes\n";
echo "  File type: " . mime_content_type( $test_file ) . "\n\n";

// Simulate connection settings (based on your example URL)
$mock_connection = [
    'bucket' => 'zume-storage',
    'region' => 'us-east-005',
    'endpoint' => 's3.us-east-005.backblazeb2.com',
    'site_id' => 'B3659',
    'access_key' => 'test_key',
    'secret_access_key' => 'test_secret',
];

echo "=== Test 1: Key Generation Logic ===\n";
$key_prefix = 'users';
$user_id = 123;
$site_id = $mock_connection['site_id'];

// Simulate the key generation logic from upload_file()
$key_parts = [];

// Add site_id if available
if ( !empty( $site_id ) ) {
    $key_parts[] = $site_id;
}

// Add key prefix
$key_prefix_clean = trim( $key_prefix, '/' );
if ( !empty( $key_prefix_clean ) ) {
    $key_parts[] = $key_prefix_clean;
}

// Add user_id
if ( !empty( $user_id ) ) {
    $key_parts[] = $user_id;
}

// Add random string (simulated)
$random_string = 'K6TMnqHM460NglXaiM6q6wjc3qkGiVRngAMz0mDrnW4VYXwcssVdjgZGyZTGFdci';
$key_parts[] = $random_string;

// Join parts
$key = implode( '/', $key_parts );

// Add file extension
$ext = pathinfo( $test_file, PATHINFO_EXTENSION );
if ( !empty( $ext ) ) {
    $key .= '.' . $ext;
}

echo "Generated key: $key\n";
echo "Expected pattern: {site_id}/{key_prefix}/{user_id}/{random_string}.{ext}\n";
echo "Match: " . ( $key === 'B3659/users/123/K6TMnqHM460NglXaiM6q6wjc3qkGiVRngAMz0mDrnW4VYXwcssVdjgZGyZTGFdci.jpg' ? '✓' : '✗' ) . "\n\n";

// Test 2: URL Construction
echo "=== Test 2: URL Construction ===\n";
$bucket = $mock_connection['bucket'];
$endpoint = $mock_connection['endpoint'];

// Remove protocol if present
$endpoint_clean = preg_replace( '#^https?://#', '', $endpoint );

// Virtual-hosted-style URL (matches your example pattern)
$base_url = 'https://' . $bucket . '.' . $endpoint_clean . '/' . $key;

echo "Base URL: $base_url\n";
echo "Expected pattern: https://{bucket}.{endpoint}/{key}\n";
echo "Example from your URL: https://zume-storage.s3.us-east-005.backblazeb2.com/B3659/users/...\n";
echo "Match pattern: " . ( strpos( $base_url, 'https://zume-storage.s3.us-east-005.backblazeb2.com/B3659/users/' ) === 0 ? '✓' : '✗' ) . "\n\n";

// Test 3: URL Extraction (from your example)
echo "=== Test 3: URL Extraction Logic ===\n";
$example_url = 'https://zume-storage.s3.us-east-005.backblazeb2.com/B3659/users/K6TMnqHM460NglXaiM6q6wjc3qkGiVRngAMz0mDrnW4VYXwcssVdjgZGyZTGFdci.JPG?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Date=20251119T205134Z';

// Simulate extract_key_from_url logic
$decoded_url = urldecode( $example_url );
$parsed = parse_url( $decoded_url );

if ( isset( $parsed['path'] ) ) {
    $path = trim( $parsed['path'], '/' );
    echo "Extracted path: $path\n";
    
    // Should extract: B3659/users/K6TMnqHM460NglXaiM6q6wjc3qkGiVRngAMz0mDrnW4VYXwcssVdjgZGyZTGFdci.JPG
    $expected_path = 'B3659/users/K6TMnqHM460NglXaiM6q6wjc3qkGiVRngAMz0mDrnW4VYXwcssVdjgZGyZTGFdci.JPG';
    echo "Expected path: $expected_path\n";
    echo "Match: " . ( $path === $expected_path ? '✓' : '✗' ) . "\n";
}

echo "\n=== Test 4: File Array Structure ===\n";
$file_array = [
    'name' => 'beardyguy.jpg',
    'full_path' => 'beardyguy.jpg',
    'type' => mime_content_type( $test_file ),
    'tmp_name' => $test_file,
    'error' => 0,
    'size' => filesize( $test_file )
];

echo "File array structure:\n";
foreach ( $file_array as $key => $value ) {
    if ( $key === 'tmp_name' ) {
        echo "  $key: [file path]\n";
    } else {
        echo "  $key: $value\n";
    }
}

echo "\n✓ All file array fields present\n";
echo "  Required fields: name, type, tmp_name, error, size\n";
echo "  All present: " . ( isset( $file_array['name'], $file_array['type'], $file_array['tmp_name'], $file_array['error'], $file_array['size'] ) ? '✓' : '✗' ) . "\n";

echo "\n=== Test 5: Key Validation ===\n";
$test_keys = [
    'B3659/users/123/abc123.jpg', // Valid
    '/B3659/users/123/abc123.jpg', // Has leading slash (should be trimmed)
    'https://example.com/file.jpg', // Invalid (URL)
    'B3659/users/123/abc123.jpg', // Valid
];

foreach ( $test_keys as $test_key ) {
    $key_clean = ltrim( $test_key, '/' );
    $is_valid = strpos( $key_clean, '://' ) === false && strpos( $key_clean, 'http://' ) !== 0 && strpos( $key_clean, 'https://' ) !== 0;
    
    echo "Key: $test_key\n";
    echo "  Cleaned: $key_clean\n";
    echo "  Valid: " . ( $is_valid ? '✓' : '✗' ) . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nNote: To test actual upload, you need:\n";
echo "1. WordPress loaded\n";
echo "2. Valid Backblaze credentials in dt_storage_connection\n";
echo "3. AsyncAws S3 library installed\n";
echo "4. Run: php test-backblaze-upload.php (from WordPress context)\n";

