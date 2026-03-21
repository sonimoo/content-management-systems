<?php get_header(); ?>

<div class="container">
    
    <?php get_sidebar(); ?>
    
    <main class="content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article class="post">
                <h2><?php the_title(); ?></h2>

                <div>
                    <?php the_content(); ?>
                </div>
            </article>

            <?php comments_template(); ?>

        <?php endwhile; endif; ?>
    </main>

</div>

<?php get_footer(); ?>