<?php
/**
 * Arnés mínimo de pruebas unitarias sin WordPress.
 *
 * Solo se prueban las funciones puras del plugin: las que no llaman a WordPress. El resto se
 * verifica con la lista de QA manual de docs/qa.md sobre una instalación real.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

// Constantes que WordPress define y que el plugin usa en constantes de clase.
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'ESCRITORIOWP_VERSION', '0.1.0' );

/**
 * Estado global del arnés.
 *
 * @var array{total: int, fallos: array<int, string>, grupo: string}
 */
$GLOBALS['escritoriowp_pruebas'] = array(
	'total'  => 0,
	'fallos' => array(),
	'grupo'  => '',
);

/**
 * Abre un grupo de pruebas.
 *
 * @param string $nombre Nombre del grupo.
 * @return void
 */
function grupo( string $nombre ): void {
	$GLOBALS['escritoriowp_pruebas']['grupo'] = $nombre;
	echo "\n  $nombre\n";
}

/**
 * Comprueba que dos valores son iguales.
 *
 * @param mixed  $esperado    Valor esperado.
 * @param mixed  $obtenido    Valor obtenido.
 * @param string $descripcion Qué se está comprobando.
 * @return void
 */
function comprobar( mixed $esperado, mixed $obtenido, string $descripcion ): void {
	$GLOBALS['escritoriowp_pruebas']['total']++;

	if ( $esperado === $obtenido ) {
		echo "    \u{2713} $descripcion\n";
		return;
	}

	$GLOBALS['escritoriowp_pruebas']['fallos'][] = $descripcion;

	echo "    \u{2717} $descripcion\n";
	echo '        esperado: ' . var_export( $esperado, true ) . "\n";
	echo '        obtenido: ' . var_export( $obtenido, true ) . "\n";
}

/**
 * Comprueba que una condición se cumple.
 *
 * @param bool   $condicion   Condición a comprobar.
 * @param string $descripcion Qué se está comprobando.
 * @return void
 */
function comprobar_que( bool $condicion, string $descripcion ): void {
	comprobar( true, $condicion, $descripcion );
}

/**
 * Imprime el resumen y devuelve el código de salida.
 *
 * @return int
 */
function resumen(): int {
	$estado = $GLOBALS['escritoriowp_pruebas'];
	$fallos = count( $estado['fallos'] );

	echo "\n";
	echo str_repeat( '-', 60 ) . "\n";

	if ( 0 === $fallos ) {
		echo "  {$estado['total']} comprobaciones, todas correctas\n";
		return 0;
	}

	echo "  {$estado['total']} comprobaciones, $fallos fallos:\n";

	foreach ( $estado['fallos'] as $fallo ) {
		echo "    - $fallo\n";
	}

	return 1;
}
