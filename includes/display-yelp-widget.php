<?php

class Display_Yelp_Widget extends WP_Widget {

    private $settings;
    protected static $instance = null;

    public function __construct() {

        $this->settings = get_option('display_yelp_widget_settings', array());

        parent::__construct(
                'display_yelp_widget', 'Yelp Widget', array(
            'description' => __('Display your business\'s Yelp rating and reviews on your Wordpress site.', DISPLAY_YELP_WIDGET_SLUG)
                )
        );

        // Load style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles')); 
    }

    /**
     * Get instance of class
     * 
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Initialize class
     *
     * @since 1.0.0
     */
    public function init() {
        self::get_instance();
    }

    /**
     * Load CSS files
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {   
        if ($this->settings['style'] == "yelp") {
            wp_enqueue_style(DISPLAY_YELP_WIDGET_SLUG . '-styles', DISPLAY_YELP_WIDGET_CSS_URL . 'style.css', array(), DISPLAY_YELP_WIDGET_VERSION);
        } else if ($this->settings['style'] == "custom") {
            wp_enqueue_style(DISPLAY_YELP_WIDGET_SLUG . '-styles', DISPLAY_YELP_WIDGET_CSS_URL . 'no-style.css', array(), DISPLAY_YELP_WIDGET_VERSION);
        } else {
            // no stylesheet at all
        }
    }

    /**
     * Define widget settings displayed in the WordPress admin area
     *
     * @since 1.0.0
     */
    public function form($instance) {

        $title          = !empty($instance['title']) ? esc_attr($instance['title']) : '';
        $id             = !empty($instance['id']) ? esc_attr($instance['id']) : '';
        
        ?>

        <!-- API Key check -->
        <p>
            <?php
            if (empty($this->settings['api_key'])) {
                echo "<span style='color:red;font-weight:bold;'>You are missing the API Key.</span> <a href='" . esc_url(get_admin_url(null, 'options-general.php?page=' . DISPLAY_YELP_WIDGET_SLUG)) . "'>Click here to set one up!</a>";
            }
            ?>

        </p>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', DISPLAY_YELP_WIDGET_SLUG); ?></label>
            <input class="widefat" 
                   name="<?php echo $this->get_field_name('title'); ?>" 
                   type="text" 
                   value="<?php echo $title; ?>"/>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('id'); ?>"><?php _e('Business ID:', DISPLAY_YELP_WIDGET_SLUG); ?>
                &nbsp;<span style='font-size:12px;'>(http://www.yelp.com/biz/<b>this-is-the-business-id</b>)</span>
            </label>
            
            <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>"
                   name="<?php echo $this->get_field_name('id'); ?>" 
                   type="text" 
                   value="<?php echo $id; ?>"/>
        </p>        

        <?php
    }

    /**
     * Update widget settings
     *
     * @since 1.0.0
     */
    public function update($new_instance, $old_instance) {

        $instance           = $old_instance;
        $instance['title']  = !empty($new_instance['title']) ? esc_attr($new_instance['title']) : '';
        $instance['id']     = !empty($new_instance['id']) ? esc_attr($new_instance['id']) : '';

        return $instance;
    }

    /**
     * Widget output
     *
     * @since 1.0.0
     */
    public function widget($args, $instance) {


        $api_key            = !empty($this->settings['api_key']) ? $this->settings['api_key'] : 'FxOqU2rGeCnXRaL87H-VP99RkGdN2d8qV9IvT-qt_IT132sNik14MueEB6d9M594b35eI4MGcnNHWO973vrjSx-3b8LwqwWeM53nwKyKusQeOyWGrwv8vuGtGNQXXHYx';
        $title              = !empty($instance['title']) ? esc_attr($instance['title']) : '';
        $id                 = !empty($instance['id']) ? esc_attr($instance['id']) : '';

        $response = self::display_yelp_widget_get_business($api_key, $id);


        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        if (!isset($response->error)) {           
            ?>
            <div class="yelp-widget">
                <div class="yelp-business">
                    <div class="yelp-business-logo">
                        <a href="<?php echo esc_url($response->url); ?>"  target='_blank'>
                        <img class="yelp-business-img" 
                             src="<?php echo (( !empty( $response->image_url )) ?  esc_attr( $response->image_url ) : DISPLAY_YELP_WIDGET_MS_URL . '/assets/images/blank-biz.png');?>
			"/>
                        </a>
                    </div>  
                    <div class="yelp-info-wrap">
                        <a class="yelp-business-name" target='_blank'
                           href="<?php echo esc_attr($response->url); ?>"
                           title="<?php echo esc_attr($response->name); ?> Yelp page"><?php echo $response->name; ?></a>
                        <div class="yelp-rating">
                           <?php self::display_yelp_widget_get_stars($response->rating); ?>
                        <span
                            class="review-count"><?php echo esc_attr($response->review_count) . '&nbsp;' . __('reviews', DISPLAY_YELP_WIDGET_SLUG); ?></span>	
                            </div>
                    </div>
                    
                </div>
                <?php
                if (isset($response->review_count) && isset($response->reviews)) {
                    ?>
                    <div class="yelp-business-reviews">
                        <?php                        
                        foreach ($response->reviews as $review) {
                            ?>
                            <div class="yelp-review clearfix">

                                <div class="yelp-review-avatar">

                                    <img src="<?php echo !empty($review->user->image_url) ? $review->user->image_url : DISPLAY_YELP_WIDGET_IMAGES_URL . 'user_60_square.png'; ?>"
                                         width="60" 
                                         height="60"
                                         alt="<?php echo $review->user->name; ?>'s Review"/>
                                    <span class="yelp-review-reviewer-name"><?php echo $review->user->name; ?></span>
                                </div>

                                <div class="yelp-review-excerpt">
                                    <div class="yelp-review-heading">
                                        <?php self::display_yelp_widget_get_stars($review->rating); ?>
                                        <time>
                                            <?php echo date('n/j/Y', strtotime($review->time_created)); ?>
                                        </time>
                                    </div>
                                    <div class="yelp-review-excerpt-text">
                                        <?php echo wpautop($review->text); ?>
                                    </div>

                                    <?php
                                    ?>
                                    <a href="<?php echo esc_url($review->url); ?>"
                                       class="yelp-review-read-more" target="_blank"><?php echo __('Read More &raquo;', DISPLAY_YELP_WIDGET_SLUG); ?></a>

                                </div>

                            </div>

                        <?php } ?>

                    </div>

                <?php } ?> 
                <div class="yelp-logo" style="text-align:center;">
                    <a href="<?php echo esc_url($response->url); ?>"  target='_blank'><?php self::display_yelp_widget_get_logo(); ?></a>
                </div>


            </div>

            <?php
        }

        echo $args['after_widget'];
    }

    /**
     * Retrieves business details based on Yelp business ID.
     *
     * @since 1.0.0
     */
    function display_yelp_widget_get_business($api_key, $business_id) {
        $url = 'https://api.yelp.com/v3/businesses/' . $business_id;

        $args = array(
            'user-agent' => '',
            'headers' => array(
                'authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = self::display_yelp_widget_get_data($url, $args);

        $reviews_response = self::yelp_get_reviews($api_key, $business_id);

        if (!empty($reviews_response) and isset($reviews_response->reviews[0])) {
            $response->reviews = $reviews_response->reviews;
        }

        return $response;
    }

    /**
     * Retrieves reviews based on Yelp business ID.
     *
     * @since 1.0.0
     */
    function yelp_get_reviews($api_key, $business_id) {
        $url = 'https://api.yelp.com/v3/businesses/' . $business_id . '/reviews';

        $args = array(
            'user-agent' => '',
            'headers' => array(
                'authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = self::display_yelp_widget_get_data($url, $args);

        return $response;
    }

    /**
     * Retrieves a response from a safe HTTP request using the GET method.
     *
     * @since 1.0.0
     */
    function display_yelp_widget_get_data($url, $args = array()) {
        
        $response = wp_safe_remote_get($url, $args);
        
        if (is_wp_error($response)) {
           return false; 
        } 
        
        $api_response = json_decode(wp_remote_retrieve_body($response));

        return $api_response;        

    }

    /**
     * Generates a star image based on numerical rating.
     *
     * @since 1.0.0
     */
    function display_yelp_widget_get_stars($rating = 0) {
        $ext = '.png';
        $floor_rating = floor($rating);

        if ($rating != $floor_rating) {
            $image_name = $floor_rating . '_half';
        } else {
            $image_name = $floor_rating;
        }

        $uri_image_name = DISPLAY_YELP_WIDGET_IMAGES_URL . 'yelp-stars/small_' . $image_name;
        $single = $uri_image_name . $ext;
        $double = $uri_image_name . '@2x' . $ext;
        $triple = $uri_image_name . '@3x' . $ext;
        $srcset = "{$single}, {$double} 2x, {$triple} 3x";
        $decimal_rating = number_format($rating, 1, '.', '');

        echo '<img class="rating" srcset="' . esc_attr($srcset) . '" src="' . esc_attr($single) . '" title="' . $decimal_rating . ' star rating" alt="' . $decimal_rating . ' star rating">';
    }

    /**
     * Displays responsive Yelp logo.
     *
     * @since 1.0.0
     */
    function display_yelp_widget_get_logo() {
        $image_name     = 'logo_desktop_medium';
	$ext            = '.png';
	$uri_image_name = DISPLAY_YELP_WIDGET_IMAGES_URL . $image_name;
	$single         = $uri_image_name . $ext;
	$double         = $uri_image_name . '@2x' . $ext;
	$srcset         = "{$single}, {$double} 2x";

	echo '<img class="ywp-logo" srcset="' . esc_attr( $srcset ) . '" src="' . esc_attr( $single ) . '" alt="Yelp logo">';
 
    }

}

// Register the widget
function register_display_yelp_widget() {
    register_widget('Display_Yelp_Widget');
}

add_action('widgets_init', 'register_display_yelp_widget');
