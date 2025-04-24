<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="error settings-error notice is-dismissible">
	<p>
		<?php
		/* translators:
		 * %1$s: Plugin name.
		 * %2$s: Minimum PHP version (displayed with <code> tags).
		 * %3$s: Recommended PHP version (displayed with <code> tags).
		 * %4$s: A link to the official PHP website.
		 */
		echo wp_kses_post(
			sprintf(
				__( '%1$s has detected that your server is running a PHP version below %2$s. Starting with our next release (5.0.0), we will no longer support PHP versions under %2$s. For optimal security and performance, please upgrade to PHP %3$s or higher. If you need assistance with upgrading, please contact your hosting provider or visit the %4$s.', 'weglot' ),
				'<strong>Weglot Translate</strong>',
				'<code>7.4</code>',
				'<code>8.1</code>',
				'<a href="https://www.php.net/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'official PHP website', 'weglot' ) . '</a>'
			)
		);
		?>
	</p>
</div>
