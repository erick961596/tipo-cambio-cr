<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('WPTCR_VENTA_CR');
delete_option('WPTCR_COMPRA_CR');
