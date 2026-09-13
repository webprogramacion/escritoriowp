<?php
/**
 * Pruebas del cargador automático.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Autoloader;

grupo( 'Autoloader' );

comprobar( 'plugin.php', Autoloader::ruta_relativa( 'EscritorioWP\\Plugin' ), 'clase de primer nivel' );
comprobar( 'busqueda/fuente-usuarios.php', Autoloader::ruta_relativa( 'EscritorioWP\\Busqueda\\FuenteUsuarios' ), 'clase en subdirectorio' );
comprobar( 'rest/controlador-busqueda.php', Autoloader::ruta_relativa( 'EscritorioWP\\Rest\\ControladorBusqueda' ), 'controlador REST' );
comprobar( 'escritorio/preferencias.php', Autoloader::ruta_relativa( 'EscritorioWP\\Escritorio\\Preferencias' ), 'clase del escritorio' );
comprobar( '', Autoloader::ruta_relativa( 'Otro\\Espacio\\Clase' ), 'clase ajena al plugin' );
comprobar( 'catalogo-comandos', Autoloader::kebab( 'CatalogoComandos' ), 'kebab-case de dos palabras' );
comprobar( 'woo', Autoloader::kebab( 'Woo' ), 'kebab-case de una palabra' );
