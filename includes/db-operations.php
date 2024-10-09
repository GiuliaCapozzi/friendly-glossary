<?php
// Insert new term function
function fg_insert_term($title, $text) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'friendly_glossary';

    $wpdb->insert(
        $table_name,
        [
            'title' => sanitize_text_field($title),
            'text' => sanitize_textarea_field($text)
        ]
    );
}

// Get all terms function
function fg_get_terms() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'friendly_glossary';
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
}

//Delete term function
function fg_delete_term($id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'friendly_glossary';
    $wpdb->delete($table_name, ['id' => $id]);
}

//Update term function
function fg_update_term($id, $title, $text) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'friendly_glossary';
    $wpdb->update(
        $table_name,
        [
            'title' => sanitize_text_field($title),
            'text' => sanitize_textarea_field($text)
        ],
        ['id' => $id]
    );
}
?>
