<?php
/**
 * Pantalla «Administradores» del menú del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Seguridad;

use EscritorioWP\Ajustes;
use EscritorioWP\Escritorio\Preferencias;

defined( 'ABSPATH' ) || exit;

/**
 * Lista a los administradores y marca a los usuarios con permisos de administrador sin el rol.
 *
 * Toda la detección la hace `Detector`; esta clase registra la pantalla y la pinta.
 */
final class Administradores {

	/**
	 * Identificador de la subpágina.
	 */
	public const PAGINA = 'escritoriowp-administradores';

	/**
	 * Capacidades que exigen la pantalla y la retirada de permisos.
	 *
	 * @var string[]
	 */
	public const CAPACIDADES = array( 'manage_options', 'list_users' );

	/**
	 * Acción de `admin-post.php` que retira los permisos sueltos. El nonce añade el identificador del
	 * usuario, para que no sirva con otro.
	 */
	public const ACCION = 'escritoriowp_retirar_permisos';

	/**
	 * Identificador de pantalla que devuelve WordPress al registrar la subpágina.
	 *
	 * @var string
	 */
	private string $pantalla = '';

	/**
	 * Informe del análisis, si ya se hizo en esta petición.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $informe = null;

	/**
	 * URL de la pantalla.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGINA );
	}

	/**
	 * Indica si el usuario actual puede ver la pantalla.
	 *
	 * @return bool
	 */
	public static function puede_ver(): bool {
		foreach ( self::CAPACIDADES as $capacidad ) {
			if ( ! current_user_can( $capacidad ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Registra los enganches del módulo.
	 *
	 * @return void
	 */
	public function registrar(): void {
		// Prioridad 11: el menú del plugin lo crea Ajustes en la prioridad 10.
		add_action( 'admin_menu', array( $this, 'anadir_pagina' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar' ) );
		add_action( 'admin_post_' . self::ACCION, array( $this, 'retirar' ) );
	}

	/**
	 * Retira los permisos críticos sueltos de un usuario y vuelve a la pantalla con el resultado.
	 *
	 * Repite todas las comprobaciones de la confirmación y vuelve a leer la base de datos: lo que se
	 * quita es lo que el usuario tiene ahora, no lo que había al pintar la confirmación. Usa
	 * `WP_User::remove_cap()` y no SQL para que se disparen los enganches de WordPress (la caché del
	 * contador se invalida sola y los plugins de registro de actividad lo anotan) y se recalcule
	 * `user_level`.
	 *
	 * @return void
	 */
	public function retirar(): void {
		/*
		 * `admin-post.php` dispara esta acción también por GET. Solo un POST puede cambiar algo; el
		 * resto vuelve a la pantalla sin tocar nada.
		 */
		if ( 'POST' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
			wp_safe_redirect( self::url() );
			exit;
		}

		$id = isset( $_POST['usuario'] ) ? absint( wp_unslash( $_POST['usuario'] ) ) : 0;

		check_admin_referer( self::ACCION . '_' . $id );

		if ( ! self::puede_ver() ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'escritoriowp' ), '', array( 'response' => 403 ) );
		}

		$usuario = Detector::analizar_usuario( $id );
		$motivo  = self::motivo_rechazo( $id, $usuario );

		if ( '' !== $motivo || null === $usuario ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'rechazo' => $motivo,
						'usuario' => $id,
					),
					self::url()
				)
			);
			exit;
		}

		$sueltos    = Detector::sueltos( $usuario );
		$wp_usuario = new \WP_User( $id );

		foreach ( $sueltos as $permiso ) {
			$wp_usuario->remove_cap( $permiso );
		}

		$despues   = Detector::analizar_usuario( $id );
		$quedan    = array_values( array_intersect( $sueltos, null === $despues ? array() : Detector::sueltos( $despues ) ) );
		$retirados = array_values( array_diff( $sueltos, $quedan ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'retirados' => implode( ',', $retirados ),
					'quedan'    => implode( ',', $quedan ),
					'usuario'   => $id,
				),
				self::url()
			)
		);
		exit;
	}

	/**
	 * Añade la subpágina al menú del plugin, justo detrás de «Ajustes».
	 *
	 * `add_submenu_page()` solo admite una capacidad: la otra se comprueba antes de registrarla, así
	 * que sin ella la página no existe y la URL directa da la denegación de WordPress.
	 *
	 * @return void
	 */
	public function anadir_pagina(): void {
		if ( ! self::puede_ver() ) {
			return;
		}

		/*
		 * En su propia pantalla se analiza ya, y no se lee la caché: el menú se pinta antes que la
		 * página y el contador tiene que coincidir con el resumen. Pintar reutiliza este informe.
		 */
		$total = $this->en_su_pantalla() ? (int) $this->informe()['total_marcados'] : Detector::contar();

		$this->pantalla = (string) add_submenu_page(
			Ajustes::PAGINA,
			__( 'Administradores', 'escritoriowp' ),
			__( 'Administradores', 'escritoriowp' ) . self::contador( $total ),
			'manage_options',
			self::PAGINA,
			array( $this, 'pintar' ),
			1
		);

		$this->anadir_contador_menu( $total );
	}

	/**
	 * Marcado del contador de usuarios marcados, igual que el de comentarios o actualizaciones de
	 * WordPress, con su texto para lectores de pantalla. Vacío si no hay ninguno.
	 *
	 * @param int $total Usuarios marcados.
	 * @return string
	 */
	public static function contador( int $total ): string {
		if ( $total <= 0 ) {
			return '';
		}

		$numero = number_format_i18n( $total );

		return sprintf(
			' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%2$s</span><span class="screen-reader-text">%3$s</span></span>',
			$total,
			esc_html( $numero ),
			esc_html(
				sprintf(
					/* translators: %s: número de usuarios que requieren revisión. */
					_n( '%s usuario que requiere revisión', '%s usuarios que requieren revisión', $total, 'escritoriowp' ),
					$numero
				)
			)
		);
	}

	/**
	 * Añade el contador a la entrada de primer nivel «EscritorioWP», para que se vea con el menú
	 * cerrado.
	 *
	 * @param int $total Usuarios marcados.
	 * @return void
	 */
	private function anadir_contador_menu( int $total ): void {
		global $menu;

		$contador = self::contador( $total );

		if ( '' === $contador || ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $posicion => $elemento ) {
			if ( is_array( $elemento ) && Ajustes::PAGINA === ( $elemento[2] ?? '' ) ) {
				/*
				 * WordPress no ofrece una API para cambiar el título de un menú ya registrado; el propio
				 * núcleo añade así los contadores de «Comentarios» y «Plugins». Solo se toca la entrada
				 * del plugin.
				 */
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$menu[ $posicion ][0] .= $contador;

				return;
			}
		}
	}

	/**
	 * Indica si la petición actual es la de esta pantalla.
	 *
	 * WordPress rellena `$plugin_page` antes de construir el menú.
	 *
	 * @return bool
	 */
	private function en_su_pantalla(): bool {
		global $plugin_page;

		return self::PAGINA === $plugin_page;
	}

	/**
	 * Informe del análisis, calculado una sola vez por petición.
	 *
	 * @return array<string, mixed>
	 */
	private function informe(): array {
		$this->informe ??= Detector::analizar();

		return $this->informe;
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
	 * Pinta la pantalla.
	 *
	 * @return void
	 */
	public function pintar(): void {
		if ( ! self::puede_ver() ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'escritoriowp' ) );
		}

		$preferencias = Preferencias::obtener();

		/*
		 * El parámetro solo elige qué vista pintar: la confirmación no cambia nada, así que no procede
		 * un nonce aquí. El cambio lo hace la acción de `admin-post.php`, que sí lo exige.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$retirar = isset( $_GET['retirar'] ) ? absint( wp_unslash( $_GET['retirar'] ) ) : 0;

		if ( $retirar > 0 ) {
			$this->pintar_confirmacion( $retirar, (string) $preferencias['tema'] );

			return;
		}

		$informe = $this->informe();

		?>
		<div class="wrap escritoriowp-administradores">
			<h1><?php echo esc_html__( 'Administradores', 'escritoriowp' ); ?></h1>

			<?php $this->pintar_resultado(); ?>

			<div class="escritoriowp" data-tema="<?php echo esc_attr( $preferencias['tema'] ); ?>">
				<p class="escritoriowp-administradores__intro">
					<?php echo esc_html__( 'Quién es administrador en este sitio y quién, sin tener ese rol, tiene guardados permisos que dan control del sitio. Es una forma habitual de esconder una puerta trasera: la cuenta aparece con un rol normal en la pantalla Usuarios.', 'escritoriowp' ); ?>
				</p>

				<?php
				$this->pintar_resumen( $informe['total_marcados'] );
				$this->pintar_administradores( $informe['administradores'] );
				$this->pintar_marcados( $informe['marcados'], $informe['restantes'], $informe['nombres_roles'], array() !== $informe['roles_alterados'] );
				$this->pintar_roles_alterados( $informe['roles_alterados'] );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Motivo por el que no se pueden retirar los permisos sueltos de un usuario, o cadena vacía si se
	 * puede.
	 *
	 * La vista de confirmación y la acción usan la misma comprobación.
	 *
	 * @param int                       $id      Usuario.
	 * @param array<string, mixed>|null $usuario Usuario analizado con `Detector::analizar_usuario()`.
	 * @return string `propio`, `no-aplica`, `sin-sueltos`, `permisos` o vacío.
	 */
	public static function motivo_rechazo( int $id, ?array $usuario ): string {
		if ( get_current_user_id() === $id ) {
			return 'propio';
		}

		if ( null === $usuario || ! empty( $usuario['administrador'] ) || ! empty( $usuario['superadmin'] ) ) {
			return 'no-aplica';
		}

		if ( array() === Detector::sueltos( $usuario ) ) {
			return 'sin-sueltos';
		}

		if ( ! current_user_can( 'promote_user', $id ) ) {
			return 'permisos';
		}

		return '';
	}

	/**
	 * Texto de cada motivo de rechazo.
	 *
	 * @param string $motivo Motivo devuelto por `motivo_rechazo()`.
	 * @return string
	 */
	private static function texto_rechazo( string $motivo ): string {
		return match ( $motivo ) {
			'propio'      => __( 'No puedes retirarte tus propios permisos.', 'escritoriowp' ),
			'sin-sueltos' => __( 'Este usuario no tiene permisos de administrador asignados que retirar.', 'escritoriowp' ),
			'permisos'    => __( 'No tienes permiso para cambiar el rol de este usuario.', 'escritoriowp' ),
			default       => __( 'Este usuario no está marcado: es administrador o no tiene permisos de administrador.', 'escritoriowp' ),
		};
	}

	/**
	 * Pinta la confirmación previa a retirar los permisos sueltos de un usuario. No cambia nada.
	 *
	 * @param int    $id   Usuario.
	 * @param string $tema Tema del usuario actual.
	 * @return void
	 */
	private function pintar_confirmacion( int $id, string $tema ): void {
		$usuario = Detector::analizar_usuario( $id );
		$motivo  = self::motivo_rechazo( $id, $usuario );

		?>
		<div class="wrap escritoriowp-administradores">
			<h1><?php echo esc_html__( 'Retirar permisos', 'escritoriowp' ); ?></h1>

			<div class="escritoriowp" data-tema="<?php echo esc_attr( $tema ); ?>">
				<div class="escritoriowp-administradores__confirmacion">
					<?php if ( '' !== $motivo || null === $usuario ) : ?>
						<p><?php echo esc_html( self::texto_rechazo( $motivo ) ); ?></p>
						<p>
							<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( self::url() ); ?>">
								<?php echo esc_html__( 'Volver a Administradores', 'escritoriowp' ); ?>
							</a>
						</p>
					<?php else : ?>
						<?php
						$sueltos   = Detector::sueltos( $usuario );
						$del_rol   = array_diff( array_keys( (array) $usuario['criticos'] ), $sueltos );
						$nombres   = Detector::nombres_roles();
						$sus_roles = $this->roles_usuario( $usuario, $nombres );
						?>
						<p>
							<?php
							printf(
								/* translators: 1: nombre visible del usuario, 2: nombre de usuario. */
								esc_html__( 'Vas a quitar a %1$s (%2$s) estos permisos de administrador, que tiene asignados directamente:', 'escritoriowp' ),
								'<strong>' . esc_html( (string) $usuario['nombre'] ) . '</strong>',
								esc_html( (string) $usuario['login'] )
							);
							?>
						</p>
						<ul class="escritoriowp-administradores__permisos">
							<?php foreach ( $sueltos as $permiso ) : ?>
								<li><code><?php echo esc_html( $permiso ); ?></code></li>
							<?php endforeach; ?>
						</ul>
						<p>
							<?php
							printf(
								/* translators: %s: roles del usuario. */
								esc_html__( 'Su rol (%s) y el resto de sus permisos no cambian.', 'escritoriowp' ),
								esc_html( $sus_roles )
							);
							?>
						</p>
						<?php if ( array() !== $del_rol ) : ?>
							<p>
								<?php
								printf(
									/* translators: %s: permisos críticos que vienen del rol. */
									esc_html( _n( 'Seguirá teniendo %s, que le viene de su rol. EscritorioWP no modifica roles.', 'Seguirá teniendo %s, que le vienen de su rol. EscritorioWP no modifica roles.', count( $del_rol ), 'escritoriowp' ) ),
									esc_html( implode( ', ', $del_rol ) )
								);
								?>
							</p>
						<?php endif; ?>

						<form class="escritoriowp-administradores__acciones" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="<?php echo esc_attr( self::ACCION ); ?>">
							<input type="hidden" name="usuario" value="<?php echo esc_attr( (string) $id ); ?>">
							<?php wp_nonce_field( self::ACCION . '_' . $id ); ?>
							<button type="submit" class="escritoriowp-boton escritoriowp-boton--peligro">
								<?php echo esc_html__( 'Retirar permisos', 'escritoriowp' ); ?>
							</button>
							<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( self::url() ); ?>">
								<?php echo esc_html__( 'Cancelar', 'escritoriowp' ); ?>
							</a>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Pinta el resultado de una retirada de permisos, si se viene de hacerla.
	 *
	 * Los parámetros solo eligen el mensaje; los nombres de permiso se filtran contra la lista de
	 * permisos críticos, así que la URL no puede inyectar otro texto.
	 *
	 * @return void
	 */
	private function pintar_resultado(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$id        = isset( $_GET['usuario'] ) ? absint( wp_unslash( $_GET['usuario'] ) ) : 0;
		$rechazo   = isset( $_GET['rechazo'] ) ? sanitize_key( wp_unslash( $_GET['rechazo'] ) ) : '';
		$retirados = isset( $_GET['retirados'] ) ? self::permisos_de_url( sanitize_text_field( wp_unslash( $_GET['retirados'] ) ) ) : null;
		$quedan    = isset( $_GET['quedan'] ) ? self::permisos_de_url( sanitize_text_field( wp_unslash( $_GET['quedan'] ) ) ) : array();
		// phpcs:enable

		if ( '' !== $rechazo ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( self::texto_rechazo( $rechazo ) ) );

			return;
		}

		if ( null === $retirados || $id <= 0 ) {
			return;
		}

		$usuario = Detector::analizar_usuario( $id );
		$login   = null === $usuario ? '#' . $id : (string) $usuario['login'];

		if ( array() !== $retirados ) {
			printf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: número de permisos retirados, 2: nombre de usuario, 3: lista de permisos. */
						_n( 'Se ha retirado %1$s permiso a %2$s: %3$s. Si era legítimo, puedes devolverlo con un editor de roles.', 'Se han retirado %1$s permisos a %2$s: %3$s. Si alguno era legítimo, puedes devolverlo con un editor de roles.', count( $retirados ), 'escritoriowp' ),
						number_format_i18n( count( $retirados ) ),
						$login,
						implode( ', ', $retirados )
					)
				)
			);
		}

		if ( array() !== $quedan ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: nombre de usuario, 2: lista de permisos. */
						__( 'No se han podido retirar a %1$s estos permisos: %2$s.', 'escritoriowp' ),
						$login,
						implode( ', ', $quedan )
					)
				)
			);
		}

		if ( array() === $retirados && array() === $quedan ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( self::texto_rechazo( 'sin-sueltos' ) ) );
		}
	}

	/**
	 * Lista de permisos críticos a partir del parámetro de la URL; descarta todo lo demás.
	 *
	 * @param string $valor Permisos separados por comas.
	 * @return string[]
	 */
	private static function permisos_de_url( string $valor ): array {
		return array_values( array_intersect( Permisos::CRITICOS, explode( ',', $valor ) ) );
	}

	/**
	 * Pinta el estado general: cuántos usuarios requieren revisión, o que todo está en orden.
	 *
	 * @param int $total Usuarios marcados.
	 * @return void
	 */
	private function pintar_resumen( int $total ): void {
		if ( 0 === $total ) {
			?>
			<div class="escritoriowp-administradores__resumen" data-estado="correcto">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<p><?php echo esc_html__( 'No hay usuarios con permisos de administrador fuera del rol Administrador ni administradores ocultos.', 'escritoriowp' ); ?></p>
			</div>
			<?php

			return;
		}

		?>
		<div class="escritoriowp-administradores__resumen" data-estado="alerta">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<div>
				<p class="escritoriowp-administradores__resumen-titulo">
					<?php
					printf(
						/* translators: %s: número de usuarios que requieren revisión. */
						esc_html( _n( 'Hay %s usuario que requiere revisión.', 'Hay %s usuarios que requieren revisión.', $total, 'escritoriowp' ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</p>
				<p><?php echo esc_html__( 'Pueden ser una puerta trasera: cuentas con permisos de administrador sin el rol Administrador, o administradores que algún código esconde de la pantalla Usuarios. Si no reconoces alguno, revísalo cuanto antes.', 'escritoriowp' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Pinta la lista de administradores.
	 *
	 * @param array<int, array<string, mixed>> $administradores Administradores y superadministradores.
	 * @return void
	 */
	private function pintar_administradores( array $administradores ): void {
		?>
		<h2 class="escritoriowp-administradores__titulo">
			<?php echo esc_html__( 'Administradores', 'escritoriowp' ); ?>
			<span class="escritoriowp-administradores__cuenta"><?php echo esc_html( number_format_i18n( count( $administradores ) ) ); ?></span>
		</h2>

		<table class="escritoriowp-tabla">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Usuario', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Correo', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Fecha de alta', 'escritoriowp' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php echo esc_html__( 'Acciones', 'escritoriowp' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $administradores as $usuario ) : ?>
					<tr>
						<td data-columna="<?php echo esc_attr__( 'Usuario', 'escritoriowp' ); ?>"><?php $this->pintar_usuario( $usuario, false ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Correo', 'escritoriowp' ); ?>"><?php echo esc_html( (string) $usuario['email'] ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Fecha de alta', 'escritoriowp' ); ?>"><?php echo esc_html( $this->fecha( (string) $usuario['alta'] ) ); ?></td>
						<td class="escritoriowp-tabla__acciones"><?php $this->pintar_enlace_ficha( (int) $usuario['id'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Pinta la sección de usuarios con permisos de administrador sin el rol.
	 *
	 * @param array<int, array<string, mixed>> $marcados      Usuarios marcados que se pintan.
	 * @param int                              $restantes     Usuarios marcados que no se pintan.
	 * @param array<int|string, string>        $nombres_roles Nombre visible de cada rol.
	 * @param bool                             $hay_alterados Si hay roles alterados.
	 * @return void
	 */
	private function pintar_marcados( array $marcados, int $restantes, array $nombres_roles, bool $hay_alterados ): void {
		?>
		<h2 class="escritoriowp-administradores__titulo">
			<?php echo esc_html__( 'Usuarios con permisos de administrador sin serlo', 'escritoriowp' ); ?>
			<span class="escritoriowp-administradores__cuenta"><?php echo esc_html( number_format_i18n( count( $marcados ) + $restantes ) ); ?></span>
		</h2>

		<?php if ( array() === $marcados ) : ?>
			<p class="escritoriowp-administradores__vacio"><?php echo esc_html__( 'Ningún usuario sin el rol Administrador tiene permisos de administrador.', 'escritoriowp' ); ?></p>
			<?php
			return;
		endif;
		?>

		<table class="escritoriowp-tabla">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Usuario', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Rol', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Permisos de administrador', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Fecha de alta', 'escritoriowp' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php echo esc_html__( 'Acciones', 'escritoriowp' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $marcados as $usuario ) : ?>
					<tr>
						<td data-columna="<?php echo esc_attr__( 'Usuario', 'escritoriowp' ); ?>"><?php $this->pintar_usuario( $usuario, true ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Rol', 'escritoriowp' ); ?>"><?php echo esc_html( $this->roles_usuario( $usuario, $nombres_roles ) ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Permisos de administrador', 'escritoriowp' ); ?>"><?php $this->pintar_criticos( (array) $usuario['criticos'], $nombres_roles ); ?></td>
						<td data-columna="<?php echo esc_attr__( 'Fecha de alta', 'escritoriowp' ); ?>"><?php echo esc_html( $this->fecha( (string) $usuario['alta'] ) ); ?></td>
						<td class="escritoriowp-tabla__acciones"><?php $this->pintar_acciones_marcado( $usuario ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $restantes > 0 ) : ?>
			<p class="escritoriowp-administradores__restantes">
				<?php
				printf(
					/* translators: %s: número de usuarios marcados que no se muestran. */
					esc_html( _n( 'Hay %s usuario más con permisos de administrador que no se muestra.', 'Hay %s usuarios más con permisos de administrador que no se muestran.', $restantes, 'escritoriowp' ) ),
					esc_html( number_format_i18n( $restantes ) )
				);

				if ( $hay_alterados ) {
					echo ' ' . esc_html__( 'Casi siempre vienen de un rol alterado: revisa la sección «Roles alterados».', 'escritoriowp' );
				}
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Pinta la sección de roles alterados, si hay alguno.
	 *
	 * @param array<int|string, array{nombre: string, criticos: string[], usuarios: int}> $alterados Roles alterados.
	 * @return void
	 */
	private function pintar_roles_alterados( array $alterados ): void {
		if ( array() === $alterados ) {
			return;
		}

		?>
		<h2 class="escritoriowp-administradores__titulo">
			<?php echo esc_html__( 'Roles alterados', 'escritoriowp' ); ?>
			<span class="escritoriowp-administradores__cuenta"><?php echo esc_html( number_format_i18n( count( $alterados ) ) ); ?></span>
		</h2>
		<p class="escritoriowp-administradores__nota">
			<?php echo esc_html__( 'Estos roles incluyen permisos de administrador, así que todos sus usuarios los tienen. EscritorioWP no modifica roles: corrígelos con un editor de roles o restaura sus permisos originales.', 'escritoriowp' ); ?>
		</p>

		<table class="escritoriowp-tabla">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Rol', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Permisos de administrador', 'escritoriowp' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Usuarios', 'escritoriowp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $alterados as $slug => $rol ) : ?>
					<tr>
						<td data-columna="<?php echo esc_attr__( 'Rol', 'escritoriowp' ); ?>">
							<strong><?php echo esc_html( $rol['nombre'] ); ?></strong>
							<code><?php echo esc_html( (string) $slug ); ?></code>
						</td>
						<td data-columna="<?php echo esc_attr__( 'Permisos de administrador', 'escritoriowp' ); ?>">
							<?php foreach ( $rol['criticos'] as $permiso ) : ?>
								<code><?php echo esc_html( $permiso ); ?></code>
							<?php endforeach; ?>
						</td>
						<td data-columna="<?php echo esc_attr__( 'Usuarios', 'escritoriowp' ); ?>"><?php echo esc_html( number_format_i18n( $rol['usuarios'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Pinta el nombre, el nombre de usuario y las etiquetas de un usuario.
	 *
	 * @param array<string, mixed> $usuario Usuario del informe.
	 * @param bool                 $marcado Si está marcado por tener permisos de administrador.
	 * @return void
	 */
	private function pintar_usuario( array $usuario, bool $marcado ): void {
		?>
		<span class="escritoriowp-administradores__nombre"><?php echo esc_html( (string) $usuario['nombre'] ); ?></span>
		<span class="escritoriowp-administradores__login"><?php echo esc_html( (string) $usuario['login'] ); ?></span>
		<span class="escritoriowp-administradores__etiquetas">
			<?php if ( $marcado ) : ?>
				<span class="escritoriowp-etiqueta" data-tono="rojo"><?php echo esc_html__( 'Permisos de administrador', 'escritoriowp' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $usuario['oculto'] ) ) : ?>
				<span class="escritoriowp-etiqueta" data-tono="rojo"><?php echo esc_html__( 'Oculto en Usuarios', 'escritoriowp' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $usuario['superadmin'] ) ) : ?>
				<span class="escritoriowp-etiqueta" data-tono="azul"><?php echo esc_html__( 'Superadministrador', 'escritoriowp' ); ?></span>
			<?php endif; ?>
		</span>
		<?php
	}

	/**
	 * Pinta los permisos críticos de un usuario con su origen.
	 *
	 * @param array<string, int|string> $criticos      Permiso → origen (`usuario` o slug del rol).
	 * @param array<int|string, string> $nombres_roles Nombre visible de cada rol.
	 * @return void
	 */
	private function pintar_criticos( array $criticos, array $nombres_roles ): void {
		?>
		<ul class="escritoriowp-administradores__permisos">
			<?php foreach ( $criticos as $permiso => $origen ) : ?>
				<li>
					<code><?php echo esc_html( (string) $permiso ); ?></code>
					<span class="escritoriowp-administradores__origen"><?php echo esc_html( $this->origen( $origen, $nombres_roles ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Pinta las acciones de un usuario marcado.
	 *
	 * @param array<string, mixed> $usuario Usuario marcado.
	 * @return void
	 */
	private function pintar_acciones_marcado( array $usuario ): void {
		$id = (int) $usuario['id'];

		$this->pintar_enlace_ficha( $id );

		if ( '' === self::motivo_rechazo( $id, $usuario ) ) {
			printf(
				' <a class="escritoriowp-boton escritoriowp-boton--peligro" href="%s">%s</a>',
				esc_url( add_query_arg( 'retirar', $id, self::url() ) ),
				esc_html__( 'Retirar permisos', 'escritoriowp' )
			);
		}

		if ( ! Detector::tiene_sueltos( $usuario ) ) {
			printf(
				'<p class="escritoriowp-administradores__explicacion">%s</p>',
				esc_html__( 'Los permisos vienen de su rol. EscritorioWP no modifica roles.', 'escritoriowp' )
			);
		}
	}

	/**
	 * Pinta el enlace a la ficha del usuario, si el usuario actual puede editarlo.
	 *
	 * @param int $id Usuario.
	 * @return void
	 */
	private function pintar_enlace_ficha( int $id ): void {
		$enlace = get_edit_user_link( $id );

		if ( '' === $enlace ) {
			return;
		}

		printf(
			'<a class="escritoriowp-boton escritoriowp-boton--claro" href="%s">%s</a>',
			esc_url( $enlace ),
			esc_html__( 'Ver ficha', 'escritoriowp' )
		);
	}

	/**
	 * Texto del origen de un permiso.
	 *
	 * @param int|string                $origen        `usuario` o slug del rol.
	 * @param array<int|string, string> $nombres_roles Nombre visible de cada rol.
	 * @return string
	 */
	private function origen( int|string $origen, array $nombres_roles ): string {
		if ( Permisos::ORIGEN_USUARIO === $origen ) {
			return __( 'Asignado al usuario', 'escritoriowp' );
		}

		return sprintf(
			/* translators: %s: nombre del rol. */
			__( 'Del rol «%s»', 'escritoriowp' ),
			$nombres_roles[ $origen ] ?? (string) $origen
		);
	}

	/**
	 * Nombres de los roles de un usuario.
	 *
	 * @param array<string, mixed>      $usuario       Usuario del informe.
	 * @param array<int|string, string> $nombres_roles Nombre visible de cada rol.
	 * @return string
	 */
	private function roles_usuario( array $usuario, array $nombres_roles ): string {
		$nombres = array_map(
			static fn( int|string $rol ): string => $nombres_roles[ $rol ] ?? (string) $rol,
			(array) $usuario['roles']
		);

		return array() === $nombres ? __( 'Sin rol', 'escritoriowp' ) : implode( ', ', $nombres );
	}

	/**
	 * Fecha de alta en el formato del sitio. `user_registered` se guarda en UTC.
	 *
	 * @param string $alta Fecha de la base de datos.
	 * @return string
	 */
	private function fecha( string $alta ): string {
		$marca = strtotime( $alta . ' UTC' );

		if ( false === $marca || $marca <= 0 ) {
			return '—';
		}

		return (string) wp_date( (string) get_option( 'date_format' ), $marca );
	}
}
