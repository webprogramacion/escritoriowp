<?php
/**
 * Pruebas de la resolución de URLs del menú de administración.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Lanzador\CatalogoComandos;

grupo( 'Catálogo de comandos · URLs' );

comprobar( 'edit.php', CatalogoComandos::resolver_ruta( 'edit.php' ), 'pantalla del núcleo' );
comprobar( 'edit.php?post_type=page', CatalogoComandos::resolver_ruta( 'edit.php?post_type=page' ), 'pantalla del núcleo con parámetros' );
comprobar( 'options-general.php', CatalogoComandos::resolver_ruta( 'options-general.php', 'options-general.php' ), 'submenú que es una pantalla del núcleo' );

comprobar(
	'admin.php?page=wc-settings',
	CatalogoComandos::resolver_ruta( 'wc-settings', 'woocommerce' ),
	'página de plugin bajo un menú propio'
);

comprobar(
	'options-general.php?page=escritoriowp',
	CatalogoComandos::resolver_ruta( 'escritoriowp', 'options-general.php', 'options-general.php' ),
	'página de plugin bajo Ajustes'
);

comprobar(
	'edit.php?post_type=product&page=product_attributes',
	CatalogoComandos::resolver_ruta( 'product_attributes', 'edit.php?post_type=product', 'edit.php?post_type=product' ),
	'página de plugin bajo un padre con parámetros'
);

comprobar(
	'admin.php?page=wc-admin&path=/customers',
	CatalogoComandos::resolver_ruta( 'wc-admin&path=/customers', 'woocommerce' ),
	'slug que ya incluye parámetros se concatena sin codificar'
);

comprobar( 'https://ejemplo.com/panel', CatalogoComandos::resolver_ruta( 'https://ejemplo.com/panel' ), 'URL absoluta' );
comprobar( '', CatalogoComandos::resolver_ruta( '  ' ), 'slug vacío' );

comprobar( true, CatalogoComandos::es_absoluta( 'https://ejemplo.com' ), 'https es absoluta' );
comprobar( true, CatalogoComandos::es_absoluta( '//ejemplo.com' ), 'sin esquema es absoluta' );
comprobar( false, CatalogoComandos::es_absoluta( 'edit.php' ), 'un fichero no es absoluto' );

grupo( 'Catálogo de comandos · iconos' );

comprobar( 'dashicons-admin-post', CatalogoComandos::icono( 'dashicons-admin-post' ), 'un Dashicon se conserva' );
comprobar( 'dashicons-admin-generic', CatalogoComandos::icono( 'data:image/svg+xml;base64,abc' ), 'un icono propio cae en el genérico' );
comprobar( 'dashicons-admin-generic', CatalogoComandos::icono( '' ), 'sin icono se usa el genérico' );
