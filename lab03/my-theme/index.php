<?php get_header(); ?>

<div class="container">
    <?php get_sidebar(); ?>
    
    <main class="content">
        <h2>Последние записи</h2>

        <?php
        $args = array(
            'posts_per_page' => 5
        );

        $latest_posts = new WP_Query($args);

        if ($latest_posts->have_posts()) :
            while ($latest_posts->have_posts()) : $latest_posts->the_post();
        ?>
                <article class="post">
                    <h3>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>

                    <p><small>Дата: <?php echo get_the_date(); ?></small></p>

                    <div>
                        <?php the_excerpt(); ?>
                    </div>
                </article>
                <hr>
        <?php
            endwhile;
            wp_reset_postdata();
        else :
            echo '<p>Записей пока нет.</p>';
        endif;
        ?>
    </main>
</div>

<?php get_footer(); ?>