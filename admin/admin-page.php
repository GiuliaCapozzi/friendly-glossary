<?php
// Set admin menu
add_action('admin_menu', 'fg_add_admin_menu');

function fg_add_admin_menu() {
    add_menu_page(
        'Friendly Glossary',
        'Friendly Glossary',
        'manage_options',
        'friendly_glossary',
        'fg_admin_page',
        'dashicons-book',
        20
    );
}

function fg_admin_page() {
    // Check if user has permissions to add terms
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'friendly_glossary';

    $edit_mode = false;
    $current_term = null;

    // Check if we are in edit mode
    if (isset($_GET['edit'])) {
        $edit_mode = true;
        $term_id = intval($_GET['edit']);
        $current_term = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $term_id));
    }

    // Processing editing form
    if (isset($_POST['fg_submit'])) {
        $title = sanitize_text_field($_POST['fg_title']);
        $text = sanitize_textarea_field($_POST['fg_text']);

        // If in edit mode, we update the term
        if ($edit_mode && isset($_POST['fg_term_id'])) {
            $term_id = intval($_POST['fg_term_id']);
            fg_update_term($term_id, $title, $text);
            echo '<div class="updated"><p>Term updated.</p></div>';
        } else {
            // If not in edit mode, we insert a new term
            fg_insert_term($title, $text);
            echo '<div class="updated"><p>Term inserted.</p></div>';
        }

        // After editing, we get back to initial status
        $edit_mode = false;
        $current_term = null;
    }

    // Delete term by ID
    if (isset($_GET['delete'])) {
        fg_delete_term($_GET['delete']);
        echo '<div class="updated"><p>Term deleted.</p></div>';
    }

    // Get existing terms
    $terms = fg_get_terms();

    // Show form and current terms list
    ?>
    <div class="wrap">
        <h1>Glossary admin</h1>

        <form method="post" class="c-admin-form">
            <div class="c-admin-form__group">
                <input type="hidden" name="fg_term_id" value="<?php echo $edit_mode ? esc_attr($current_term->id) : ''; ?>">
            </div>
            <div class="c-admin-form__group">
                <input type="text" name="fg_title" placeholder="Term title" value="<?php echo $edit_mode ? esc_attr($current_term->title) : ''; ?>" required>
            </div>
            <div class="c-admin-form__group">
                <textarea name="fg_text" placeholder="Term text" required><?php echo $edit_mode ? esc_textarea($current_term->text) : ''; ?></textarea>
            </div>
            <input type="submit" name="fg_submit" value="<?php echo $edit_mode ? 'Update Term' : 'Add Term'; ?>" class="button button-primary">
        </form>

        <h2>Existing Terms</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Text</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><?php echo $term->id; ?></td>
                        <td><?php echo esc_html($term->title); ?></td>
                        <td><?php echo wp_trim_words($term->text, 10); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=friendly_glossary&edit=' . $term->id); ?>">Edit</a>
                            <a href="<?php echo admin_url('admin.php?page=friendly_glossary&delete=' . $term->id); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este término?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
