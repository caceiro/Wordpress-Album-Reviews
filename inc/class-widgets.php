<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Album_Review_Widgets
 *
 * Registers and initializes the review widgets.
 */
class Album_Review_Widgets {
    /**
     * Constructor.
     *
     * Hooks the widget registration into WordPress.
     */
    public function __construct() {
        add_action('widgets_init', [$this, 'register_widgets']);
    }

    /**
     * Registers the Latest Reviews and Top Rated Reviews widgets.
     */
    public function register_widgets() {
        register_widget('Latest_Reviews_Widget');
        register_widget('Top_Rated_Reviews_Widget');
    }
}

/**
 * Class Latest_Reviews_Widget
 *
 * Widget to display the latest reviews.
 */
class Latest_Reviews_Widget extends WP_Widget {
    /**
     * Cache duration in seconds (6 hours).
     *
     * @var int
     */
    private $cache_duration = 6 * HOUR_IN_SECONDS;

    /**
     * Constructor.
     *
     * Initializes the widget with a unique identifier, name, and description.
     */
    public function __construct() {
        parent::__construct(
            'latest_reviews_widget',
            __('Latest Reviews', 'album-review-box'),
            ['description' => __('Displays the latest reviews with thumbnails and ratings.', 'album-review-box')]
        );
    }

    /**
     * Outputs the widget content on the front-end.
     *
     * @param array $args      Widget arguments.
     * @param array $instance  Widget instance settings.
     */
    public function widget($args, $instance) {
        // Retrieve widget settings with defaults.
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Reviews', 'album-review-box');
        $post_type = !empty($instance['post_type']) ? sanitize_text_field($instance['post_type']) : 'album-review';
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 5;
        $rating_field = !empty($instance['rating_field']) ? sanitize_text_field($instance['rating_field']) : 'rating';

        // Begin widget output.
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Generate a unique cache key based on widget settings.
        $cache_key = 'latest_reviews_' . md5($post_type . '_' . $number_of_posts . '_' . $rating_field);
        $reviews = get_transient($cache_key);

        if (false === $reviews) {
            // Define query arguments to fetch latest reviews.
            $query_args = [
                'post_type' => $post_type,
                'posts_per_page' => $number_of_posts,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => $rating_field,
                        'compare' => 'EXISTS',
                        'type' => 'NUMERIC'
                    ]
                ]
            ];

            // Execute the query.
            $query = new WP_Query($query_args);
            ob_start(); // Start output buffering.

            if ($query->have_posts()) {
                echo '<ul class="album-widget-list" role="list">';
                while ($query->have_posts()) {
                    $query->the_post();
                    $rating = get_field($rating_field) ?: 0;
                    $rating_class = $this->get_rating_class($rating);
                    $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), [80,80]) ?: 'https://via.placeholder.com/80';
                    ?>
                    <li role="listitem">
                        <a href="<?php echo esc_url(get_permalink()); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                            <div class="widget-thumb-container">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="80" height="80" loading="lazy">
                                <?php if ($rating): ?>
                                    <div class="rating-badge <?php echo esc_attr($rating_class); ?>">
                                        <?php echo esc_html(number_format($rating, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="review-title-link">
                            <?php echo esc_html(get_the_title()); ?>
                        </a>
                    </li>
                    <?php
                }
                echo '</ul>';
            } else {
                // Display a generic message when no reviews are found.
                echo '<p>' . esc_html__('No Review Found.', 'album-review-box') . '</p>';
            }
            wp_reset_postdata(); // Reset post data.

            $reviews = ob_get_clean(); // Get the buffered content.
            set_transient($cache_key, $reviews, $this->cache_duration); // Cache the output.
        }

        // Output the cached or freshly generated reviews.
        echo $reviews;

        echo $args['after_widget']; // End widget output.
    }

    /**
     * Determines the CSS class based on the rating value.
     *
     * @param float $rating The rating value.
     * @return string        The corresponding CSS class.
     */
    private function get_rating_class($rating) {
        if ($rating >= 8) return 'high';
        elseif ($rating >= 5) return 'medium';
        else return 'low';
    }

    /**
     * Outputs the widget settings form in the admin dashboard.
     *
     * @param array $instance The current widget settings.
     */
    public function form($instance) {
        // Retrieve existing values or set defaults.
        $title = !empty($instance['title']) ? $instance['title'] : __('Latest Reviews', 'album-review-box');
        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'album-review';
        $number_of_posts = !empty($instance['number_of_posts']) ? $instance['number_of_posts'] : 5;
        $rating_field = !empty($instance['rating_field']) ? $instance['rating_field'] : 'rating';

        // Retrieve all public post types for the dropdown.
        $post_types = get_post_types(['public' => true], 'objects');

        // Generate a unique nonce field for security.
        $nonce_action = 'update_latest_reviews_widget_' . $this->id;
        $nonce_field = 'latest_reviews_widget_nonce';
        wp_nonce_field($nonce_action, $nonce_field);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'album-review-box'); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                type="text" 
                value="<?php echo esc_attr($title); ?>" 
                aria-label="<?php esc_attr_e('Widget Title', 'album-review-box'); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('post_type')); ?>">
                <?php esc_html_e('Post Type:', 'album-review-box'); ?>
            </label>
            <select 
                class="widefat" 
                id="<?php echo esc_attr($this->get_field_id('post_type')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('post_type')); ?>"
                aria-label="<?php esc_attr_e('Select Post Type', 'album-review-box'); ?>">
                <?php
                foreach ($post_types as $pt) {
                    echo '<option value="' . esc_attr($pt->name) . '" ' . selected($post_type, $pt->name, false) . '>' . esc_html($pt->label) . '</option>';
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>">
                <?php esc_html_e('Number of Posts to Show:', 'album-review-box'); ?>
            </label>
            <input 
                class="tiny-text" 
                id="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('number_of_posts')); ?>" 
                type="number" 
                step="1" 
                min="1" 
                value="<?php echo esc_attr($number_of_posts); ?>" 
                size="3"
                aria-label="<?php esc_attr_e('Number of Posts to Display', 'album-review-box'); ?>">
        </p>
        <?php
    }

    /**
     * Processes widget options to be saved.
     *
     * @param array $new_instance New settings for the widget.
     * @param array $old_instance Previous settings for the widget.
     * @return array              Updated settings to save.
     */
    public function update($new_instance, $old_instance) {
        // Verify the nonce for security.
        $nonce_field = 'latest_reviews_widget_nonce';
        $nonce_action = 'update_latest_reviews_widget_' . $this->id;
        if (
            !isset($_POST[$nonce_field]) ||
            !wp_verify_nonce($_POST[$nonce_field], $nonce_action)
        ) {
            // If nonce verification fails, return the old instance without changes.
            return $old_instance;
        }

        // Sanitize and save the widget settings.
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['post_type'] = (!empty($new_instance['post_type'])) ? sanitize_text_field($new_instance['post_type']) : 'album-review';
        $instance['number_of_posts'] = (!empty($new_instance['number_of_posts'])) ? absint($new_instance['number_of_posts']) : 5;
        $instance['rating_field'] = (!empty($new_instance['rating_field'])) ? sanitize_text_field($new_instance['rating_field']) : 'rating';

        // Delete the transient cache to ensure fresh data is fetched.
        $cache_key = 'latest_reviews_' . md5($instance['post_type'] . '_' . $instance['number_of_posts'] . '_' . $instance['rating_field']);
        delete_transient($cache_key);

        return $instance;
    }
}

/**
 * Class Top_Rated_Reviews_Widget
 *
 * Widget to display the top rated reviews of the current year.
 */
class Top_Rated_Reviews_Widget extends WP_Widget {
    /**
     * Cache duration in seconds (6 hours).
     *
     * @var int
     */
    private $cache_duration = 6 * HOUR_IN_SECONDS;

    /**
     * Constructor.
     *
     * Initializes the widget with a unique identifier, name, and description.
     */
    public function __construct() {
        parent::__construct(
            'top_rated_reviews_widget',
            __('Top Rated Reviews', 'album-review-box'),
            ['description' => __('Displays the top rated reviews (this year) sorted by rating, with thumbnails and rating badges.', 'album-review-box')]
        );
    }

    /**
     * Outputs the widget content on the front-end.
     *
     * @param array $args      Widget arguments.
     * @param array $instance  Widget instance settings.
     */
    public function widget($args, $instance) {
        // Retrieve widget settings with defaults.
        $title = !empty($instance['title']) ? $instance['title'] : __('Top Rated Reviews', 'album-review-box');
        $post_type = !empty($instance['post_type']) ? sanitize_text_field($instance['post_type']) : 'album-review';
        $number_of_posts = !empty($instance['number_of_posts']) ? absint($instance['number_of_posts']) : 10;
        $rating_field = !empty($instance['rating_field']) ? sanitize_text_field($instance['rating_field']) : 'rating';

        // Begin widget output.
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Generate a unique cache key based on widget settings.
        $cache_key = 'top_rated_reviews_' . md5($post_type . '_' . $number_of_posts . '_' . $rating_field);
        $reviews = get_transient($cache_key);

        if (false === $reviews) {
            // Define query arguments to fetch top rated reviews of the current year.
            $query_args = [
                'post_type' => $post_type,
                'posts_per_page' => $number_of_posts,
                'meta_key' => $rating_field,
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'date_query' => [
                    [
                        'year' => date('Y')
                    ]
                ],
                'meta_query' => [
                    [
                        'key' => $rating_field,
                        'compare' => 'EXISTS',
                        'type' => 'NUMERIC'
                    ]
                ]
            ];

            // Execute the query.
            $query = new WP_Query($query_args);
            ob_start(); // Start output buffering.

            echo '<div class="top-reviews-widget" role="region" aria-label="' . esc_attr__('Top Rated Reviews', 'album-review-box') . '">';
            if ($query->have_posts()) {
                $count = 1;
                while ($query->have_posts()) {
                    $query->the_post();
                    $rating = get_field($rating_field) ?: 0;
                    $rating_class = $this->get_rating_class($rating);
                    $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), [80,80]) ?: 'https://via.placeholder.com/80';
                    ?>
                    <div class="review-item">
                        <span class="review-number" aria-hidden="true"><?php echo esc_html($count . '.'); ?></span>
                        <a href="<?php echo esc_url(get_permalink()); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                            <div class="review-thumb-container">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="80" height="80" loading="lazy">
                                <?php if ($rating): ?>
                                    <div class="rating-badge <?php echo esc_attr($rating_class); ?>">
                                        <?php echo esc_html(number_format($rating, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="review-info">
                            <div class="review-title">
                                <a href="<?php echo esc_url(get_permalink()); ?>" class="review-title-link">
                                    <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                    $count++;
                }
            } else {
                // Display a generic message when no reviews are found.
                echo '<p>' . esc_html__('No Review Found.', 'album-review-box') . '</p>';
            }
            echo '</div>';
            wp_reset_postdata(); // Reset post data.

            $reviews = ob_get_clean(); // Get the buffered content.
            set_transient($cache_key, $reviews, $this->cache_duration); // Cache the output.
        }

        // Output the cached or freshly generated reviews.
        echo $reviews;

        echo $args['after_widget']; // End widget output.
    }

    /**
     * Determines the CSS class based on the rating value.
     *
     * @param float $rating The rating value.
     * @return string        The corresponding CSS class.
     */
    private function get_rating_class($rating) {
        if ($rating >= 8) return 'high';
        elseif ($rating >= 5) return 'medium';
        else return 'low';
    }

    /**
     * Outputs the widget settings form in the admin dashboard.
     *
     * @param array $instance The current widget settings.
     */
    public function form($instance) {
        // Retrieve existing values or set defaults.
        $title = !empty($instance['title']) ? $instance['title'] : __('Top Rated Reviews', 'album-review-box');
        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'album-review';
        $number_of_posts = !empty($instance['number_of_posts']) ? $instance['number_of_posts'] : 10;
        $rating_field = !empty($instance['rating_field']) ? $instance['rating_field'] : 'rating';

        // Retrieve all public post types for the dropdown.
        $post_types = get_post_types(['public' => true], 'objects');

        // Generate a unique nonce field for security.
        $nonce_action = 'update_top_rated_reviews_widget_' . $this->id;
        $nonce_field = 'top_rated_reviews_widget_nonce';
        wp_nonce_field($nonce_action, $nonce_field);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'album-review-box'); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                type="text" 
                value="<?php echo esc_attr($title); ?>" 
                aria-label="<?php esc_attr_e('Widget Title', 'album-review-box'); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('post_type')); ?>">
                <?php esc_html_e('Post Type:', 'album-review-box'); ?>
            </label>
            <select 
                class="widefat" 
                id="<?php echo esc_attr($this->get_field_id('post_type')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('post_type')); ?>"
                aria-label="<?php esc_attr_e('Select Post Type', 'album-review-box'); ?>">
                <?php
                foreach ($post_types as $pt) {
                    echo '<option value="' . esc_attr($pt->name) . '" ' . selected($post_type, $pt->name, false) . '>' . esc_html($pt->label) . '</option>';
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>">
                <?php esc_html_e('Number of Posts to Show:', 'album-review-box'); ?>
            </label>
            <input 
                class="tiny-text" 
                id="<?php echo esc_attr($this->get_field_id('number_of_posts')); ?>" 
                name="<?php echo esc_attr($this->get_field_name('number_of_posts')); ?>" 
                type="number" 
                step="1" 
                min="1" 
                value="<?php echo esc_attr($number_of_posts); ?>" 
                size="3"
                aria-label="<?php esc_attr_e('Number of Posts to Display', 'album-review-box'); ?>">
        </p>
        <?php
    }

    /**
     * Processes widget options to be saved.
     *
     * @param array $new_instance New settings for the widget.
     * @param array $old_instance Previous settings for the widget.
     * @return array              Updated settings to save.
     */
    public function update($new_instance, $old_instance) {
        // Verify the nonce for security.
        $nonce_field = 'top_rated_reviews_widget_nonce';
        $nonce_action = 'update_top_rated_reviews_widget_' . $this->id;
        if (
            !isset($_POST[$nonce_field]) ||
            !wp_verify_nonce($_POST[$nonce_field], $nonce_action)
        ) {
            // If nonce verification fails, return the old instance without changes.
            return $old_instance;
        }

        // Sanitize and save the widget settings.
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['post_type'] = (!empty($new_instance['post_type'])) ? sanitize_text_field($new_instance['post_type']) : 'album-review';
        $instance['number_of_posts'] = (!empty($new_instance['number_of_posts'])) ? absint($new_instance['number_of_posts']) : 10;
        $instance['rating_field'] = (!empty($new_instance['rating_field'])) ? sanitize_text_field($new_instance['rating_field']) : 'rating';

        // Delete the transient cache to ensure fresh data is fetched.
        $cache_key = 'top_rated_reviews_' . md5($instance['post_type'] . '_' . $instance['number_of_posts'] . '_' . $instance['rating_field']);
        delete_transient($cache_key);

        return $instance;
    }
}
