/**
 * Pruebas unitarias del JavaScript de EscritorioWP.
 *
 * Uso: node tests/ejecutar.js
 *
 * Solo se prueban las funciones puras: las que no tocan el DOM ni la red.
 */
'use strict';

var total = 0;
var fallos = [];

/**
 * Abre un grupo de pruebas.
 *
 * @param {string} nombre Nombre del grupo.
 * @return {void}
 */
function grupo( nombre ) {
	console.log( '\n  ' + nombre );
}

/**
 * Comprueba que dos valores son iguales.
 *
 * @param {*}      esperado    Valor esperado.
 * @param {*}      obtenido    Valor obtenido.
 * @param {string} descripcion Qué se comprueba.
 * @return {void}
 */
function comprobar( esperado, obtenido, descripcion ) {
	total++;

	var iguales = JSON.stringify( esperado ) === JSON.stringify( obtenido );

	if ( iguales ) {
		console.log( '    ✓ ' + descripcion );
		return;
	}

	fallos.push( descripcion );
	console.log( '    ✗ ' + descripcion );
	console.log( '        esperado: ' + JSON.stringify( esperado ) );
	console.log( '        obtenido: ' + JSON.stringify( obtenido ) );
}

// Configuración que normalmente inyecta PHP.
globalThis.escritoriowpConfig = {
	textos: {
		cargando: 'Cargando…',
		lanzadorSinResultados: 'Sin resultados para «%s»'
	},
	usuarioId: 1,
	esMac: false
};

globalThis.escritoriowpLanzador = {
	comandos: [
		{ id: '1', titulo: 'Plugins', contexto: '', url: 'plugins.php', icono: 'i', grupo: 'menu' },
		{ id: '2', titulo: 'Añadir nuevo', contexto: 'Plugins', url: 'plugin-install.php', icono: 'i', grupo: 'menu' },
		{ id: '3', titulo: 'Editor de archivos de plugins', contexto: 'Herramientas', url: 'plugin-editor.php', icono: 'i', grupo: 'menu' },
		{ id: '4', titulo: 'Páginas', contexto: '', url: 'edit.php?post_type=page', icono: 'i', grupo: 'menu' },
		{ id: '5', titulo: 'Ajustes', contexto: '', url: 'options-general.php', icono: 'i', grupo: 'menu' },
		{ id: '6', titulo: 'Lectura', contexto: 'Ajustes', url: 'options-reading.php', icono: 'i', grupo: 'menu' },
		{ id: '7', titulo: 'Nueva entrada', contexto: '', url: 'post-new.php', icono: 'i', grupo: 'accion' }
	],
	filtros: [],
	minCaracteres: 2,
	limite: 5,
	espera: 200,
	maxRecientes: 8
};

var EWP = require( '../escritoriowp/assets/js/comun.js' );
var lanzador = require( '../escritoriowp/assets/js/lanzador.js' );

console.log( 'Pruebas unitarias de JavaScript de EscritorioWP' );

grupo( 'Normalización de texto' );

comprobar( 'paginas', EWP.normalizar( 'Páginas' ), 'quita los acentos y pasa a minúsculas' );
comprobar( 'anadir nuevo', EWP.normalizar( 'Añadir Nuevo' ), 'la eñe se descompone como el resto' );
comprobar( 'ajustes', EWP.normalizar( '  Ajustes  ' ), 'recorta los espacios' );
comprobar( '', EWP.normalizar( null ), 'un valor nulo da una cadena vacía' );

grupo( 'Formateo de textos' );

comprobar( 'hace 2 horas', EWP.fmt( 'hace %s', '2 horas' ), 'sustituye un marcador simple' );
comprobar( 'a de b', EWP.fmt( '%1$s de %2$s', 'a', 'b' ), 'sustituye marcadores numerados' );
comprobar( 'b antes de a', EWP.fmt( '%2$s antes de %1$s', 'a', 'b' ), 'respeta el orden de los numerados' );
comprobar( 'Cargando…', EWP.t( 'cargando' ), 'devuelve el texto traducido' );
comprobar( 'inexistente', EWP.t( 'inexistente' ), 'una clave desconocida devuelve la propia clave' );
comprobar( 'Sin resultados para «xyz»', EWP.tf( 'lanzadorSinResultados', 'xyz' ), 'traduce y formatea en un paso' );

grupo( 'Puntuación de coincidencias' );

comprobar( 100, EWP.puntuar( 'Plugins', 'plugins' ), 'coincidencia exacta' );
comprobar( 80, EWP.puntuar( 'Plugins', 'plug' ), 'el texto empieza por la búsqueda' );
comprobar( 60, EWP.puntuar( 'Editor de plugins', 'plug' ), 'una palabra empieza por la búsqueda' );
comprobar( 40, EWP.puntuar( 'Multiplugins', 'plug' ), 'el texto contiene la búsqueda' );
comprobar( 0, EWP.puntuar( 'Ajustes', 'plug' ), 'sin coincidencia' );
comprobar( 80, EWP.puntuar( 'Páginas', 'pagina' ), 'la búsqueda sin acentos encuentra el texto acentuado' );
comprobar( 1, EWP.puntuar( 'Cualquiera', '' ), 'sin búsqueda todo coincide' );

grupo( 'Filtrado de comandos' );

var resultado = lanzador.filtrarComandos( 'plug' ).map( function ( comando ) {
	return comando.contexto ? comando.contexto + ' > ' + comando.titulo : comando.titulo;
} );

comprobar(
	[ 'Plugins', 'Plugins > Añadir nuevo', 'Herramientas > Editor de archivos de plugins' ],
	resultado,
	'«plug» ordena «Plugins» antes que sus submenús'
);

comprobar( [ 'Páginas' ], lanzador.filtrarComandos( 'pagina' ).map( function ( c ) {
	return c.titulo;
} ), '«pagina» encuentra «Páginas» sin acentos' );

comprobar(
	[ 'Ajustes', 'Ajustes > Lectura' ],
	lanzador.filtrarComandos( 'ajustes' ).map( function ( c ) {
		return c.contexto ? c.contexto + ' > ' + c.titulo : c.titulo;
	} ),
	'el menú padre gana a su submenú'
);

comprobar(
	[ 'Ajustes > Lectura' ],
	lanzador.filtrarComandos( 'lectura' ).map( function ( c ) {
		return c.contexto + ' > ' + c.titulo;
	} ),
	'se puede buscar directamente por el nombre del submenú'
);

comprobar(
	[ 'Ajustes > Lectura' ],
	lanzador.filtrarComandos( 'ajustes lectura' ).map( function ( c ) {
		return c.contexto + ' > ' + c.titulo;
	} ),
	'la ruta completa también coincide'
);

comprobar( [], lanzador.filtrarComandos( 'xyzzy' ), 'una búsqueda sin coincidencias no devuelve nada' );

grupo( 'Puntuación de comandos' );

var padre = { titulo: 'Plugins', contexto: '' };
var hijo = { titulo: 'Añadir nuevo', contexto: 'Plugins' };

comprobar(
	true,
	lanzador.puntuarComando( padre, 'plug' ) > lanzador.puntuarComando( hijo, 'plug' ),
	'el menú padre puntúa más que su submenú'
);

comprobar( 0, lanzador.puntuarComando( { titulo: 'Ajustes', contexto: '' }, 'zzz' ), 'sin coincidencia puntúa cero' );

grupo( 'Construcción de URLs' );

globalThis.escritoriowpConfig.rest = 'https://ejemplo.com/wp-json/escritoriowp/v1/';

comprobar( 'https://ejemplo.com/wp-json/escritoriowp/v1/buscar', EWP.url( 'buscar' ), 'ruta sin parámetros' );
comprobar( 'https://ejemplo.com/wp-json/escritoriowp/v1/buscar?q=ana', EWP.url( 'buscar', { q: 'ana' } ), 'un parámetro' );
comprobar( 'https://ejemplo.com/wp-json/escritoriowp/v1/buscar?q=ana%20ruiz&limite=5', EWP.url( 'buscar', { q: 'ana ruiz', limite: 5 } ), 'varios parámetros, con codificación' );
comprobar( 'https://ejemplo.com/wp-json/escritoriowp/v1/buscar?q=%231043', EWP.url( 'buscar', { q: '#1043', tipos: '' } ), 'los parámetros vacíos se omiten' );

console.log( '\n' + '-'.repeat( 60 ) );

if ( fallos.length === 0 ) {
	console.log( '  ' + total + ' comprobaciones, todas correctas' );
	process.exit( 0 );
}

console.log( '  ' + total + ' comprobaciones, ' + fallos.length + ' fallos:' );
fallos.forEach( function ( fallo ) {
	console.log( '    - ' + fallo );
} );
process.exit( 1 );
