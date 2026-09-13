/**
 * EscritorioWP · interfaz del escritorio.
 *
 * Pinta los indicadores y los paneles de últimos elementos, gestiona la creación rápida en modal y
 * guarda las preferencias de cada usuario en el servidor.
 */
(function (global) {
	'use strict';

	var EWP = global.EscritorioWP;

	var config = global.escritoriowpEscritorio || {
		paneles: [],
		preferencias: { paneles_ocultos: [], orden_paneles: [], tema: 'auto' },
		elementosPorPanel: 5,
		roles: [],
		categorias: []
	};

	var estado = {
		preferencias: config.preferencias,
		orden: config.paneles.map( function ( panel ) {
			return panel.clave;
		} ),
		modal: null,
		liberarFoco: null,
		abridor: null
	};

	var nodos = {};

	/**
	 * Devuelve la definición de un panel por su clave.
	 *
	 * @param {string} clave Clave del panel.
	 * @return {Object|null} Definición del panel.
	 */
	function panelPorClave( clave ) {
		return config.paneles.filter( function ( panel ) {
			return panel.clave === clave;
		} )[ 0 ] || null;
	}

	/**
	 * Indica si un panel está oculto por preferencia del usuario.
	 *
	 * @param {string} clave Clave del panel.
	 * @return {boolean} Verdadero si está oculto.
	 */
	function estaOculto( clave ) {
		return ( estado.preferencias.paneles_ocultos || [] ).indexOf( clave ) !== -1;
	}

	/**
	 * Pasa una clave de panel a su forma capitalizada, para componer claves de texto.
	 *
	 * @param {string} clave Clave del panel.
	 * @return {string} Clave capitalizada.
	 */
	function capitalizar( clave ) {
		return clave.charAt( 0 ).toUpperCase() + clave.slice( 1 );
	}

	/* --------------------------------------------------------------------- Indicadores */

	/**
	 * Carga y pinta las tarjetas de indicadores.
	 *
	 * @param {boolean} refrescar Si debe ignorarse la caché del servidor.
	 * @return {void}
	 */
	function cargarResumen( refrescar ) {
		pintarEsqueletoResumen();

		EWP.obtener( 'resumen', { params: { refrescar: refrescar ? 1 : 0 } } )
			.then( function ( datos ) {
				pintarResumen( datos.tarjetas || [] );
			} )
			.catch( function () {
				EWP.vaciar( nodos.resumen ).appendChild( bloqueError( function () {
					cargarResumen( true );
				} ) );
			} );
	}

	/**
	 * Pinta el esqueleto de carga de los indicadores.
	 *
	 * @return {void}
	 */
	function pintarEsqueletoResumen() {
		EWP.vaciar( nodos.resumen );

		for ( var i = 0; i < 4; i++ ) {
			nodos.resumen.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-tarjeta escritoriowp-tarjeta--cargando' }, [
					EWP.crear( 'div', { clase: 'escritoriowp-esqueleto', style: 'height:14px;width:55%' } ),
					EWP.crear( 'div', { clase: 'escritoriowp-esqueleto', style: 'height:28px;width:35%;margin-top:10px' } )
				] )
			);
		}
	}

	/**
	 * Pinta las tarjetas de indicadores.
	 *
	 * @param {Array} tarjetas Tarjetas devueltas por el servidor.
	 * @return {void}
	 */
	function pintarResumen( tarjetas ) {
		EWP.vaciar( nodos.resumen );

		tarjetas.forEach( function ( tarjeta ) {
			nodos.resumen.appendChild(
				EWP.crear(
					'a',
					{
						clase: 'escritoriowp-tarjeta',
						href: tarjeta.url,
						datos: { tono: tarjeta.tono }
					},
					[
						EWP.crear( 'span', { clase: 'escritoriowp-tarjeta__icono' }, [ EWP.icono( tarjeta.icono ) ] ),
						EWP.crear( 'span', { clase: 'escritoriowp-tarjeta__etiqueta', texto: tarjeta.etiqueta } ),
						EWP.crear( 'strong', { clase: 'escritoriowp-tarjeta__valor', texto: tarjeta.valor } ),
						tarjeta.detalle
							? EWP.crear( 'span', { clase: 'escritoriowp-tarjeta__detalle', texto: tarjeta.detalle } )
							: null
					]
				)
			);
		} );
	}

	/* ------------------------------------------------------------------------- Paneles */

	/**
	 * Pinta todos los paneles visibles y carga su contenido.
	 *
	 * @return {void}
	 */
	function pintarPaneles() {
		EWP.vaciar( nodos.paneles );

		estado.orden.forEach( function ( clave ) {
			var panel = panelPorClave( clave );

			if ( ! panel || estaOculto( clave ) ) {
				return;
			}

			var cuerpo = EWP.crear( 'div', { clase: 'escritoriowp-panel__cuerpo' } );

			nodos.paneles.appendChild(
				EWP.crear( 'section', { clase: 'escritoriowp-panel', datos: { panel: clave } }, [
					EWP.crear( 'header', { clase: 'escritoriowp-panel__cabecera' }, [
						EWP.crear( 'h3', { clase: 'escritoriowp-panel__titulo' }, [
							EWP.icono( panel.icono ),
							EWP.crear( 'span', { texto: panel.etiqueta } )
						] ),
						EWP.crear( 'div', { clase: 'escritoriowp-panel__acciones' }, [
							panel.puedeCrear ? botonNuevo( panel, 'escritoriowp-boton escritoriowp-boton--claro escritoriowp-boton--mini' ) : null,
							EWP.crear( 'a', {
								clase: 'escritoriowp-boton escritoriowp-boton--enlace',
								href: panel.urlListado,
								texto: EWP.t( 'verTodos' )
							} )
						] )
					] ),
					cuerpo
				] )
			);

			cargarPanel( clave, cuerpo );
		} );
	}

	/**
	 * Crea el botón de creación de un panel.
	 *
	 * @param {Object} panel Definición del panel.
	 * @param {string} clase Clases del botón.
	 * @return {HTMLElement} Botón o enlace.
	 */
	function botonNuevo( panel, clase ) {
		if ( ! panel.enModal ) {
			return EWP.crear( 'a', { clase: clase, href: panel.urlNuevo, texto: panel.etiquetaNuevo } );
		}

		return EWP.crear( 'button', {
			type: 'button',
			clase: clase,
			texto: panel.etiquetaNuevo,
			onClick: function ( evento ) {
				abrirModal( panel.clave, evento.currentTarget );
			}
		} );
	}

	/**
	 * Carga el contenido de un panel.
	 *
	 * @param {string}      clave  Clave del panel.
	 * @param {HTMLElement} cuerpo Contenedor del contenido.
	 * @return {void}
	 */
	function cargarPanel( clave, cuerpo ) {
		EWP.vaciar( cuerpo );

		for ( var i = 0; i < 3; i++ ) {
			cuerpo.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-fila escritoriowp-fila--cargando' }, [
					EWP.crear( 'div', { clase: 'escritoriowp-esqueleto', style: 'height:36px;width:36px;border-radius:8px' } ),
					EWP.crear( 'div', { clase: 'escritoriowp-esqueleto', style: 'height:32px;flex:1' } )
				] )
			);
		}

		EWP.obtener( 'recientes/' + clave, { params: { limite: config.elementosPorPanel } } )
			.then( function ( datos ) {
				pintarFilas( clave, cuerpo, datos.elementos || [] );
			} )
			.catch( function () {
				EWP.vaciar( cuerpo ).appendChild( bloqueError( function () {
					cargarPanel( clave, cuerpo );
				} ) );
			} );
	}

	/**
	 * Pinta las filas de un panel.
	 *
	 * @param {string}      clave     Clave del panel.
	 * @param {HTMLElement} cuerpo    Contenedor del contenido.
	 * @param {Array}       elementos Filas devueltas por el servidor.
	 * @return {void}
	 */
	function pintarFilas( clave, cuerpo, elementos ) {
		EWP.vaciar( cuerpo );

		if ( ! elementos.length ) {
			var panel = panelPorClave( clave );

			cuerpo.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-vacio' }, [
					EWP.crear( 'p', { texto: EWP.t( 'panelVacio' + capitalizar( clave ) ) } ),
					panel && panel.puedeCrear
						? ( panel.enModal
							? EWP.crear( 'button', {
								type: 'button',
								clase: 'escritoriowp-boton escritoriowp-boton--claro',
								texto: EWP.t( 'crearPrimero' + capitalizar( clave ) ),
								onClick: function ( evento ) {
									abrirModal( clave, evento.currentTarget );
								}
							} )
							: EWP.crear( 'a', {
								clase: 'escritoriowp-boton escritoriowp-boton--claro',
								href: panel.urlNuevo,
								texto: EWP.t( 'crearPrimero' + capitalizar( clave ) )
							} ) )
						: null
				] )
			);

			return;
		}

		elementos.forEach( function ( elemento ) {
			var metas = ( elemento.metas || [] ).map( function ( meta ) {
				if ( meta.tono ) {
					return EWP.crear( 'span', {
						clase: 'escritoriowp-etiqueta',
						datos: { tono: meta.tono },
						texto: meta.texto
					} );
				}

				return EWP.crear( 'span', { clase: 'escritoriowp-fila__meta', texto: meta.texto } );
			} );

			cuerpo.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-fila' }, [
					elemento.imagen
						? EWP.crear( 'img', {
							clase: 'escritoriowp-fila__imagen',
							src: elemento.imagen,
							alt: '',
							loading: 'lazy'
						} )
						: EWP.crear( 'span', { clase: 'escritoriowp-fila__inicial', texto: elemento.inicial } ),
					EWP.crear( 'span', { clase: 'escritoriowp-fila__textos' }, [
						EWP.crear( 'a', {
							clase: 'escritoriowp-fila__titulo',
							href: elemento.urlEditar,
							texto: elemento.titulo
						} ),
						EWP.crear( 'span', { clase: 'escritoriowp-fila__metas' }, metas )
					] ),
					elemento.urlVer
						? EWP.crear( 'a', {
							clase: 'escritoriowp-fila__ver',
							href: elemento.urlVer,
							target: '_blank',
							rel: 'noopener',
							title: EWP.t( 'ver' ),
							'aria-label': EWP.t( 'ver' )
						}, [ EWP.icono( 'dashicons-visibility' ) ] )
						: null
				] )
			);
		} );
	}

	/**
	 * Bloque de error con botón de reintento.
	 *
	 * @param {Function} alReintentar Acción de reintento.
	 * @return {HTMLElement} Bloque de error.
	 */
	function bloqueError( alReintentar ) {
		return EWP.crear( 'div', { clase: 'escritoriowp-error' }, [
			EWP.crear( 'p', { texto: EWP.t( 'errorCarga' ) } ),
			EWP.crear( 'button', {
				type: 'button',
				clase: 'escritoriowp-boton escritoriowp-boton--claro',
				texto: EWP.t( 'reintentar' ),
				onClick: alReintentar
			} )
		] );
	}

	/* ------------------------------------------------------------------ Personalización */

	/**
	 * Pinta el menú de personalización.
	 *
	 * @return {void}
	 */
	function pintarPersonalizar() {
		EWP.vaciar( nodos.personalizar );

		var panelMenu = EWP.crear( 'div', {
			clase: 'escritoriowp-menu',
			hidden: true,
			role: 'group',
			'aria-label': EWP.t( 'personalizar' )
		} );

		var boton = EWP.crear( 'button', {
			type: 'button',
			clase: 'escritoriowp-boton escritoriowp-boton--fantasma',
			'aria-expanded': 'false',
			onClick: function () {
				var abierto = ! panelMenu.hidden;

				panelMenu.hidden = abierto;
				boton.setAttribute( 'aria-expanded', abierto ? 'false' : 'true' );
			}
		}, [ EWP.icono( 'dashicons-admin-generic' ), EWP.crear( 'span', { texto: EWP.t( 'personalizar' ) } ) ] );

		panelMenu.appendChild( EWP.crear( 'p', { clase: 'escritoriowp-menu__titulo', texto: EWP.t( 'paneles' ) } ) );

		estado.orden.forEach( function ( clave, indice ) {
			var panel = panelPorClave( clave );

			if ( ! panel ) {
				return;
			}

			var oculto = estaOculto( clave );

			panelMenu.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-menu__fila' }, [
					EWP.crear( 'label', { clase: 'escritoriowp-menu__etiqueta' }, [
						EWP.crear( 'input', {
							type: 'checkbox',
							checked: ! oculto,
							onChange: function () {
								alternarPanel( clave );
							}
						} ),
						EWP.crear( 'span', { texto: panel.etiqueta } )
					] ),
					EWP.crear( 'span', { clase: 'escritoriowp-menu__botones' }, [
						EWP.crear( 'button', {
							type: 'button',
							clase: 'escritoriowp-menu__boton',
							disabled: indice === 0,
							'aria-label': EWP.tf( 'subirPanel', panel.etiqueta ),
							title: EWP.tf( 'subirPanel', panel.etiqueta ),
							onClick: function () {
								moverPanel( clave, -1 );
							}
						}, [ EWP.icono( 'dashicons-arrow-up-alt2' ) ] ),
						EWP.crear( 'button', {
							type: 'button',
							clase: 'escritoriowp-menu__boton',
							disabled: indice === estado.orden.length - 1,
							'aria-label': EWP.tf( 'bajarPanel', panel.etiqueta ),
							title: EWP.tf( 'bajarPanel', panel.etiqueta ),
							onClick: function () {
								moverPanel( clave, 1 );
							}
						}, [ EWP.icono( 'dashicons-arrow-down-alt2' ) ] )
					] )
				] )
			);
		} );

		panelMenu.appendChild( EWP.crear( 'p', { clase: 'escritoriowp-menu__titulo', texto: EWP.t( 'tema' ) } ) );

		[
			{ valor: 'auto', texto: EWP.t( 'temaAuto' ) },
			{ valor: 'claro', texto: EWP.t( 'temaClaro' ) },
			{ valor: 'oscuro', texto: EWP.t( 'temaOscuro' ) }
		].forEach( function ( opcion ) {
			panelMenu.appendChild(
				EWP.crear( 'label', { clase: 'escritoriowp-menu__etiqueta' }, [
					EWP.crear( 'input', {
						type: 'radio',
						name: 'escritoriowp-tema',
						value: opcion.valor,
						checked: estado.preferencias.tema === opcion.valor,
						onChange: function () {
							estado.preferencias.tema = opcion.valor;
							aplicarTema();
							guardarPreferencias();
						}
					} ),
					EWP.crear( 'span', { texto: opcion.texto } )
				] )
			);
		} );

		panelMenu.appendChild(
			EWP.crear( 'button', {
				type: 'button',
				clase: 'escritoriowp-boton escritoriowp-boton--claro escritoriowp-menu__restablecer',
				texto: EWP.t( 'restablecer' ),
				onClick: restablecer
			} )
		);

		nodos.personalizar.appendChild( boton );
		nodos.personalizar.appendChild( panelMenu );
	}

	/**
	 * Muestra u oculta un panel.
	 *
	 * @param {string} clave Clave del panel.
	 * @return {void}
	 */
	function alternarPanel( clave ) {
		var ocultos = estado.preferencias.paneles_ocultos || [];

		estado.preferencias.paneles_ocultos = estaOculto( clave )
			? ocultos.filter( function ( item ) {
				return item !== clave;
			} )
			: ocultos.concat( [ clave ] );

		guardarPreferencias();
		pintarPersonalizar();
		pintarPaneles();
	}

	/**
	 * Cambia un panel de posición.
	 *
	 * @param {string} clave Clave del panel.
	 * @param {number} delta Desplazamiento.
	 * @return {void}
	 */
	function moverPanel( clave, delta ) {
		var indice = estado.orden.indexOf( clave );
		var destino = indice + delta;

		if ( indice === -1 || destino < 0 || destino >= estado.orden.length ) {
			return;
		}

		estado.orden.splice( indice, 1 );
		estado.orden.splice( destino, 0, clave );
		estado.preferencias.orden_paneles = estado.orden.slice();

		guardarPreferencias();
		pintarPersonalizar();
		pintarPaneles();
	}

	/**
	 * Devuelve las preferencias a sus valores por defecto.
	 *
	 * @return {void}
	 */
	function restablecer() {
		estado.preferencias = { paneles_ocultos: [], orden_paneles: [], tema: 'auto' };
		estado.orden = config.paneles.map( function ( panel ) {
			return panel.clave;
		} );

		aplicarTema();
		guardarPreferencias();
		pintarPersonalizar();
		pintarPaneles();
	}

	/**
	 * Aplica el tema elegido a la interfaz y al lanzador.
	 *
	 * @return {void}
	 */
	function aplicarTema() {
		var tema = estado.preferencias.tema || 'auto';

		EWP.config.tema = tema;
		nodos.raiz.setAttribute( 'data-tema', tema );

		var lanzador = global.document.querySelector( '.escritoriowp-lanzador' );

		if ( lanzador ) {
			lanzador.setAttribute( 'data-tema', tema );
		}
	}

	/**
	 * Guarda las preferencias en el servidor.
	 *
	 * @return {void}
	 */
	function guardarPreferencias() {
		EWP.enviar( 'preferencias', { cuerpo: estado.preferencias } ).catch( function () {
			// Si falla el guardado, la interfaz mantiene el cambio hasta la siguiente carga.
		} );
	}

	/* ----------------------------------------------------------------- Creación rápida */

	/**
	 * Define los campos del formulario de cada tipo.
	 *
	 * @param {string} tipo Clave del tipo.
	 * @return {Array} Campos del formulario.
	 */
	function camposDe( tipo ) {
		if ( tipo === 'usuarios' ) {
			return [
				{ nombre: 'login', etiqueta: EWP.t( 'campoLogin' ), tipo: 'text', requerido: true },
				{ nombre: 'email', etiqueta: EWP.t( 'campoEmail' ), tipo: 'email', requerido: true },
				{ nombre: 'nombre', etiqueta: EWP.t( 'campoNombre' ), tipo: 'text' },
				{ nombre: 'apellidos', etiqueta: EWP.t( 'campoApellidos' ), tipo: 'text' },
				{ nombre: 'rol', etiqueta: EWP.t( 'campoRol' ), tipo: 'select', opciones: config.roles.map( function ( rol ) {
					return { valor: rol.valor, texto: rol.etiqueta, defecto: rol.defecto };
				} ) },
				{ nombre: 'avisar', etiqueta: EWP.t( 'campoAvisoEmail' ), tipo: 'checkbox', valor: true }
			];
		}

		if ( tipo === 'productos' ) {
			return [
				{ nombre: 'titulo', etiqueta: EWP.t( 'campoNombre' ), tipo: 'text', requerido: true },
				{ nombre: 'precio', etiqueta: EWP.t( 'campoPrecio' ), tipo: 'text' },
				{ nombre: 'sku', etiqueta: EWP.t( 'campoSku' ), tipo: 'text' }
			];
		}

		var campos = [
			{ nombre: 'titulo', etiqueta: EWP.t( 'campoTitulo' ), tipo: 'text', requerido: true }
		];

		if ( tipo === 'entradas' ) {
			campos.push( { nombre: 'contenido', etiqueta: EWP.t( 'campoContenido' ), tipo: 'textarea' } );

			if ( config.categorias.length ) {
				campos.push( {
					nombre: 'categoria',
					etiqueta: EWP.t( 'campoCategoria' ),
					tipo: 'select',
					opciones: config.categorias.map( function ( categoria ) {
						return { valor: categoria.valor, texto: categoria.etiqueta };
					} )
				} );
			}
		}

		return campos;
	}

	/**
	 * Abre el modal de creación rápida.
	 *
	 * @param {string}      tipo    Clave del tipo.
	 * @param {HTMLElement} abridor Elemento que abrió el modal.
	 * @return {void}
	 */
	function abrirModal( tipo, abridor ) {
		cerrarModal();

		var panel = panelPorClave( tipo );

		if ( ! panel ) {
			return;
		}

		estado.abridor = abridor || null;

		var campos = camposDe( tipo );
		var controles = {};
		var contenido = EWP.crear( 'div', { clase: 'escritoriowp-modal__contenido' } );

		campos.forEach( function ( campo ) {
			var id = 'escritoriowp-campo-' + campo.nombre;
			var control;

			if ( campo.tipo === 'textarea' ) {
				control = EWP.crear( 'textarea', { id: id, rows: '4' } );
			} else if ( campo.tipo === 'select' ) {
				control = EWP.crear( 'select', { id: id }, ( campo.opciones || [] ).map( function ( opcion ) {
					return EWP.crear( 'option', {
						value: opcion.valor,
						texto: opcion.texto,
						selected: opcion.defecto ? 'selected' : null
					} );
				} ) );
			} else if ( campo.tipo === 'checkbox' ) {
				control = EWP.crear( 'input', { type: 'checkbox', id: id, checked: campo.valor ? 'checked' : null } );
			} else {
				control = EWP.crear( 'input', { type: campo.tipo, id: id } );
			}

			controles[ campo.nombre ] = control;

			var error = EWP.crear( 'p', { clase: 'escritoriowp-campo__error', hidden: true } );
			var etiqueta = EWP.crear( 'label', { for: id }, [
				EWP.crear( 'span', { texto: campo.etiqueta } ),
				campo.requerido ? null : EWP.crear( 'span', { clase: 'escritoriowp-campo__opcional', texto: EWP.t( 'opcional' ) } )
			] );

			contenido.appendChild(
				EWP.crear( 'div', {
					clase: 'escritoriowp-campo' + ( campo.tipo === 'checkbox' ? ' escritoriowp-campo--casilla' : '' ),
					datos: { campo: campo.nombre }
				}, campo.tipo === 'checkbox'
					? [ EWP.crear( 'label', { for: id }, [ control, EWP.crear( 'span', { texto: campo.etiqueta } ) ] ), error ]
					: [ etiqueta, control, error ] )
			);
		} );

		var selectorEstado = null;

		if ( tipo !== 'usuarios' ) {
			var opciones = [ EWP.crear( 'option', { value: 'draft', texto: EWP.t( 'estadoBorrador' ) } ) ];

			if ( panel.puedePublicar ) {
				opciones.push( EWP.crear( 'option', { value: 'publish', texto: EWP.t( 'estadoPublicado' ) } ) );
			}

			selectorEstado = EWP.crear( 'select', { id: 'escritoriowp-campo-estado' }, opciones );

			contenido.appendChild(
				EWP.crear( 'div', { clase: 'escritoriowp-campo', datos: { campo: 'estado' } }, [
					EWP.crear( 'label', { for: 'escritoriowp-campo-estado' }, [
						EWP.crear( 'span', { texto: EWP.t( 'campoEstado' ) } )
					] ),
					selectorEstado,
					panel.puedePublicar
						? null
						: EWP.crear( 'p', { clase: 'escritoriowp-campo__nota', texto: EWP.t( 'soloBorradores' ) } )
				] )
			);
		}

		var aviso = EWP.crear( 'p', { clase: 'escritoriowp-modal__aviso', hidden: true, role: 'alert' } );

		var enviar = EWP.crear( 'button', {
			type: 'submit',
			clase: 'escritoriowp-boton',
			texto: tipo === 'usuarios' ? EWP.t( 'crear' ) : EWP.t( 'guardarBorrador' )
		} );

		if ( selectorEstado ) {
			selectorEstado.addEventListener( 'change', function () {
				enviar.textContent = selectorEstado.value === 'publish' ? EWP.t( 'publicar' ) : EWP.t( 'guardarBorrador' );
			} );
		}

		var formulario = EWP.crear( 'form', {
			clase: 'escritoriowp-modal__formulario',
			onSubmit: function ( evento ) {
				evento.preventDefault();
				enviarFormulario( tipo, campos, controles, selectorEstado, aviso, enviar, contenido );
			}
		}, [
			contenido,
			aviso,
			EWP.crear( 'div', { clase: 'escritoriowp-modal__pie' }, [
				EWP.crear( 'button', {
					type: 'button',
					clase: 'escritoriowp-boton escritoriowp-boton--fantasma',
					texto: EWP.t( 'cancelar' ),
					onClick: cerrarModal
				} ),
				enviar
			] )
		] );

		abrirDialogo( panel.etiquetaNuevo, formulario );

		var primero = contenido.querySelector( 'input, textarea, select' );

		if ( primero ) {
			primero.focus();
		}
	}

	/**
	 * Envía el formulario de creación.
	 *
	 * @param {string}      tipo            Clave del tipo.
	 * @param {Array}       campos          Definición de campos.
	 * @param {Object}      controles       Controles del formulario.
	 * @param {HTMLElement} selectorEstado  Selector de estado, si lo hay.
	 * @param {HTMLElement} aviso           Nodo de aviso general.
	 * @param {HTMLElement} enviar          Botón de envío.
	 * @param {HTMLElement} contenido       Contenedor de campos.
	 * @return {void}
	 */
	function enviarFormulario( tipo, campos, controles, selectorEstado, aviso, enviar, contenido ) {
		var datos = {};

		campos.forEach( function ( campo ) {
			var control = controles[ campo.nombre ];

			datos[ campo.nombre ] = campo.tipo === 'checkbox' ? control.checked : control.value;
		} );

		if ( selectorEstado ) {
			datos.estado = selectorEstado.value;
		}

		limpiarErrores( contenido, aviso );

		var faltan = campos.filter( function ( campo ) {
			return campo.requerido && ! String( datos[ campo.nombre ] || '' ).trim();
		} );

		if ( faltan.length ) {
			faltan.forEach( function ( campo ) {
				marcarError( contenido, campo.nombre, EWP.t( 'obligatorio' ) );
			} );
			return;
		}

		enviar.disabled = true;
		enviar.textContent = EWP.t( 'cargando' );

		EWP.enviar( 'crear/' + tipo, { cuerpo: datos } )
			.then( function ( creado ) {
				pintarConfirmacion( tipo, creado );
				cargarPanelPorClave( tipo );
				cargarResumen( true );
			} )
			.catch( function ( error ) {
				enviar.disabled = false;
				enviar.textContent = selectorEstado && selectorEstado.value === 'publish'
					? EWP.t( 'publicar' )
					: ( tipo === 'usuarios' ? EWP.t( 'crear' ) : EWP.t( 'guardarBorrador' ) );

				var detalle = error && error.datos && error.datos.data ? error.datos.data : {};

				if ( detalle.campos ) {
					Object.keys( detalle.campos ).forEach( function ( nombre ) {
						marcarError( contenido, nombre, detalle.campos[ nombre ] );
					} );
					return;
				}

				aviso.textContent = ( error && error.message ) || EWP.t( 'errorGuardar' );
				aviso.hidden = false;
			} );
	}

	/**
	 * Limpia los errores del formulario.
	 *
	 * @param {HTMLElement} contenido Contenedor de campos.
	 * @param {HTMLElement} aviso     Nodo de aviso general.
	 * @return {void}
	 */
	function limpiarErrores( contenido, aviso ) {
		aviso.hidden = true;
		aviso.textContent = '';

		Array.prototype.forEach.call( contenido.querySelectorAll( '.escritoriowp-campo' ), function ( campo ) {
			campo.classList.remove( 'tiene-error' );

			var error = campo.querySelector( '.escritoriowp-campo__error' );

			if ( error ) {
				error.hidden = true;
				error.textContent = '';
			}
		} );
	}

	/**
	 * Marca un campo con su error.
	 *
	 * @param {HTMLElement} contenido Contenedor de campos.
	 * @param {string}      nombre    Nombre del campo.
	 * @param {string}      mensaje   Mensaje de error.
	 * @return {void}
	 */
	function marcarError( contenido, nombre, mensaje ) {
		var campo = contenido.querySelector( '[data-campo="' + nombre + '"]' );

		if ( ! campo ) {
			return;
		}

		campo.classList.add( 'tiene-error' );

		var error = campo.querySelector( '.escritoriowp-campo__error' );

		if ( error ) {
			error.textContent = mensaje;
			error.hidden = false;
		}
	}

	/**
	 * Pinta la confirmación tras crear un elemento.
	 *
	 * @param {string} tipo   Clave del tipo.
	 * @param {Object} creado Elemento creado.
	 * @return {void}
	 */
	function pintarConfirmacion( tipo, creado ) {
		var cuerpo = nodos.modal.querySelector( '.escritoriowp-modal__cuerpo' );

		EWP.vaciar( cuerpo ).appendChild(
			EWP.crear( 'div', { clase: 'escritoriowp-modal__exito' }, [
				EWP.icono( 'dashicons-yes-alt' ),
				EWP.crear( 'p', { texto: EWP.tf( 'creadoOk', creado.titulo ) } ),
				EWP.crear( 'div', { clase: 'escritoriowp-modal__pie' }, [
					EWP.crear( 'a', {
						clase: 'escritoriowp-boton',
						href: creado.urlEditar,
						texto: EWP.t( 'editar' )
					} ),
					creado.urlVer
						? EWP.crear( 'a', {
							clase: 'escritoriowp-boton escritoriowp-boton--claro',
							href: creado.urlVer,
							target: '_blank',
							rel: 'noopener',
							texto: EWP.t( 'ver' )
						} )
						: null,
					EWP.crear( 'button', {
						type: 'button',
						clase: 'escritoriowp-boton escritoriowp-boton--claro',
						texto: EWP.t( 'crearOtro' ),
						onClick: function () {
							abrirModal( tipo, estado.abridor );
						}
					} ),
					EWP.crear( 'button', {
						type: 'button',
						clase: 'escritoriowp-boton escritoriowp-boton--fantasma',
						texto: EWP.t( 'cerrar' ),
						onClick: cerrarModal
					} )
				] )
			] )
		);
	}

	/**
	 * Recarga el panel de un tipo.
	 *
	 * @param {string} clave Clave del panel.
	 * @return {void}
	 */
	function cargarPanelPorClave( clave ) {
		var seccion = nodos.paneles.querySelector( '[data-panel="' + clave + '"] .escritoriowp-panel__cuerpo' );

		if ( seccion ) {
			cargarPanel( clave, seccion );
		}
	}

	/* -------------------------------------------------------------------------- Modal */

	/**
	 * Abre un diálogo modal con el contenido indicado.
	 *
	 * @param {string}      titulo    Título del diálogo.
	 * @param {HTMLElement} contenido Contenido del diálogo.
	 * @return {void}
	 */
	function abrirDialogo( titulo, contenido ) {
		var cuerpo = EWP.crear( 'div', { clase: 'escritoriowp-modal__cuerpo' }, [ contenido ] );

		var dialogo = EWP.crear( 'div', {
			clase: 'escritoriowp-modal__dialogo',
			role: 'dialog',
			'aria-modal': 'true',
			'aria-labelledby': 'escritoriowp-modal-titulo'
		}, [
			EWP.crear( 'header', { clase: 'escritoriowp-modal__cabecera' }, [
				EWP.crear( 'h2', { id: 'escritoriowp-modal-titulo', texto: titulo } ),
				EWP.crear( 'button', {
					type: 'button',
					clase: 'escritoriowp-modal__cerrar',
					'aria-label': EWP.t( 'cerrar' ),
					onClick: cerrarModal
				}, [ EWP.icono( 'dashicons-no-alt' ) ] )
			] ),
			cuerpo
		] );

		nodos.modal = EWP.crear( 'div', {
			clase: 'escritoriowp-modal',
			'data-tema': estado.preferencias.tema || 'auto',
			onMousedown: function ( evento ) {
				if ( evento.target === nodos.modal ) {
					cerrarModal();
				}
			}
		}, [ dialogo ] );

		nodos.modal.addEventListener( 'keydown', function ( evento ) {
			if ( evento.key === 'Escape' ) {
				evento.preventDefault();
				cerrarModal();
			}
		} );

		global.document.body.appendChild( nodos.modal );
		estado.liberarFoco = EWP.atraparFoco( dialogo );
	}

	/**
	 * Cierra el modal y devuelve el foco.
	 *
	 * @return {void}
	 */
	function cerrarModal() {
		if ( ! nodos.modal ) {
			return;
		}

		if ( estado.liberarFoco ) {
			estado.liberarFoco();
			estado.liberarFoco = null;
		}

		nodos.modal.remove();
		nodos.modal = null;

		if ( estado.abridor && estado.abridor.focus ) {
			estado.abridor.focus();
		}
	}

	/* ------------------------------------------------------------------------ Arranque */

	/**
	 * Arranca la interfaz del escritorio.
	 *
	 * @return {void}
	 */
	function arrancar() {
		nodos.raiz = global.document.getElementById( 'escritoriowp-raiz' );

		if ( ! nodos.raiz ) {
			return;
		}

		nodos.resumen = nodos.raiz.querySelector( '[data-escritoriowp-resumen]' );
		nodos.paneles = nodos.raiz.querySelector( '[data-escritoriowp-paneles]' );
		nodos.personalizar = nodos.raiz.querySelector( '[data-escritoriowp-personalizar]' );

		estado.orden = ( function () {
			var disponibles = config.paneles.map( function ( panel ) {
				return panel.clave;
			} );
			var preferido = ( estado.preferencias.orden_paneles || [] ).filter( function ( clave ) {
				return disponibles.indexOf( clave ) !== -1;
			} );
			var resto = disponibles.filter( function ( clave ) {
				return preferido.indexOf( clave ) === -1;
			} );

			return preferido.concat( resto );
		} )();

		Array.prototype.forEach.call(
			nodos.raiz.querySelectorAll( '[data-escritoriowp-crear]' ),
			function ( boton ) {
				boton.addEventListener( 'click', function () {
					abrirModal( boton.dataset.escritoriowpCrear, boton );
				} );
			}
		);

		var refrescar = nodos.raiz.querySelector( '[data-escritoriowp-accion="refrescar"]' );

		if ( refrescar ) {
			refrescar.addEventListener( 'click', function () {
				cargarResumen( true );
				estado.orden.forEach( cargarPanelPorClave );
			} );
		}

		pintarPersonalizar();
		cargarResumen( false );
		pintarPaneles();
	}

	if ( typeof document !== 'undefined' ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', arrancar );
		} else {
			arrancar();
		}
	}
})( typeof window !== 'undefined' ? window : globalThis );
