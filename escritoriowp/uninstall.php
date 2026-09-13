<?php
/**
 * Desinstalación de EscritorioWP.
 *
 * Borra todo lo que el plugin guarda: la opción de ajustes, las preferencias de cada usuario y los
 * transitorios propios. Desactivar el plugin no borra nada; solo el borrado desde Plugins lo hace.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'escritoriowp_ajustes' );
delete_site_option( 'escritoriowp_ajustes' );

/*
 * Al desinstalar se borran los datos de todos los usuarios y todos los transitorios del plugin.
 * WordPress no ofrece una API para borrar un metadato de todos los usuarios ni transitorios por
 * prefijo, así que aquí las consultas directas son la única vía. No necesitan caché: la limpian.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'escritoriowp_preferencias' ) );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_escritoriowp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_escritoriowp_' ) . '%'
	)
);

wp_cache_flush();
