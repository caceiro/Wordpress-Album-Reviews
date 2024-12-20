<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Archives_Review_Box {

    public function __construct() {
        add_filter('post_thumbnail_html', [$this, 'add_rating_to_thumbnail'], 10, 5);
    }

    public function add_rating_to_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if ('album-review' === get_post_type($post_id) && (is_archive() || is_category())) {
            $options = get_option('album_review_box_options', []);
            $rating_field = isset($options['rating_field']) ? $options['rating_field'] : 'rating';

            $rating = get_field($rating_field, $post_id);
            $rating_class = $this->get_rating_class($rating);
            if ($rating) {
                $html = "<div class='post-thumbnail-container'>{$html}<div class='rating-badge {$rating_class}'>" . esc_html(number_format($rating, 1)) . "</div></div>";
            }
        }
        return $html;
    }

    private function get_rating_class($rating) {
        if ($rating >= 8) return 'high';
        elseif ($rating >= 5) return 'medium';
        else return 'low';
    }

}
