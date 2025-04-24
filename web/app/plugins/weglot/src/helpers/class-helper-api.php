<?php

namespace WeglotWP\Helpers;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 3.0.0
 */
abstract class Helper_API {

	const API_BASE         = 'https://api.weglot.com';
	const API_BASE_STAGING = 'https://api.weglot.dev';
	const ROOT_CDN_BASE         = 'https://cdn.weglot.com';
	const ROOT_CDN_BASE_STAGING         = 'https://cdn.weglot.dev';
	const API_CDN_BASE         = 'https://cdn-api-weglot.com';
	const API_CDN_BASE_STAGING = 'https://cdn-api-weglot.dev';
	const CDN_BASE         = 'https://cdn.weglot.com/projects-settings/';
	const CDN_BASE_SWITCHERS_TPL         = 'https://cdn.weglot.com/switchers/';
	const CDN_BASE_SWITCHERS_TPL_STAGING         = 'https://cdn.weglot.dev/switchers/';

	/**
	 * @since 3.0.0
	 * @return string
	 */
	public static function get_cdn_url() {
		if ( WEGLOT_DEV ) {
			return self::CDN_BASE . 'staging/';
		}

		return self::CDN_BASE;
	}

	/**
	 * @since 3.0.0
	 * @return string
	 */
	public static function get_api_url() {
		if ( WEGLOT_DEV ) {
			return self::API_BASE_STAGING;
		}

		return self::API_BASE;
	}

	/**
	 * @since 3.0.0
	 * @return string
	 */
	public static function get_tpl_switchers_url() {
		if ( WEGLOT_DEV ) {
			return self::CDN_BASE_SWITCHERS_TPL_STAGING;
		}

		return self::CDN_BASE_SWITCHERS_TPL;
	}

	/**
	 * Fetches remote content using the appropriate function for the environment.
	 *
	 * Uses `vip_safe_wp_remote_get()` if it exists (typically on WordPress VIP Go),
	 * otherwise falls back to `wp_remote_get()`.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url  The URL to fetch the content from.
	 * @param array  $args Optional. An array of request arguments. Default is an empty array.
	 *
	 * @return array|WP_Error The response or WP_Error on failure.
	 */

	public static function get_remote_content($url, $args = []) {
		if ( function_exists('vip_safe_wp_remote_get') ) {
			return vip_safe_wp_remote_get($url, $args);
		}
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		return wp_remote_get($url, $args);
	}

}


