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
        
        return [
            'success' => true,
            'message' => 'Coach profile updated successfully.',
            'updated_fields' => $updated_fields,
        ];
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
