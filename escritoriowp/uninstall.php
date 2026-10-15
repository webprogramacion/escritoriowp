<?php
/**
 * Desinstalación de EscritorioWP.
 *
 * Borra todo lo que el plugin guarda: la opción de ajustes, la última revisión de administradores, las
 * preferencias de cada usuario, los transitorios propios, la tarea de WP-Cron y, en cada sitio, la
 * tabla de avisos guardados con la versión de su esquema y la marca de avisos leídos de cada usuario. Desactivar el plugin solo quita la tarea; el
 * resto lo borra únicamente el borrado desde Plugins.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'escritoriowp_ajustes' );
delete_site_option( 'escritoriowp_ajustes' );

// La última situación revisada del aviso de administradores y su tarea de WP-Cron, por si quedara.
delete_option( 'escritoriowp_revision_administradores' );
wp_clear_scheduled_hook( 'escritoriowp_revisar_administradores' );

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

/*
 * La tabla de avisos guardados y la versión de su esquema, de cada sitio: en multisitio cada sitio
 * tiene las suyas y las creó con su primer aviso. Los nombres se forman con el prefijo de cada sitio,
 * sin cambiar de sitio y sin buscar tablas por patrón: en una base de datos compartida por dos
 * instalaciones, un patrón borraría también la tabla de la otra.
 */
$escritoriowp_sitios = is_multisite()
	? get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	)
	: array( get_current_blog_id() );

foreach ( $escritoriowp_sitios as $escritoriowp_sitio ) {
	$escritoriowp_prefijo = $wpdb->get_blog_prefix( (int) $escritoriowp_sitio );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- La tabla es del plugin y WordPress no ofrece una API para borrar tablas.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $escritoriowp_prefijo . 'escritoriowp_avisos' ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- La opción de otro sitio sin `switch_to_blog()`, que cargaría cada sitio solo para borrar una fila.
	$wpdb->delete( $escritoriowp_prefijo . 'options', array( 'option_name' => 'escritoriowp_avisos_bd' ) );

	// La marca de avisos leídos de cada usuario en este sitio, una opción de usuario con su prefijo.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $escritoriowp_prefijo . 'escritoriowp_avisos_vistos' ) );
}

wp_cache_flush();
