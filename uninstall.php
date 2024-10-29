<?php

namespace AppScenic;

// Exit if called directly
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Cleanup
delete_option( 'appscenic_version' );
delete_option( 'appscenic_webhook_secret' );
delete_option( 'appscenic_option' );
