<?php
/**
 * Pruebas de la interpretación de las releases de GitHub.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Actualizaciones\ActualizadorGithub;

grupo( 'Actualizador · interpretación de la release' );

/**
 * Devuelve una release de ejemplo, con las claves que se quieran cambiar.
 *
 * @param array<string, mixed> $cambios Claves a sustituir.
 * @return array<string, mixed>
 */
function release_de_ejemplo( array $cambios = array() ): array {
	return array_merge(
		array(
			'tag_name'     => 'v0.2.0',
			'draft'        => false,
			'prerelease'   => false,
			'html_url'     => 'https://github.com/webprogramacion/escritoriowp/releases/tag/v0.2.0',
			'body'         => "### Añadido\n\n- Release automática.",
			'published_at' => '2026-09-20T10:00:00Z',
			'assets'       => array(
				array(
					'name'                 => 'escritoriowp-0.2.0.zip',
					'browser_download_url' => 'https://github.com/webprogramacion/escritoriowp/releases/download/v0.2.0/escritoriowp-0.2.0.zip',
				),
			),
		),
		$cambios
	);
}

$release = ActualizadorGithub::interpretar_release( release_de_ejemplo() );

comprobar( '0.2.0', $release['version'], 'quita la «v» del tag' );
comprobar(
	'https://github.com/webprogramacion/escritoriowp/releases/download/v0.2.0/escritoriowp-0.2.0.zip',
	$release['paquete'],
	'toma la URL de descarga del zip'
);
comprobar( "### Añadido\n\n- Release automática.", $release['notas'], 'conserva las notas de la release' );
comprobar( '2026-09-20T10:00:00Z', $release['publicada'], 'conserva la fecha de publicación' );

comprobar(
	'0.2.0',
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'tag_name' => '0.2.0' ) ) )['version'],
	'un tag sin «v» también vale'
);

comprobar(
	'1.10.0',
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'tag_name' => 'v1.10.0' ) ) )['version'],
	'las versiones de dos cifras se leen enteras'
);

comprobar(
	null,
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'tag_name' => 'ultima' ) ) ),
	'un tag que no es una versión se descarta'
);

comprobar(
	null,
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'assets' => array() ) ) ),
	'una release sin activos se descarta'
);

comprobar(
	null,
	ActualizadorGithub::interpretar_release(
		release_de_ejemplo( array( 'assets' => array( array( 'name' => 'notas.txt', 'browser_download_url' => 'https://ejemplo.test/notas.txt' ) ) ) )
	),
	'una release sin ningún zip se descarta'
);

comprobar(
	'https://ejemplo.test/escritoriowp-0.2.0.zip',
	ActualizadorGithub::interpretar_release(
		release_de_ejemplo(
			array(
				'assets' => array(
					array( 'name' => 'escritoriowp-0.2.0.zip.asc', 'browser_download_url' => 'https://ejemplo.test/firma' ),
					array( 'name' => 'escritoriowp-0.2.0.zip', 'browser_download_url' => 'https://ejemplo.test/escritoriowp-0.2.0.zip' ),
				),
			)
		)
	)['paquete'],
	'encuentra el zip aunque no sea el primer activo'
);

comprobar(
	null,
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'draft' => true ) ) ),
	'un borrador se descarta'
);

comprobar(
	null,
	ActualizadorGithub::interpretar_release( release_de_ejemplo( array( 'prerelease' => true ) ) ),
	'un prelanzamiento se descarta'
);

comprobar( null, ActualizadorGithub::interpretar_release( array() ), 'una respuesta vacía se descarta' );
