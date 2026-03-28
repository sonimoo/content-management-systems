<?php
/**
 * Plugin Name: USM Notes
 * Plugin URI: https://example.com
 * Description: Учебный плагин для управления заметками с приоритетами и датой напоминания.
 * Version: 1.0
 * Author: Sofia
 * Author URI: https://example.com
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Custom Post Type "Заметки"
 */
function usm_register_notes_cpt() {
    $labels = array(
        'name'               => 'Заметки',
        'singular_name'      => 'Заметка',
        'add_new'            => 'Добавить новую',
        'add_new_item'       => 'Добавить заметку',
        'edit_item'          => 'Редактировать заметку',
        'new_item'           => 'Новая заметка',
        'view_item'          => 'Просмотреть заметку',
        'search_items'       => 'Искать заметки',
        'not_found'          => 'Заметки не найдены',
        'not_found_in_trash' => 'В корзине заметок нет',
        'menu_name'          => 'Заметки',
    );

    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'supports'     => array('title', 'editor', 'author', 'thumbnail'),
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-edit-page',
        'show_in_rest' => true,
    );

    register_post_type('usm_note', $args);
}

add_action('init', 'usm_register_notes_cpt');

/**
 * Регистрация таксономии "Приоритет"
 */
function usm_register_priority_taxonomy() {
    $labels = array(
        'name'              => 'Приоритеты',
        'singular_name'     => 'Приоритет',
        'search_items'      => 'Искать приоритет',
        'all_items'         => 'Все приоритеты',
        'parent_item'       => 'Родительский приоритет',
        'parent_item_colon' => 'Родительский приоритет:',
        'edit_item'         => 'Редактировать приоритет',
        'update_item'       => 'Обновить приоритет',
        'add_new_item'      => 'Добавить новый приоритет',
        'new_item_name'     => 'Название нового приоритета',
        'menu_name'         => 'Приоритет',
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'public'            => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
    );

    register_taxonomy('priority', array('usm_note'), $args);
}
add_action('init', 'usm_register_priority_taxonomy');

/**
 * Добавление метабокса для даты напоминания
 */
function usm_add_due_date_metabox() {
    add_meta_box(
        'usm_due_date',
        'Дата напоминания',
        'usm_due_date_metabox_callback',
        'usm_note',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'usm_add_due_date_metabox');

/**
 * HTML метабокса
 */
function usm_due_date_metabox_callback($post) {
    wp_nonce_field('usm_save_due_date', 'usm_due_date_nonce');

    $value = get_post_meta($post->ID, '_usm_due_date', true);

    echo '<label for="usm_due_date_field">Выберите дату:</label><br><br>';
    echo '<input type="date" id="usm_due_date_field" name="usm_due_date_field" value="' . esc_attr($value) . '" required>';
}

/**
 * Сохранение даты напоминания
 */
function usm_save_due_date($post_id) {
    // Проверка nonce
    if (!isset($_POST['usm_due_date_nonce']) || !wp_verify_nonce($_POST['usm_due_date_nonce'], 'usm_save_due_date')) {
        return;
    }

    // Защита от автосохранения
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Проверка прав
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Только для заметок
    if (get_post_type($post_id) !== 'usm_note') {
        return;
    }

    $has_error = false;
    $error_message = '';

    // Поле обязательно
    if (!isset($_POST['usm_due_date_field']) || empty($_POST['usm_due_date_field'])) {
        $has_error = true;
        $error_message = 'Дата напоминания обязательна для заполнения.';
    } else {
        $due_date = sanitize_text_field($_POST['usm_due_date_field']);
        $today = current_time('Y-m-d');

        // Проверка формата даты
        $date_obj = DateTime::createFromFormat('Y-m-d', $due_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $due_date) {
            $has_error = true;
            $error_message = 'Некорректный формат даты.';
        }

        // Дата не может быть в прошлом
        if (!$has_error && $due_date < $today) {
            $has_error = true;
            $error_message = 'Дата напоминания не может быть в прошлом.';
        }
    }

    // Если ошибка — сохраняем сообщение и переводим запись в черновик
    if ($has_error) {
        set_transient('usm_due_date_error_' . $post_id, $error_message, 30);

        remove_action('save_post', 'usm_save_due_date');

        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));

        add_action('save_post', 'usm_save_due_date');

        return;
    }

    update_post_meta($post_id, '_usm_due_date', $due_date);
}
add_action('save_post', 'usm_save_due_date');

/**
 * Вывод ошибки в админке
 */
function usm_admin_error_notice() {
    global $pagenow;

    if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $error = get_transient('usm_due_date_error_' . $post_id);

        if ($error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('usm_due_date_error_' . $post_id);
        }
    }
}
add_action('admin_notices', 'usm_admin_error_notice');

/**
 * Колонка даты напоминания в списке заметок
 */
function usm_add_due_date_column($columns) {
    $columns['usm_due_date'] = 'Due Date';
    return $columns;
}
add_filter('manage_usm_note_posts_columns', 'usm_add_due_date_column');

function usm_show_due_date_column($column, $post_id) {
    if ($column === 'usm_due_date') {
        $due_date = get_post_meta($post_id, '_usm_due_date', true);
        echo $due_date ? esc_html($due_date) : '—';
    }
}
add_action('manage_usm_note_posts_custom_column', 'usm_show_due_date_column', 10, 2);

function usm_notes_shortcode($atts) {
    $atts = shortcode_atts(array(
        'priority' => '',
        'before_date' => ''
    ), $atts);

    $meta_query = array();
    $tax_query = array();

    if (!empty($atts['before_date'])) {
        $meta_query[] = array(
            'key' => '_usm_due_date',
            'value' => $atts['before_date'],
            'compare' => '<=',
            'type' => 'DATE'
        );
    }

    if (!empty($atts['priority'])) {
        $tax_query[] = array(
            'taxonomy' => 'priority',
            'field' => 'slug',
            'terms' => sanitize_title($atts['priority'])
        );
    }

    $args = array(
        'post_type' => 'usm_note',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '<div class="usm-notes">';

        while ($query->have_posts()) {
            $query->the_post();

            $date = get_post_meta(get_the_ID(), '_usm_due_date', true);

            $output .= '<div class="usm-note">';
            $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
            $output .= '<p><strong>Дата:</strong> ' . esc_html($date ? $date : 'Не указана') . '</p>';
            $output .= '<div>' . wp_kses_post(get_the_content()) . '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output = '<p>Нет заметок с заданными параметрами</p>';
    }

    return $output;
}

add_shortcode('usm_notes', 'usm_notes_shortcode');

function usm_notes_styles() {
    echo '<style>
        .usm-notes {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .usm-note {
            padding: 20px;
            border-radius: 15px;
            background: #f5f5f5;
        }
        .usm-note h3 {
            margin-bottom: 10px;
        }
    </style>';
}
add_action('wp_head', 'usm_notes_styles');