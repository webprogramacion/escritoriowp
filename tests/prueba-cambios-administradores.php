<?php
/**
 * Pruebas de la comparación entre revisiones de los administradores.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Avisos\CambiosAdministradores as Cambios;
use EscritorioWP\Seguridad\Permisos;

grupo( 'Avisos · situación de los administradores' );

$usuario = static fn( string $login, bool $administrador, bool $superadmin, array $criticos ): array => array(
	'login'         => $login,
	'administrador' => $administrador,
	'superadmin'    => $superadmin,
	'criticos'      => $criticos,
);

comprobar(
	array(
		2 => array(
			'login'  => 'ana',
			'estado' => 'administrador',
		),
		3 => array(
			'login'  => 'red',
			'estado' => 'administrador',
		),
		4 => array(
			'login'  => 'tienda',
			'estado' => 'marcado',
		),
	),
	Cambios::situacion(
		array(
			2 => $usuario( 'ana', true, false, array( 'manage_options' => 'administrator' ) ),
			3 => $usuario( 'red', false, true, array() ),
			4 => $usuario( 'tienda', false, false, array( 'manage_options' => Permisos::ORIGEN_USUARIO ) ),
			5 => $usuario( 'cliente', false, false, array() ),
		)
	),
	'la situación guarda administradores, superadministradores y marcados, y descarta al resto'
);
comprobar( array(), Cambios::situacion( array() ), 'sin usuarios la situación está vacía' );

comprobar( false, Cambios::valida( false ), 'una opción que no existe no es una situación' );
comprobar( false, Cambios::valida( '' ), 'una cadena no es una situación' );
comprobar( false, Cambios::valida( array( 'usuarios' => array() ) ), 'sin fecha no es una situación' );
comprobar(
	false,
	Cambios::valida(
		array(
			'fecha'    => 1760443200,
			'usuarios' => 'nadie',
		)
	),
	'con usuarios que no son una lista no es una situación'
);
comprobar(
	true,
	Cambios::valida(
		array(
			'fecha'    => 1760443200,
			'usuarios' => array(),
		)
	),
	'con fecha y una lista de usuarios, aunque esté vacía, es una situación'
);

grupo( 'Avisos · comparación de revisiones' );

$admin   = static fn( string $login ): array => array(
	'login'  => $login,
	'estado' => 'administrador',
);
$marcado = static fn( string $login ): array => array(
	'login'  => $login,
	'estado' => 'marcado',
);
$vacio   = array(
	Cambios::ADMINISTRADORES_NUEVOS => array(),
	Cambios::MARCADOS_NUEVOS        => array(),
	Cambios::YA_NO_ADMINISTRADORES  => array(),
	Cambios::YA_NO_MARCADOS         => array(),
);
$antes   = array(
	2 => $admin( 'ana' ),
	3 => $admin( 'luis' ),
	4 => $marcado( 'tienda' ),
);

comprobar( $vacio, Cambios::comparar( $antes, $antes ), 'sin cambios, los cuatro apartados están vacíos' );
comprobar( true, Cambios::sin_cambios( Cambios::comparar( $antes, $antes ) ), 'sin_cambios() lo reconoce' );
comprobar(
	array_merge( $vacio, array( Cambios::ADMINISTRADORES_NUEVOS => array( 9 ) ) ),
	Cambios::comparar( $antes, $antes + array( 9 => $admin( 'pepa' ) ) ),
	'un administrador nuevo'
);
comprobar( false, Cambios::sin_cambios( Cambios::comparar( $antes, $antes + array( 9 => $admin( 'pepa' ) ) ) ), 'sin_cambios() ve un administrador nuevo' );
comprobar(
	array_merge( $vacio, array( Cambios::ADMINISTRADORES_NUEVOS => array( 4 ) ) ),
	Cambios::comparar( $antes, array( 4 => $admin( 'tienda' ) ) + $antes ),
	'un marcado que pasa a administrador solo es administrador nuevo'
);
comprobar(
	array_merge( $vacio, array( Cambios::MARCADOS_NUEVOS => array( 3 ) ) ),
	Cambios::comparar( $antes, array( 3 => $marcado( 'luis' ) ) + $antes ),
	'un administrador que pasa a marcado solo es marcado nuevo'
);
comprobar(
	array_merge( $vacio, array( Cambios::YA_NO_ADMINISTRADORES => array( 3 ) ) ),
	Cambios::comparar( $antes, array_diff_key( $antes, array( 3 => true ) ) ),
	'un administrador que desaparece ya no es administrador'
);
comprobar(
	array_merge( $vacio, array( Cambios::YA_NO_MARCADOS => array( 4 ) ) ),
	Cambios::comparar( $antes, array_diff_key( $antes, array( 4 => true ) ) ),
	'un marcado que desaparece ya no está marcado'
);
comprobar(
	array(
		Cambios::ADMINISTRADORES_NUEVOS => array( 12, 10, 11 ),
		Cambios::MARCADOS_NUEVOS        => array(),
		Cambios::YA_NO_ADMINISTRADORES  => array( 2, 3 ),
		Cambios::YA_NO_MARCADOS         => array(),
	),
	Cambios::comparar(
		$antes,
		array(
			4  => $marcado( 'tienda' ),
			10 => $admin( 'carla' ),
			11 => $admin( 'zoe' ),
			12 => $admin( 'berta' ),
		)
	),
	'varios usuarios en un apartado salen ordenados por nombre de usuario'
);
comprobar(
	array_merge( $vacio, array( Cambios::YA_NO_MARCADOS => array( 4 ) ) ),
	Cambios::comparar( $antes + array( 7 => 'basura' ), array_diff_key( $antes, array( 4 => true ) ) ),
	'una entrada mal formada en la situación guardada no cuenta'
);

grupo( 'Avisos · líneas del aviso de administradores' );

comprobar( '- Ana Pérez (ana, ana@ejemplo.es)', Cambios::linea( 'ana', 'Ana Pérez', 'ana@ejemplo.es', array() ), 'con nombre visible, nombre de usuario y correo' );
comprobar( '- ana (ana@ejemplo.es)', Cambios::linea( 'ana', 'ana', 'ana@ejemplo.es', array() ), 'con el nombre visible igual al nombre de usuario' );
comprobar( '- luis', Cambios::linea( 'luis', '', '', array() ), 'sin nombre visible ni correo' );
comprobar(
	'- luis: Usuario borrado.',
	Cambios::linea( 'luis', '', '', array( 'Usuario borrado' ) ),
	'con un detalle, tras dos puntos y acabado en punto'
);
comprobar(
	'- Tienda (tienda, tienda@ejemplo.es): manage_options (asignado al usuario). Oculto en la pantalla Usuarios.',
	Cambios::linea( 'tienda', 'Tienda', 'tienda@ejemplo.es', array( 'manage_options (asignado al usuario)', 'Oculto en la pantalla Usuarios.' ) ),
	'con varios detalles, sin duplicar el punto final'
);
comprobar( '- Ana Pérez (ana)', Cambios::linea( 'ana', 'Ana Pérez', '', array() ), 'con nombre visible y sin correo' );
comprobar( '- Ana Pérez (ana, ana@ejemplo.es)', Cambios::linea( 'ana', "Ana\nPérez", 'ana@ejemplo.es', array( '' ) ), 'un nombre con salto de línea queda en una línea y un detalle vacío no se pinta' );

$lineas = array_map( static fn( int $n ): string => '- usuario' . $n, range( 1, 300 ) );

comprobar( 50, count( Cambios::recortar( $lineas, 50 )['lineas'] ), 'recortar() con 300 líneas y máximo 50 deja 50' );
comprobar( 250, Cambios::recortar( $lineas, 50 )['restantes'], 'recortar() con 300 líneas y máximo 50 deja 250 restantes' );
comprobar( '- usuario50', Cambios::recortar( $lineas, 50 )['lineas'][49], 'recortar() se queda con las primeras' );
comprobar(
	array(
		'lineas'    => array( 'a', 'b', 'c' ),
		'restantes' => 0,
	),
	Cambios::recortar( array( 'a', 'b', 'c' ), 50 ),
	'recortar() con menos líneas que el máximo las deja todas'
);
