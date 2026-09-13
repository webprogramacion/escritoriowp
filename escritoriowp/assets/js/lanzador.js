/**
 * EscritorioWP · lanzador de comandos y búsqueda unificada.
 *
 * Sustituye a la paleta de comandos de wp-admin: se abre con ⌘K o Ctrl+K, filtra en el navegador
 * los comandos del menú de administración y consulta al servidor el contenido del sitio.
 */
(function (global) {
	'use strict';

	var EWP = global.EscritorioWP;

	var config = global.escritoriowpLanzador || {
		comandos: [],
		filtros: [],
		minCaracteres: 2,
		limite: 5,
		espera: 200,
		maxRecientes: 8
	};

	var estado = {
		abierto: false,
		consulta: '',
		filtro: '',
		comandos: [],
		grupos: [],
		elementos: [],
		seleccion: 0,
		cargando: false,
		error: false,
		controlador: null,
		peticion: 0,
		abridor: null,
		liberarFoco: null
	};

	var nodos = {};

	/**
	 * Clave donde se guardan los elementos recientes de este usuario.
	 *
	 * @return {string} Clave de almacenamiento local.
	 */
	function claveRecientes() {
		return 'escritoriowp:recientes:' + ( EWP.config.usuarioId || 0 );
	}

	/**
	 * Lee los elementos recientes del navegador.
	 *
	 * @return {Array} Lista de elementos recientes.
	 */
	function leerRecientes() {
		try {
			var crudo = global.localStorage.getItem( claveRecientes() );
			var datos = crudo ? JSON.parse( crudo ) : [];

			return Array.isArray( datos ) ? datos : [];
		} catch ( error ) {
			return [];
		}
	}

	/**
	 * Guarda un elemento como reciente.
	 *
	 * @param {Object} elemento Elemento abierto desde el lanzador.
	 * @return {void}
	 */
	function guardarReciente( elemento ) {
		if ( ! elemento || ! elemento.url ) {
			return;
		}

		try {
			var recientes = leerRecientes().filter( function ( item ) {
				return item.url !== elemento.url;
			} );

			recientes.unshift( {
				titulo: elemento.titulo,
				contexto: elemento.contexto || '',
				url: elemento.url,
				icono: elemento.icono || 'dashicons-marker'
			} );

			global.localStorage.setItem(
				claveRecientes(),
				JSON.stringify( recientes.slice( 0, config.maxRecientes || 8 ) )
			);
		} catch ( error ) {
			// El almacenamiento local puede estar bloqueado: los recientes son prescindibles.
		}
	}

	/**
	 * Puntúa un comando frente a la búsqueda.
	 *
	 * El contexto (el menú padre) puntúa algo menos que el título, de modo que «Plugins» siempre
	 * gana a «Plugins › Añadir nuevo» cuando se escribe «plug».
	 *
	 * @param {Object} comando  Comando del catálogo.
	 * @param {string} busqueda Texto buscado.
	 * @return {number} Puntuación.
	 */
	function puntuarComando( comando, busqueda ) {
		var directo = EWP.puntuar( comando.titulo, busqueda );

		if ( ! comando.contexto ) {
			return directo;
		}

		var completo = EWP.puntuar( comando.contexto + ' ' + comando.titulo, busqueda );

		return Math.max( directo, completo * 0.9 );
	}

	/**
	 * Filtra y ordena el catálogo de comandos.
	 *
	 * @param {string} busqueda Texto buscado.
	 * @return {Array} Comandos coincidentes.
	 */
	function filtrarComandos( busqueda ) {
		var coincidencias = [];

		( config.comandos || [] ).forEach( function ( comando ) {
			var puntos = puntuarComando( comando, busqueda );

			if ( puntos > 0 ) {
				coincidencias.push( { comando: comando, puntos: puntos } );
			}
		} );

		coincidencias.sort( function ( a, b ) {
			if ( b.puntos !== a.puntos ) {
				return b.puntos - a.puntos;
			}

			return a.comando.titulo.length - b.comando.titulo.length;
		} );

		return coincidencias.map( function ( item ) {
			return item.comando;
		} );
	}

	/**
	 * Construye el diálogo del lanzador la primera vez que se abre.
	 *
	 * @return {void}
	 */
	function construir() {
		if ( nodos.raiz ) {
			return;
		}

		nodos.campo = EWP.crear( 'input', {
			type: 'search',
			clase: 'escritoriowp-lanzador__campo',
			id: 'escritoriowp-lanzador-campo',
			placeholder: EWP.t( 'lanzadorPlaceholder' ),
			'aria-label': EWP.t( 'lanzadorPlaceholder' ),
			role: 'combobox',
			'aria-expanded': 'true',
			'aria-controls': 'escritoriowp-lanzador-lista',
			'aria-autocomplete': 'list',
			autocomplete: 'off',
			spellcheck: 'false'
		} );

		nodos.filtros = EWP.crear( 'div', {
			clase: 'escritoriowp-lanzador__filtros',
			role: 'tablist',
			'aria-label': EWP.t( 'lanzadorFiltrar' )
		} );

		nodos.lista = EWP.crear( 'div', {
			clase: 'escritoriowp-lanzador__lista',
			id: 'escritoriowp-lanzador-lista',
			role: 'listbox',
			'aria-label': EWP.t( 'lanzadorTitulo' )
		} );

		nodos.aviso = EWP.crear( 'div', {
			clase: 'escritoriowp-lanzador__aviso',
			'aria-live': 'polite'
		} );

		nodos.titulo = EWP.crear( 'h2', {
			clase: 'escritoriowp-lanzador__titulo-oculto',
			id: 'escritoriowp-lanzador-titulo',
			texto: EWP.t( 'lanzadorTitulo' )
		} );

		nodos.dialogo = EWP.crear(
			'div',
			{
				clase: 'escritoriowp-lanzador__dialogo',
				role: 'dialog',
				'aria-modal': 'true',
				'aria-labelledby': 'escritoriowp-lanzador-titulo'
			},
			[
				nodos.titulo,
				EWP.crear( 'div', { clase: 'escritoriowp-lanzador__buscador' }, [
					EWP.icono( 'dashicons-search' ),
					nodos.campo,
					EWP.crear( 'kbd', { clase: 'escritoriowp-atajo', texto: 'Esc' } )
				] ),
				nodos.filtros,
				nodos.lista,
				nodos.aviso,
				pieAyuda()
			]
		);

		nodos.raiz = EWP.crear(
			'div',
			{
				clase: 'escritoriowp-lanzador',
				'data-tema': EWP.config.tema || 'auto',
				hidden: true
			},
			[ nodos.dialogo ]
		);

		nodos.raiz.addEventListener( 'mousedown', function ( evento ) {
			if ( evento.target === nodos.raiz ) {
				cerrar();
			}
		} );

		nodos.campo.addEventListener( 'input', function () {
			estado.consulta = nodos.campo.value;
			alCambiarConsulta();
		} );

		nodos.dialogo.addEventListener( 'keydown', alPulsarEnDialogo );

		global.document.body.appendChild( nodos.raiz );

		pintarFiltros();
	}

	/**
	 * Pie con la ayuda de atajos de teclado.
	 *
	 * @return {HTMLElement} Elemento del pie.
	 */
	function pieAyuda() {
		function pista( teclas, texto ) {
			return EWP.crear( 'span', { clase: 'escritoriowp-lanzador__pista' }, [
				EWP.crear( 'kbd', { clase: 'escritoriowp-atajo', texto: teclas } ),
				EWP.crear( 'span', { texto: texto } )
			] );
		}

		var modificador = EWP.esMac() ? '⌘' : 'Ctrl';

		return EWP.crear( 'div', { clase: 'escritoriowp-lanzador__pie' }, [
			pista( '↑↓', EWP.t( 'lanzadorNavegar' ) ),
			pista( '↵', EWP.t( 'lanzadorAbrir' ) ),
			pista( modificador + '+↵', EWP.t( 'lanzadorNuevaPestana' ) ),
			pista( '⇧+↵', EWP.t( 'lanzadorVerSitio' ) ),
			pista( 'Tab', EWP.t( 'lanzadorFiltrar' ) )
		] );
	}

	/**
	 * Pinta los botones de filtro por tipo.
	 *
	 * @return {void}
	 */
	function pintarFiltros() {
		EWP.vaciar( nodos.filtros );

		( config.filtros || [] ).forEach( function ( filtro ) {
			var activo = filtro.clave === estado.filtro;

			nodos.filtros.appendChild(
				EWP.crear( 'button', {
					type: 'button',
					clase: 'escritoriowp-lanzador__filtro' + ( activo ? ' es-activo' : '' ),
					role: 'tab',
					'aria-selected': activo ? 'true' : 'false',
					texto: filtro.etiqueta,
					onClick: function () {
						estado.filtro = filtro.clave;
						pintarFiltros();
						alCambiarConsulta();
						nodos.campo.focus();
					}
				} )
			);
		} );
	}

	/**
	 * Reacciona a un cambio de la consulta o del filtro.
	 *
	 * @return {void}
	 */
	function alCambiarConsulta() {
		var consulta = estado.consulta.trim();

		estado.comandos = ( estado.filtro === '' || estado.filtro === 'comandos' )
			? filtrarComandos( consulta )
			: [];

		estado.seleccion = 0;

		if ( consulta.length < ( config.minCaracteres || 2 ) || estado.filtro === 'comandos' ) {
			cancelarBusqueda();
			estado.grupos = [];
			estado.cargando = false;
			estado.error = false;
			pintar();
			return;
		}

		estado.cargando = true;
		estado.error = false;
		pintar();
		buscarConEspera( consulta );
	}

	/**
	 * Cancela la búsqueda en curso.
	 *
	 * @return {void}
	 */
	function cancelarBusqueda() {
		if ( estado.controlador ) {
			estado.controlador.abort();
			estado.controlador = null;
		}
	}

	/**
	 * Consulta al servidor, descartando las respuestas que llegan fuera de orden.
	 *
	 * @param {string} consulta Texto buscado.
	 * @return {void}
	 */
	function buscar( consulta ) {
		cancelarBusqueda();

		var controlador = typeof global.AbortController !== 'undefined' ? new global.AbortController() : null;
		var marca = ++estado.peticion;

		estado.controlador = controlador;

		EWP.obtener( 'buscar', {
			params: {
				q: consulta,
				tipos: estado.filtro,
				limite: config.limite || 5
			},
			signal: controlador ? controlador.signal : undefined
		} ).then( function ( datos ) {
			if ( marca !== estado.peticion ) {
				return;
			}

			estado.grupos = datos.grupos || [];
			estado.cargando = false;
			estado.error = false;
			estado.seleccion = 0;
			pintar();
		} ).catch( function ( error ) {
			if ( ( error && error.name === 'AbortError' ) || marca !== estado.peticion ) {
				return;
			}

			estado.grupos = [];
			estado.cargando = false;
			estado.error = true;
			pintar();
		} );
	}

	var buscarConEspera = EWP.esperar( buscar, config.espera || 200 );

	/**
	 * Pinta el contenido del lanzador según el estado actual.
	 *
	 * @return {void}
	 */
	function pintar() {
		EWP.vaciar( nodos.lista );
		estado.elementos = [];

		var consulta = estado.consulta.trim();

		if ( ! consulta ) {
			pintarInicio();
		} else {
			pintarResultados( consulta );
		}

		marcarSeleccion();
		anunciar();
	}

	/**
	 * Pinta el estado inicial: recientes y acciones rápidas.
	 *
	 * @return {void}
	 */
	function pintarInicio() {
		var recientes = leerRecientes();

		if ( recientes.length ) {
			pintarGrupo( EWP.t( 'lanzadorRecientes' ), recientes.map( function ( item ) {
				return {
					titulo: item.titulo,
					contexto: item.contexto,
					subtitulo: item.contexto,
					url: item.url,
					icono: item.icono,
					urlVer: ''
				};
			} ) );
		}

		var acciones = ( config.comandos || [] ).filter( function ( comando ) {
			return comando.grupo === 'accion';
		} );

		if ( acciones.length ) {
			pintarGrupo( EWP.t( 'lanzadorAcciones' ), acciones.map( comandoAElemento ) );
		}

		nodos.lista.appendChild(
			EWP.crear( 'p', {
				clase: 'escritoriowp-lanzador__sugerencia',
				texto: EWP.t( 'lanzadorSugerencia' )
			} )
		);
	}

	/**
	 * Pinta los resultados de una búsqueda.
	 *
	 * @param {string} consulta Texto buscado.
	 * @return {void}
	 */
	function pintarResultados( consulta ) {
		if ( estado.comandos.length ) {
			pintarGrupo( EWP.t( 'lanzadorFiltroMenu' ), estado.comandos.map( comandoAElemento ) );
		}

		estado.grupos.forEach( function ( grupo ) {
			var elementos = ( grupo.resultados || [] ).map( function ( resultado ) {
				return {
					titulo: resultado.titulo,
					subtitulo: resultado.subtitulo,
					contexto: grupo.etiqueta,
					url: resultado.urlEditar,
					urlVer: resultado.urlVer,
					icono: resultado.icono
				};
			} );

			var extra = null;

			if ( grupo.urlListado && grupo.total > elementos.length ) {
				extra = {
					titulo: EWP.t( 'lanzadorVerTodos' ),
					subtitulo: EWP.fmt( '%s', grupo.total ),
					contexto: grupo.etiqueta,
					url: grupo.urlListado,
					urlVer: '',
					icono: 'dashicons-external'
				};
			}

			pintarGrupo( grupo.etiqueta, elementos, extra );
		} );

		if ( estado.cargando ) {
			nodos.lista.appendChild(
				EWP.crear( 'p', { clase: 'escritoriowp-lanzador__cargando', texto: EWP.t( 'cargando' ) } )
			);
		}

		if ( estado.error ) {
			nodos.lista.appendChild(
				EWP.crear( 'p', { clase: 'escritoriowp-lanzador__error', texto: EWP.t( 'lanzadorError' ) } )
			);
		}

		if ( ! estado.elementos.length && ! estado.cargando ) {
			nodos.lista.appendChild(
				EWP.crear( 'p', {
					clase: 'escritoriowp-lanzador__vacio',
					texto: EWP.tf( 'lanzadorSinResultados', consulta )
				} )
			);
		}
	}

	/**
	 * Convierte un comando del catálogo en un elemento de la lista.
	 *
	 * @param {Object} comando Comando del catálogo.
	 * @return {Object} Elemento de la lista.
	 */
	function comandoAElemento( comando ) {
		return {
			titulo: comando.titulo,
			subtitulo: comando.contexto,
			contexto: comando.contexto,
			url: comando.url,
			urlVer: '',
			icono: comando.icono
		};
	}

	/**
	 * Pinta un grupo de resultados con su cabecera.
	 *
	 * @param {string} etiqueta  Cabecera del grupo.
	 * @param {Array}  elementos Elementos del grupo.
	 * @param {Object} extra     Elemento adicional al final, si lo hay.
	 * @return {void}
	 */
	function pintarGrupo( etiqueta, elementos, extra ) {
		if ( ! elementos.length ) {
			return;
		}

		nodos.lista.appendChild(
			EWP.crear( 'p', { clase: 'escritoriowp-lanzador__grupo', texto: etiqueta } )
		);

		elementos.concat( extra ? [ extra ] : [] ).forEach( function ( elemento ) {
			var indice = estado.elementos.length;

			estado.elementos.push( elemento );
			nodos.lista.appendChild( pintarFila( elemento, indice ) );
		} );
	}

	/**
	 * Pinta una fila de resultado.
	 *
	 * @param {Object} elemento Elemento a pintar.
	 * @param {number} indice   Posición en la lista.
	 * @return {HTMLElement} Fila.
	 */
	function pintarFila( elemento, indice ) {
		var textos = [ EWP.crear( 'span', { clase: 'escritoriowp-lanzador__fila-titulo', texto: elemento.titulo } ) ];

		if ( elemento.subtitulo ) {
			textos.push(
				EWP.crear( 'span', { clase: 'escritoriowp-lanzador__fila-sub', texto: elemento.subtitulo } )
			);
		}

		var acciones = [ EWP.crear( 'kbd', { clase: 'escritoriowp-atajo', texto: '↵' } ) ];

		if ( elemento.urlVer ) {
			acciones.unshift(
				EWP.crear( 'span', { clase: 'escritoriowp-lanzador__accion', texto: EWP.t( 'lanzadorVerSitio' ) } )
			);
		}

		return EWP.crear(
			'div',
			{
				clase: 'escritoriowp-lanzador__fila',
				id: 'escritoriowp-lanzador-op-' + indice,
				role: 'option',
				'aria-selected': 'false',
				datos: { indice: String( indice ) },
				onMousemove: function () {
					if ( estado.seleccion !== indice ) {
						estado.seleccion = indice;
						marcarSeleccion();
					}
				},
				onClick: function ( evento ) {
					evento.preventDefault();
					ejecutar( elemento, evento.metaKey || evento.ctrlKey ? 'nueva' : 'abrir' );
				}
			},
			[
				EWP.icono( elemento.icono ),
				EWP.crear( 'span', { clase: 'escritoriowp-lanzador__fila-textos' }, textos ),
				EWP.crear( 'span', { clase: 'escritoriowp-lanzador__fila-acciones' }, acciones )
			]
		);
	}

	/**
	 * Marca visualmente la fila seleccionada y la mantiene a la vista.
	 *
	 * @return {void}
	 */
	function marcarSeleccion() {
		var filas = nodos.lista.querySelectorAll( '.escritoriowp-lanzador__fila' );

		if ( estado.seleccion >= estado.elementos.length ) {
			estado.seleccion = Math.max( 0, estado.elementos.length - 1 );
		}

		Array.prototype.forEach.call( filas, function ( fila ) {
			var indice = parseInt( fila.dataset.indice, 10 );
			var activa = indice === estado.seleccion;

			fila.classList.toggle( 'es-activa', activa );
			fila.setAttribute( 'aria-selected', activa ? 'true' : 'false' );

			if ( activa ) {
				nodos.campo.setAttribute( 'aria-activedescendant', fila.id );

				if ( fila.scrollIntoView ) {
					fila.scrollIntoView( { block: 'nearest' } );
				}
			}
		} );

		if ( ! estado.elementos.length ) {
			nodos.campo.removeAttribute( 'aria-activedescendant' );
		}
	}

	/**
	 * Anuncia el número de resultados a los lectores de pantalla.
	 *
	 * @return {void}
	 */
	function anunciar() {
		var total = estado.elementos.length;

		nodos.aviso.textContent = total === 1
			? EWP.t( 'lanzadorUnResultado' )
			: EWP.tf( 'lanzadorResultados', total );
	}

	/**
	 * Mueve la selección.
	 *
	 * @param {number} delta Desplazamiento.
	 * @return {void}
	 */
	function mover( delta ) {
		if ( ! estado.elementos.length ) {
			return;
		}

		var total = estado.elementos.length;

		estado.seleccion = ( estado.seleccion + delta + total ) % total;
		marcarSeleccion();
	}

	/**
	 * Abre el destino de un elemento.
	 *
	 * @param {Object} elemento Elemento seleccionado.
	 * @param {string} modo     «abrir», «nueva» o «ver».
	 * @return {void}
	 */
	function ejecutar( elemento, modo ) {
		if ( ! elemento ) {
			return;
		}

		var destino = modo === 'ver' && elemento.urlVer ? elemento.urlVer : elemento.url;

		if ( ! destino ) {
			return;
		}

		guardarReciente( elemento );

		if ( modo === 'nueva' || modo === 'ver' ) {
			global.open( destino, '_blank', 'noopener' );
			return;
		}

		cerrar();
		global.location.href = destino;
	}

	/**
	 * Rota el filtro activo.
	 *
	 * @param {number} delta Desplazamiento.
	 * @return {void}
	 */
	function rotarFiltro( delta ) {
		var filtros = config.filtros || [];

		if ( ! filtros.length ) {
			return;
		}

		var actual = filtros.findIndex( function ( filtro ) {
			return filtro.clave === estado.filtro;
		} );

		var siguiente = ( ( actual === -1 ? 0 : actual ) + delta + filtros.length ) % filtros.length;

		estado.filtro = filtros[ siguiente ].clave;
		pintarFiltros();
		alCambiarConsulta();
	}

	/**
	 * Gestiona el teclado dentro del diálogo.
	 *
	 * @param {KeyboardEvent} evento Evento de teclado.
	 * @return {void}
	 */
	function alPulsarEnDialogo( evento ) {
		if ( evento.key === 'Escape' ) {
			evento.preventDefault();
			cerrar();
			return;
		}

		if ( evento.key === 'ArrowDown' ) {
			evento.preventDefault();
			mover( 1 );
			return;
		}

		if ( evento.key === 'ArrowUp' ) {
			evento.preventDefault();
			mover( -1 );
			return;
		}

		if ( evento.key === 'Tab' ) {
			evento.preventDefault();
			rotarFiltro( evento.shiftKey ? -1 : 1 );
			return;
		}

		if ( evento.key === 'Enter' ) {
			evento.preventDefault();

			var elemento = estado.elementos[ estado.seleccion ];

			if ( evento.metaKey || evento.ctrlKey ) {
				ejecutar( elemento, 'nueva' );
			} else if ( evento.shiftKey ) {
				ejecutar( elemento, elemento && elemento.urlVer ? 'ver' : 'abrir' );
			} else {
				ejecutar( elemento, 'abrir' );
			}
		}
	}

	/**
	 * Abre el lanzador.
	 *
	 * @return {void}
	 */
	function abrir() {
		construir();

		if ( estado.abierto ) {
			return;
		}

		estado.abierto = true;
		estado.abridor = global.document.activeElement;
		nodos.raiz.hidden = false;
		nodos.raiz.setAttribute( 'data-tema', EWP.config.tema || 'auto' );
		global.document.body.classList.add( 'escritoriowp-lanzador-abierto' );

		estado.liberarFoco = EWP.atraparFoco( nodos.dialogo );

		nodos.campo.value = estado.consulta;
		nodos.campo.focus();
		nodos.campo.select();

		alCambiarConsulta();
	}

	/**
	 * Cierra el lanzador y devuelve el foco.
	 *
	 * @return {void}
	 */
	function cerrar() {
		if ( ! estado.abierto ) {
			return;
		}

		cancelarBusqueda();

		estado.abierto = false;
		nodos.raiz.hidden = true;
		global.document.body.classList.remove( 'escritoriowp-lanzador-abierto' );

		if ( estado.liberarFoco ) {
			estado.liberarFoco();
			estado.liberarFoco = null;
		}

		if ( estado.abridor && estado.abridor.focus ) {
			estado.abridor.focus();
		}
	}

	/**
	 * Abre o cierra el lanzador.
	 *
	 * @return {void}
	 */
	function alternar() {
		if ( estado.abierto ) {
			cerrar();
		} else {
			abrir();
		}
	}

	/**
	 * Arranca el lanzador: atajo de teclado y botones de apertura.
	 *
	 * @return {void}
	 */
	function arrancar() {
		/*
		 * El atajo se captura en fase de captura para ganar a cualquier otro manejador, incluida la
		 * paleta nativa si por cualquier motivo siguiera cargada, y funciona también cuando el foco
		 * está dentro de un campo de texto.
		 */
		global.document.addEventListener( 'keydown', function ( evento ) {
			if ( ! ( evento.metaKey || evento.ctrlKey ) || evento.altKey || evento.shiftKey ) {
				return;
			}

			if ( String( evento.key ).toLowerCase() !== 'k' ) {
				return;
			}

			evento.preventDefault();
			evento.stopPropagation();
			alternar();
		}, true );

		global.document.addEventListener( 'click', function ( evento ) {
			var disparador = evento.target.closest(
				'.escritoriowp-abrir-lanzador, #wp-admin-bar-escritoriowp-lanzador a, #wp-admin-bar-escritoriowp-lanzador'
			);

			if ( ! disparador ) {
				return;
			}

			evento.preventDefault();
			abrir();
		} );
	}

	if ( typeof document !== 'undefined' ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', arrancar );
		} else {
			arrancar();
		}
	}

	var api = {
		abrir: abrir,
		cerrar: cerrar,
		alternar: alternar,
		puntuarComando: puntuarComando,
		filtrarComandos: filtrarComandos,
		estado: estado
	};

	global.escritoriowpLanzadorApi = api;

	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = api;
	}
})( typeof window !== 'undefined' ? window : globalThis );
