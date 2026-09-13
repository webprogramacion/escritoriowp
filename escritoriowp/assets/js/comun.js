/**
 * EscritorioWP · utilidades compartidas por el escritorio y el lanzador.
 *
 * No contiene ningún texto visible: todos los textos llegan traducidos desde PHP en
 * window.escritoriowpConfig.textos.
 */
(function (global) {
	'use strict';

	var config = global.escritoriowpConfig || { textos: {} };

	/**
	 * Devuelve un texto traducido a partir de su clave.
	 *
	 * @param {string} clave Clave del catálogo de textos.
	 * @return {string} Texto traducido, o la propia clave si no existe.
	 */
	function t( clave ) {
		var textos = config.textos || {};

		return Object.prototype.hasOwnProperty.call( textos, clave ) ? textos[ clave ] : clave;
	}

	/**
	 * Sustituye los marcadores %s y %1$s de una plantilla.
	 *
	 * @param {string} plantilla Plantilla con marcadores.
	 * @return {string} Texto con los valores sustituidos.
	 */
	function fmt( plantilla ) {
		var valores = Array.prototype.slice.call( arguments, 1 );
		var indice = 0;

		return String( plantilla ).replace( /%(\d+\$)?s/g, function ( coincidencia, posicion ) {
			if ( posicion ) {
				return String( valores[ parseInt( posicion, 10 ) - 1 ] );
			}

			return String( valores[ indice++ ] );
		} );
	}

	/**
	 * Traduce y formatea en un solo paso.
	 *
	 * @param {string} clave Clave del catálogo de textos.
	 * @return {string} Texto traducido y formateado.
	 */
	function tf( clave ) {
		var valores = Array.prototype.slice.call( arguments, 1 );

		return fmt.apply( null, [ t( clave ) ].concat( valores ) );
	}

	/**
	 * Pasa un texto a minúsculas y le quita los acentos, para comparar búsquedas.
	 *
	 * @param {string} texto Texto de origen.
	 * @return {string} Texto normalizado.
	 */
	function normalizar( texto ) {
		return String( texto == null ? '' : texto )
			.normalize( 'NFD' )
			.replace( /[̀-ͯ]/g, '' )
			.toLowerCase()
			.trim();
	}

	/**
	 * Retrasa la ejecución de una función hasta que dejan de llegar llamadas.
	 *
	 * @param {Function} funcion Función a retrasar.
	 * @param {number}   espera  Milisegundos de espera.
	 * @return {Function} Función retrasada.
	 */
	function esperar( funcion, espera ) {
		var temporizador = null;

		return function () {
			var contexto = this;
			var args = arguments;

			global.clearTimeout( temporizador );
			temporizador = global.setTimeout( function () {
				funcion.apply( contexto, args );
			}, espera );
		};
	}

	/**
	 * Indica si el usuario navega desde macOS.
	 *
	 * @return {boolean} Verdadero en macOS y en dispositivos de Apple.
	 */
	function esMac() {
		if ( typeof navigator === 'undefined' ) {
			return !! config.esMac;
		}

		var plataforma = ( navigator.userAgentData && navigator.userAgentData.platform ) || navigator.platform || navigator.userAgent || '';

		return /Mac|iPhone|iPad|iPod/i.test( plataforma );
	}

	/**
	 * Devuelve el atajo del lanzador según el sistema operativo.
	 *
	 * @return {string} «⌘K» o «Ctrl+K».
	 */
	function atajo() {
		return esMac() ? '⌘K' : 'Ctrl+K';
	}

	/**
	 * Crea un elemento del DOM.
	 *
	 * El contenido siempre se asigna como texto, nunca como HTML, de modo que ningún dato del sitio
	 * puede inyectar marcado.
	 *
	 * @param {string} etiqueta Nombre de la etiqueta.
	 * @param {Object} props    Propiedades: texto, clase, datos, atributos y manejadores «onX».
	 * @param {Array}  hijos    Nodos o cadenas a añadir.
	 * @return {HTMLElement} Elemento creado.
	 */
	function crear( etiqueta, props, hijos ) {
		var nodo = global.document.createElement( etiqueta );

		Object.keys( props || {} ).forEach( function ( clave ) {
			var valor = props[ clave ];

			if ( valor === null || valor === undefined || valor === false ) {
				return;
			}

			if ( clave === 'texto' ) {
				nodo.textContent = valor;
			} else if ( clave === 'clase' ) {
				nodo.className = valor;
			} else if ( clave === 'datos' ) {
				Object.keys( valor ).forEach( function ( dato ) {
					nodo.dataset[ dato ] = valor[ dato ];
				} );
			} else if ( clave.indexOf( 'on' ) === 0 ) {
				nodo.addEventListener( clave.slice( 2 ).toLowerCase(), valor );
			} else {
				nodo.setAttribute( clave, valor === true ? '' : valor );
			}
		} );

		( hijos || [] ).forEach( function ( hijo ) {
			if ( hijo === null || hijo === undefined || hijo === false ) {
				return;
			}

			nodo.appendChild( typeof hijo === 'string' ? global.document.createTextNode( hijo ) : hijo );
		} );

		return nodo;
	}

	/**
	 * Crea un icono de Dashicons decorativo.
	 *
	 * @param {string} nombre Clase del icono.
	 * @return {HTMLElement} Elemento del icono.
	 */
	function icono( nombre ) {
		return crear( 'span', {
			clase: 'dashicons ' + ( nombre || 'dashicons-marker' ),
			'aria-hidden': 'true'
		} );
	}

	/**
	 * Vacía un elemento.
	 *
	 * @param {HTMLElement} nodo Elemento a vaciar.
	 * @return {HTMLElement} El mismo elemento.
	 */
	function vaciar( nodo ) {
		while ( nodo && nodo.firstChild ) {
			nodo.removeChild( nodo.firstChild );
		}

		return nodo;
	}

	/**
	 * Construye la URL de una ruta de la API del plugin.
	 *
	 * @param {string} ruta   Ruta relativa.
	 * @param {Object} params Parámetros de consulta.
	 * @return {string} URL completa.
	 */
	function url( ruta, params ) {
		var base = ( config.rest || '' ) + ruta;
		var partes = [];

		Object.keys( params || {} ).forEach( function ( clave ) {
			var valor = params[ clave ];

			if ( valor === null || valor === undefined || valor === '' ) {
				return;
			}

			partes.push( encodeURIComponent( clave ) + '=' + encodeURIComponent( valor ) );
		} );

		if ( ! partes.length ) {
			return base;
		}

		return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + partes.join( '&' );
	}

	/**
	 * Lanza una petición a la API del plugin.
	 *
	 * @param {string} metodo   Método HTTP.
	 * @param {string} ruta     Ruta relativa.
	 * @param {Object} opciones Parámetros, cuerpo y señal de cancelación.
	 * @return {Promise<Object>} Datos de la respuesta.
	 */
	function peticion( metodo, ruta, opciones ) {
		var ajustes = opciones || {};
		var cabeceras = { 'X-WP-Nonce': config.nonce || '' };
		var init = {
			method: metodo,
			credentials: 'same-origin',
			headers: cabeceras
		};

		if ( ajustes.cuerpo ) {
			cabeceras[ 'Content-Type' ] = 'application/json';
			init.body = JSON.stringify( ajustes.cuerpo );
		}

		if ( ajustes.signal ) {
			init.signal = ajustes.signal;
		}

		return global.fetch( url( ruta, ajustes.params ), init ).then( function ( respuesta ) {
			return respuesta.json().catch( function () {
				return {};
			} ).then( function ( datos ) {
				if ( ! respuesta.ok ) {
					var error = new Error( ( datos && datos.message ) || t( 'errorCarga' ) );
					error.estado = respuesta.status;
					error.datos = datos;
					throw error;
				}

				return datos;
			} );
		} );
	}

	/**
	 * Mantiene el foco dentro de un contenedor mientras está abierto.
	 *
	 * @param {HTMLElement} contenedor Contenedor del diálogo.
	 * @return {Function} Función que libera la trampa de foco.
	 */
	function atraparFoco( contenedor ) {
		function alPulsar( evento ) {
			if ( evento.key !== 'Tab' ) {
				return;
			}

			var enfocables = contenedor.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);

			if ( ! enfocables.length ) {
				return;
			}

			var primero = enfocables[ 0 ];
			var ultimo = enfocables[ enfocables.length - 1 ];

			if ( evento.shiftKey && global.document.activeElement === primero ) {
				evento.preventDefault();
				ultimo.focus();
			} else if ( ! evento.shiftKey && global.document.activeElement === ultimo ) {
				evento.preventDefault();
				primero.focus();
			}
		}

		contenedor.addEventListener( 'keydown', alPulsar );

		return function () {
			contenedor.removeEventListener( 'keydown', alPulsar );
		};
	}

	/**
	 * Puntúa la coincidencia de un texto con una búsqueda.
	 *
	 * Prioriza, en este orden: el texto empieza por la búsqueda, alguna palabra empieza por ella,
	 * la contiene, o contiene todas las palabras de la búsqueda por separado.
	 *
	 * @param {string} texto    Texto candidato.
	 * @param {string} busqueda Texto buscado.
	 * @return {number} Puntuación; 0 si no hay coincidencia.
	 */
	function puntuar( texto, busqueda ) {
		var candidato = normalizar( texto );
		var termino = normalizar( busqueda );

		if ( ! termino ) {
			return 1;
		}

		if ( ! candidato ) {
			return 0;
		}

		if ( candidato === termino ) {
			return 100;
		}

		if ( candidato.indexOf( termino ) === 0 ) {
			return 80;
		}

		var palabras = candidato.split( /[\s·>/-]+/ );
		var empiezaPalabra = palabras.some( function ( palabra ) {
			return palabra.indexOf( termino ) === 0;
		} );

		if ( empiezaPalabra ) {
			return 60;
		}

		if ( candidato.indexOf( termino ) !== -1 ) {
			return 40;
		}

		var buscadas = termino.split( /\s+/ ).filter( Boolean );

		if ( buscadas.length > 1 && buscadas.every( function ( parte ) {
			return candidato.indexOf( parte ) !== -1;
		} ) ) {
			return 20;
		}

		return 0;
	}

	var EscritorioWP = {
		config: config,
		t: t,
		tf: tf,
		fmt: fmt,
		normalizar: normalizar,
		esperar: esperar,
		esMac: esMac,
		atajo: atajo,
		crear: crear,
		icono: icono,
		vaciar: vaciar,
		url: url,
		atraparFoco: atraparFoco,
		puntuar: puntuar,
		obtener: function ( ruta, opciones ) {
			return peticion( 'GET', ruta, opciones );
		},
		enviar: function ( ruta, opciones ) {
			return peticion( 'POST', ruta, opciones );
		}
	};

	global.EscritorioWP = EscritorioWP;

	// Corrige la pista del atajo según el sistema real del visitante.
	if ( typeof document !== 'undefined' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			var pistas = document.querySelectorAll( '[data-escritoriowp-atajo]' );

			Array.prototype.forEach.call( pistas, function ( pista ) {
				pista.textContent = atajo();
			} );
		} );
	}

	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = EscritorioWP;
	}
})( typeof window !== 'undefined' ? window : globalThis );
