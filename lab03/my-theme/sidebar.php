<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo is_front_page() ? 'active' : ''; ?>">
                <a href="<?php echo home_url(); ?>">Главная</a>
            </li>

            <li class="<?php echo is_page('about') ? 'active' : ''; ?>">
                <a href="<?php echo home_url('/about'); ?>">О нас</a>
            </li>

            <li class="<?php echo is_page('contacts') ? 'active' : ''; ?>">
                <a href="<?php echo home_url('/contacts'); ?>">Контакты</a>
            </li>
        </ul>
    </nav>
</aside>