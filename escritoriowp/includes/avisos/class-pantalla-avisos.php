<?php
/**
 * Pantalla «Avisos» del menú del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Ajustes;
use EscritorioWP\Escritorio\Preferencias;
use EscritorioWP\Escritorio\Recientes;
use EscritorioWP\Seguridad\Administradores;

defined( 'ABSPATH' ) || exit;

/**
 * Lista los avisos por correo guardados en el registro y muestra el detalle de cada uno.
 *
 * `puede_ver()` es la única comprobación de acceso a los avisos: la usan esta pantalla, el bloque del
 * escritorio y la ruta REST. No está en `Capacidades`, que describe tipos de contenido.
 */
final class PantallaAvisos {

	/**
	 * Identificador de la subpágina.
	 */
	public const PAGINA = 'escritoriowp-avisos';

	/**
	 * Capacidad que exigen la pantalla, el bloque del escritorio y la ruta REST.
	 */
	public const CAPACIDAD = 'manage_options';

	/**
	 * Identificador de pantalla que devuelve WordPress al registrar la subpágina.
	 *
	 * @var string
	 */
	private string $pantalla = '';

	/**
	 * Indica si el usuario actual puede ver los avisos.
	 *
	 * @return bool
	 */
	public static function puede_ver(): bool {
		return current_user_can( self::CAPACIDAD );
	}

	/**
	 * URL del listado.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGINA );
	}

	/**
	 * URL del detalle de un aviso, conservando los filtros y la página del listado del que se viene.
	 *
	 * @param int                  $id      Identificador del aviso.
	 * @param array<string, mixed> $filtros `tipo`, `estado` y `paged`; los vacíos no se añaden.
	 * @return string
	 */
	public static function url_detalle( int $id, array $filtros = array() ): string {
		return add_query_arg( array_merge( self::argumentos_listado( $filtros ), array( 'aviso' => $id ) ), self::url() );
	}

	/**
	 * Parámetros de la URL del listado con sus filtros válidos y su página, sin los vacíos.
	 *
	 * @param array<string, mixed> $filtros `tipo`, `estado` y `paged`.
	 * @return array<string, int|string>
	 */
	private static function argumentos_listado( array $filtros ): array {
		$validos = Registro::filtros( $filtros['tipo'] ?? '', $filtros['estado'] ?? '' );
		$pagina  = (int) ( $filtros['paged'] ?? 0 );

		if ( $pagina > 1 ) {
			$validos['paged'] = $pagina;
		}

		return array_filter( $validos, static fn( int|string $valor ): bool => '' !== $valor );
	}

	/**
	 * Registra los enganches del módulo.
	 *
	 * @return void
	 */
	public function registrar(): void {
		/*
		 * Prioridad 12: «Administradores» y «Acerca de» se registran en la 11, así que ya están y la
		 * posición cae siempre justo antes de «Acerca de», sea cual sea el orden en que se componen los
		 * módulos.
		 */
		add_action( 'admin_menu', array( $this, 'anadir_pagina' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar' ) );
	}

	/**
	 * Añade la subpágina al menú del plugin, detrás de «Administradores» o, si el usuario no la tiene,
	 * de «Ajustes».
	 *
	 * @return void
	 */
	public function anadir_pagina(): void {
		$this->pantalla = (string) add_submenu_page(
			Ajustes::PAGINA,
			__( 'Avisos por correo', 'escritoriowp' ),
			__( 'Avisos', 'escritoriowp' ),
			self::CAPACIDAD,
			self::PAGINA,
			array( $this, 'pintar' ),
			Administradores::puede_ver() ? 2 : 1
		);
	}

	/**
	 * Encola la hoja de estilo de la pantalla.
	 *
	 * @param string $pantalla Identificador de la pantalla actual.
	 * @return void
	 */
	public function encolar( string $pantalla ): void {
		if ( '' === $this->pantalla || $this->pantalla !== $pantalla ) {
			return;
		}

		wp_enqueue_style( 'escritoriowp-ajustes' );
	}

	/**
	 * Nombre de un tipo de aviso.
	 *
	 * @param string $tipo Uno de `Registro::TIPOS`.
	 * @return string
	 */
	public static function nombre_tipo( string $tipo ): string {
		return Registro::ADMINISTRADORES === $tipo ? __( 'Administradores', 'escritoriowp' ) : AvisosPlugins::etiqueta_evento( $tipo );
	}

	/**
	 * Nombre de un estado del envío.
	 *
	 * @param string $estado Uno de `Registro::ESTADOS`.
	 * @return string
	 */
	public static function nombre_estado( string $estado ): string {
		return match ( $estado ) {
			Registro::ENVIADO   => __( 'Enviado', 'escritoriowp' ),
			Registro::FALLIDO   => __( 'Fallido', 'escritoriowp' ),
			Registro::PENDIENTE => __( 'Sin confirmar', 'escritoriowp' ),
			default             => $estado,
		};
	}

	/**
	 * Fila de un aviso para el bloque del escritorio, con la forma de las filas de los paneles y un
	 * icono en el lugar de la inicial. Los nombres van ya traducidos.
	 *
	 * @param array<string, int|string> $aviso Aviso de `Registro::recientes()`.
	 * @return array<string, mixed>
	 */
	public static function fila( array $aviso ): array {
		$tipo   = (string) ( $aviso['tipo'] ?? '' );
		$estado = (string) ( $aviso['estado'] ?? '' );
		$marca  = strtotime( (string) ( $aviso['fecha'] ?? '' ) . ' UTC' );

		return array(
			'titulo'    => self::asunto( (string) ( $aviso['asunto'] ?? '' ) ),
			'urlEditar' => self::url_detalle( (int) ( $aviso['id'] ?? 0 ) ),
			'urlVer'    => '',
			'imagen'    => '',
			'inicial'   => '',
			'icono'     => Registro::ADMINISTRADORES === $tipo ? 'dashicons-shield' : 'dashicons-admin-plugins',
			'metas'     => array(
				array(
					'texto' => self::nombre_estado( $estado ),
					'tono'  => Registro::tono( $estado ),
				),
				array(
					'texto' => self::nombre_tipo( $tipo ),
					'tono'  => '',
				),
				array(
					'texto' => Recientes::fecha_relativa( false === $marca ? 0 : $marca ),
					'tono'  => '',
				),
			),
		);
	}

	/**
	 * Fecha y hora de un aviso en el formato y la zona horaria del sitio. Se guarda en UTC.
	 *
	 * @param string $fecha Fecha de la base de datos.
	 * @return string
	 */
	private static function fecha( string $fecha ): string {
		$marca = strtotime( $fecha . ' UTC' );

		if ( false === $marca || $marca <= 0 ) {
			return '—';
		}

		return (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $marca );
	}

	/**
	 * Pinta la pantalla: el detalle de un aviso si se pide uno y, si no, el listado.
	 *
	 * @return void
	 */
	public function pintar(): void {
		if ( ! self::puede_ver() ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'escritoriowp' ) );
		}

		/*
		 * Los parámetros solo eligen qué se ve: ni el listado ni el detalle cambian nada, así que no
		 * procede un nonce. Los filtros pasan por `Registro::filtros()`, que solo deja valores
		 * conocidos, y los números por `absint()`.
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filtros = Registro::filtros(
			isset( $_GET['tipo'] ) ? sanitize_text_field( wp_unslash( $_GET['tipo'] ) ) : '',
			isset( $_GET['estado'] ) ? sanitize_text_field( wp_unslash( $_GET['estado'] ) ) : ''
		);
		$pagina  = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
		$aviso   = isset( $_GET['aviso'] ) ? absint( wp_unslash( $_GET['aviso'] ) ) : 0;
		// phpcs:enable

		$tema = (string) Preferencias::obtener()['tema'];

		?>
		<div class="wrap escritoriowp-avisos">
			<h1><?php echo esc_html( $aviso > 0 ? __( 'Aviso por correo', 'escritoriowp' ) : __( 'Avisos por correo', 'escritoriowp' ) ); ?></h1>

			<div class="escritoriowp" data-tema="<?php echo esc_attr( $tema ); ?>">
				<?php
				if ( $aviso > 0 ) {
					$this->pintar_detalle( $aviso, array_merge( $filtros, array( 'paged' => $pagina ) ) );
				} else {
					$this->pintar_listado( $filtros, $pagina );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Pinta el listado: introducción, filtros, total, tabla y paginación.
	 *
	 * @param array{tipo: string, estado: string} $filtros Filtros ya validados.
	 * @param int                                 $pedida  Página pedida.
	 * @return void
	 */
	private function pintar_listado( array $filtros, int $pedida ): void {
		$total         = Registro::contar( $filtros );
		$total_paginas = Registro::total_paginas( $total );
		$pagina        = Registro::pagina( $pedida, $total_paginas );
		$con_filtros   = '' !== $filtros['tipo'] || '' !== $filtros['estado'];

		?>
		<p class="escritoriowp-avisos__intro">
			<?php echo esc_html__( 'Aquí se guardan todos los avisos por correo que EscritorioWP intenta enviar, también los que no se pudieron enviar. «Enviado» quiere decir que el servidor de correo del sitio aceptó el mensaje, no que haya llegado al buzón.', 'escritoriowp' ); ?>
		</p>
		<?php

		if ( 0 === $total && ! $con_filtros ) {
			?>
			<p class="escritoriowp-avisos__vacio">
				<?php echo esc_html__( 'Todavía no se ha guardado ningún aviso.', 'escritoriowp' ); ?>
				<a href="<?php echo esc_url( Ajustes::url() ); ?>"><?php echo esc_html__( 'Elige qué avisos quieres recibir en los ajustes.', 'escritoriowp' ); ?></a>
			</p>
			<?php

			return;
		}

		$this->pintar_filtros( $filtros, $con_filtros );

		if ( 0 === $total ) {
			?>
			<p class="escritoriowp-avisos__vacio">
				<?php echo esc_html__( 'Ningún aviso coincide con estos filtros.', 'escritoriowp' ); ?>
				<a href="<?php echo esc_url( self::url() ); ?>"><?php echo esc_html__( 'Quitar filtros', 'escritoriowp' ); ?></a>
			</p>
			<?php

			return;
		}

		$numero = number_format_i18n( $total );

		?>
		<p class="escritoriowp-avisos__total">
			<?php
			echo esc_html(
				$con_filtros
					/* translators: %s: número de avisos. */
					? sprintf( _n( '%s aviso coincide con los filtros.', '%s avisos coinciden con los filtros.', $total, 'escritoriowp' ), $numero )
					/* translators: %s: número de avisos. */
					: sprintf( _n( '%s aviso guardado.', '%s avisos guardados.', $total, 'escritoriowp' ), $numero )
			);
			?>
		</p>

		<table class="escritoriowp-tabla escritoriowp-avisos__tabla">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Fecha', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Tipo', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Asunto', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Destinatarios', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Estado', 'escritoriowp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( Registro::listar( $filtros, $pagina ) as $aviso ) : ?>
					<tr>
						<td class="escritoriowp-avisos__fecha" data-columna="<?php echo esc_attr__( 'Fecha', 'escritoriowp' ); ?>"><?php echo esc_html( self::fecha( (string) $aviso['fecha'] ) ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Tipo', 'escritoriowp' ); ?>"><?php echo esc_html( self::nombre_tipo( (string) $aviso['tipo'] ) ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Asunto', 'escritoriowp' ); ?>">
							<a href="<?php echo esc_url( self::url_detalle( (int) $aviso['id'], array_merge( $filtros, array( 'paged' => $pagina ) ) ) ); ?>"><?php echo esc_html( self::asunto( (string) $aviso['asunto'] ) ); ?></a>
						</td>
						<td class="escritoriowp-avisos__destinatarios" data-columna="<?php echo esc_attr__( 'Destinatarios', 'escritoriowp' ); ?>"><?php echo esc_html( (string) $aviso['destinatarios'] ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Estado', 'escritoriowp' ); ?>"><?php self::pintar_estado( (string) $aviso['estado'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php

		$this->pintar_paginacion( $filtros, $pagina, $total_paginas );
	}

	/**
	 * Pinta el formulario de filtros. Va por GET y no cambia nada.
	 *
	 * @param array{tipo: string, estado: string} $filtros     Filtros ya validados.
	 * @param bool                                $con_filtros Si hay alguno aplicado.
	 * @return void
	 */
	private function pintar_filtros( array $filtros, bool $con_filtros ): void {
		?>
		<form class="escritoriowp-avisos__filtros" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGINA ); ?>">

			<span class="escritoriowp-avisos__filtro">
				<label for="escritoriowp-avisos-tipo"><?php echo esc_html__( 'Tipo', 'escritoriowp' ); ?></label>
				<select id="escritoriowp-avisos-tipo" name="tipo">
					<option value=""><?php echo esc_html__( 'Todos los tipos', 'escritoriowp' ); ?></option>
					<?php foreach ( Registro::TIPOS as $tipo ) : ?>
						<option value="<?php echo esc_attr( $tipo ); ?>" <?php selected( $filtros['tipo'], $tipo ); ?>><?php echo esc_html( self::nombre_tipo( $tipo ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</span>

			<span class="escritoriowp-avisos__filtro">
				<label for="escritoriowp-avisos-estado"><?php echo esc_html__( 'Estado', 'escritoriowp' ); ?></label>
				<select id="escritoriowp-avisos-estado" name="estado">
					<option value=""><?php echo esc_html__( 'Todos los estados', 'escritoriowp' ); ?></option>
					<?php foreach ( Registro::ESTADOS as $estado ) : ?>
						<option value="<?php echo esc_attr( $estado ); ?>" <?php selected( $filtros['estado'], $estado ); ?>><?php echo esc_html( self::nombre_estado( $estado ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</span>

			<button type="submit" class="escritoriowp-boton escritoriowp-boton--claro"><?php echo esc_html__( 'Filtrar', 'escritoriowp' ); ?></button>

			<?php if ( $con_filtros ) : ?>
				<a class="escritoriowp-boton escritoriowp-boton--enlace" href="<?php echo esc_url( self::url() ); ?>"><?php echo esc_html__( 'Quitar filtros', 'escritoriowp' ); ?></a>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Pinta la paginación, si hay más de una página.
	 *
	 * Los filtros van en la base de los enlaces y no en `add_args`: `paginate_links()` copia en los
	 * enlaces los parámetros de la URL actual que no estén en la base, y así un valor de filtro
	 * desconocido no pasaría a la página siguiente.
	 *
	 * @param array{tipo: string, estado: string} $filtros       Filtros ya validados.
	 * @param int                                 $pagina        Página actual.
	 * @param int                                 $total_paginas Páginas que hay.
	 * @return void
	 */
	private function pintar_paginacion( array $filtros, int $pagina, int $total_paginas ): void {
		$enlaces = paginate_links(
			array(
				'base'      => add_query_arg( array_merge( $filtros, array( 'paged' => '%#%' ) ), self::url() ),
				'format'    => '',
				'current'   => $pagina,
				'total'     => $total_paginas,
				'prev_text' => __( '‹ Anterior', 'escritoriowp' ),
				'next_text' => __( 'Siguiente ›', 'escritoriowp' ),
			)
		);

		if ( ! is_string( $enlaces ) || '' === $enlaces ) {
			return;
		}

		printf(
			'<nav class="escritoriowp-avisos__paginas" aria-label="%1$s">%2$s</nav>',
			esc_attr__( 'Páginas de avisos', 'escritoriowp' ),
			wp_kses_post( $enlaces )
		);
	}

	/**
	 * Pinta el detalle de un aviso, o que no existe.
	 *
	 * @param int                  $id     Identificador del aviso.
	 * @param array<string, mixed> $volver Filtros y página del listado al que vuelve el enlace.
	 * @return void
	 */
	private function pintar_detalle( int $id, array $volver ): void {
		$aviso = Registro::obtener( $id );

		?>
		<p class="escritoriowp-avisos__volver">
			<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( add_query_arg( self::argumentos_listado( $volver ), self::url() ) ); ?>">
				<?php echo esc_html__( '← Volver a los avisos', 'escritoriowp' ); ?>
			</a>
		</p>
		<?php

		if ( null === $aviso ) {
			?>
			<p class="escritoriowp-avisos__vacio"><?php echo esc_html__( 'Este aviso no existe.', 'escritoriowp' ); ?></p>
			<?php

			return;
		}

		$estado = (string) $aviso['estado'];

		?>
		<h2 class="escritoriowp-avisos__asunto"><?php echo esc_html( self::asunto( (string) $aviso['asunto'] ) ); ?></h2>

		<dl class="escritoriowp-avisos__datos">
			<dt><?php echo esc_html__( 'Fecha', 'escritoriowp' ); ?></dt>
			<dd><?php echo esc_html( self::fecha( (string) $aviso['fecha'] ) ); ?></dd>

			<dt><?php echo esc_html__( 'Tipo', 'escritoriowp' ); ?></dt>
			<dd><?php echo esc_html( self::nombre_tipo( (string) $aviso['tipo'] ) ); ?></dd>

			<dt><?php echo esc_html__( 'Estado', 'escritoriowp' ); ?></dt>
			<dd>
				<?php self::pintar_estado( $estado ); ?>
				<?php if ( Registro::PENDIENTE === $estado ) : ?>
					<span class="escritoriowp-avisos__nota"><?php echo esc_html__( 'La petición terminó antes de saber si el correo se envió.', 'escritoriowp' ); ?></span>
				<?php endif; ?>
			</dd>

			<dt><?php echo esc_html__( 'Destinatarios', 'escritoriowp' ); ?></dt>
			<dd class="escritoriowp-avisos__destinatarios"><?php echo esc_html( (string) $aviso['destinatarios'] ); ?></dd>

			<?php if ( Registro::FALLIDO === $estado ) : ?>
				<dt><?php echo esc_html__( 'Motivo del fallo', 'escritoriowp' ); ?></dt>
				<dd>
					<?php
					echo esc_html(
						'' === (string) $aviso['motivo']
							? __( 'La función de correo de WordPress no aceptó el mensaje y no dio ningún motivo.', 'escritoriowp' )
							: (string) $aviso['motivo']
					);
					?>
				</dd>
			<?php endif; ?>
		</dl>

		<h3 class="escritoriowp-avisos__titulo-mensaje"><?php echo esc_html__( 'Mensaje', 'escritoriowp' ); ?></h3>
		<div class="escritoriowp-avisos__mensaje"><?php echo esc_html( (string) $aviso['mensaje'] ); ?></div>
		<?php
	}

	/**
	 * Pinta la etiqueta de color de un estado.
	 *
	 * @param string $estado Uno de `Registro::ESTADOS`.
	 * @return void
	 */
	private static function pintar_estado( string $estado ): void {
		printf(
			'<span class="escritoriowp-etiqueta" data-tono="%1$s">%2$s</span>',
			esc_attr( Registro::tono( $estado ) ),
			esc_html( self::nombre_estado( $estado ) )
		);
	}

	/**
	 * Asunto de un aviso, o un texto si está vacío, para que el enlace tenga algo que pulsar.
	 *
	 * @param string $asunto Asunto guardado.
	 * @return string
	 */
	private static function asunto( string $asunto ): string {
		return '' === trim( $asunto ) ? __( '(sin asunto)', 'escritoriowp' ) : $asunto;
	}
}
