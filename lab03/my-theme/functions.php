<?php

function my_theme_enqueue_styles() {
    wp_enqueue_style(
        'my-theme-style',
        get_stylesheet_uri()
    );
}

add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');