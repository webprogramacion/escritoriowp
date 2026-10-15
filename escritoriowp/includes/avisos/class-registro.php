<?php
/**
 * Registro en la base de datos de los avisos por correo.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Ajustes;

defined( 'ABSPATH' ) || exit;

/**
 * Guarda cada aviso que el plugin intenta enviar, con el resultado del envío, en una tabla propia.
 *
 * El registro existe antes de llamar a `wp_mail()` y se completa después: así un aviso no se pierde
 * aunque el envío rompa la petición. Ninguna operación puede romper el envío: todas capturan sus
 * errores y devuelven un valor neutro.
 */
final class Registro {

	/**
	 * Tipo del aviso de cambios en los administradores. Los de plugins son los eventos de
	 * `AvisosPlugins`.
	 */
	public const ADMINISTRADORES = 'administradores';

	/**
	 * Tipos de aviso que se guardan, en el orden en que se ofrecen en los filtros.
	 *
	 * @var string[]
	 */
	public const TIPOS = array(
		AvisosPlugins::ACTIVACION,
		AvisosPlugins::DESACTIVACION,
		AvisosPlugins::INSTALACION,
		AvisosPlugins::BORRADO,
		self::ADMINISTRADORES,
	);

	/**
	 * Estado: guardado y todavía sin resultado. Si se queda así, la petición terminó durante el envío.
	 */
	public const PENDIENTE = 'pendiente';

	/**
	 * Estado: `wp_mail()` aceptó el correo, que no es lo mismo que entregado.
	 */
	public const ENVIADO = 'enviado';

	/**
	 * Estado: `wp_mail()` rechazó el correo o lanzó un error.
	 */
	public const FALLIDO = 'fallido';

	/**
	 * Estados posibles, en el orden en que se ofrecen en los filtros.
	 *
	 * @var string[]
	 */
	public const ESTADOS = array( self::ENVIADO, self::FALLIDO, self::PENDIENTE );

	/**
	 * Versión del esquema de la tabla. Subirla hace que `dbDelta()` aplique el cambio con el aviso
	 * siguiente.
	 */
	public const VERSION_ESQUEMA = '1';

	/**
	 * Opción con la versión del esquema instalada en este sitio.
	 */
	public const OPCION_VERSION = 'escritoriowp_avisos_bd';

	/**
	 * Avisos por página en la pantalla Avisos.
	 */
	public const POR_PAGINA = 20;

	/**
	 * Avisos que muestra el bloque del escritorio si no se pide otro número.
	 */
	public const LIMITE_DEFECTO = 5;

	/**
	 * Avisos que se devuelven como mucho en una petición de los más recientes.
	 */
	public const LIMITE_MAXIMO = 20;

	/**
	 * Caracteres que se guardan como mucho del motivo de un fallo.
	 */
	private const MAX_MOTIVO = 500;


	/**
	 * Nombre de la tabla del sitio actual: en multisitio, cada sitio tiene la suya.
	 *
	 * @return string
	 */
	public static function tabla(): string {
		global $wpdb;

		return $wpdb->prefix . 'escritoriowp_avisos';
	}

	/**
	 * Crea la tabla, o la pone al día, y guarda la versión del esquema.
	 *
	 * La versión solo se guarda si `dbDelta()` termina sin error de SQL: si la tabla no se pudo crear,
	 * el aviso siguiente lo vuelve a intentar. La opción lleva carga automática porque es diminuta y se
	 * lee en cada aviso y en el escritorio de los administradores.
	 *
	 * @return bool Si la tabla está lista.
	 */
	public static function instalar(): bool {
		return (bool) self::protegido(
			static function ( \wpdb $wpdb ): bool {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';

				$tabla = self::tabla();

				// `dbDelta()` exige un campo por línea, dos espacios tras `PRIMARY KEY` y `KEY` en lugar de `INDEX`.
				dbDelta(
					"CREATE TABLE {$tabla} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
fecha datetime NOT NULL,
tipo varchar(20) NOT NULL,
estado varchar(20) NOT NULL,
asunto text NOT NULL,
mensaje longtext NOT NULL,
destinatarios text NOT NULL,
motivo text NOT NULL,
PRIMARY KEY  (id),
KEY tipo (tipo),
KEY estado (estado)
) {$wpdb->get_charset_collate()};"
				);

				if ( '' !== $wpdb->last_error ) {
					return false;
				}

				update_option( self::OPCION_VERSION, self::VERSION_ESQUEMA, true );

				return true;
			},
			false
		);
	}

	/**
	 * Crea la tabla si este sitio no tiene la versión actual del esquema.
	 *
	 * Solo lo llaman la activación y `guardar()`: así la tabla aparece también tras una actualización
	 * sin activación, en cada sitio de una red y en los sitios creados después. Las lecturas no crean
	 * nada.
	 *
	 * @return bool Si la tabla está lista.
	 */
	public static function asegurar_tabla(): bool {
		return self::instalada() || self::instalar();
	}

	/**
	 * Indica si este sitio tiene la tabla con la versión actual del esquema. Sin consultar la tabla:
	 * solo lee la opción, que tiene carga automática. Si no existe, WordPress la busca una vez por
	 * petición y recuerda que falta.
	 *
	 * @return bool
	 */
	public static function instalada(): bool {
		return self::VERSION_ESQUEMA === (string) get_option( self::OPCION_VERSION, '' );
	}

	/*
	 * Las consultas de abajo van directas contra la tabla propia del plugin: WordPress no ofrece otra
	 * API para leerla ni escribirla. No usan la caché de objetos: los avisos se escriben y se leen en
	 * peticiones distintas, el escritorio tiene que mostrar el último y son muy pocos.
	 *
	 * Los filtros del listado van en una condición fija, `( %s = '' OR tipo = %s )`, con cada valor dos
	 * veces: así la consulta se prepara entera, sin interpolar nada. Un filtro vacío deja una condición
	 * constante que la base de datos descarta al optimizar, y el otro sigue usando su índice.
	 */

	/**
	 * Guarda un aviso como pendiente, justo antes de enviarlo. Crea la tabla si falta.
	 *
	 * @param string   $tipo          Uno de `TIPOS`.
	 * @param string   $asunto        Asunto tal como se pasa a `wp_mail()`.
	 * @param string   $mensaje       Mensaje tal como se pasa a `wp_mail()`.
	 * @param string[] $destinatarios Direcciones tal como se pasan a `wp_mail()`.
	 * @return int Identificador del registro, o 0 si no se pudo guardar.
	 */
	public static function guardar( string $tipo, string $asunto, string $mensaje, array $destinatarios ): int {
		return (int) self::protegido(
			static function ( \wpdb $wpdb ) use ( $tipo, $asunto, $mensaje, $destinatarios ): int {
				if ( ! self::asegurar_tabla() ) {
					return 0;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Tabla propia: no hay otra API.
				$insertado = $wpdb->insert(
					self::tabla(),
					array(
						'fecha'         => gmdate( 'Y-m-d H:i:s' ),
						'tipo'          => $tipo,
						'estado'        => self::PENDIENTE,
						'asunto'        => $asunto,
						'mensaje'       => $mensaje,
						'destinatarios' => implode( ', ', $destinatarios ),
						'motivo'        => '',
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);

				return false === $insertado ? 0 : (int) $wpdb->insert_id;
			},
			0
		);
	}

	/**
	 * Completa un aviso con el resultado del envío. El motivo solo se guarda si ha fallado.
	 *
	 * @param int    $id      Identificador devuelto por `guardar()`; con 0 no hace nada.
	 * @param bool   $enviado Si `wp_mail()` aceptó el correo.
	 * @param string $motivo  Mensaje del error, si lo hay.
	 * @return void
	 */
	public static function terminar( int $id, bool $enviado, string $motivo ): void {
		if ( $id <= 0 ) {
			return;
		}

		self::protegido(
			static function ( \wpdb $wpdb ) use ( $id, $enviado, $motivo ): bool {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				return false !== $wpdb->update(
					self::tabla(),
					array(
						'estado' => $enviado ? self::ENVIADO : self::FALLIDO,
						'motivo' => $enviado ? '' : self::motivo( $motivo ),
					),
					array( 'id' => $id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			},
			false
		);
	}

	/**
	 * Avisos más recientes, sin el mensaje.
	 *
	 * @param int $limite Cuántos, entre 1 y `LIMITE_MAXIMO`.
	 * @return array<int, array<string, int|string>> Con `id`, `fecha`, `tipo`, `estado` y `asunto`.
	 */
	public static function recientes( int $limite ): array {
		$limite = self::limite( $limite );

		return (array) self::protegido(
			static function ( \wpdb $wpdb ) use ( $limite ): array {
				if ( ! self::instalada() ) {
					return array();
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				$filas = $wpdb->get_results( $wpdb->prepare( 'SELECT id, fecha, tipo, estado, asunto FROM %i ORDER BY id DESC LIMIT %d', self::tabla(), $limite ), ARRAY_A );

				return self::normalizar( $filas );
			},
			array()
		);
	}

	/**
	 * Una página del listado, del más reciente al más antiguo, con los destinatarios y sin el mensaje.
	 *
	 * @param array<string, mixed> $filtros `tipo` y `estado`; se vuelven a validar con `filtros()`.
	 * @param int                  $pagina  Página, empezando en 1.
	 * @return array<int, array<string, int|string>> Con `id`, `fecha`, `tipo`, `estado`, `asunto` y `destinatarios`.
	 */
	public static function listar( array $filtros, int $pagina ): array {
		$filtros = self::filtros( $filtros['tipo'] ?? '', $filtros['estado'] ?? '' );
		$desde   = ( max( 1, $pagina ) - 1 ) * self::POR_PAGINA;

		return (array) self::protegido(
			static function ( \wpdb $wpdb ) use ( $filtros, $desde ): array {
				if ( ! self::instalada() ) {
					return array();
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				$filas = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, fecha, tipo, estado, asunto, destinatarios FROM %i WHERE ( %s = '' OR tipo = %s ) AND ( %s = '' OR estado = %s ) ORDER BY id DESC LIMIT %d OFFSET %d",
						self::tabla(),
						$filtros['tipo'],
						$filtros['tipo'],
						$filtros['estado'],
						$filtros['estado'],
						self::POR_PAGINA,
						$desde
					),
					ARRAY_A
				);

				return self::normalizar( $filas );
			},
			array()
		);
	}

	/**
	 * Avisos que coinciden con los filtros.
	 *
	 * @param array<string, mixed> $filtros `tipo` y `estado`; se vuelven a validar con `filtros()`.
	 * @return int
	 */
	public static function contar( array $filtros ): int {
		$filtros = self::filtros( $filtros['tipo'] ?? '', $filtros['estado'] ?? '' );

		return (int) self::protegido(
			static function ( \wpdb $wpdb ) use ( $filtros ): int {
				if ( ! self::instalada() ) {
					return 0;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				$total = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM %i WHERE ( %s = '' OR tipo = %s ) AND ( %s = '' OR estado = %s )",
						self::tabla(),
						$filtros['tipo'],
						$filtros['tipo'],
						$filtros['estado'],
						$filtros['estado']
					)
				);

				return (int) $total;
			},
			0
		);
	}

	/**
	 * Un aviso completo.
	 *
	 * @param int $id Identificador.
	 * @return array<string, int|string>|null Null si no existe o no se puede leer.
	 */
	public static function obtener( int $id ): ?array {
		$aviso = self::protegido(
			static function ( \wpdb $wpdb ) use ( $id ): ?array {
				if ( $id <= 0 || ! self::instalada() ) {
					return null;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				$fila = $wpdb->get_row( $wpdb->prepare( 'SELECT id, fecha, tipo, estado, asunto, mensaje, destinatarios, motivo FROM %i WHERE id = %d', self::tabla(), $id ), ARRAY_A );

				return is_array( $fila ) ? self::normalizar( array( $fila ) )[0] : null;
			},
			null
		);

		return is_array( $aviso ) ? $aviso : null;
	}

	/**
	 * Indica si hay algún aviso guardado.
	 *
	 * @return bool
	 */
	public static function hay_avisos(): bool {
		return (bool) self::protegido(
			static function ( \wpdb $wpdb ): bool {
				if ( ! self::instalada() ) {
					return false;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Tabla propia: no hay otra API.
				return null !== $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM %i LIMIT 1', self::tabla() ) );
			},
			false
		);
	}

	/**
	 * Filas leídas de la base de datos con el identificador como entero y el resto como texto.
	 *
	 * @param mixed $filas Resultado de `get_results()` con `ARRAY_A`.
	 * @return array<int, array<string, int|string>>
	 */
	private static function normalizar( mixed $filas ): array {
		if ( ! is_array( $filas ) ) {
			return array();
		}

		return array_map(
			static fn( array $fila ): array => array( 'id' => (int) $fila['id'] ) + array_map( 'strval', $fila ),
			array_values( array_filter( $filas, 'is_array' ) )
		);
	}

	/**
	 * Ejecuta una operación sobre la base de datos sin que pueda romper nada.
	 *
	 * `hide_errors()` evita que `$wpdb` imprima un error de SQL, que ensuciaría una respuesta AJAX o
	 * REST o impediría una redirección, pero lo sigue anotando en el `error_log`, al contrario que
	 * `suppress_errors()`. Cualquier excepción devuelve el valor neutro.
	 *
	 * @param callable $operacion Recibe `$wpdb` y devuelve el resultado.
	 * @param mixed    $neutro    Valor si algo falla.
	 * @return mixed
	 */
	private static function protegido( callable $operacion, mixed $neutro ): mixed {
		global $wpdb;

		$previo = $wpdb->hide_errors();

		try {
			return $operacion( $wpdb );
		} catch ( \Throwable ) {
			return $neutro;
		} finally {
			$wpdb->show_errors( $previo );
		}
	}

	/**
	 * Filtros del listado con los valores válidos; cualquier otro valor, de cualquier tipo, se queda
	 * en cadena vacía, que es «sin filtro».
	 *
	 * @param mixed $tipo   Tipo pedido.
	 * @param mixed $estado Estado pedido.
	 * @return array{tipo: string, estado: string}
	 */
	public static function filtros( mixed $tipo, mixed $estado ): array {
		return array(
			'tipo'   => is_string( $tipo ) && in_array( $tipo, self::TIPOS, true ) ? $tipo : '',
			'estado' => is_string( $estado ) && in_array( $estado, self::ESTADOS, true ) ? $estado : '',
		);
	}

	/**
	 * Número de avisos recientes que se piden: un entero entre 1 y `LIMITE_MAXIMO`, o
	 * `LIMITE_DEFECTO` si el valor no es un entero positivo.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return int
	 */
	public static function limite( mixed $valor ): int {
		$numero = is_int( $valor ) || is_string( $valor ) ? filter_var( $valor, FILTER_VALIDATE_INT ) : false;

		if ( false === $numero || $numero < 1 ) {
			return self::LIMITE_DEFECTO;
		}

		return min( self::LIMITE_MAXIMO, $numero );
	}

	/**
	 * Páginas del listado para un total de avisos; al menos una, aunque no haya ninguno.
	 *
	 * @param int $total Avisos que coinciden con los filtros.
	 * @return int
	 */
	public static function total_paginas( int $total ): int {
		return max( 1, (int) ceil( $total / self::POR_PAGINA ) );
	}

	/**
	 * Página pedida, entre la primera y la última.
	 *
	 * @param mixed $valor         Valor recibido.
	 * @param int   $total_paginas Páginas que hay.
	 * @return int
	 */
	public static function pagina( mixed $valor, int $total_paginas ): int {
		$numero = is_int( $valor ) || is_string( $valor ) ? filter_var( $valor, FILTER_VALIDATE_INT ) : false;

		if ( false === $numero || $numero < 1 ) {
			return 1;
		}

		return min( max( 1, $total_paginas ), $numero );
	}

	/**
	 * Motivo de un fallo listo para guardar: sin etiquetas, en una línea y con longitud máxima.
	 *
	 * @param string $texto Mensaje del error.
	 * @return string
	 */
	public static function motivo( string $texto ): string {
		return trim( mb_substr( Plantilla::una_linea( Ajustes::sin_etiquetas( $texto ) ), 0, self::MAX_MOTIVO ) );
	}

	/**
	 * Color de la etiqueta de un estado.
	 *
	 * @param string $estado Uno de `ESTADOS`.
	 * @return string Uno de los tonos de `.escritoriowp-etiqueta`.
	 */
	public static function tono( string $estado ): string {
		return match ( $estado ) {
			self::ENVIADO   => 'verde',
			self::FALLIDO   => 'rojo',
			self::PENDIENTE => 'ambar',
			default         => 'neutro',
		};
	}
}
