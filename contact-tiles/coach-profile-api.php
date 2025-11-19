<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coach_Profile_API
{
    public $namespace = 'zume_coaching/v1';
    private static $_instance = null;

    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        if ( dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }
    
    public function add_api_routes()
    {
        register_rest_route(
            $this->namespace, '/coach-profile', [
                'methods' => [ 'POST' ],
                'callback' => [ $this, 'save_coach_profile' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'public_profile_enabled' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'public_slug' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'bio' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'experience' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'location' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'focus_of_ministry' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'greeting_video_url' => [
                        'type' => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                    'testimonials' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                    'sanitize_callback' => 'sanitize_text_field',
                                ],
                                'quote' => [
                                    'type' => 'string',
                                    'sanitize_callback' => 'sanitize_textarea_field',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Storage upload endpoint for users
        register_rest_route(
            $this->namespace, '/users/(?P<user_id>\d+)/storage_upload', [
                'methods' => [ 'POST' ],
                'callback' => [ $this, 'storage_upload' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                ],
            ]
        );

        // Storage delete endpoint for users
        register_rest_route(
            $this->namespace, '/users/(?P<user_id>\d+)/storage_delete', [
                'methods' => [ 'POST' ],
                'callback' => [ $this, 'storage_delete' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                ],
            ]
        );
    }
    
    public function permission_check( $request ) {
        // Check if user is logged in
        if ( !is_user_logged_in() ) {
            return new WP_Error( 'rest_forbidden', 'You must be logged in to access this endpoint.', array( 'status' => 401 ) );
        }
        
        $user_id = $request->get_param( 'user_id' );
        
        // Check if user is trying to update their own profile or has admin capabilities
        if ( get_current_user_id() != $user_id && !current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'rest_forbidden', 'You can only update your own coach profile.', array( 'status' => 403 ) );
        }
        
        return true;
    }
    
    public function save_coach_profile( WP_REST_Request $request ) {
        $params = $request->get_params();
        $user_id = intval( $params['user_id'] );
        
        // Verify user exists
        $user = get_user_by( 'ID', $user_id );
        if ( !$user ) {
            return new WP_Error( 'invalid_user', 'User not found.', array( 'status' => 404 ) );
        }
        
        $errors = [];
        $updated_fields = [];
        
        // Handle coach profile photo upload
        if ( isset( $_FILES['coach_profile_photo'] ) && !empty( $_FILES['coach_profile_photo']['tmp_name'] ) ) {
            
            // Check if Zume_Backblaze_Storage is available
            if ( !Zume_Backblaze_Storage::is_enabled() ) {
                $errors[] = 'Photo storage is not available. Please contact your administrator.';
            } else {
                // Get existing photo URL to extract key for deletion (optional - we'll generate new key)
                $existing_photo_url = get_user_meta( $user_id, 'coach_profile_photo', true );
                $existing_photo_key = '';
                
                // Try to extract key from existing URL if it exists
                if ( !empty( $existing_photo_url ) && ( strpos( $existing_photo_url, 'http://' ) === 0 || strpos( $existing_photo_url, 'https://' ) === 0 ) ) {
                    // Get bucket name from connection settings for proper key extraction
                    $connection = Zume_Backblaze_Storage::get_connection_settings();
                    $bucket_name = !empty( $connection['bucket'] ) ? $connection['bucket'] : '';
                    
                    // Use the helper method to extract key from URL
                    $existing_photo_key = Zume_Backblaze_Storage::extract_key_from_url( $existing_photo_url, $bucket_name );
                    
                    // If extraction failed or resulted in invalid key, clear it
                    if ( empty( $existing_photo_key ) || strpos( $existing_photo_key, '://' ) !== false ) {
                        $existing_photo_key = '';
                    }
                }
                
                // Upload to Backblaze B2 storage
                if ( function_exists( 'dt_write_log' ) ) {
                    dt_write_log( 'Uploading coach profile photo for user ' . $user_id . ': ' . print_r( $_FILES['coach_profile_photo'], true ) );
                }
                $uploaded = Zume_Backblaze_Storage::upload_file( 'users', $_FILES['coach_profile_photo'], $existing_photo_key, $user_id );
                
                // Handle upload errors
                if ( is_wp_error( $uploaded ) ) {
                    if ( function_exists( 'dt_write_log' ) ) {
                        dt_write_log( 'Coach profile photo upload failed for user ' . $user_id . ': ' . $uploaded->get_error_message() );
                    }
                    $errors[] = 'Failed to upload profile photo. Please try again.';
                } elseif ( $uploaded['uploaded'] === true && !empty( $uploaded['uploaded_key'] ) ) {
                    // Generate the full URL from the uploaded key
                    $full_photo_url = Zume_Backblaze_Storage::get_file_url( $uploaded['uploaded_key'] );
                    if ( !empty( $full_photo_url ) ) {
                        // Store the complete, fully qualified URL in user meta
                        update_user_meta( $user_id, 'coach_profile_photo', $full_photo_url );
                        $updated_fields[] = 'coach_profile_photo';
                        $photo_updated = true;
                        
                        // Delete old photo if it exists and is different
                        if ( !empty( $existing_photo_key ) && $existing_photo_key !== $uploaded['uploaded_key'] ) {
                            Zume_Backblaze_Storage::delete_file( $existing_photo_key );
                        }
                    } else {
                        $errors[] = 'Failed to generate photo URL. Please try again.';
                    }
                }
            }
        }
        
        // Handle public profile enabled
        if ( isset( $params['public_profile_enabled'] ) ) {
            $enabled = $params['public_profile_enabled'] === '1' ? '1' : '';
            update_user_meta( $user_id, 'coach_public_profile_enabled', $enabled );
            $updated_fields[] = 'public_profile_enabled';
        }
        
        // Handle public slug
        if ( isset( $params['public_slug'] ) ) {
            $slug = sanitize_text_field( $params['public_slug'] );
            
            if ( !empty( $slug ) ) {
                // Validate slug
                $validation = $this->validate_coach_slug( $slug, $user_id );
                if ( !$validation['valid'] ) {
                    $errors[] = $validation['message'];
                } else {
                    update_user_meta( $user_id, 'coach_public_slug', $slug );
                    $updated_fields[] = 'public_slug';
                }
            } else {
                // If slug is empty, remove the custom slug (will fallback to user_nicename)
                delete_user_meta( $user_id, 'coach_public_slug' );
                $updated_fields[] = 'public_slug';
            }
        }
        
        // Handle bio
        if ( isset( $params['bio'] ) ) {
            $bio = sanitize_textarea_field( $params['bio'] );
            update_user_meta( $user_id, 'coach_bio', $bio );
            $updated_fields[] = 'bio';
        }
        
        // Handle experience
        if ( isset( $params['experience'] ) ) {
            $experience = sanitize_textarea_field( $params['experience'] );
            update_user_meta( $user_id, 'coach_experience', $experience );
            $updated_fields[] = 'experience';
        }
        
        // Handle location
        if ( isset( $params['location'] ) ) {
            $location = sanitize_text_field( $params['location'] );
            update_user_meta( $user_id, 'coach_location', $location );
            $updated_fields[] = 'location';
        }
        
        // Handle focus of ministry
        if ( isset( $params['focus_of_ministry'] ) ) {
            $focus_of_ministry = sanitize_textarea_field( $params['focus_of_ministry'] );
            update_user_meta( $user_id, 'coach_focus_of_ministry', $focus_of_ministry );
            $updated_fields[] = 'focus_of_ministry';
        }
        
        // Handle greeting video URL
        if ( isset( $params['greeting_video_url'] ) ) {
            $video_url = esc_url_raw( $params['greeting_video_url'] );
            update_user_meta( $user_id, 'coach_greeting_video_url', $video_url );
            $updated_fields[] = 'greeting_video_url';
        }
        
        // Handle testimonials
        if ( isset( $params['testimonials'] ) ) {
            $testimonials = [];
            foreach ( $params['testimonials'] as $testimonial ) {
                if ( !empty( $testimonial['name'] ) || !empty( $testimonial['quote'] ) ) {
                    $testimonials[] = [
                        'name' => sanitize_text_field( $testimonial['name'] ),
                        'quote' => sanitize_textarea_field( $testimonial['quote'] ),
                    ];
                }
            }
            update_user_meta( $user_id, 'coach_testimonials', $testimonials );
            $updated_fields[] = 'testimonials';
        }
        
        if ( !empty( $errors ) ) {
            return new WP_Error( 'validation_error', implode( '; ', $errors ), array( 'status' => 400 ) );
        }
        
        $response = [
            'success' => true,
            'message' => 'Coach profile updated successfully.',
            'updated_fields' => $updated_fields,
            'photo_updated' => isset( $photo_updated ) && $photo_updated,
        ];
        return $response;
    }

    /**
     * Storage upload endpoint for users
     * Mimics the structure of dt-posts storage_upload endpoint
     */
    public function storage_upload( WP_REST_Request $request ) {
        $params = $request->get_params();
        $user_id = intval( $params['user_id'] );

        // Verify user exists
        $user = get_user_by( 'ID', $user_id );
        if ( !$user ) {
            return new WP_Error( 'invalid_user', 'User not found.', array( 'status' => 404 ) );
        }

        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( !isset( $params['meta_key'], $_FILES['storage_upload_files'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }

        if ( !Zume_Backblaze_Storage::is_enabled() ) {
            return new WP_Error( __METHOD__, 'Zume_Backblaze_Storage Unavailable.' );
        }

        $meta_key = sanitize_text_field( $params['meta_key'] );
        $key_prefix = isset( $params['key_prefix'] ) ? sanitize_text_field( $params['key_prefix'] ) : '';
        
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $files = dt_recursive_sanitize_array( $_FILES['storage_upload_files'] );

        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Coach Profile Upload Debug: $_FILES' );
            dt_write_log( $_FILES );
            dt_write_log( 'Coach Profile Upload Debug: Sanitized $files' );
            dt_write_log( $files );
        }

        // Only process the first file within the uploaded array.
        $uploaded_file = [
            'name' => $files['name'][0],
            'full_path' => isset( $files['full_path'][0] ) ? $files['full_path'][0] : '',
            'type' => $files['type'][0],
            'tmp_name' => $files['tmp_name'][0],
            'error' => (int) $files['error'][0],
            'size' => (int) $files['size'][0]
        ];

        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( 'Coach Profile Upload Debug: $uploaded_file' );
            dt_write_log( $uploaded_file );
        }

        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Coach Profile Upload Debug: Checking upload errors" );
        }

        // Check for PHP upload errors
        if ( $uploaded_file['error'] !== UPLOAD_ERR_OK ) {
            return [
                'uploaded' => false,
                'uploaded_key' => '',
                'uploaded_msg' => 'File upload error: ' . $this->get_upload_error_message( $uploaded_file['error'] )
            ];
        }

        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Coach Profile Upload Debug: Getting existing meta" );
        }

        // Get existing photo value to extract key for deletion
        $existing_photo_value = get_user_meta( $user_id, $meta_key, true );
        $existing_key = '';
        
        // If existing value is a URL, try to extract the key from it
        if ( !empty( $existing_photo_value ) ) {
            if ( function_exists( 'dt_write_log' ) ) {
                dt_write_log( "Coach Profile Upload Debug: Parsing existing URL: $existing_photo_value" );
            }
            // Check if it's a URL (contains http:// or https://)
            if ( strpos( $existing_photo_value, 'http://' ) === 0 || strpos( $existing_photo_value, 'https://' ) === 0 ) {
                // Get bucket name from connection settings for proper key extraction
                $connection = Zume_Backblaze_Storage::get_connection_settings();
                $bucket_name = !empty( $connection['bucket'] ) ? $connection['bucket'] : '';
                
                // Use the helper method to extract key from URL
                $existing_key = Zume_Backblaze_Storage::extract_key_from_url( $existing_photo_value, $bucket_name );
                
                if ( function_exists( 'dt_write_log' ) ) {
                    dt_write_log( "Coach Profile Upload Debug: Extracted key: $existing_key" );
                }
                
                // If extraction failed or resulted in invalid key, clear it
                if ( empty( $existing_key ) || strpos( $existing_key, '://' ) !== false ) {
                    if ( function_exists( 'dt_write_log' ) ) {
                        dt_write_log( "Coach Profile Upload Debug: Invalid key extracted, clearing existing_key" );
                    }
                    $existing_key = '';
                }
            } else {
                // Assume it's already a storage key
                $existing_key = $existing_photo_value;
            }
        }

        if ( function_exists( 'dt_write_log' ) ) {
            dt_write_log( "Coach Profile Upload Debug: Calling upload_file with key: $existing_key" );
        }

        // Push an uploaded file to backend storage service.
        $uploaded = Zume_Backblaze_Storage::upload_file( $key_prefix, $uploaded_file, $existing_key, $user_id );

        // Handle WP_Error returns from Zume_Backblaze_Storage::upload_file()
        if ( is_wp_error( $uploaded ) ) {
            return [
                'uploaded' => false,
                'uploaded_key' => '',
                'uploaded_msg' => $uploaded->get_error_message()
            ];
        }

        // Handle false return (client build failure)
        if ( $uploaded === false ) {
            return [
                'uploaded' => false,
                'uploaded_key' => '',
                'uploaded_msg' => 'Failed to initialize storage connection. Please contact your administrator.'
            ];
        }

        // If successful, generate full URL and store in user meta
        if ( $uploaded['uploaded'] === true ) {
            if ( !empty( $uploaded['uploaded_key'] ) ) {
                $uploaded_key = $uploaded['uploaded_key'];

                // Generate the full URL from the uploaded key
                $full_photo_url = Zume_Backblaze_Storage::get_file_url( $uploaded_key );
                
                if ( !empty( $full_photo_url ) ) {
                    // Store the complete, fully qualified URL in user meta
                    update_user_meta( $user_id, $meta_key, $full_photo_url );
                    
                    // Also store the key separately for delete operations
                    update_user_meta( $user_id, $meta_key . '_key', $uploaded_key );
                    
                    // Delete old photo if it exists and is different
                    if ( !empty( $existing_key ) && $existing_key !== $uploaded_key ) {
                        Zume_Backblaze_Storage::delete_file( $existing_key );
                    }
                } else {
                    return [
                        'uploaded' => false,
                        'uploaded_key' => '',
                        'uploaded_msg' => 'Failed to generate photo URL. Please try again.'
                    ];
                }
            }
        }

        return [
            'uploaded' => $uploaded['uploaded'],
            'uploaded_key' => $uploaded_key ?? '',
            'uploaded_msg' => $uploaded['uploaded_msg'] ?? null
        ];
    }

    /**
     * Storage delete endpoint for users
     */
    public function storage_delete( WP_REST_Request $request ) {
        $params = $request->get_params();
        $user_id = intval( $params['user_id'] );

        // Verify user exists
        $user = get_user_by( 'ID', $user_id );
        if ( !$user ) {
            return new WP_Error( 'invalid_user', 'User not found.', array( 'status' => 404 ) );
        }

        if ( !isset( $params['meta_key'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }

        if ( !( method_exists( 'Zume_Backblaze_Storage', 'delete_file' ) && Zume_Backblaze_Storage::is_enabled() ) ) {
            return new WP_Error( __METHOD__, 'Zume_Backblaze_Storage Delete Function Unavailable.' );
        }

        $meta_key = sanitize_text_field( $params['meta_key'] );

        // Get stored key (prefer stored key, fallback to extracting from URL)
        $stored_key = get_user_meta( $user_id, $meta_key . '_key', true );
        
        // If no stored key, try to get from URL
        if ( empty( $stored_key ) ) {
            $stored_value = get_user_meta( $user_id, $meta_key, true );
            
            if ( !empty( $stored_value ) ) {
                // Check if it's a URL
                if ( strpos( $stored_value, 'http://' ) === 0 || strpos( $stored_value, 'https://' ) === 0 ) {
                    // Get bucket name from connection settings for proper key extraction
                    $connection = Zume_Backblaze_Storage::get_connection_settings();
                    $bucket_name = !empty( $connection['bucket'] ) ? $connection['bucket'] : '';
                    
                    // Use the helper method to extract key from URL
                    $stored_key = Zume_Backblaze_Storage::extract_key_from_url( $stored_value, $bucket_name );
                    
                    // If extraction failed or resulted in invalid key, clear it
                    if ( empty( $stored_key ) || strpos( $stored_key, '://' ) !== false ) {
                        $stored_key = '';
                    }
                } else {
                    // Assume it's a key
                    $stored_key = $stored_value;
                }
            }
        }

        if ( empty( $stored_key ) ) {
            // No key found, just remove the meta
            delete_user_meta( $user_id, $meta_key );
            delete_user_meta( $user_id, $meta_key . '_key' );
            return [
                'deleted' => true,
                'message' => 'Meta entry removed (no storage key found).'
            ];
        }

        // Delete file from storage
        $deleted = Zume_Backblaze_Storage::delete_file( $stored_key );

        if ( $deleted !== null ) {
            // Remove user meta entries
            delete_user_meta( $user_id, $meta_key );
            delete_user_meta( $user_id, $meta_key . '_key' );

            return [
                'deleted' => true,
                'file_key' => $stored_key
            ];
        }

        return [
            'deleted' => false,
            'error' => 'Failed to delete file from storage.'
        ];
    }

    /**
     * Get human-readable upload error message
     */
    private function get_upload_error_message( $error_code ) {
        switch ( $error_code ) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize limit.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE limit.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Validate coach slug is URL-safe and unique
     * 
     * @param string $slug The slug to validate
     * @param int $exclude_user_id User ID to exclude from uniqueness check (for updates)
     * @return array Validation result with 'valid' boolean and 'message' string
     */
    private function validate_coach_slug( $slug, $exclude_user_id = 0 ) {
        if ( empty( $slug ) ) {
            return [ 'valid' => false, 'message' => 'Slug cannot be empty' ];
        }
        
        // Check if slug is URL-safe (lowercase, alphanumeric, hyphens only)
        if ( !preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
            return [ 'valid' => false, 'message' => 'Slug must contain only lowercase letters, numbers, and hyphens' ];
        }
        
        // Check if slug starts or ends with hyphen
        if ( substr( $slug, 0, 1 ) === '-' || substr( $slug, -1 ) === '-' ) {
            return [ 'valid' => false, 'message' => 'Slug cannot start or end with a hyphen' ];
        }
        
        // Check for uniqueness
        global $wpdb;
        $existing_user_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM zume_usermeta 
             WHERE meta_key = 'coach_public_slug' 
             AND meta_value = %s 
             AND user_id != %d",
            $slug,
            $exclude_user_id
        ) );
        
        if ( $existing_user_id ) {
            return [ 'valid' => false, 'message' => 'This slug is already taken by another coach' ];
        }
        
        return [ 'valid' => true, 'message' => 'Slug is valid' ];
    }
}
Zume_Coach_Profile_API::instance();
