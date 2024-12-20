<?php
/*
Plugin Name: Album Review Box
Description: Adds a custom album details box before the content for Album Reviews, Spotify embeds, rating badges, and includes two widgets for latest and top 10 reviews. Now also includes a settings page to configure rating texts and fields.
Version: 4.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define plugin paths if needed
define('ALBUM_REVIEW_BOX_PATH', plugin_dir_path(__FILE__));
define('ALBUM_REVIEW_BOX_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once ALBUM_REVIEW_BOX_PATH . 'inc/class-single-review-box.php';
require_once ALBUM_REVIEW_BOX_PATH . 'inc/class-archives.php';
require_once ALBUM_REVIEW_BOX_PATH . 'inc/class-widgets.php';
require_once ALBUM_REVIEW_BOX_PATH . 'inc/class-settings.php'; // Include the settings class

class AlbumReviewBox {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);

        // Instantiate classes that handle each functionality
        new Single_Review_Box();
        new Archives_Review_Box();
        new Album_Review_Widgets();
        new Album_Review_Box_Settings();
    }

    // Enqueue the main CSS file
    public function enqueue_styles() {
        wp_enqueue_style('album-review-box-styles', ALBUM_REVIEW_BOX_URL . 'assets/css/style.css', [], '4.1');
    }
}

// Initialize the plugin
new AlbumReviewBox();
