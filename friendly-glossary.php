<?php
/**
 * Plugin Name: Friendly Glossary
 * Description: A very simple and friendly glossary for your website
 * Version: 1.0
 * Author: Giulia Capozzi
 */

// Avoid direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
include_once plugin_dir_path(__FILE__) . 'includes/db-operations.php';
include_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';

// Activate the plugin
register_activation_hook(__FILE__, 'fg_create_database');

// Create table in database
function fg_create_database() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'friendly_glossary';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(300) NOT NULL,
        text text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

//create shortcode
add_shortcode('friendly_glossary', 'fg_display_glossary');

function fg_display_glossary($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friendly_glossary';

    // Get url parameters to eventually show the letter or the number
    $selected_letter = isset($_GET['letter']) ? sanitize_text_field($_GET['letter']) : '';
    $selected_term_id = isset($_GET['term']) ? intval($_GET['term']) : 0;

    // Backlink to initial view
    $back_link = remove_query_arg(['letter', 'term'], get_permalink());

    ob_start();

    // show backlink only if has a selected term in parameters 
    if ($selected_term_id) {
        echo '<a href="' . esc_url($back_link) . '" class="back-link">← Back to terms list</a>';
    }

    // Show term description and backlink
    if ($selected_term_id) {
        $term = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $selected_term_id));

        if ($term) {
            echo '<h2 class="term-description-title">' . esc_html($term->title) . '</h2>';
            echo '<p>' . wp_kses_post($term->text) . '</p>';
            return ob_get_clean(); // No mostramos nada más
        } else {
            return '<p>The selected term doesn\'t exist.</p>';
        }
    }

    // Search input
    echo '<div class="glossary-search">';
    echo '<input type="text" id="term-search" placeholder="Look for a term here..." oninput="searchTerms(this.value)">';
    echo '<div id="search-results" class="search-results"></div>';
    echo '</div>';

    // Show pagination letters
    $letters = $wpdb->get_col("SELECT DISTINCT UPPER(LEFT(title, 1)) AS letter FROM $table_name ORDER BY letter ASC");

    echo '<div class="glossary-pagination">';
    foreach ($letters as $letter) {
        $url = add_query_arg('letter', $letter, get_permalink());
        $class = ($selected_letter === $letter) ? 'glossary-letter selected' : 'glossary-letter'; // Set .selected class
        echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($letter) . '</a> ';
    }
    echo '</div>';

    // Show filtered terms for letter, if we have a selected letter
    if (!empty($selected_letter)) {
        $terms = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM $table_name WHERE title LIKE %s ORDER BY title ASC", $selected_letter . '%'
        ));

        if ($terms) {
            echo '<div class="selected-letter-bar">';
            echo '<h2 class="selected-letter-title">Showing: ' . esc_html($selected_letter) . '</h2>';
            echo '<a href="' . esc_url($back_link) . '" class="back-link-button">Show all</a>';
            echo '</div>';
            echo '<ul class="glossary-list">';
            foreach ($terms as $term) {
                $url = add_query_arg('term', $term->id, get_permalink());
                echo '<li><a href="' . esc_url($url) . '">' . esc_html($term->title) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No terms found for letter' . esc_html($selected_letter) . '.</p>';
        }
    } else {
        // Si no hay letra seleccionada, mostrar todos los términos agrupados por letra
        echo '<div class="glossary-terms-grouped">';
        foreach ($letters as $letter) {
            $terms = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title FROM $table_name WHERE title LIKE %s ORDER BY title ASC", $letter . '%'
            ));

            if ($terms) {
                echo '<h2>' . esc_html($letter) . '</h2>';
                echo '<ul class="glossary-list">';
                foreach ($terms as $term) {
                    $url = add_query_arg('term', $term->id, get_permalink());
                    echo '<li><a href="' . esc_url($url) . '">' . esc_html($term->title) . '</a></li>';
                }
                echo '</ul>';
            }
        }
        echo '</div>';
    }

    return ob_get_clean();
}

// Manejar la búsqueda AJAX
add_action('wp_ajax_fg_search_terms', 'fg_search_terms_callback');

function fg_search_terms_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friendly_glossary';

    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

    if ($query) {
        $terms = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM $table_name WHERE title LIKE %s ORDER BY title ASC",
            '%' . $wpdb->esc_like($query) . '%'
        ));

        if ($terms) {
            foreach ($terms as $term) {
                $url = add_query_arg('term', $term->id, wp_get_referer()); // Cambiado aquí
                echo '<div class="search-term"><a href="' . esc_url($url) . '">' . esc_html($term->title) . '</a></div>';
            }
        } else {
            echo '<div>Not terms found.</div>';
        }
    }
    wp_die(); // Detener la ejecución de la función
}




// Encolar los estilos en el frontend para el shortcode
function fg_enqueue_public_styles() {
    if (!is_admin()) {
        // Encolar el mismo archivo de estilos que se usa en admin
        wp_enqueue_style('fg-glossary-public-styles', plugins_url('admin/style.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'fg_enqueue_public_styles');


function fg_admin_styles() {
    wp_enqueue_style('fg-admin-style', plugin_dir_url(__FILE__) . 'admin/style.css');
}
add_action('admin_enqueue_scripts', 'fg_admin_styles');

function fg_enqueue_scripts() {
    // Solo en la página donde se muestra el glosario
    if (is_page() && has_shortcode(get_post()->post_content, 'friendly_glossary')) {
        wp_enqueue_script('fg-ajax-search', plugin_dir_url(__FILE__) . 'js/fg-ajax-search.js', array('jquery'), null, true);

        // Localizar ajaxurl para que esté disponible en el script
        wp_localize_script('fg-ajax-search', 'ajaxurl', admin_url('admin-ajax.php'));
    }
}
add_action('wp_enqueue_scripts', 'fg_enqueue_scripts');


?>
