/**
 * EscritorioWP · pantalla de ajustes.
 *
 * Convierte el campo del color de fondo del menú en el selector de color de WordPress, que trae sus
 * propios textos traducidos. Sin JavaScript sigue siendo un campo de texto que acepta el valor
 * hexadecimal, y el servidor lo sanea igual.
 */
(function (global) {
	'use strict';

	var $ = global.jQuery;

	if ( ! $ || ! $.fn || ! $.fn.wpColorPicker ) {
		return;
	}

	$( function () {
		$( '.escritoriowp-color' ).wpColorPicker();
	} );
})( typeof window !== 'undefined' ? window : globalThis );
