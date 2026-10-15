<?php
/**
 * Envío de los avisos por correo en texto plano.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Ajustes;

defined( 'ABSPATH' ) || exit;

/**
 * Envía los avisos del plugin a los destinatarios configurados, siempre en texto plano, y los guarda
 * en el registro de avisos.
 */
final class Correo {

	/**
	 * Ajustes del plugin.
	 *
	 * @var Ajustes
	 */
	private Ajustes $ajustes;

	/**
	 * Constructor.
	 *
	 * @param Ajustes $ajustes Ajustes del plugin.
	 */
	public function __construct( Ajustes $ajustes ) {
		$this->ajustes = $ajustes;
	}

	/**
	 * Envía un correo de texto plano a los destinatarios configurados y lo deja en el registro de avisos.
	 *
	 * El aviso se guarda antes de llamar a `wp_mail()` y se completa después: si el envío rompe la
	 * petición, el registro se queda «Sin confirmar» pero existe. Guardarlo no puede impedir el envío.
	 *
	 * La cabecera no basta: `wp_mail()` aplica `wp_mail_content_type` después de leerla, así que un
	 * plugin que pone todos los correos en HTML ganaría. Durante este envío, y solo durante él, manda
	 * un filtro con la prioridad más alta, y se escucha `wp_mail_failed` para guardar el motivo de un
	 * fallo de este correo y no de otro.
	 *
	 * @param string $tipo    Tipo del aviso: un evento de `AvisosPlugins` o `Registro::ADMINISTRADORES`.
	 * @param string $asunto  Asunto ya rellenado, en una línea.
	 * @param string $mensaje Mensaje ya rellenado.
	 * @return bool Si `wp_mail()` ha aceptado el correo.
	 * @throws \Throwable Lo que lance `wp_mail()`, después de dejar el aviso como fallido.
	 */
	public function enviar( string $tipo, string $asunto, string $mensaje ): bool {
		$destinatarios = $this->destinatarios();
		$registro      = Registro::guardar( $tipo, $asunto, $mensaje, $destinatarios );
		$motivo        = '';
		$texto_plano   = static fn(): string => 'text/plain';
		$al_fallar     = static function ( mixed $error ) use ( &$motivo ): void {
			if ( $error instanceof \WP_Error ) {
				$motivo = $error->get_error_message();
			}
		};

		add_filter( 'wp_mail_content_type', $texto_plano, PHP_INT_MAX );
		add_action( 'wp_mail_failed', $al_fallar );

		try {
			$enviado = (bool) wp_mail( $destinatarios, $asunto, $mensaje, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		} catch ( \Throwable $error ) {
			Registro::terminar( $registro, false, $error->getMessage() );

			throw $error;
		} finally {
			remove_filter( 'wp_mail_content_type', $texto_plano, PHP_INT_MAX );
			remove_action( 'wp_mail_failed', $al_fallar );
		}

		Registro::terminar( $registro, $enviado, $motivo );

		return $enviado;
	}

	/**
	 * Direcciones a las que se envía: las del ajuste o, si está vacío, el correo de administración
	 * vigente en este momento.
	 *
	 * @return array<int, string>
	 */
	public function destinatarios(): array {
		$correos = array_filter( array_map( 'trim', explode( ',', (string) $this->ajustes->obtener( 'aviso_destinatarios' ) ) ) );

		return array() === $correos ? array( (string) get_option( 'admin_email' ) ) : array_values( $correos );
	}
}
