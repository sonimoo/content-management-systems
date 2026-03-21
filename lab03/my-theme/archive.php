<?php get_header(); ?>

<div class="container">

    <?php get_sidebar(); ?>
    
    <main class="content">
        <h2><?php the_archive_title(); ?></h2>
        <p><?php the_archive_description(); ?></p>

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
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
        <?php endwhile; else : ?>
            <p>Архивных записей не найдено.</p>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>