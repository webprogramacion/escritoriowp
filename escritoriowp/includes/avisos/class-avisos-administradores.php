<?php
/**
 * Aviso por correo de los cambios en los administradores del sitio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Ajustes;
use EscritorioWP\Seguridad\Administradores;
use EscritorioWP\Seguridad\Detector;
use EscritorioWP\Seguridad\Permisos;

defined( 'ABSPATH' ) || exit;

/**
 * Revisa cada 6 horas quién es administrador y quién tiene permisos de administrador sin el rol, y
 * avisa por correo de lo que haya cambiado desde la revisión anterior.
 *
 * La revisión compara estados, no ve acciones: por eso también descubre los cambios hechos
 * directamente en la base de datos, pero no sabe quién los hizo.
 */
final class AvisosAdministradores {

	/**
	 * Tarea de WP-Cron que hace la revisión.
	 */
	public const EVENTO = 'escritoriowp_revisar_administradores';

	/**
	 * Intervalo propio de WP-Cron: WordPress no trae uno de 6 horas.
	 */
	public const INTERVALO = 'escritoriowp_seis_horas';

	/**
	 * Opción con la situación de la última revisión guardada.
	 */
	public const OPCION = 'escritoriowp_revision_administradores';

	/**
	 * Usuarios que se listan como mucho en cada apartado del correo.
	 */
	public const MAX_LINEAS = 50;

	/**
	 * Casilla de ajustes que enciende el aviso.
	 */
	private const CASILLA = 'aviso_administradores';

	/**
	 * Segundos entre dos revisiones.
	 */
	private const CADA = 6 * HOUR_IN_SECONDS;

	/**
	 * Ajustes del plugin.
	 *
	 * @var Ajustes
	 */
	private Ajustes $ajustes;

	/**
	 * Envío de los correos.
	 *
	 * @var Correo
	 */
	private Correo $correo;

	/**
	 * Constructor.
	 *
	 * @param Ajustes $ajustes Ajustes del plugin.
	 * @param Correo  $correo  Envío de los correos.
	 */
	public function __construct( Ajustes $ajustes, Correo $correo ) {
		$this->ajustes = $ajustes;
		$this->correo  = $correo;
	}

	/**
	 * Registra los enganches del módulo.
	 *
	 * El intervalo, el cambio de la casilla y la propia tarea se escuchan siempre: el intervalo lo
	 * necesita WP-Cron en cada petición para reprogramar, y una tarea que quedara programada con la
	 * casilla apagada se quita sola al correr. Comprobar en `init` que la tarea existe, como hace
	 * WordPress con `wp_schedule_update_checks()`, solo tiene sentido con la casilla marcada.
	 *
	 * @return void
	 */
	public function registrar(): void {
		// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- La regla no lee constantes de clase: el intervalo es `CADA`, 6 horas, muy por encima de los 15 minutos que vigila.
		add_filter( 'cron_schedules', array( self::class, 'anadir_intervalo' ) );
		add_action( 'update_option_' . Ajustes::OPCION, array( $this, 'al_actualizar_ajustes' ), 10, 2 );
		add_action( 'add_option_' . Ajustes::OPCION, array( $this, 'al_crear_ajustes' ), 10, 2 );
		add_action( self::EVENTO, array( $this, 'revisar' ) );

		if ( $this->activo() ) {
			add_action( 'init', array( self::class, 'asegurar_programacion' ) );
		}
	}

	/**
	 * Añade el intervalo de 6 horas a los de WP-Cron.
	 *
	 * El nombre solo se traduce a partir de `after_setup_theme`: otro código puede pedir los intervalos
	 * antes, y traducir entonces cargaría las traducciones del plugin demasiado pronto.
	 *
	 * @param mixed $intervalos Intervalos registrados.
	 * @return array<string, mixed>
	 */
	public static function anadir_intervalo( mixed $intervalos ): array {
		$intervalos = is_array( $intervalos ) ? $intervalos : array();

		$intervalos[ self::INTERVALO ] = array(
			'interval' => self::CADA,
			'display'  => did_action( 'after_setup_theme' ) ? __( 'Cada 6 horas (EscritorioWP)', 'escritoriowp' ) : 'Cada 6 horas (EscritorioWP)',
		);

		return $intervalos;
	}

	/**
	 * Programa la revisión si no está programada, para dentro de 6 horas.
	 *
	 * Es lo que la recupera tras reactivar el plugin o si otro código limpia las tareas de WP-Cron.
	 *
	 * @return void
	 */
	public static function asegurar_programacion(): void {
		if ( false === wp_next_scheduled( self::EVENTO ) ) {
			wp_schedule_event( time() + self::CADA, self::INTERVALO, self::EVENTO );
		}
	}

	/**
	 * Empieza o deja de vigilar cuando cambia la casilla al guardar los ajustes.
	 *
	 * El valor nuevo llega ya saneado; el anterior, tal como estaba en la base de datos. Guardar otro
	 * ajuste sin tocar la casilla no reinicia nada.
	 *
	 * @param mixed $anterior Valor anterior de la opción.
	 * @param mixed $nuevo    Valor nuevo.
	 * @return void
	 */
	public function al_actualizar_ajustes( mixed $anterior, mixed $nuevo ): void {
		$antes = (bool) Ajustes::sanear( $anterior )[ self::CASILLA ];
		$ahora = (bool) Ajustes::sanear( $nuevo )[ self::CASILLA ];

		if ( $antes === $ahora ) {
			return;
		}

		if ( $ahora ) {
			$this->empezar();
		} else {
			self::parar();
		}
	}

	/**
	 * Empieza a vigilar si la opción de ajustes se crea con la casilla marcada.
	 *
	 * @param mixed $opcion Nombre de la opción.
	 * @param mixed $valor  Valor guardado.
	 * @return void
	 */
	public function al_crear_ajustes( mixed $opcion, mixed $valor ): void {
		if ( Ajustes::sanear( $valor )[ self::CASILLA ] ) {
			$this->empezar();
		}
	}

	/**
	 * Guarda la situación de ahora como punto de partida, sin avisar, y programa la primera revisión
	 * para dentro de 6 horas.
	 *
	 * @return void
	 */
	private function empezar(): void {
		self::guardar( CambiosAdministradores::situacion( Detector::usuarios_evaluados() ), time() );

		wp_clear_scheduled_hook( self::EVENTO );
		wp_schedule_event( time() + self::CADA, self::INTERVALO, self::EVENTO );
	}

	/**
	 * Quita la revisión programada y olvida la situación guardada, para que volver a marcar la casilla
	 * empiece de cero.
	 *
	 * @return void
	 */
	private static function parar(): void {
		wp_clear_scheduled_hook( self::EVENTO );
		delete_option( self::OPCION );
	}

	/**
	 * Revisión periódica: compara la situación de ahora con la guardada y avisa si ha cambiado.
	 *
	 * La situación nueva solo se guarda si no había nada que contar o si `wp_mail()` ha aceptado el
	 * correo: si el envío falla, la revisión siguiente vuelve a encontrar los mismos cambios y los
	 * cuenta junto con los que haya después. Perder el aviso de un administrador nuevo es justo lo que
	 * este módulo tiene que evitar.
	 *
	 * @return void
	 */
	public function revisar(): void {
		if ( ! $this->activo() ) {
			wp_clear_scheduled_hook( self::EVENTO );
			return;
		}

		$fecha    = time();
		$usuarios = Detector::usuarios_evaluados();
		$ahora    = CambiosAdministradores::situacion( $usuarios );
		$guardada = get_option( self::OPCION );

		if ( ! CambiosAdministradores::valida( $guardada ) ) {
			self::guardar( $ahora, $fecha );
			return;
		}

		$cambios = CambiosAdministradores::comparar( $guardada['usuarios'], $ahora );

		if ( CambiosAdministradores::sin_cambios( $cambios ) ) {
			self::guardar( $ahora, $fecha );
			return;
		}

		try {
			$enviado = $this->correo->enviar( self::asunto(), self::mensaje( $cambios, $guardada, $usuarios ) );
		} catch ( \Throwable ) {
			// Sin registro propio: `wp_mail()` ya dispara `wp_mail_failed` con el motivo.
			$enviado = false;
		}

		if ( $enviado ) {
			self::guardar( $ahora, $fecha );
		}
	}

	/**
	 * Asunto del aviso, fijo y en una línea.
	 *
	 * @return string
	 */
	private static function asunto(): string {
		return Plantilla::una_linea(
			sprintf(
				/* translators: %s: nombre del sitio. */
				__( '[%s] Cambios en los administradores', 'escritoriowp' ),
				self::nombre_sitio()
			)
		);
	}

	/**
	 * Mensaje del aviso: la introducción, un apartado por cada tipo de cambio que haya y el enlace a
	 * la pantalla Administradores.
	 *
	 * @param array<string, int[]>                                                          $cambios  Resultado de `CambiosAdministradores::comparar()`.
	 * @param array{fecha: int, usuarios: array<int, array{login?: mixed, estado?: mixed}>} $guardada Situación de la revisión anterior.
	 * @param array<int, array<string, mixed>>                                              $usuarios Usuarios evaluados ahora, por identificador.
	 * @return string
	 */
	private static function mensaje( array $cambios, array $guardada, array $usuarios ): string {
		$bloques = array(
			sprintf(
				/* translators: 1: nombre del sitio, 2: dirección del sitio, 3: fecha y hora de la revisión anterior. */
				__( 'La revisión periódica de EscritorioWP ha encontrado cambios en los administradores de %1$s (%2$s) desde la revisión anterior, del %3$s.', 'escritoriowp' ),
				self::nombre_sitio(),
				home_url( '/' ),
				self::fecha( $guardada['fecha'] )
			),
		);

		$nombres_roles = Detector::nombres_roles();

		foreach ( CambiosAdministradores::APARTADOS as $apartado ) {
			$ids = $cambios[ $apartado ] ?? array();

			if ( array() === $ids ) {
				continue;
			}

			$lineas = self::lineas( $apartado, $ids, $guardada['usuarios'], $usuarios, $nombres_roles );
			$corte  = CambiosAdministradores::recortar( $lineas, self::MAX_LINEAS );
			$bloque = array_merge( array( self::titulo( $apartado, count( $ids ) ) ), $corte['lineas'] );

			if ( $corte['restantes'] > 0 ) {
				$bloque[] = sprintf(
					/* translators: %d: usuarios que no se listan. */
					__( '… y %d más. La pantalla Administradores muestra la situación completa.', 'escritoriowp' ),
					$corte['restantes']
				);
			}

			$bloques[] = implode( "\n", $bloque );
		}

		$bloques[] = sprintf(
			/* translators: %s: enlace a la pantalla Administradores. */
			__( 'Revisa los administradores en %s', 'escritoriowp' ),
			Administradores::url()
		);
		$bloques[] = __( 'Este aviso compara la situación de ahora con la de la revisión anterior: no dice quién hizo los cambios ni cuándo exactamente.', 'escritoriowp' );

		return implode( "\n\n", $bloques );
	}

	/**
	 * Líneas de los usuarios de un apartado.
	 *
	 * Solo se listan los primeros `MAX_LINEAS`, así que las comprobaciones que consultan la base de
	 * datos (quién está oculto y quién se ha borrado) se limitan a ellos; las líneas de los demás se
	 * descartan al recortar.
	 *
	 * @param string                                           $apartado      Uno de `CambiosAdministradores::APARTADOS`.
	 * @param int[]                                            $ids           Usuarios del apartado, en orden.
	 * @param array<int, array{login?: mixed, estado?: mixed}> $antes         Situación de la revisión anterior.
	 * @param array<int, array<string, mixed>>                 $usuarios      Usuarios evaluados ahora, por identificador.
	 * @param array<int|string, string>                        $nombres_roles Nombre visible de cada rol.
	 * @return string[]
	 */
	private static function lineas( string $apartado, array $ids, array $antes, array $usuarios, array $nombres_roles ): array {
		$mostrados = array_slice( $ids, 0, self::MAX_LINEAS );
		$nuevos    = in_array( $apartado, array( CambiosAdministradores::ADMINISTRADORES_NUEVOS, CambiosAdministradores::MARCADOS_NUEVOS ), true );
		$lineas    = array();

		if ( $nuevos ) {
			$ocultos    = Detector::ocultos( array_values( array_intersect_key( $usuarios, array_flip( $mostrados ) ) ) );
			$existentes = array();
		} else {
			$ocultos    = array();
			$existentes = Detector::existentes( $mostrados );
		}

		foreach ( $ids as $id ) {
			$anterior = is_array( $antes[ $id ] ?? null ) ? (string) ( $antes[ $id ]['estado'] ?? '' ) : '';
			$detalles = array();

			if ( $nuevos ) {
				$usuario = $usuarios[ $id ] ?? array();

				if ( CambiosAdministradores::ADMINISTRADORES_NUEVOS === $apartado ) {
					if ( ! empty( $usuario['superadmin'] ) && empty( $usuario['administrador'] ) ) {
						$detalles[] = __( 'Superadministrador de la red', 'escritoriowp' );
					}

					if ( Detector::ESTADO_MARCADO === $anterior ) {
						$detalles[] = __( 'Antes tenía permisos de administrador sin el rol', 'escritoriowp' );
					}
				} else {
					$detalles = self::detalle_criticos( (array) ( $usuario['criticos'] ?? array() ), $nombres_roles );

					if ( Detector::ESTADO_ADMINISTRADOR === $anterior ) {
						$detalles[] = __( 'Antes era administrador', 'escritoriowp' );
					}
				}

				if ( in_array( $id, $ocultos, true ) ) {
					$detalles[] = __( 'Oculto en la pantalla Usuarios', 'escritoriowp' );
				}
			} else {
				$usuario = $existentes[ $id ] ?? array();

				$detalles[] = array() === $usuario
					? __( 'Usuario borrado', 'escritoriowp' )
					: __( 'Sigue existiendo, sin permisos de administrador', 'escritoriowp' );
			}

			$login = (string) ( $usuario['login'] ?? '' );

			if ( '' === $login ) {
				$login = is_array( $antes[ $id ] ?? null ) ? (string) ( $antes[ $id ]['login'] ?? '' ) : '';
			}

			$lineas[] = CambiosAdministradores::linea(
				$login,
				(string) ( $usuario['nombre'] ?? '' ),
				(string) ( $usuario['email'] ?? '' ),
				$detalles
			);
		}

		return $lineas;
	}

	/**
	 * Permisos críticos de un usuario marcado, agrupados por origen: primero los asignados al usuario
	 * y después los de cada rol, en el orden en que aparecen.
	 *
	 * @param array<string, int|string> $criticos      Permiso ⇒ origen (`usuario` o el rol).
	 * @param array<int|string, string> $nombres_roles Nombre visible de cada rol.
	 * @return string[]
	 */
	private static function detalle_criticos( array $criticos, array $nombres_roles ): array {
		$por_origen = array();

		foreach ( $criticos as $permiso => $origen ) {
			$por_origen[ (string) $origen ][] = (string) $permiso;
		}

		// Un rol con identificador numérico deja una clave entera: se compara como texto.
		uksort(
			$por_origen,
			static fn( int|string $a, int|string $b ): int => (int) ( Permisos::ORIGEN_USUARIO === (string) $b ) <=> (int) ( Permisos::ORIGEN_USUARIO === (string) $a )
		);

		$detalles = array();

		foreach ( $por_origen as $origen => $permisos ) {
			$lista = implode( ', ', $permisos );

			if ( Permisos::ORIGEN_USUARIO === $origen ) {
				$detalles[] = sprintf(
					/* translators: %s: permisos separados por comas. */
					_n( '%s (asignado al usuario)', '%s (asignados al usuario)', count( $permisos ), 'escritoriowp' ),
					$lista
				);
			} else {
				$detalles[] = sprintf(
					/* translators: 1: permisos separados por comas, 2: nombre del rol. */
					__( '%1$s (del rol «%2$s»)', 'escritoriowp' ),
					$lista,
					$nombres_roles[ $origen ] ?? $origen
				);
			}
		}

		return $detalles;
	}

	/**
	 * Título de un apartado, con el total de usuarios que incluye.
	 *
	 * @param string $apartado Uno de `CambiosAdministradores::APARTADOS`.
	 * @param int    $total    Usuarios del apartado.
	 * @return string
	 */
	private static function titulo( string $apartado, int $total ): string {
		$titulo = match ( $apartado ) {
			CambiosAdministradores::ADMINISTRADORES_NUEVOS => __( 'Administradores nuevos', 'escritoriowp' ),
			CambiosAdministradores::MARCADOS_NUEVOS        => __( 'Usuarios nuevos con permisos de administrador sin el rol', 'escritoriowp' ),
			CambiosAdministradores::YA_NO_ADMINISTRADORES  => __( 'Ya no son administradores', 'escritoriowp' ),
			default                                        => __( 'Ya no tienen permisos de administrador', 'escritoriowp' ),
		};

		return $titulo . ' (' . $total . '):';
	}

	/**
	 * Nombre del sitio sin entidades HTML.
	 *
	 * @return string
	 */
	private static function nombre_sitio(): string {
		return wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Guarda una situación sin carga automática: con miles de usuarios marcados por un rol alterado
	 * puede ocupar cientos de kilobytes, y solo se lee al revisar y en la pantalla de ajustes.
	 *
	 * @param array<int, array{login: string, estado: string}> $usuarios Situación de cada usuario.
	 * @param int                                              $fecha    Momento de la revisión.
	 * @return void
	 */
	private static function guardar( array $usuarios, int $fecha ): void {
		update_option(
			self::OPCION,
			array(
				'fecha'    => $fecha,
				'usuarios' => $usuarios,
			),
			false
		);
	}

	/**
	 * Indica si el aviso está encendido.
	 *
	 * @return bool
	 */
	private function activo(): bool {
		return (bool) $this->ajustes->obtener( self::CASILLA );
	}

	/**
	 * Cuándo fue la última revisión (o el punto de partida) y cuándo toca la próxima, para la pantalla
	 * de ajustes. Omite lo que falte.
	 *
	 * @return string
	 */
	public static function estado_revision(): string {
		$partes   = array();
		$guardada = get_option( self::OPCION );
		$proxima  = wp_next_scheduled( self::EVENTO );

		if ( CambiosAdministradores::valida( $guardada ) ) {
			/* translators: %s: fecha y hora de la última revisión. */
			$partes[] = sprintf( __( 'Última revisión: %s.', 'escritoriowp' ), self::fecha( $guardada['fecha'] ) );
		}

		if ( false !== $proxima ) {
			/* translators: %s: fecha y hora de la próxima revisión. */
			$partes[] = sprintf( __( 'Próxima: %s.', 'escritoriowp' ), self::fecha( (int) $proxima ) );
		}

		return implode( ' ', $partes );
	}

	/**
	 * Fecha y hora en el formato y la zona horaria del sitio.
	 *
	 * @param int $marca Marca de tiempo Unix.
	 * @return string
	 */
	private static function fecha( int $marca ): string {
		return (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $marca );
	}
}
