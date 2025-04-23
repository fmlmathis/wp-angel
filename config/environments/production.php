<?php
/**
 * Configuration overrides for WP_ENV === 'production'
 */

use Roots\WPConfig\Config;

// Disable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', true);