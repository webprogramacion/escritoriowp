<?php
/**
 * Pruebas de la posición del menú del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Menu;

grupo( 'Menú · posición' );

// Orden con el que WordPress entrega la barra lateral: el menú del plugin, sin posición, al final.
$nativo = array(
	'index.php',
	'separator1',
	'edit.php',
	'upload.php',
	'edit.php?post_type=page',
	'edit-comments.php',
	'separator2',
	'themes.php',
	'plugins.php',
	'users.php',
	'tools.php',
	'options-general.php',
	'separator-last',
	'escritoriowp',
);

/**
 * Lista sin el menú del plugin, para comprobar que el resto conserva su orden.
 *
 * @param array<int, mixed> $orden Identificadores.
 * @return array<int, mixed>
 */
function sin_propio( array $orden ): array {
	return array_values( array_diff( $orden, array( 'escritoriowp' ) ) );
}

$defecto = Menu::recolocar( $nativo, 'escritoriowp', 'index.php' );

comprobar( array( 'index.php', 'escritoriowp', 'separator1' ), array_slice( $defecto, 0, 3 ), 'por defecto queda justo tras el Escritorio y antes del primer separador' );
comprobar( sin_propio( $nativo ), sin_propio( $defecto ), 'el resto de menús conserva su orden relativo' );
comprobar( range( 0, count( $nativo ) - 1 ), array_keys( $defecto ), 'el resultado tiene índices consecutivos desde 0' );

$paginas = Menu::recolocar( $nativo, 'escritoriowp', 'edit.php?post_type=page' );

comprobar( array( 'edit.php?post_type=page', 'escritoriowp', 'edit-comments.php' ), array_slice( $paginas, 4, 3 ), 'debajo de Páginas' );
comprobar( sin_propio( $nativo ), sin_propio( $paginas ), 'debajo de Páginas, el resto conserva su orden' );

$final = Menu::recolocar( $defecto, 'escritoriowp', '' );

comprobar( 'escritoriowp', end( $final ), 'con la referencia vacía queda el último' );
comprobar( sin_propio( $nativo ), sin_propio( $final ), 'al final, el resto conserva su orden' );

comprobar( $defecto, Menu::recolocar( $nativo, 'escritoriowp', 'woocommerce' ), 'con una referencia ausente queda tras el Escritorio' );

$sin_escritorio = array_values( array_diff( $nativo, array( 'index.php' ) ) );

comprobar( $sin_escritorio, Menu::recolocar( $sin_escritorio, 'escritoriowp', 'woocommerce' ), 'sin referencia ni Escritorio, la lista no cambia' );

$sin_propio = sin_propio( $nativo );

comprobar( $sin_propio, Menu::recolocar( $sin_propio, 'escritoriowp', 'index.php' ), 'si el usuario no ve el menú del plugin, la lista no cambia' );
comprobar( $defecto, Menu::recolocar( $defecto, 'escritoriowp', 'index.php' ), 'si ya está en su sitio, la lista no cambia' );
comprobar(
	array( 'index.php', 'escritoriowp', 'separator1' ),
	array_slice( Menu::recolocar( array( 5 => 'index.php', 9 => 'escritoriowp', 12 => 'separator1' ), 'escritoriowp', 'index.php' ), 0, 3 ),
	'una lista con índices sueltos se reindexa'
);

grupo( 'Menú · color' );

comprobar( '#ffffff', Menu::color_texto( '#7a1f5c' ), 'sobre un fondo oscuro el texto es blanco' );
comprobar( '#ffffff', Menu::color_texto( '#000000' ), 'sobre negro el texto es blanco' );
comprobar( '#ffffff', Menu::color_texto( '#767676' ), '#767676 todavía llega a 4,5:1 con blanco' );
comprobar( '#000000', Menu::color_texto( '#ffd60a' ), 'sobre un fondo claro el texto es negro' );
comprobar( '#000000', Menu::color_texto( '#ffffff' ), 'sobre blanco el texto es negro' );
comprobar( '#000000', Menu::color_texto( '#777777' ), '#777777 ya no llega a 4,5:1 con blanco' );

comprobar( '', Menu::css( '' ), 'sin color no hay reglas' );
comprobar( '', Menu::css( 'rojo' ), 'con un color inválido no hay reglas' );

$css = Menu::css( '#7A1F5C' );

comprobar_que( str_contains( $css, '#toplevel_page_escritoriowp' ), 'las reglas apuntan solo a la entrada del plugin' );
comprobar_que( str_contains( $css, 'background-color:#7a1f5c' ), 'el fondo es el color elegido, saneado' );
comprobar_que( str_contains( $css, 'color:#ffffff' ), 'el texto usa el color calculado' );
comprobar_que( str_contains( $css, '.wp-menu-image::before' ) && str_contains( $css, '.wp-menu-name' ), 'el icono y el nombre usan el mismo color' );
comprobar_que( str_contains( $css, ':not(:hover)' ) && str_contains( $css, ':not(:focus)' ), 'con ratón o foco se aplican las reglas de WordPress' );
comprobar_que( str_contains( $css, '.wp-has-current-submenu > a.menu-top:not(:focus)' ), 'abierta conserva el color salvo con el foco' );
comprobar_que( ! str_contains( $css, '<' ), 'las reglas no contienen marcado' );
