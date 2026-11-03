<?php
// templates/single-conboard_presenter.php

/**
 * Single Presenter
 */

get_header();

the_post();

$presenter_id = get_the_ID();

// Meta
$pronouns = get_post_meta($presenter_id, 'pronouns', true);
$title    = get_post_meta($presenter_id, 'presenter_title', true);
$org      = get_post_meta($presenter_id, 'presenter_organization', true);
$email    = get_post_meta($presenter_id, 'presenter_email', true);

// Social and web links
$links_html = conboard_render_post_links($presenter_id, 'presenter-websites');

// Sessions this presenter is part of
$sessions = conboard_get_sessions_for_presenter($presenter_id);

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

			<?php if ($pronouns) : ?>
				<div class="presenter-pronouns"><?php echo esc_html($pronouns); ?></div>
			<?php endif; ?>

			<?php if ($title || $org) : ?>
				<div class="presenter-title-org">
					<?php if ($title) : ?>
						<span class="presenter-title"><?php echo esc_html($title); ?></span>
					<?php endif; ?>

					<?php if ($title && $org) : ?>
						<span class="separator">, </span>
					<?php endif; ?>

					<?php if ($org) : ?>
						<span class="presenter-org"><?php echo esc_html($org); ?></span>
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

			<?php echo $links_html; // Escaped in conboard_render_post_links() ?>

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
					$role  = conboard_get_presenter_role_in_session($session->ID, $presenter_id);
					?>

					<li class="presenter-session">

						<?php if ($start) : ?>
							<time class="session-time">
								<?php echo esc_html(date_i18n('D g:i A', strtotime($start))); ?>
							</time>
						<?php endif; ?>

						<a href="<?php echo esc_url(get_permalink($session->ID)); ?>">
							<?php echo esc_html(get_the_title($session->ID)); ?>
						</a>

						<span class="presenter-role">
							(<?php echo esc_html(conboard_get_presenter_role_label($role)); ?>)
						</span>

					</li>

				<?php endforeach; ?>

			</ul>

		</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
