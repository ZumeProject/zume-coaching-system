<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_Coach_Profile {
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
    }

    public function get( $post_id, $post_type ) {
        ?>
        <div class="cell small-12 medium-8">
            <h3>Coach Public Profile Settings</h3>
            
            <?php
            $this_post = DT_Posts::get_post( $post_type, $post_id );
            
            // Get user ID - try trainee_user_id first, then fallback to post author
            $user_id = null;
            if ( isset( $this_post['trainee_user_id'] ) ) {
                $user_id = $this_post['trainee_user_id'];
            } elseif ( isset( $this_post['post_author'] ) ) {
                $user_id = $this_post['post_author'];
            }
            
            if ( !$user_id ) {
                echo '<p style="color: red;">No User ID Found - Cannot load coach profile</p>';
                return;
            }
            
            // Check if coach profile functions are available
            if ( !function_exists( 'zume_get_coach_public_profile' ) ) {
                echo '<p style="color: red;">Coach Profile Functions Not Loaded - Please check coach-profile-functions.php</p>';
                return;
            }
            
            // Get current coach profile settings
            $public_profile_enabled = get_user_meta( $user_id, 'coach_public_profile_enabled', true );
            $public_slug = get_user_meta( $user_id, 'coach_public_slug', true );
            $bio = get_user_meta( $user_id, 'coach_bio', true );
            $experience = get_user_meta( $user_id, 'coach_experience', true );
            $location = get_user_meta( $user_id, 'coach_location', true );
            $focus_of_ministry = get_user_meta( $user_id, 'coach_focus_of_ministry', true );
            $testimonials = get_user_meta( $user_id, 'coach_testimonials', true );
            $greeting_video_url = get_user_meta( $user_id, 'coach_greeting_video_url', true );
            
            // If no custom slug set, use user_nicename as fallback
            if ( empty( $public_slug ) ) {
                $user = get_user_by( 'ID', $user_id );
                $public_slug = $user ? $user->user_nicename : '';
            }
            
            // Parse testimonials if it's a string
            if ( is_string( $testimonials ) ) {
                $testimonials = maybe_unserialize( $testimonials );
            }
            if ( !is_array( $testimonials ) ) {
                $testimonials = [];
            }
            ?>
            
            <style>
            .coach-profile-form {
                max-width: 800px;
            }
            .coach-profile-form .form-group {
                margin-bottom: 1rem;
            }
            .coach-profile-form label {
                display: block;
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            .coach-profile-form input[type="text"],
            .coach-profile-form input[type="url"],
            .coach-profile-form textarea {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            .coach-profile-form textarea {
                min-height: 100px;
                resize: vertical;
            }
            .coach-profile-form .checkbox-group {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .coach-profile-form .testimonial-item {
                border: 1px solid #ddd;
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 4px;
                background: #f9f9f9;
            }
            .coach-profile-form .testimonial-item input,
            .coach-profile-form .testimonial-item textarea {
                margin-bottom: 0.5rem;
            }
            .coach-profile-form .testimonial-item textarea {
                min-height: 80px;
            }
            .coach-profile-form .add-testimonial {
                background: #007cba;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                cursor: pointer;
                margin-bottom: 1rem;
            }
            .coach-profile-form .remove-testimonial {
                background: #dc3232;
                color: white;
                border: none;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.8rem;
            }
            .coach-profile-form .save-button {
                background: #00a32a;
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
            }
            .coach-profile-form .save-button:hover {
                background: #008a20;
            }
            .coach-profile-form .profile-url {
                background: #f0f0f0;
                padding: 0.5rem;
                border-radius: 4px;
                font-family: monospace;
                margin-top: 0.5rem;
            }
            .coach-profile-form .success-message {
                background: #d4edda;
                color: #155724;
                padding: 1rem;
                border-radius: 4px;
                margin-bottom: 1rem;
                display: none;
            }
            .coach-profile-form .error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 1rem;
                border-radius: 4px;
                margin-bottom: 1rem;
                display: none;
            }
        </style>

            <p>Manage your public coach profile that will be displayed at <code>/app/coaches/?name={your-slug}</code></p>
            
            <div class="success-message" id="success-message"></div>
            <div class="error-message" id="error-message"></div>
            
            <form class="coach-profile-form" id="coach-profile-form">
                <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
                
                <!-- Enable Public Profile -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="public_profile_enabled" name="public_profile_enabled" value="1" <?php checked( $public_profile_enabled, '1' ); ?>>
                        <label for="public_profile_enabled">Enable Public Coach Profile</label>
                    </div>
                    <p class="description">When enabled, your profile will be publicly accessible at the URL below.</p>
                </div>
                
                <!-- Public Slug -->
                <div class="form-group">
                    <label for="public_slug">Profile URL Slug</label>
                    <input type="text" id="public_slug" name="public_slug" value="<?php echo esc_attr( $public_slug ); ?>" placeholder="your-username">
                    <div class="profile-url" id="profile-url-preview">
                        <?php echo esc_url( 'https://zume.training/app/coaches/?name=' . $public_slug ); ?>
                    </div>
                    <p class="description">This will be your public profile URL. Use only lowercase letters, numbers, and hyphens.</p>
                </div>
                
                <!-- Bio -->
                <div class="form-group">
                    <label for="bio">Biography</label>
                    <textarea id="bio" name="bio" placeholder="Tell people about yourself, your background, and your passion for disciple making..."><?php echo esc_textarea( $bio ); ?></textarea>
                    <p class="description">A brief introduction about yourself that will appear on your public profile.</p>
                </div>
                
                <!-- Experience -->
                <div class="form-group">
                    <label for="experience">Experience & Background</label>
                    <textarea id="experience" name="experience" placeholder="Share your experience in disciple making, training, coaching, and ministry..."><?php echo esc_textarea( $experience ); ?></textarea>
                    <p class="description">Describe your experience and expertise in disciple making and training.</p>
                </div>
                
                <!-- Location -->
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo esc_attr( $location ); ?>" placeholder="City, State/Province, Country">
                    <p class="description">Your general location (city, state/province, country).</p>
                </div>
                
                <!-- Focus of Ministry -->
                <div class="form-group">
                    <label for="focus_of_ministry">Focus of Ministry</label>
                    <textarea id="focus_of_ministry" name="focus_of_ministry" placeholder="Describe your specific focus areas in ministry, such as youth ministry, church planting, leadership development, etc..."><?php echo esc_textarea( $focus_of_ministry ); ?></textarea>
                    <p class="description">Describe your specific areas of focus in ministry and disciple making.</p>
                </div>
                
                <!-- Greeting Video -->
                <div class="form-group">
                    <label for="greeting_video_url">Greeting Video URL</label>
                    <input type="url" id="greeting_video_url" name="greeting_video_url" value="<?php echo esc_attr( $greeting_video_url ); ?>" placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                    <p class="description">Optional: Add a YouTube or Vimeo video URL for a personal greeting.</p>
                </div>
                
                <!-- Testimonials -->
                <div class="form-group">
                    <label>Testimonials</label>
                    <button type="button" class="add-testimonial" onclick="addTestimonial()">Add Testimonial</button>
                    <div id="testimonials-container">
                        <?php foreach ( $testimonials as $index => $testimonial ) : ?>
                            <div class="testimonial-item" data-index="<?php echo esc_attr( $index ); ?>">
                                <input type="text" name="testimonials[<?php echo esc_attr( $index ); ?>][name]" placeholder="Trainee Name" value="<?php echo esc_attr( $testimonial['name'] ?? '' ); ?>">
                                <textarea name="testimonials[<?php echo esc_attr( $index ); ?>][quote]" placeholder="What did they say about your coaching?"><?php echo esc_textarea( $testimonial['quote'] ?? '' ); ?></textarea>
                                <button type="button" class="remove-testimonial" onclick="removeTestimonial(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">Add testimonials from trainees you've coached.</p>
                </div>
                
                <button type="submit" class="save-button">Save Profile Settings</button>
            </form>
        </div>

        <script>
        let testimonialIndex = <?php echo count( $testimonials ); ?>;
        
        function addTestimonial() {
            const container = document.getElementById('testimonials-container');
            const testimonialItem = document.createElement('div');
            testimonialItem.className = 'testimonial-item';
            testimonialItem.setAttribute('data-index', testimonialIndex);
            
            testimonialItem.innerHTML = `
                <input type="text" name="testimonials[${testimonialIndex}][name]" placeholder="Trainee Name">
                <textarea name="testimonials[${testimonialIndex}][quote]" placeholder="What did they say about your coaching?"></textarea>
                <button type="button" class="remove-testimonial" onclick="removeTestimonial(this)">Remove</button>
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
            const data = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('testimonials[')) {
                    // Handle testimonials array
                    const match = key.match(/testimonials\[(\d+)\]\[(\w+)\]/);
                    if (match) {
                        const index = match[1];
                        const field = match[2];
                        if (!data.testimonials) data.testimonials = {};
                        if (!data.testimonials[index]) data.testimonials[index] = {};
                        data.testimonials[index][field] = value;
                    }
                } else {
                    data[key] = value;
                }
            }
            
            // Convert testimonials object to array
            if (data.testimonials) {
                data.testimonials = Object.values(data.testimonials).filter(t => t.name || t.quote);
            }
            
            // Show loading state
            const submitButton = this.querySelector('.save-button');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;
            
            // Make API request
            fetch('<?php echo rest_url( 'zume_coaching/v1/coach-profile' ); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('success-message').textContent = 'Profile settings saved successfully!';
                    document.getElementById('success-message').style.display = 'block';
                    document.getElementById('error-message').style.display = 'none';
                } else {
                    document.getElementById('error-message').textContent = result.message || 'Error saving profile settings.';
                    document.getElementById('error-message').style.display = 'block';
                    document.getElementById('success-message').style.display = 'none';
                }
            })
            .catch(error => {
                document.getElementById('error-message').textContent = 'Error saving profile settings.';
                document.getElementById('error-message').style.display = 'block';
                document.getElementById('success-message').style.display = 'none';
            })
            .finally(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
        </script>
        <?php
    }
}
