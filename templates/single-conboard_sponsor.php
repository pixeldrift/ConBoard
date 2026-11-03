<?php
// templates/single-conboard_sponsor.php

/**
 * Single Sponsor
 */

get_header();

the_post();

$sponsor_id = get_the_ID();

$level   = (string) get_post_meta($sponsor_id, 'sponsor_level', true);
$website = (string) get_post_meta($sponsor_id, 'website', true);
$socials = get_field('socials', $sponsor_id);

?>

<main class="conboard conboard-sponsor">

	<header class="sponsor-header">

		<?php if (has_post_thumbnail()) : ?>
			<div class="sponsor-logo">
				<?php the_post_thumbnail('large', ['alt' => get_the_title()]); ?>
			</div>
		<?php endif; ?>

		<h1 class="sponsor-name"><?php the_title(); ?></h1>

		<?php if ($level) : ?>
			<div class="sponsor-level conboard-card-badge">
				<?php echo esc_html(conboard_get_sponsor_level_label($level)); ?>
			</div>
		<?php endif; ?>

		<?php if ($website) : ?>
			<p class="sponsor-website">
				<a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
					<?php echo esc_html(parse_url($website, PHP_URL_HOST) ?: $website); ?>
				</a>
			</p>
		<?php endif; ?>

		<?php if (is_array($socials) && !empty($socials)) : ?>
			<ul class="sponsor-socials">
				<?php foreach ($socials as $social) : ?>
					<?php
					if (empty($social['url'])) continue;
					$label = !empty($social['label']) ? $social['label'] : ucfirst((string) ($social['platform'] ?? 'Link'));
					?>
					<li>
						<a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener">
							<?php echo esc_html($label); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</header>

	<section class="sponsor-description">
		<?php the_content(); ?>
	</section>

	<p class="sponsor-back">
		<a href="<?php echo esc_url(get_post_type_archive_link('sponsor')); ?>">&larr; All sponsors</a>
	</p>

</main>

<?php get_footer(); ?>
