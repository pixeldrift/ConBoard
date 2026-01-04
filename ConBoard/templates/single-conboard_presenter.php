<?php
// templates/single-conboard_presenter.php

/**
 * Single Presenter
 */

get_header();

the_post();

$presenter_id = get_the_ID();

// Meta
$title = get_post_meta($presenter_id, 'presenter_title', true);
$org   = get_post_meta($presenter_id, 'presenter_org', true);
$email = get_post_meta($presenter_id, 'presenter_email', true);


// Socials
$socials = [];
$rows = get_field('presenter_socials', $presenter_id);

if ($rows) {
    foreach ($rows as $row) {
        if (!empty($row['network']) && !empty($row['url'])) {
            $socials[$row['network']] = $row['url'];
        }
    }
}

// Websites
$websites = [];
$rows = get_field('presenter_websites', $presenter_id);

if ($rows) {
    foreach ($rows as $row) {
        if (!empty($row['url'])) {
            $websites[] = [
                'url'   => $row['url'],
                'label' => $row['label'] ?? ''
            ];
        }
    }
}


// Sessions
$sessions = conboard_get_sessions_for_speaker($presenter_id);

?>

<main class="conboard conboard-presenter">

    <header class="presenter-header">

        <?php if (has_post_thumbnail()) : ?>
            <div class="presenter-photo">
                <?php the_post_thumbnail('large'); ?>
            </div>
        <?php endif; ?>

        <div class="presenter-meta">

            <h1 class="presenter-name"><?php the_title(); ?></h1>

            <?php if ($title || $org) : ?>
                <div class="presenter-title-org">
                    <?php if ($title) : ?>
                        <span class="presenter-title">
                            <?php echo esc_html($title); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($title && $org) : ?>
                        <span class="separator">, </span>
                    <?php endif; ?>

                    <?php if ($org) : ?>
                        <span class="presenter-org">
                            <?php echo esc_html($org); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <?php if ($email) : ?>
                <div class="presenter-email">
                    <a href="mailto:<?php echo esc_attr($email); ?>">
                        <?php echo esc_html($email); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php 
                if (!empty($socials)) {
                    echo '<ul class="presenter-socials">';
                    foreach ($socials as $network => $url) {
                        printf(
                            '<li class="social-%1$s"><a href="%2$s" target="_blank" rel="noopener">%1$s</a></li>',
                            esc_html($network),
                            esc_url($url)
                        );
                    }
                    echo '</ul>';
                }

                if (!empty($websites)) {
                    echo '<ul class="presenter-websites">';
                    foreach ($websites as $site) {
                        $label = $site['label'] ?: parse_url($site['url'], PHP_URL_HOST);

                        printf(
                            '<li><a href="%s" target="_blank" rel="noopener">%s</a></li>',
                            esc_url($site['url']),
                            esc_html($label)
                        );
                    }
                    echo '</ul>';
                }
            ?>
    

        </div>

    </header>

    <section class="presenter-bio">
        <?php the_content(); ?>
    </section>

    <?php if (!empty($sessions)) : ?>
        <section class="presenter-sessions">

            <h2>Sessions</h2>

            <ul class="presenter-session-list">

                <?php foreach ($sessions as $session) : ?>

                    <?php
                    $start = get_post_meta($session->ID, 'start_time', true);
                    ?>

                    <li class="presenter-session">

                        <time class="session-time">
                            <?php echo esc_html(date('D g:i A', strtotime($start))); ?>
                        </time>

                        <a href="<?php echo get_permalink($session->ID); ?>">
                            <?php echo esc_html(get_the_title($session->ID)); ?>
                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        </section>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
