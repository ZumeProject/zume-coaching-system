<?php
/**
 * User Profile Coach Tile for Zume Coaching System
 * 
 * Adds a coach profile management section to the user's profile settings page.
 * 
 * @package Zume_Coaching
 * @since 0.1
 */

if ( !defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly.
}

class Zume_User_Profile_Coach_Tile {
    private static $_instance = null;
    
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        // Add menu item to user profile settings
        add_action( 'dt_profile_settings_page_menu', [ $this, 'add_profile_menu_item' ], 10, 4 );
        
        // Add section content to user profile settings
        add_action( 'dt_profile_settings_page_sections', [ $this, 'add_profile_section' ], 10, 4 );
        
        // Enqueue scripts for coach profile modal
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99 );
    }
    
    /**
     * Enqueue scripts for coach profile photo upload modal
     */
    public function enqueue_scripts() {
        // Only enqueue if user is logged in
        if ( !is_user_logged_in() ) {
            return;
        }
        
        $script_path = plugin_dir_path( __FILE__ ) . 'contact-tiles/coach-profile-modal.js';
        $script_url = plugin_dir_url( __FILE__ ) . 'contact-tiles/coach-profile-modal.js';
        
        if ( !file_exists( $script_path ) ) {
            return;
        }
        
        // Check if Foundation is already enqueued, if not, try to enqueue it
        // Foundation is typically loaded by the theme, but we'll add it as a dependency
        $dependencies = [ 'jquery' ];
        
        // Try to detect if Foundation is available
        if ( !wp_script_is( 'foundation', 'enqueued' ) && !wp_script_is( 'foundation', 'registered' ) ) {
            // Foundation might be loaded by theme, so we'll just depend on jQuery
            // The script will handle Foundation availability check
        } else {
            $dependencies[] = 'foundation';
        }
        
        wp_enqueue_script(
            'coach-profile-modal',
            $script_url,
            $dependencies,
            filemtime( $script_path ),
            true
        );
        
        // Localize script with REST API URL and nonce
        wp_localize_script(
            'coach-profile-modal',
            'coachProfileModalSettings',
            [
                'rest_url' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'translations' => [
                    'title' => __( 'File Upload', 'zume-coaching' ),
                    'choose_file' => __( 'Choose a file', 'zume-coaching' ),
                    'or_drag_it' => __( 'or drag it here', 'zume-coaching' ),
                    'success' => __( 'Successfully Uploaded!', 'zume-coaching' ),
                    'error' => __( 'Error!', 'zume-coaching' ),
                    'error_msg' => __( 'Unable to upload, please try again', 'zume-coaching' ),
                    'but_upload' => __( 'Upload', 'zume-coaching' ),
                    'but_delete' => __( 'Delete Existing File', 'zume-coaching' ),
                    'but_replace' => __( 'Replace Existing Image', 'zume-coaching' ),
                    'delete_msg' => __( 'Are you sure you wish to delete existing file?', 'zume-coaching' ),
                    'delete_success_msg' => __( 'Successfully Deleted!', 'zume-coaching' ),
                    'delete_error_msg' => __( 'Delete failed, please try again', 'zume-coaching' ),
                    'but_close' => __( 'Close', 'zume-coaching' ),
                ],
            ]
        );
    }
    
    /**
     * Add menu item to the user profile settings navigation
     */
    public function add_profile_menu_item( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        ?>
        <li><a href="#coach-profile"><?php esc_html_e( 'Coach Profile', 'zume-coaching' ); ?></a></li>
        <?php
    }
    
    /**
     * Add coach profile section to the user profile settings page
     */
    public function add_profile_section( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        $user_id = $dt_user->ID;
        
        // Get current coach profile settings
        $public_profile_enabled = get_user_meta( $user_id, 'coach_public_profile_enabled', true );
        $public_slug = get_user_meta( $user_id, 'coach_public_slug', true );
        $bio = get_user_meta( $user_id, 'coach_bio', true );
        $experience = get_user_meta( $user_id, 'coach_experience', true );
        $public_location = get_user_meta( $user_id, 'coach_location', true );
        $focus_of_ministry = get_user_meta( $user_id, 'coach_focus_of_ministry', true );
        $testimonials = get_user_meta( $user_id, 'coach_testimonials', true );
        $greeting_video_url = get_user_meta( $user_id, 'coach_greeting_video_url', true );
        
        // If no custom slug set, use user_nicename as fallback
        if ( empty( $public_slug ) ) {
            $public_slug = $dt_user->user_nicename;
        }
        
        // Parse testimonials if it's a string
        if ( is_string( $testimonials ) ) {
            $testimonials = maybe_unserialize( $testimonials );
        }
        if ( !is_array( $testimonials ) ) {
            $testimonials = [];
        }
        
        ?>
        <div class="bordered-box cell" id="coach-profile" data-magellan-target="coach-profile">
            
            <button class="help-button float-right" data-section="coach-profile-help-text">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
            </button>
            
            <span class="section-header"><?php esc_html_e( 'Coach Public Profile', 'zume-coaching' ); ?></span>
            
            <hr/>
            
            <div class="grid-x grid-margin-x grid-padding-x grid-padding-y">
                
                <div class="small-12 cell">
                    <p><?php esc_html_e( 'Manage your public coach profile that will be displayed at', 'zume-coaching' ); ?> <code>/app/coaches/?name={your-slug}</code></p>
                    
                    
                    <div class="success-message" id="success-message" style="display: none;"></div>
                    <div class="error-message" id="error-message" style="display: none;"></div>
                    
                    <form class="coach-profile-form" id="coach-profile-form">
                        <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
                        
                        <!-- Enable Public Profile -->
                        <div class="form-group">
                            <div class="checkbox-group" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="public_profile_enabled" name="public_profile_enabled" value="1" <?php checked( $public_profile_enabled, '1' ); ?>>
                                <label for="public_profile_enabled"><?php esc_html_e( 'Enable Public Coach Profile', 'zume-coaching' ); ?></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'When enabled, your profile will be publicly accessible at the URL below.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Public Slug -->
                        <div class="form-group">
                            <label for="public_slug"><?php esc_html_e( 'Profile URL Slug', 'zume-coaching' ); ?></label>
                            <input type="text" id="public_slug" name="public_slug" value="<?php echo esc_attr( $public_slug ); ?>" placeholder="your-username" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                            <div class="profile-url" id="profile-url-preview" style="background: #f0f0f0; padding: 0.5rem; border-radius: 4px; font-family: monospace; margin-top: 0.5rem;">
                                <?php echo esc_url( 'https://zume.training/app/coaches/?name=' . $public_slug ); ?>
                            </div>
                            <p class="description"><?php esc_html_e( 'This will be your public profile URL. Use only lowercase letters, numbers, and hyphens.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Coach Profile Photo -->
                        <div class="form-group">
                            <label for="coach_profile_photo"><?php esc_html_e( 'Profile Photo', 'zume-coaching' ); ?></label>
                            <?php
                            $coach_photo_url = get_user_meta( $user_id, 'coach_profile_photo', true );
                            if ( !empty( $coach_photo_url ) ) {
                                echo '<div style="margin-bottom: 1rem;"><img src="' . esc_url( $coach_photo_url ) . '" alt="' . esc_attr__( 'Coach Profile Photo', 'zume-coaching' ) . '" style="width: 200px; height: 200px; object-fit: cover; border-radius: 8px;"></div>';
                            }
                            ?>
                            <button type="button" class="button coach-profile-upload-button" 
                                    data-coach-upload-user-id="<?php echo esc_attr( $user_id ); ?>"
                                    data-coach-upload-meta-key="coach_profile_photo"
                                    data-coach-upload-key-prefix="users"
                                    data-coach-upload-delete-enabled="1"
                                    style="background: #007cba; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                                <?php echo !empty( $coach_photo_url ) ? esc_html__( 'Replace Photo', 'zume-coaching' ) : esc_html__( 'Upload Photo', 'zume-coaching' ); ?>
                            </button>
                            <p class="description"><?php esc_html_e( 'Upload a profile photo for your public coach profile (recommended: square image, at least 400x400px).', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Bio -->
                        <div class="form-group">
                            <label for="bio"><?php esc_html_e( 'Biography', 'zume-coaching' ); ?></label>
                            <textarea id="bio" name="bio" placeholder="<?php esc_attr_e( 'Tell people about yourself, your background, and your passion for disciple making...', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; min-height: 100px; resize: vertical;"><?php echo esc_textarea( $bio ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'A brief introduction about yourself that will appear on your public profile.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Experience -->
                        <div class="form-group">
                            <label for="experience"><?php esc_html_e( 'Experience & Background', 'zume-coaching' ); ?></label>
                            <textarea id="experience" name="experience" placeholder="<?php esc_attr_e( 'Share your experience in disciple making, training, coaching, and ministry...', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; min-height: 100px; resize: vertical;"><?php echo esc_textarea( $experience ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Describe your experience and expertise in disciple making and training.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Location -->
                        <div class="form-group">
                            <label for="location"><?php esc_html_e( 'Location', 'zume-coaching' ); ?></label>
                            <input type="text" id="location" name="location" value="<?php echo esc_attr( $public_location); ?>" placeholder="<?php esc_attr_e( 'City, State/Province, Country', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description"><?php esc_html_e( 'Your general location (city, state/province, country).', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Focus of Ministry -->
                        <div class="form-group">
                            <label for="focus_of_ministry"><?php esc_html_e( 'Focus of Ministry', 'zume-coaching' ); ?></label>
                            <textarea id="focus_of_ministry" name="focus_of_ministry" placeholder="<?php esc_attr_e( 'Describe your specific focus areas in ministry, such as youth ministry, church planting, leadership development, etc...', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; min-height: 100px; resize: vertical;"><?php echo esc_textarea( $focus_of_ministry ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Describe your specific areas of focus in ministry and disciple making.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Greeting Video -->
                        <div class="form-group">
                            <label for="greeting_video_url"><?php esc_html_e( 'Greeting Video URL', 'zume-coaching' ); ?></label>
                            <input type="url" id="greeting_video_url" name="greeting_video_url" value="<?php echo esc_attr( $greeting_video_url ); ?>" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..." style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description"><?php esc_html_e( 'Optional: Add a YouTube or Vimeo video URL for a personal greeting.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <!-- Testimonials -->
                        <div class="form-group">
                            <label><?php esc_html_e( 'Testimonials', 'zume-coaching' ); ?></label>
                            <button type="button" class="add-testimonial" onclick="addTestimonial()" style="background: #007cba; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-bottom: 1rem;"><?php esc_html_e( 'Add Testimonial', 'zume-coaching' ); ?></button>
                            <div id="testimonials-container">
                                <?php foreach ( $testimonials as $index => $testimonial ) : ?>
                                    <div class="testimonial-item" data-index="<?php echo esc_attr( $index ); ?>" style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background: #f9f9f9;">
                                        <input type="text" name="testimonials[<?php echo esc_attr( $index ); ?>][name]" placeholder="<?php esc_attr_e( 'Trainee Name', 'zume-coaching' ); ?>" value="<?php echo esc_attr( $testimonial['name'] ?? '' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 0.5rem;">
                                        <textarea name="testimonials[<?php echo esc_attr( $index ); ?>][quote]" placeholder="<?php esc_attr_e( 'What did they say about your coaching?', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; min-height: 80px; resize: vertical; margin-bottom: 0.5rem;"><?php echo esc_textarea( $testimonial['quote'] ?? '' ); ?></textarea>
                                        <button type="button" class="remove-testimonial" onclick="removeTestimonial(this)" style="background: #dc3232; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;"><?php esc_html_e( 'Remove', 'zume-coaching' ); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="description"><?php esc_html_e( 'Add testimonials from trainees you\'ve coached.', 'zume-coaching' ); ?></p>
                        </div>
                        
                        <button type="submit" class="save-button" style="background: #00a32a; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; font-size: 1rem;"><?php esc_html_e( 'Save Profile Settings', 'zume-coaching' ); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        let testimonialIndex = <?php echo count( $testimonials ); ?>;
        
        function addTestimonial() {
            const container = document.getElementById('testimonials-container');
            const testimonialItem = document.createElement('div');
            testimonialItem.className = 'testimonial-item';
            testimonialItem.setAttribute('data-index', testimonialIndex);
            testimonialItem.style.cssText = 'border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background: #f9f9f9;';
            
            testimonialItem.innerHTML = `
                <input type="text" name="testimonials[${testimonialIndex}][name]" placeholder="<?php esc_attr_e( 'Trainee Name', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 0.5rem;">
                <textarea name="testimonials[${testimonialIndex}][quote]" placeholder="<?php esc_attr_e( 'What did they say about your coaching?', 'zume-coaching' ); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; min-height: 80px; resize: vertical; margin-bottom: 0.5rem;"></textarea>
                <button type="button" class="remove-testimonial" onclick="removeTestimonial(this)" style="background: #dc3232; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;"><?php esc_html_e( 'Remove', 'zume-coaching' ); ?></button>
            `;
            
            container.appendChild(testimonialItem);
            testimonialIndex++;
        }
        
        function removeTestimonial(button) {
            button.parentElement.remove();
        }
        
        // Update profile URL preview
        document.getElementById('public_slug').addEventListener('input', function() {
            const slug = this.value;
            const preview = document.getElementById('profile-url-preview');
            if (slug) {
                preview.textContent = 'https://zume.training/app/coaches/?name=' + slug;
            } else {
                preview.textContent = 'https://zume.training/app/coaches/?name=your-slug';
            }
        });
        
        // Form submission
        document.getElementById('coach-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading state
            const submitButton = this.querySelector('.save-button');
            const originalText = submitButton.textContent;
            submitButton.textContent = '<?php esc_html_e( 'Saving...', 'zume-coaching' ); ?>';
            submitButton.disabled = true;
            
            // Make API request with multipart/form-data
            fetch('<?php echo rest_url( 'zume_coaching/v1/coach-profile' ); ?>', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('success-message').textContent = '<?php esc_html_e( 'Profile settings saved successfully!', 'zume-coaching' ); ?>';
                    document.getElementById('success-message').style.display = 'block';
                    document.getElementById('error-message').style.display = 'none';
                    // Reload page to show new photo
                    if (result.photo_updated) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    document.getElementById('error-message').textContent = result.message || '<?php esc_html_e( 'Error saving profile settings.', 'zume-coaching' ); ?>';
                    document.getElementById('error-message').style.display = 'block';
                    document.getElementById('success-message').style.display = 'none';
                }
            })
            .catch(error => {
                document.getElementById('error-message').textContent = '<?php esc_html_e( 'Error saving profile settings.', 'zume-coaching' ); ?>';
                document.getElementById('error-message').style.display = 'block';
                document.getElementById('success-message').style.display = 'none';
            })
            .finally(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
        </script>
        
        <style>
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .form-group .description {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }
        </style>
        <?php
    }
}

// Initialize the user profile coach tile
Zume_User_Profile_Coach_Tile::instance();
