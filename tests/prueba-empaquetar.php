<?php
/**
 * Pruebas del empaquetado del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

grupo( 'Empaquetado · exclusiones' );

comprobar_que( debe_excluirse( '.DS_Store' ), 'se excluye .DS_Store de la raíz' );
comprobar_que( debe_excluirse( 'assets/css/.DS_Store' ), 'se excluye .DS_Store de cualquier subdirectorio' );
comprobar_que( debe_excluirse( 'Thumbs.db' ), 'se excluye Thumbs.db' );
comprobar_que( debe_excluirse( 'assets/js/comun.js.map' ), 'se excluyen los mapas de fuentes' );
comprobar( false, debe_excluirse( 'escritoriowp.php' ), 'el fichero principal se incluye' );
comprobar( false, debe_excluirse( 'assets/js/comun.js' ), 'el JavaScript se incluye' );
comprobar( false, debe_excluirse( 'languages/escritoriowp.pot' ), 'la plantilla de traducción se incluye' );

grupo( 'Empaquetado · versión de la cabecera' );

comprobar( '1.2.3', version_de_cabecera( " * Plugin Name: X\n * Version:           1.2.3\n" ), 'lee la versión con espacios de relleno' );
comprobar( null, version_de_cabecera( " * Plugin Name: X\n" ), 'sin cabecera de versión devuelve null' );

grupo( 'Empaquetado · variante para WordPress.org' );

$principal = <<<'PHP'
<?php
/**
 * Plugin Name:       EscritorioWP
 * Version:           0.2.0
 * Update URI:        https://github.com/webprogramacion/escritoriowp
 * Text Domain:       escritoriowp
 */

const VERSION     = '0.2.0';
const REPOSITORIO = 'https://github.com/webprogramacion/escritoriowp';

define( 'ESCRITORIOWP_REPOSITORIO', REPOSITORIO );
PHP;

$preparado = preparar_para_wordpress_org( $principal );

comprobar_que( ! str_contains( $preparado, 'Update URI' ), 'la cabecera Update URI desaparece' );
comprobar_que( str_contains( $preparado, "const REPOSITORIO = '';" ), 'la constante del repositorio queda vacía' );
comprobar_que( str_contains( $preparado, "const VERSION     = '0.2.0';" ), 'el resto de constantes no se toca' );
comprobar_que( str_contains( $preparado, ' * Text Domain:       escritoriowp' ), 'el resto de la cabecera se conserva' );
comprobar_que( str_contains( $preparado, "define( 'ESCRITORIOWP_REPOSITORIO', REPOSITORIO );" ), 'la definición de la constante global se conserva' );
comprobar_que( sin_actualizador( $preparado ), 'el resultado pasa la comprobación de variante sin actualizador' );
comprobar( false, sin_actualizador( $principal ), 'el fichero original no pasa esa comprobación' );

comprobar_que(
	str_starts_with( DIRECTORIO_ACTUALIZACIONES, 'includes/' ),
	'el módulo de actualizaciones vive bajo includes/'
);
