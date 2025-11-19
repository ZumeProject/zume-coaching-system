<?php
/**
 * Zume Backblaze B2 Storage API
 * 
 * Custom storage implementation for uploading coach profile photos to Backblaze B2
 * Removes dependency on DT_Storage_API
 */

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Backblaze_Storage {
    
    /**
     * Get connection settings from zume_3_options table
     * 
     * @return array|false Connection settings array or false if not found/configured
     */
    public static function get_connection_settings() {
        global $wpdb;
        
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Zume_Backblaze_Storage: Getting connection settings' );
        }
        
        // Query zume_3_options table for dt_storage_connection
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT option_value FROM zume_3_options WHERE option_name = %s",
            'dt_storage_connection'
        ) );
        
        if ( empty( $result ) ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: No settings found in zume_3_options' );
            }
            return false;
        }
        
        // Unserialize PHP serialized data
        $connection = maybe_unserialize( $result );
        
        // Validate required fields
        if ( !is_array( $connection ) || empty( $connection['enabled'] ) ) {
             if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Settings found but not enabled or invalid format' );
                if ( is_array( $connection ) ) {
                    dt_write_log( 'Zume_Backblaze_Storage: enabled value: ' . ( $connection['enabled'] ?? 'not set' ) );
                }
            }
            return false;
        }
        
        // Log available fields for debugging
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Zume_Backblaze_Storage: Available connection fields: ' . implode( ', ', array_keys( $connection ) ) );
        }
        
        // Check if required fields exist and are not empty
        $missing = [];
        if ( empty( $connection['access_key'] ?? '' ) ) $missing[] = 'access_key';
        if ( empty( $connection['secret_access_key'] ?? '' ) ) $missing[] = 'secret_access_key';
        if ( empty( $connection['region'] ?? '' ) ) $missing[] = 'region';
        if ( empty( $connection['bucket'] ?? '' ) ) $missing[] = 'bucket';
        if ( empty( $connection['endpoint'] ?? '' ) ) $missing[] = 'endpoint';
        
        if ( !empty( $missing ) ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Missing or empty required connection fields: ' . implode( ', ', $missing ) );
                // Log actual values (masked for security)
                dt_write_log( 'Zume_Backblaze_Storage: Field values (masked): ' . print_r( [
                    'access_key' => !empty( $connection['access_key'] ) ? substr( $connection['access_key'], 0, 4 ) . '...' : 'empty',
                    'secret_access_key' => !empty( $connection['secret_access_key'] ) ? substr( $connection['secret_access_key'], 0, 4 ) . '...' : 'empty',
                    'region' => $connection['region'] ?? 'not set',
                    'bucket' => $connection['bucket'] ?? 'not set',
                    'endpoint' => $connection['endpoint'] ?? 'not set',
                ], true ) );
            }
            // Don't return false yet - try to construct missing values
        }
        
        // Try to extract or construct missing values from existing URLs
        // Check if we can extract bucket/region/endpoint from existing coach profile photos
        if ( empty( $connection['bucket'] ) || empty( $connection['region'] ) || empty( $connection['endpoint'] ) || empty( $connection['site_id'] ?? '' ) ) {
            global $wpdb;
            // Try to find an existing coach profile photo URL to extract values from
            $existing_url = $wpdb->get_var(
                "SELECT meta_value FROM zume_usermeta 
                 WHERE meta_key = 'coach_profile_photo' 
                 AND meta_value LIKE 'https://%.backblazeb2.com%' 
                 LIMIT 1"
            );
            
            if ( !empty( $existing_url ) ) {
                // Extract bucket and region from URL pattern: https://{bucket}.s3.{region}.backblazeb2.com
                if ( preg_match( '#https://([^.]+)\.s3\.([^.]+)\.backblazeb2\.com#', $existing_url, $matches ) ) {
                    if ( empty( $connection['bucket'] ) && !empty( $matches[1] ) ) {
                        $connection['bucket'] = $matches[1];
                        if ( function_exists( 'dt_write_log' ) ) {
                            dt_write_log( 'Zume_Backblaze_Storage: Extracted bucket from existing URL: ' . $connection['bucket'] );
                        }
                    }
                    if ( empty( $connection['region'] ) && !empty( $matches[2] ) ) {
                        $connection['region'] = $matches[2];
                        if ( function_exists( 'dt_write_log' ) ) {
                            dt_write_log( 'Zume_Backblaze_Storage: Extracted region from existing URL: ' . $connection['region'] );
                        }
                    }
                }
                
                // Attempt to extract site_id from the path (first segment)
                if ( empty( $connection['site_id'] ) ) {
                    $path_from_url = parse_url( $existing_url, PHP_URL_PATH );
                    if ( !empty( $path_from_url ) ) {
                        $trimmed_path = trim( $path_from_url, '/' );
                        $path_segments = explode( '/', $trimmed_path );
                        
                        if ( !empty( $path_segments[0] ) ) {
                            // For path-style URLs, the first segment may be the bucket name; skip if equal
                            if ( empty( $connection['bucket'] ) || $path_segments[0] !== $connection['bucket'] ) {
                                $connection['site_id'] = $path_segments[0];
                                if ( function_exists( 'dt_write_log' ) ) {
                                    dt_write_log( 'Zume_Backblaze_Storage: Extracted site_id from existing URL: ' . $connection['site_id'] );
                                }
                            } elseif ( !empty( $path_segments[1] ) ) {
                                // Handle path-style by using the second segment
                                $connection['site_id'] = $path_segments[1];
                                if ( function_exists( 'dt_write_log' ) ) {
                                    dt_write_log( 'Zume_Backblaze_Storage: Extracted site_id (path-style) from existing URL: ' . $connection['site_id'] );
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Validate endpoint is not empty
        // Check for endpoint under various possible field names
        $endpoint_value = '';
        if ( !empty( $connection['endpoint'] ) ) {
            $endpoint_value = trim( $connection['endpoint'] );
        } elseif ( !empty( $connection['endpoint_url'] ) ) {
            $endpoint_value = trim( $connection['endpoint_url'] );
            $connection['endpoint'] = $endpoint_value;
        } elseif ( !empty( $connection['s3_endpoint'] ) ) {
            $endpoint_value = trim( $connection['s3_endpoint'] );
            $connection['endpoint'] = $endpoint_value;
        }
        
        // If endpoint is still empty, try to construct it from region (Backblaze pattern: s3.{region}.backblazeb2.com)
        if ( empty( $endpoint_value ) && !empty( $connection['region'] ) ) {
            $region = trim( $connection['region'] );
            // Construct Backblaze endpoint: s3.{region}.backblazeb2.com
            $endpoint_value = 's3.' . $region . '.backblazeb2.com';
            $connection['endpoint'] = $endpoint_value;
            
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Endpoint was empty, constructed from region: ' . $endpoint_value );
            }
        }
        
        // Final validation - check if we have all required values now
        if ( empty( $endpoint_value ) || empty( $connection['bucket'] ) || empty( $connection['region'] ) ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Still missing required values after extraction/construction' );
                dt_write_log( 'Zume_Backblaze_Storage: Final values: ' . print_r( [
                    'endpoint' => $endpoint_value ?: ( $connection['endpoint'] ?? 'empty' ),
                    'bucket' => $connection['bucket'] ?? 'empty',
                    'region' => $connection['region'] ?? 'empty',
                    'access_key_set' => !empty( $connection['access_key'] ) ? 'yes' : 'no',
                    'secret_key_set' => !empty( $connection['secret_access_key'] ) ? 'yes' : 'no',
                ], true ) );
                dt_write_log( 'Zume_Backblaze_Storage: Please configure bucket, region, and endpoint in dt_storage_connection settings' );
            }
            return false;
        }
        
        // Update connection array with constructed/extracted values
        $connection['endpoint'] = $endpoint_value;
        
        // Get site_id from connection settings (stored separately or in connection array)
        // If not in connection, try to get from zume_3_options
        if ( empty( $connection['site_id'] ) ) {
            global $wpdb;
            $site_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT option_value FROM zume_3_options WHERE option_name = %s",
                'dt_storage_site_id'
            ) );
            if ( !empty( $site_id ) ) {
                $connection['site_id'] = $site_id;
            }
        }
        
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Zume_Backblaze_Storage: Settings retrieved successfully' );
            dt_write_log( 'Zume_Backblaze_Storage: Endpoint value: ' . $connection['endpoint'] );
            dt_write_log( 'Zume_Backblaze_Storage: Bucket value: ' . $connection['bucket'] );
            dt_write_log( 'Zume_Backblaze_Storage: Site ID value: ' . ( $connection['site_id'] ?? 'not set' ) );
        }

        return $connection;
    }
    
    /**
     * Build S3 client for Backblaze B2
     * 
     * @return array [S3Client|null, bucket|null, connection_id|null]
     */
    private static function build_s3_client() {
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Zume_Backblaze_Storage: Building S3 Client' );
        }

        $connection = self::get_connection_settings();
        
        if ( !$connection ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Connection settings failed' );
            }
            return [ null, null, null ];
        }
        
        // Check if AsyncAws S3Client is available
        $class = '\\AsyncAws\\S3\\S3Client';
        if ( !class_exists( $class ) ) {
             if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: AsyncAws\S3\S3Client class not found' );
            }
            return [ null, null, null ];
        }
        
        // Validate endpoint exists and is not empty
        if ( empty( $connection['endpoint'] ) ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Endpoint is empty or missing' );
            }
            return [ null, null, null ];
        }
        
        // Validate and format endpoint URL
        $endpoint = self::validate_url( $connection['endpoint'] );
        
        // Double-check endpoint is valid after validation
        if ( empty( $endpoint ) || $endpoint === 'https://' || $endpoint === 'http://' ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( "Zume_Backblaze_Storage: Invalid endpoint after validation: " . $connection['endpoint'] );
            }
            return [ null, null, null ];
        }
        
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Zume_Backblaze_Storage: Creating client for endpoint $endpoint" );
        }

        // Logic to match DT_Storage_API path_style default
        $path_style = $connection['path_style'] ?? isset( $connection['type'] );

        try {
            // Create S3 client
            $client = new $class([
                'region' => $connection['region'],
                'endpoint' => $endpoint,
                'accessKeyId' => trim( $connection['access_key'] ),
                'accessKeySecret' => trim( $connection['secret_access_key'] ),
                'pathStyleEndpoint' => (bool) $path_style,
            ]);
             if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Client created successfully' );
            }
        } catch ( Exception $e ) {
             if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Client creation failed: ' . $e->getMessage() );
            }
            return [ null, null, null ];
        }
        
        return [ $client, $connection['bucket'], $connection['id'] ?? null ];
    }
    
    /**
     * Validate and format URL
     * 
     * @param string $url URL to validate
     * @return string Validated URL with protocol, or empty string if invalid
     */
    private static function validate_url( $url ): string {
        // Trim whitespace
        $url = trim( $url );
        
        // Return empty if URL is empty or just protocol
        if ( empty( $url ) || $url === 'https://' || $url === 'http://' ) {
            return '';
        }
        
        // If it's already a valid URL, return it
        if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return $url;
        }
        
        // If it doesn't start with http:// or https://, add https://
        $http = 'http://';
        $https = 'https://';
        if ( substr( $url, 0, strlen( $http ) ) !== $http && substr( $url, 0, strlen( $https ) ) !== $https ) {
            $url = $https . $url;
        }
        
        // Validate the final URL
        if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return '';
        }
        
        return $url;
    }
    
    /**
     * Check if storage is enabled and configured
     * 
     * @return bool True if enabled, false otherwise
     */
    public static function is_enabled(): bool {
        $connection = self::get_connection_settings();
        return !empty( $connection );
    }
    
    /**
     * Generate random string for file keys
     * 
     * @param int $length Length of random string
     * @return string Random string
     */
    private static function generate_random_string( $length = 64 ): string {
        $random_string = '';
        $keys = array_merge( range( 0, 9 ), range( 'a', 'z' ), range( 'A', 'Z' ) );
        for ( $i = 0; $i < $length; $i++ ) {
            $random_string .= $keys[mt_rand( 0, count( $keys ) - 1 )];
        }
        return $random_string;
    }
    
    /**
     * Upload file to Backblaze B2
     * 
     * @param string $key_prefix Key prefix (e.g., 'users')
     * @param array $upload File upload array from $_FILES
     * @param string $existing_key Existing key to reuse (optional)
     * @param int $user_id User ID for path structure
     * @return array|WP_Error Upload result or error
     */
    public static function upload_file( string $key_prefix = '', array $upload = [], string $existing_key = '', int $user_id = 0 ) {
        [ $client, $bucket ] = self::build_s3_client();
        
        if ( !$client ) {
            return new WP_Error(
                'storage_client_failed',
                'Failed to initialize storage connection. Please contact your administrator.',
                [ 'status' => 500 ]
            );
        }
        
        if ( empty( $bucket ) ) {
            return new WP_Error(
                'storage_bucket_missing',
                'Storage bucket is not configured. Please contact your administrator.',
                [ 'status' => 500 ]
            );
        }
        
        // Validate existing_key - if it looks like a URL or is invalid, ignore it and generate new
        if ( !empty( $existing_key ) ) {
            // Trim leading/trailing slashes
            $existing_key = trim( $existing_key, '/' );
            
            // If the key contains a protocol or looks like a URL, it's invalid
            if ( strpos( $existing_key, '://' ) !== false || 
                 strpos( $existing_key, 'http://' ) === 0 || 
                 strpos( $existing_key, 'https://' ) === 0 ) {
                if ( function_exists( 'dt_write_log' ) ) {
                    dt_write_log( "Zume_Backblaze_Storage: Invalid existing_key detected (contains URL), generating new key. Invalid key: $existing_key" );
                }
                $existing_key = '';
            }
        }
        
        // Get site_id from connection settings
        $connection = self::get_connection_settings();
        $site_id = ( is_array( $connection ) && isset( $connection['site_id'] ) ) ? $connection['site_id'] : '';
        
        // Build file key with structure: {site_id}/{key_prefix}/{user_id}/{random_string}.{ext}
        if ( empty( $existing_key ) ) {
            $key_prefix = trim( $key_prefix, '/' );
            
            // Build key parts
            $key_parts = [];
            
            // Add site_id if available
            if ( !empty( $site_id ) ) {
                $key_parts[] = $site_id;
            }
            
            // Add key prefix (e.g., 'users' or other storage folders)
            if ( !empty( $key_prefix ) ) {
                $key_parts[] = $key_prefix;
            }
            
            // Add user_id
            if ( !empty( $user_id ) ) {
                $key_parts[] = $user_id;
            }
            
            // Add random string
            $random_string = self::generate_random_string( 64 );
            $key_parts[] = $random_string;
            
            // Join parts
            $key = implode( '/', $key_parts );
            
            // Add file extension if available
            if ( !empty( $upload['full_path'] ) ) {
                $ext = pathinfo( $upload['full_path'], PATHINFO_EXTENSION );
                if ( !empty( $ext ) ) {
                    $key .= '.' . $ext;
                }
            } elseif ( !empty( $upload['name'] ) ) {
                $ext = pathinfo( $upload['name'], PATHINFO_EXTENSION );
                if ( !empty( $ext ) ) {
                    $key .= '.' . $ext;
                }
            }
        } else {
            // Use existing key, but ensure it doesn't start with a slash
            $key = ltrim( $existing_key, '/' );
            
            // If existing key doesn't have site_id and we have one, prepend it
            if ( !empty( $site_id ) && strpos( $key, $site_id . '/' ) !== 0 ) {
                $key = $site_id . '/' . $key;
            }
        }
        
        $tmp = $upload['tmp_name'] ?? '';
        $type = $upload['type'] ?? '';
        
        if ( empty( $tmp ) || !file_exists( $tmp ) ) {
            return new WP_Error(
                'storage_upload_failed',
                'Invalid file upload. Please try again.',
                [ 'status' => 400 ]
            );
        }
        
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Attempting Backblaze upload to bucket: $bucket with key: $key" );
            dt_write_log( "File size: " . filesize( $tmp ) );
        }

        try {
            $client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => fopen( $tmp, 'r' ),
                'ContentType' => $type
            ]);
            
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( "Backblaze upload successful for key: $key" );
            }

            return [
                'uploaded' => true,
                'uploaded_key' => $key,
                'uploaded_msg' => null
            ];
        } catch ( Throwable $e ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Backblaze Upload Error: ' . $e->getMessage() );
                // dt_write_log( 'Backblaze Upload Error Trace: ' . $e->getTraceAsString() ); // Trace might be too long
            }
            return new WP_Error(
                'storage_upload_failed',
                'Something went wrong. Please try again or have an admin check the connection settings. Error: ' . $e->getMessage(),
                [
                    'technical_details' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );
        }
    }
    
    /**
     * Generate fully qualified presigned URL for a file
     * Matches the pattern: https://{bucket}.{endpoint}/{site_id}/{key_prefix}/{user_id}/{filename}?presigned_params
     * 
     * @param string $key File key (should include site_id if not in connection settings)
     * @param int $expires_in Expiration time in seconds (default 24 hours)
     * @return string Presigned URL or empty string on failure
     */
    public static function get_file_url( string $key, int $expires_in = 86400 ): string {
        $connection = self::get_connection_settings();
        
        if ( !$connection || empty( $connection['bucket'] ) || empty( $connection['endpoint'] ) ) {
            return '';
        }
        
        // Ensure key doesn't start with a slash
        $key = ltrim( $key, '/' );
        
        if ( empty( $key ) ) {
            return '';
        }
        
        // Get site_id
        $site_id = $connection['site_id'] ?? '';
        
        // If key doesn't start with site_id and we have one, prepend it
        if ( !empty( $site_id ) && strpos( $key, $site_id . '/' ) !== 0 ) {
            $key = $site_id . '/' . $key;
        }
        
        // Build S3 client for presigning
        [ $client, $bucket ] = self::build_s3_client();
        
        if ( !$client || empty( $bucket ) ) {
            return '';
        }
        
        // Build base URL structure matching the pattern
        // Pattern: https://{bucket}.{endpoint}/{site_id}/{key_prefix}/{user_id}/{filename}
        $endpoint = trim( $connection['endpoint'] );
        $endpoint = preg_replace( '#^https?://#', '', $endpoint );
        
        // Virtual-hosted-style URL (matches the example pattern)
        // This is the fully qualified domain name format
        $base_url = 'https://' . $connection['bucket'] . '.' . $endpoint . '/' . $key;
        
        try {
            // Try to generate presigned URL using AsyncAws
            // Create GetObject request
            $request = new \AsyncAws\S3\Input\GetObjectRequest([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
            
            // Create presigned URL with expiration
            $expires_at = new \DateTimeImmutable( '+' . $expires_in . ' seconds' );
            
            // Try different AsyncAws presigning approaches
            $presigned_url = null;
            
            // Method 1: Check if client has presign method
            if ( method_exists( $client, 'presign' ) ) {
                try {
                    $presignedUrl = $client->presign( $request, $expires_at );
                    $presigned_url = (string) $presignedUrl->getUri();
                } catch ( Throwable $e ) {
                    // Try next method
                }
            }
            
            // Method 2: Try using PresignedUrlProvider
            if ( empty( $presigned_url ) && class_exists( '\\AsyncAws\\S3\\PresignedUrlProvider' ) ) {
                try {
                    $presigner = new \AsyncAws\S3\PresignedUrlProvider( $client );
                    $presigned_url = $presigner->getPresignedUrl( $request, $expires_at );
                } catch ( Throwable $e ) {
                    // Try next method
                }
            }
            
            // Method 3: Try using Signer class
            if ( empty( $presigned_url ) && class_exists( '\\AsyncAws\\Core\\Signer\\Signer' ) ) {
                try {
                    // This would require more setup, skip for now
                } catch ( Throwable $e ) {
                    // Fall through to base URL
                }
            }
            
            if ( !empty( $presigned_url ) ) {
                if ( function_exists( 'dt_write_log' ) ) {
                    dt_write_log( "Zume_Backblaze_Storage: Generated presigned URL: $presigned_url" );
                }
                return $presigned_url;
            }
            
        } catch ( Throwable $e ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Zume_Backblaze_Storage: Failed to generate presigned URL: ' . $e->getMessage() );
                dt_write_log( 'Zume_Backblaze_Storage: Error class: ' . get_class( $e ) );
            }
        }
        
        // Fallback: Return base URL (non-presigned)
        // Note: This URL will work if the bucket is public, but won't have expiration
        // For production, presigned URLs should be generated
        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Zume_Backblaze_Storage: Using base URL (non-presigned): $base_url" );
        }
        
        return $base_url;
    }
    
    /**
     * Extract storage key from a public URL
     * Handles nested/encoded URLs and extracts the actual object key
     * Pattern: https://{bucket}.{endpoint}/{site_id}/{key_prefix}/{user_id}/{filename}?params
     * 
     * @param string $url Public URL to extract key from
     * @param string $bucket_name Bucket name to remove from path (optional, auto-detected)
     * @return string Extracted key (includes site_id) or empty string on failure
     */
    public static function extract_key_from_url( string $url, string $bucket_name = '' ): string {
        if ( empty( $url ) ) {
            return '';
        }
        
        // Decode URL-encoded strings multiple times to handle nested encoding
        $decoded_url = $url;
        $previous_url = '';
        while ( $decoded_url !== $previous_url ) {
            $previous_url = $decoded_url;
            $decoded_url = urldecode( $decoded_url );
        }
        
        // Parse the URL
        $parsed = parse_url( $decoded_url );
        
        if ( !isset( $parsed['path'] ) || empty( $parsed['path'] ) ) {
            return '';
        }
        
        // Get the path and remove leading/trailing slashes
        $path = trim( $parsed['path'], '/' );
        
        // Handle virtual-hosted-style URLs: {bucket}.{endpoint}/{key}
        // The path should start directly with the key (site_id/key_prefix/...)
        // For path-style URLs: {endpoint}/{bucket}/{key}, we need to remove bucket
        
        // If bucket_name is provided and path starts with it, remove it
        if ( !empty( $bucket_name ) ) {
            $path_parts = explode( '/', $path, 2 );
            if ( count( $path_parts ) > 1 && $path_parts[0] === $bucket_name ) {
                $path = $path_parts[1];
            }
        } else {
            // Try to detect bucket from hostname
            // Pattern: {bucket}.s3.{region}.backblazeb2.com
            if ( isset( $parsed['host'] ) ) {
                $host = $parsed['host'];
                // Check if it matches bucket.endpoint pattern
                if ( strpos( $host, '.' ) !== false ) {
                    $host_parts = explode( '.', $host, 2 );
                    // If first part looks like a bucket name and path might start with it, check
                    // But for virtual-hosted-style, the path shouldn't include bucket
                    // So we don't remove anything here
                }
            }
        }
        
        // If path still looks like a URL (contains http:// or https://), 
        // recursively extract from it
        if ( strpos( $path, 'http://' ) === 0 || strpos( $path, 'https://' ) === 0 ) {
            return self::extract_key_from_url( $path, $bucket_name );
        }
        
        // Validate that the key looks reasonable (not a full URL)
        if ( strpos( $path, '://' ) !== false ) {
            // Still contains a protocol, something is wrong
            return '';
        }
        
        // Ensure key doesn't start with a slash
        $path = ltrim( $path, '/' );
        
        // The path should now be: {site_id}/{key_prefix}/{user_id}/{filename}
        // Return it as-is (includes site_id)
        return $path;
    }
    
    /**
     * Delete file from Backblaze B2
     * 
     * @param string $key File key to delete
     * @return array|null Delete result or null on failure
     */
    public static function delete_file( string $key ) {
        [ $client, $bucket ] = self::build_s3_client();
        
        if ( !$client || empty( $bucket ) ) {
            return null;
        }
        
        // Ensure key doesn't start with a slash and is valid
        $key = ltrim( $key, '/' );
        
        if ( empty( $key ) || strpos( $key, '://' ) !== false ) {
            // Invalid key
            return null;
        }
        
        try {
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key
            ]);
            
            return [
                'file_key' => $key,
                'file_deleted' => true
            ];
        } catch ( Throwable $e ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( 'Backblaze Delete Error: ' . $e->getMessage() );
            }
            return null;
        }
    }
}

