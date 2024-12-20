<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Album_Review_Box_Settings
 *
 * Handles the settings page for the Album Review Box plugin.
 */
class Album_Review_Box_Settings {

    /**
     * Option name for storing plugin settings.
     *
     * @var string
     */
    private $option_name = 'album_review_box_options';

    /**
     * Constructor.
     *
     * Hooks into WordPress actions to add settings page and register settings.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Adds the settings page under the "Settings" menu in WordPress admin.
     */
    public function add_settings_page() {
        add_options_page(
            __('Review Box Settings', 'album-review-box'),
            __('Review Box', 'album-review-box'),
            'manage_options',
            'album-review-box-settings',
            [$this, 'settings_page_html']
        );
    }

    /**
     * Registers plugin settings, sections, and fields.
     */
    public function register_settings() {
        // Register the main setting.
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_callback']);

        /**
         * Rating Criteria Section
         */
        add_settings_section(
            'album_review_box_rating_section',
            __('Rating Criteria Text', 'album-review-box'),
            [$this, 'rating_section_cb'],
            $this->option_name
        );

        // Add text fields for rating criteria.
        $this->add_text_field('outstanding_text', __('Outstanding Text', 'album-review-box'), 'Outstanding!');
        $this->add_text_field('great_text', __('Great Text', 'album-review-box'), 'Great');
        $this->add_text_field('good_text', __('Good Text', 'album-review-box'), 'Good');
        $this->add_text_field('average_text', __('Average Text', 'album-review-box'), 'Average');
        $this->add_text_field('poor_text', __('Poor Text', 'album-review-box'), 'Poor');

        /**
         * Post Type and Fields Section
         */
        add_settings_section(
            'album_review_box_fields_section',
            __('Post Type and Fields', 'album-review-box'),
            [$this, 'fields_section_cb'],
            $this->option_name
        );

        // Add dropdown fields for post types and taxonomies.
        $this->add_select_field(
            'post_type',
            __('Post Type', 'album-review-box'),
            $this->get_post_types(),
            'album_review_box_fields_section'
        );

        $this->add_select_field(
            'artist_tax',
            __('Artist Taxonomy', 'album-review-box'),
            $this->get_taxonomies(),
            'album_review_box_fields_section'
        );

        $this->add_select_field(
            'label_tax',
            __('Label Taxonomy', 'album-review-box'),
            $this->get_taxonomies(),
            'album_review_box_fields_section'
        );

        $this->add_select_field(
            'genre_tax',
            __('Genre Taxonomy', 'album-review-box'),
            $this->get_taxonomies(),
            'album_review_box_fields_section'
        );

        // Add dropdown fields for custom fields.
        $this->add_select_field(
            'rating_field',
            __('Rating Field', 'album-review-box'),
            $this->get_meta_keys(),
            'album_review_box_fields_section'
        );

        $this->add_select_field(
            'spotify_field',
            __('Spotify Embed Field', 'album-review-box'),
            $this->get_meta_keys(),
            'album_review_box_fields_section'
        );

        $this->add_select_field(
            'release_date_field',
            __('Release Date Field', 'album-review-box'),
            $this->get_meta_keys(),
            'album_review_box_fields_section'
        );
    }

    /**
     * Callback for the rating criteria section.
     */
    public function rating_section_cb() {
        echo '<p>' . __('Set the text for each rating level.', 'album-review-box') . '</p>';
    }

    /**
     * Callback for the post type and fields section.
     */
    public function fields_section_cb() {
        echo '<p>' . __('Define the post type, taxonomies, and custom fields used to build the review box.', 'album-review-box') . '</p>';
    }

    /**
     * Adds a text field to the settings page.
     *
     * @param string $field_id The ID of the field.
     * @param string $label    The label for the field.
     * @param string $default  The default value.
     * @param string $section  The section ID where the field belongs.
     */
    private function add_text_field($field_id, $label, $default, $section = 'album_review_box_rating_section') {
        add_settings_field(
            $field_id,
            $label,
            [$this, 'text_field_cb'],
            $this->option_name,
            $section,
            ['label_for' => $field_id, 'default' => $default]
        );
    }

    /**
     * Adds a select (dropdown) field to the settings page.
     *
     * @param string $field_id The ID of the field.
     * @param string $label    The label for the field.
     * @param array  $choices  The choices for the dropdown.
     * @param string $section  The section ID where the field belongs.
     */
    private function add_select_field($field_id, $label, $choices, $section = 'album_review_box_fields_section') {
        add_settings_field(
            $field_id,
            $label,
            function ($args) use ($choices) {
                $this->select_field_cb($args, $choices);
            },
            $this->option_name,
            $section,
            ['label_for' => $field_id]
        );
    }

    /**
     * Callback function to render a text input field.
     *
     * @param array $args The arguments for the field.
     */
    public function text_field_cb($args) {
        $options = get_option($this->option_name, []);
        $field_id = $args['label_for'];
        $default = isset($args['default']) ? $args['default'] : '';
        $value = isset($options[$field_id]) ? $options[$field_id] : $default;
        ?>
        <input type="text" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($this->option_name . '[' . $field_id . ']'); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text" aria-label="<?php echo esc_attr($args['label_for']); ?>">
        <?php
    }

    /**
     * Callback function to render a select (dropdown) field.
     *
     * @param array $args    The arguments for the field.
     * @param array $choices The choices for the dropdown.
     */
    public function select_field_cb($args, $choices) {
        $options = get_option($this->option_name, []);
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        ?>
        <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($this->option_name . '[' . $field_id . ']'); ?>" class="widefat" aria-label="<?php echo esc_attr($args['label_for']); ?>">
            <option value=""><?php esc_html_e('Select an option', 'album-review-box'); ?></option>
            <?php
            foreach ($choices as $key => $label) {
                echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
        <?php
    }

    /**
     * Retrieves all public post types for the dropdown.
     *
     * @return array Associative array of post type slugs and labels.
     */
    private function get_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $choices = [];
        foreach ($post_types as $pt) {
            $choices[$pt->name] = $pt->label;
        }
        return $choices;
    }

    /**
     * Retrieves all public taxonomies for the dropdown.
     *
     * @return array Associative array of taxonomy slugs and labels.
     */
    private function get_taxonomies() {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $choices = [];
        foreach ($taxonomies as $tax) {
            $choices[$tax->name] = $tax->label;
        }
        return $choices;
    }

    /**
     * Retrieves unique meta keys used across all posts for the dropdown.
     *
     * @return array Associative array of meta keys and their labels.
     */
    private function get_meta_keys() {
        global $wpdb;
        $meta_keys = $wpdb->get_col("
            SELECT DISTINCT meta_key
            FROM {$wpdb->postmeta}
            WHERE meta_key != ''
        ");

        // Remove unwanted meta keys (e.g., those starting with '_').
        $meta_keys = array_filter($meta_keys, function($key) {
            return strpos($key, '_') !== 0;
        });

        // Create associative array for dropdown.
        $choices = [];
        foreach ($meta_keys as $key) {
            $choices[$key] = $key;
        }
        return $choices;
    }

    /**
     * Sanitizes and validates the plugin settings input.
     *
     * @param array $input The input array from the settings form.
     * @return array       The sanitized and validated settings array.
     */
    public function sanitize_callback($input) {
        // Define default values.
        $defaults = [
            'outstanding_text' => 'Outstanding!',
            'great_text' => 'Great',
            'good_text' => 'Good',
            'average_text' => 'Average',
            'poor_text' => 'Poor',
            'post_type' => 'album-review',
            'artist_tax' => 'artist',
            'label_tax' => 'labels',
            'genre_tax' => 'genre',
            'rating_field' => 'rating',
            'spotify_field' => 'spotify_embed',
            'release_date_field' => 'release_date'
        ];

        // Merge input with defaults.
        $output = wp_parse_args($input, $defaults);

        // Sanitize text fields.
        $output['outstanding_text'] = sanitize_text_field($output['outstanding_text']);
        $output['great_text'] = sanitize_text_field($output['great_text']);
        $output['good_text'] = sanitize_text_field($output['good_text']);
        $output['average_text'] = sanitize_text_field($output['average_text']);
        $output['poor_text'] = sanitize_text_field($output['poor_text']);

        // Sanitize select fields by ensuring the selected value exists in choices.
        $valid_post_types = array_keys($this->get_post_types());
        if (!in_array($output['post_type'], $valid_post_types)) {
            $output['post_type'] = $defaults['post_type'];
        }

        $valid_taxonomies = array_keys($this->get_taxonomies());
        if (!in_array($output['artist_tax'], $valid_taxonomies)) {
            $output['artist_tax'] = $defaults['artist_tax'];
        }
        if (!in_array($output['label_tax'], $valid_taxonomies)) {
            $output['label_tax'] = $defaults['label_tax'];
        }
        if (!in_array($output['genre_tax'], $valid_taxonomies)) {
            $output['genre_tax'] = $defaults['genre_tax'];
        }

        $valid_meta_keys = array_keys($this->get_meta_keys());
        if (!in_array($output['rating_field'], $valid_meta_keys)) {
            $output['rating_field'] = $defaults['rating_field'];
        }
        if (!in_array($output['spotify_field'], $valid_meta_keys)) {
            $output['spotify_field'] = $defaults['spotify_field'];
        }
        if (!in_array($output['release_date_field'], $valid_meta_keys)) {
            $output['release_date_field'] = $defaults['release_date_field'];
        }

        return $output;
    }

    /**
     * Renders the settings page HTML.
     */
    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Review Box Settings', 'album-review-box'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
