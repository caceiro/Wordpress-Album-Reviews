<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Single_Review_Box {

    public function __construct() {
        add_filter('the_content', [$this, 'add_album_details_box']);
    }

    public function add_album_details_box($content) {
        if ('album-review' === get_post_type() && is_singular('album-review')) {
            // Get settings
            $options = get_option('album_review_box_options', []);
            
            $post_type        = isset($options['post_type']) ? $options['post_type'] : 'album-review';
            $artist_tax       = isset($options['artist_tax']) ? $options['artist_tax'] : 'artist';
            $label_tax        = isset($options['label_tax']) ? $options['label_tax'] : 'labels';
            $genre_tax        = isset($options['genre_tax']) ? $options['genre_tax'] : 'genre';
            $rating_field     = isset($options['rating_field']) ? $options['rating_field'] : 'rating';
            $spotify_field    = isset($options['spotify_field']) ? $options['spotify_field'] : 'spotify_embed';
            $release_date_field = isset($options['release_date_field']) ? $options['release_date_field'] : 'release_date';

            $thumbnail = get_the_post_thumbnail_url(null, 'medium') ?: 'https://via.placeholder.com/150';
            $artist = $this->get_taxonomy_terms($artist_tax);
            $release_date = get_field($release_date_field) ?: 'N/A';
            $label = $this->get_taxonomy_terms($label_tax);
            $genre = $this->get_taxonomy_terms($genre_tax);
            $rating = get_field($rating_field) ?: 'N/A';

            $rating_class = $this->get_rating_class($rating);
            $rating_info = $this->get_rating_info($rating);

            // Generate JSON-LD Schema Markup
            $schema_markup = $this->generate_schema_markup($artist, $release_date, $label, $genre, $rating, $thumbnail);

            // Album Details Box
            $album_box = "
            <div class='album-review-box'>
                <h3>" . esc_html(get_the_title()) . "</h3>
                <div class='album-details-content'>
                    <img src='" . esc_url($thumbnail) . "' alt='Album Cover'>
                    <div class='album-info'>
                        <p><strong>Artist:</strong> " . esc_html($artist) . "</p>
                        <p><strong>Release Date:</strong> " . esc_html($release_date) . "</p>
                        <p><strong>Label:</strong> " . esc_html($label) . "</p>
                        <p><strong>Genre:</strong> " . esc_html($genre) . "</p>
                    </div>
                    <div class='album-score " . esc_attr($rating_class) . "'>
                        <span>" . esc_html(number_format($rating, 1)) . "</span>
                        <div class='album-score-info'>" . esc_html($rating_info) . "</div>
                    </div>
                </div>
            </div>";

            $spotify_embed = get_field($spotify_field) ?: '';
            if (!empty($spotify_embed)) {
                $content .= "<div class='spotify-embed'>{$spotify_embed}</div>";
            }

            return $album_box . $content . $schema_markup;
        }
        return $content;
    }

    private function generate_schema_markup($artist, $release_date, $label, $genre, $rating, $thumbnail) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Review",
            "datePublished" => get_the_date('c'),
            "publisher" => [
                "@type" => "Organization",
                "name" => get_bloginfo('name'),
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => (get_theme_mod('custom_logo')) ? wp_get_attachment_url(get_theme_mod('custom_logo')) : ''
                ]
            ],
            "itemReviewed" => [
                "@type" => "MusicAlbum",
                "name" => get_the_title(),
                "byArtist" => ["@type" => "MusicGroup", "name" => $artist],
                "releaseDate" => $release_date,
                "genre" => explode(', ', $genre),
                "recordLabel" => ["@type" => "Organization", "name" => $label],
                "image" => $thumbnail
            ],
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => $rating,
                "bestRating" => "10",
                "worstRating" => "0"
            ],
            "author" => [
                "@type" => "Person",
                "name" => get_the_author_meta('display_name')
            ],
            "reviewBody" => strip_tags(get_the_content())
        ];

        return '<script type="application/ld+json">' . json_encode($schema) . '</script>';
    }

    private function get_taxonomy_terms($taxonomy) {
        $terms = wp_get_post_terms(get_the_ID(), $taxonomy, ['fields' => 'names']);
        return !empty($terms) ? esc_html(implode(', ', $terms)) : 'N/A';
    }

    private function get_rating_class($rating) {
        if ($rating >= 8) return 'high';
        elseif ($rating >= 5) return 'medium';
        else return 'low';
    }

    private function get_rating_info($rating) {
        $options = get_option('album_review_box_options', []);
        $outstanding = isset($options['outstanding_text']) ? $options['outstanding_text'] : 'Outstanding!';
        $great       = isset($options['great_text']) ? $options['great_text'] : 'Great';
        $good        = isset($options['good_text']) ? $options['good_text'] : 'Good';
        $average     = isset($options['average_text']) ? $options['average_text'] : 'Average';
        $poor        = isset($options['poor_text']) ? $options['poor_text'] : 'Poor';

        if ($rating >= 8.5) return $outstanding;
        elseif ($rating >= 7.0) return $great;
        elseif ($rating >= 5.0) return $good;
        elseif ($rating >= 3.0) return $average;
        else return $poor;
    }
}
