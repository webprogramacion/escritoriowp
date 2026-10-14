<?php
/**
 * Pruebas del cálculo de roles y permisos críticos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Seguridad\Detector;
use EscritorioWP\Seguridad\Permisos;

/**
 * Clase de prueba: si se instancia al desserializar, deja una marca.
 */
final class EscritorioWP_Prueba_Trampa {

	/**
	 * Marca que se activa al despertar el objeto.
	 *
	 * @var bool
	 */
	public static bool $despertado = false;

	/**
	 * Se ejecuta al desserializar un objeto de esta clase.
	 *
	 * @return void
	 */
	public function __wakeup(): void {
		self::$despertado = true;
	}
}

grupo( 'Seguridad · permisos críticos' );

// Roles de un sitio estándar con WooCommerce, reducidos a lo que importa en cada prueba.
$roles = array(
	'administrator' => array(
		'name'         => 'Administrator',
		'capabilities' => array(
			'manage_options'  => true,
			'install_plugins' => true,
			'edit_users'      => true,
			'level_10'        => true,
			'read'            => true,
		),
	),
	'editor'        => array(
		'name'         => 'Editor',
		'capabilities' => array(
			'edit_others_posts' => true,
			'unfiltered_html'   => true,
			'moderate_comments' => true,
			'level_7'           => true,
			'read'              => true,
		),
	),
	'subscriber'    => array(
		'name'         => 'Subscriber',
		'capabilities' => array(
			'read'    => true,
			'level_0' => true,
		),
	),
	'shop_manager'  => array(
		'name'         => 'Shop manager',
		'capabilities' => array(
			'level_9'            => true,
			'import'             => true,
			'export'             => true,
			'list_users'         => true,
			'edit_theme_options' => true,
			'manage_woocommerce' => true,
			'read'               => true,
		),
	),
);

comprobar( 30, count( Permisos::CRITICOS ), 'la lista cerrada tiene los 30 permisos de la especificación' );
comprobar( 30, count( array_unique( Permisos::CRITICOS ) ), 'la lista no repite ningún permiso' );

foreach ( array( 'import', 'export', 'list_users', 'edit_theme_options', 'unfiltered_html', 'level_9' ) as $permiso ) {
	comprobar( false, in_array( $permiso, Permisos::CRITICOS, true ), "$permiso no es crítico" );
}

// Roles.
$resultado = Permisos::evaluar( array( 'administrator' => true ), $roles );
comprobar( true, $resultado['administrador'], 'el rol administrator hace administrador' );

$resultado = Permisos::evaluar( array( 'administrator' => false ), $roles );
comprobar( true, $resultado['administrador'], 'administrator con valor falso también hace administrador, como en WordPress' );
comprobar( array( 'administrator' ), $resultado['roles'], 'administrator con valor falso cuenta como rol' );

$resultado = Permisos::evaluar( array( 'subscriber' => true ), $roles );
comprobar( false, $resultado['administrador'], 'un suscriptor no es administrador' );
comprobar( array(), $resultado['criticos'], 'un suscriptor sin modificar no tiene permisos críticos' );

$resultado = Permisos::evaluar( array( 'editor' => true ), $roles );
comprobar( array(), $resultado['criticos'], 'un editor sin modificar no tiene permisos críticos' );

$resultado = Permisos::evaluar( array( 'shop_manager' => true ), $roles );
comprobar( array(), $resultado['criticos'], 'un gestor de tienda sin modificar no tiene permisos críticos' );

// Permisos sueltos.
$resultado = Permisos::evaluar(
	array(
		'subscriber'     => true,
		'manage_options' => true,
	),
	$roles
);
comprobar( array( 'manage_options' => 'usuario' ), $resultado['criticos'], 'manage_options suelto tiene origen usuario' );

$resultado = Permisos::evaluar(
	array(
		'subscriber' => true,
		'edit_users' => '1',
	),
	$roles
);
comprobar( array( 'edit_users' => 'usuario' ), $resultado['criticos'], 'edit_users con el valor "1" cuenta' );

$resultado = Permisos::evaluar(
	array(
		'subscriber'      => true,
		'install_plugins' => 0,
		'edit_users'      => '',
		'update_core'     => '0',
	),
	$roles
);
comprobar( array(), $resultado['criticos'], 'los valores vacíos (0, "", "0") no conceden nada' );

$resultado = Permisos::evaluar(
	array(
		'rol_inexistente' => true,
		'manage_options'  => true,
	),
	$roles
);
comprobar( array(), $resultado['roles'], 'la clave de un rol inexistente no es un rol' );
comprobar( array( 'manage_options' => 'usuario' ), $resultado['criticos'], 'con un rol inexistente el resto se evalúa igual' );

// Denegación explícita sobre un rol que concede el permiso.
$alterados                                                 = $roles;
$alterados['subscriber']['capabilities']['manage_options'] = true;

$resultado = Permisos::evaluar(
	array(
		'subscriber'     => true,
		'manage_options' => false,
	),
	$alterados
);
comprobar( array(), $resultado['criticos'], 'manage_options en falso en el usuario anula el del rol' );

// Rol alterado.
$alterados                                                  = $roles;
$alterados['subscriber']['capabilities']['install_plugins'] = true;

$resultado = Permisos::evaluar( array( 'subscriber' => true ), $alterados );
comprobar( array( 'install_plugins' => 'subscriber' ), $resultado['criticos'], 'install_plugins de un suscriptor alterado tiene origen subscriber' );

$resultado = Permisos::evaluar(
	array(
		'subscriber'     => true,
		'manage_options' => true,
	),
	$alterados
);
comprobar(
	array(
		'manage_options'  => 'usuario',
		'install_plugins' => 'subscriber',
	),
	$resultado['criticos'],
	'un permiso suelto y otro del rol conservan cada uno su origen'
);

// Con dos roles, el origen es el último que define el permiso, que es el que aporta el valor.
$dos                                             = $roles;
$dos['editor']['capabilities']['edit_users']     = false;
$dos['subscriber']['capabilities']['edit_users'] = true;

$resultado = Permisos::evaluar(
	array(
		'editor'     => true,
		'subscriber' => true,
	),
	$dos
);
comprobar( array( 'edit_users' => 'subscriber' ), $resultado['criticos'], 'con dos roles el origen es el último que define el permiso' );

$resultado = Permisos::evaluar(
	array(
		'subscriber' => true,
		'editor'     => true,
	),
	$dos
);
comprobar( array(), $resultado['criticos'], 'con el orden inverso el último rol lo deniega, como en WordPress' );

// Desserializado.
comprobar(
	array(
		'subscriber'     => true,
		'manage_options' => true,
	),
	Permisos::desserializar( 'a:2:{s:10:"subscriber";b:1;s:14:"manage_options";b:1;}' ),
	'un array serializado se recupera'
);
comprobar( array( 'administrator' => true ), Permisos::desserializar( "  a:1:{s:13:\"administrator\";b:1;}\n" ), 'se toleran espacios alrededor, como en WordPress' );
comprobar( array(), Permisos::desserializar( null ), 'un valor nulo da un array vacío' );
comprobar( array(), Permisos::desserializar( 42 ), 'un número da un array vacío' );
comprobar( array(), Permisos::desserializar( 'administrator' ), 'una cadena sin serializar da un array vacío' );
comprobar( array(), Permisos::desserializar( 'a:1:{s:13:"administr' ), 'un array serializado truncado da un array vacío' );
comprobar( array(), Permisos::desserializar( 's:24:"a:1:{s:4:\"read\";b:1;}";' ), 'una cadena serializada dos veces da un array vacío' );
comprobar( array(), Permisos::desserializar( 'O:8:"stdClass":0:{}' ), 'un objeto serializado da un array vacío' );

$trampa = Permisos::desserializar( serialize( array( 'manage_options' => new EscritorioWP_Prueba_Trampa() ) ) );
comprobar( false, EscritorioWP_Prueba_Trampa::$despertado, 'un objeto dentro del array no se instancia' );
comprobar( true, $trampa['manage_options'] instanceof __PHP_Incomplete_Class, 'el objeto queda como clase incompleta' );

// Roles alterados.
comprobar( array(), Permisos::roles_alterados( $roles ), 'en un sitio estándar con WooCommerce no hay roles alterados' );

$alterados                                                 = $roles;
$alterados['subscriber']['capabilities']['manage_options'] = true;
$alterados['subscriber_2']                                 = array(
	'name'         => 'Subscriber',
	'capabilities' => array_fill_keys( Permisos::CRITICOS, true ),
);
$alterados['7']                                            = array(
	'name'         => 'Numérico',
	'capabilities' => array( 'edit_files' => true ),
);

$resultado = Permisos::roles_alterados( $alterados );
comprobar( array( 'subscriber', 'subscriber_2', 7 ), array_keys( $resultado ), 'salen el suscriptor alterado, el que lo imita y el numérico, nunca administrator ni shop_manager' );
comprobar(
	array(
		'nombre'   => 'Subscriber',
		'criticos' => array( 'manage_options' ),
	),
	$resultado['subscriber'],
	'el suscriptor alterado sale con su nombre y su permiso crítico'
);
comprobar( Permisos::CRITICOS, $resultado['subscriber_2']['criticos'], 'el rol que imita al suscriptor sale con su slug y todos sus críticos' );

// Patrones del filtro grueso.
$patrones = Permisos::patrones( $roles );
comprobar( count( Permisos::CRITICOS ) + 1, count( $patrones ), 'sin roles alterados, un patrón por permiso crítico y otro para administrator' );

$patrones = Permisos::patrones( $alterados );
foreach ( array( '"administrator"', '"manage_options"', '"subscriber"', '"subscriber_2"', '"7"', 'i:7;' ) as $patron ) {
	comprobar( 1, count( array_keys( $patrones, $patron, true ) ), "los patrones incluyen $patron una sola vez" );
}
comprobar( false, in_array( '"shop_manager"', $patrones, true ), 'un rol sin alterar no genera patrón' );
comprobar( true, str_contains( serialize( array( 7 => true ) ), 'i:7;' ), 'el patrón numérico coincide con cómo se serializa la clave' );

// Orden de los usuarios marcados.
grupo( 'Seguridad · orden de los marcados' );

$marcados = array(
	array(
		'id'       => 10,
		'alta'     => '2025-05-01 10:00:00',
		'criticos' => array( 'install_plugins' => 'subscriber' ),
	),
	array(
		'id'       => 11,
		'alta'     => '2024-05-01 10:00:00',
		'criticos' => array(
			'manage_options'  => 'usuario',
			'install_plugins' => 'subscriber',
		),
	),
	array(
		'id'       => 12,
		'alta'     => '2026-05-01 10:00:00',
		'criticos' => array( 'install_plugins' => 'subscriber' ),
	),
	array(
		'id'       => 13,
		'alta'     => '2026-05-01 10:00:00',
		'criticos' => array( 'install_plugins' => 'subscriber' ),
	),
);

comprobar(
	array( 11, 13, 12, 10 ),
	array_column( Detector::ordenar_marcados( $marcados ), 'id' ),
	'primero el de permisos sueltos aunque sea el más antiguo; después los de rol, del alta más reciente a la más antigua, y a igual fecha el de identificador mayor'
);
comprobar( true, Detector::tiene_sueltos( $marcados[1] ), 'un permiso con origen usuario cuenta como suelto' );
comprobar( false, Detector::tiene_sueltos( $marcados[0] ), 'un permiso que solo viene del rol no cuenta como suelto' );
comprobar( array(), Detector::ordenar_marcados( array() ), 'sin marcados el orden es una lista vacía' );
comprobar( 100, Detector::LIMITE, 'se pintan como mucho 100 marcados' );
comprobar( 900, Detector::CADUCIDAD, 'el contador se cachea 15 minutos' );

// Sospechoso: decide si un alta de usuario invalida la caché del contador.
grupo( 'Seguridad · altas sospechosas' );

comprobar( false, Permisos::sospechoso( array( 'subscriber' => true ), $roles ), 'un cliente o suscriptor normal no es sospechoso' );
comprobar( false, Permisos::sospechoso( array( 'shop_manager' => true ), $roles ), 'un gestor de tienda no es sospechoso' );
comprobar( true, Permisos::sospechoso( array( 'administrator' => true ), $roles ), 'un administrador nuevo es sospechoso' );
comprobar(
	true,
	Permisos::sospechoso(
		array(
			'subscriber'  => true,
			'update_core' => true,
		),
		$roles
	),
	'un suscriptor con un permiso crítico suelto es sospechoso'
);
