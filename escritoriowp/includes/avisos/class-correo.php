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
 * Envía los avisos del plugin a los destinatarios configurados, siempre en texto plano.
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
	 * Envía un correo de texto plano a los destinatarios configurados.
	 *
	 * La cabecera no basta: `wp_mail()` aplica `wp_mail_content_type` después de leerla, así que un
	 * plugin que pone todos los correos en HTML ganaría. Durante este envío, y solo durante él, manda
	 * un filtro con la prioridad más alta.
	 *
	 * @param string $asunto  Asunto ya rellenado, en una línea.
	 * @param string $mensaje Mensaje ya rellenado.
	 * @return bool Si `wp_mail()` ha aceptado el correo.
	 */
	public function enviar( string $asunto, string $mensaje ): bool {
		$texto_plano = static fn(): string => 'text/plain';

		add_filter( 'wp_mail_content_type', $texto_plano, PHP_INT_MAX );

		try {
			return (bool) wp_mail( $this->destinatarios(), $asunto, $mensaje, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		} finally {
			remove_filter( 'wp_mail_content_type', $texto_plano, PHP_INT_MAX );
		}
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
