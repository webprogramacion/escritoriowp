<?php
/**
 * Ejecuta todas las pruebas unitarias de PHP.
 *
 * Uso: php tests/ejecutar.php
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$raiz   = dirname( __DIR__ );
$plugin = $raiz . '/escritoriowp/includes/';

require_once $plugin . 'class-autoloader.php';
require_once $plugin . 'class-ajustes.php';
require_once $plugin . 'class-capacidades.php';
require_once $plugin . 'class-changelog.php';
require_once $plugin . 'escritorio/class-preferencias.php';
require_once $plugin . 'escritorio/class-resumen.php';
require_once $plugin . 'escritorio/class-recientes.php';
require_once $plugin . 'lanzador/class-catalogo-comandos.php';
require_once $plugin . 'busqueda/interface-fuente.php';
require_once $plugin . 'busqueda/class-resultado.php';
require_once $plugin . 'busqueda/class-grupo.php';
require_once $plugin . 'busqueda/class-buscador.php';
require_once $plugin . 'busqueda/class-fuente-pedidos.php';
require_once $plugin . 'actualizaciones/class-actualizador-github.php';

// Herramientas de desarrollo: incluirlas no ejecuta su punto de entrada de línea de órdenes.
require_once $raiz . '/tools/notas-release.php';
require_once $raiz . '/tools/empaquetar.php';

echo "Pruebas unitarias de EscritorioWP\n";

foreach ( glob( __DIR__ . '/prueba-*.php' ) as $archivo ) {
	require_once $archivo;
}

exit( resumen() );
