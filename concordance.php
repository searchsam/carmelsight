<?php
/*
Plugin Name: Carmelsight
Description: Keyword search engine for books on Carmelite saints
Version: 1.0
Author: Samuel Gutiérrez
*/

// Requires plugin files
require_once plugin_dir_path(__FILE__) . 'includes/ms-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/install.php';

register_activation_hook(__FILE__, 'ms_craft_db');

// Load JavaScript
add_action('wp_enqueue_scripts', 'ms_load_js');

function ms_load_js()
{
    wp_enqueue_script('ms-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', [], null, true);
    wp_localize_script('ms-script', 'ms_ajax', ['url' => admin_url('admin-ajax.php')]);
}

// Shortcode
add_shortcode('concordance', 'ms_render_form');

function ms_render_form()
{
    ob_start(); ?>
    <form id="ms-search-form">
        <div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-28f84493 wp-block-columns-is-layout-flex">
            <div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
                <div class="wp-block-group is-vertical is-layout-flex wp-container-core-group-is-layout-fe9cc265 wp-block-group-is-layout-flex">
                    <label for="ms-keyword">Palabra o frase:
                        <input type="text" name="keyword" id="ms-keyword" required>
                    </label>
                </div>
            </div>

            <div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
                <div class="wp-block-group is-vertical is-layout-flex wp-container-core-group-is-layout-fe9cc265 wp-block-group-is-layout-flex">
                    <label for="ms-author">Autor:
                        <select name="author" id="ms-author" required>
                            <option value="">Seleccione un autor</option>
                            <?php
                            $authors = ms_get_authors();
                            foreach ($authors as $author) {
                                echo '<option value="' . esc_attr($author->id) . '">' . esc_html($author->name) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>
            </div>

            <div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
                <div class="wp-block-group is-vertical is-layout-flex wp-container-core-group-is-layout-fe9cc265 wp-block-group-is-layout-flex">
                    <label for="ms-book">Libro:
                        <select name="book" id="ms-book" required>
                            <option value="">Seleccione un libro</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
            <div class="wp-block-button btn-secondary">
                <button class="wp-element-button" type="submit">Buscar</button>
            </div>
        </div>
    </form>
    <div id="ms-results"></div>
<?php
    return ob_get_clean();
}

// AJAX get books
add_action('wp_ajax_ms_get_books', 'ms_get_books');
add_action('wp_ajax_nopriv_ms_get_books', 'ms_get_books');

function ms_get_books()
{
    $author_id = intval($_GET['author_id']);
    $books = ms_get_books_by_author($author_id);

    echo json_encode($books);
    wp_die();
}

// AJAX search keyword
add_action('wp_ajax_ms_search_keyword', 'ms_search_keyword');
add_action('wp_ajax_nopriv_ms_search_keyword', 'ms_search_keyword');

function ms_search_keyword()
{
    $keyword = sanitize_text_field($_POST['keyword']);
    $author_id = intval($_POST['author']);
    $book_id = intval($_POST['book']);

    $quotes = ms_search_on_books($author_id, $keyword, $book_id);

    if (empty($quotes)) {
        echo '<div style="height: 100px" aria-hidden="true" class="wp-block-spacer"></div>';
        echo '<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">';
        echo '<p>No se encontraron Concordancias.</p>';
        echo '</div>';
        wp_die();
    }

    echo '<div style="height: 100px" aria-hidden="true" class="wp-block-spacer"></div>';
    echo '<div id="ms-table-container">';
    echo '<table id="ms-table-results" border="1" cellpadding="5">';
    echo '<thead><tr><th>Autor</th><th>Libro</th><th>Cita</th><th>Extracto</th><th>Ocurrencias</th></tr></thead>';
    echo '<tbody>';

    foreach ($quotes as $quote) {
        $content = strip_tags($quote->quote);
        $count = substr_count(strtolower($quote->quote), strtolower($keyword));

        if ($count > 0) {
            $pos = stripos($content, $keyword);
            $extract_raw = substr($content, max(0, $pos - 50), 100);
            $extract = preg_replace(
                '/' . preg_quote($keyword, '/') . '/i',
                '<span class="ms-highlight">$0</span>',
                $extract_raw
            );

            echo '<tr>';
            echo '<td>' . $quote->author . '</td>';
            echo '<td>' . $quote->book . '</td>';
            echo '<td>' . "$quote->abbreviation c$quote->chapter n$quote->numeral" . '</td>';
            echo '<td>...' . $extract . '...</td>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '<div id="ms-pagination"></div>';
    echo '</div>';
    wp_die();
}

add_action('wp_head', function () {
    echo '<style>
    .ms-highlight { background: yellow; font-weight: bold; }
    #ms-pagination button { margin: 2px; padding: 4px 8px; }
    #ms-pagination button.active { font-weight: bold; background: #eee; }
    </style>';
});
