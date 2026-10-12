<?php
/**
 * Pruebas de la preparación de versiones nuevas.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

grupo( 'Nueva versión · cálculo del número' );

comprobar( '0.2.2', calcular_version( '0.2.1', 'patch' ), 'patch sube el tercer número' );
comprobar( '0.3.0', calcular_version( '0.2.1', 'minor' ), 'minor sube el segundo y pone el tercero a cero' );
comprobar( '1.0.0', calcular_version( '0.2.1', 'major' ), 'major sube el primero y pone los otros a cero' );
comprobar( '0.10.0', calcular_version( '0.9.9', 'minor' ), '0.9.9 + minor es 0.10.0, no 1.0.0' );
comprobar( '1.0.0', calcular_version( '0.2.1', '1.0.0' ), 'una versión explícita mayor se acepta' );
comprobar( '0.2.10', calcular_version( '0.2.9', '0.2.10' ), 'la explícita se compara por números, no como texto' );
comprobar( null, calcular_version( '0.2.1', '0.2.1' ), 'una versión explícita igual se rechaza' );
comprobar( null, calcular_version( '0.2.1', '0.1.9' ), 'una versión explícita menor se rechaza' );
comprobar( null, calcular_version( '0.2.1', '1.0' ), 'una versión incompleta se rechaza' );
comprobar( null, calcular_version( '0.2.1', 'v1.0.0' ), 'una versión con «v» se rechaza' );
comprobar( null, calcular_version( '0.2.1', 'mayor' ), 'una petición desconocida se rechaza' );
comprobar( null, calcular_version( '0.2.1', '1.0.0-beta.1' ), 'las versiones previas no se admiten' );
comprobar( null, calcular_version( '0.2', 'patch' ), 'sin versión actual válida no se calcula nada' );

grupo( 'Nueva versión · versión en los tres sitios' );

$php_real    = (string) file_get_contents( dirname( __DIR__ ) . '/escritoriowp/escritoriowp.php' );
$readme_real = (string) file_get_contents( dirname( __DIR__ ) . '/escritoriowp/readme.txt' );
$declaradas  = versiones_declaradas( $php_real, $readme_real );
$actual      = (string) $declaradas['cabecera'];

comprobar_que( 1 === preg_match( PATRON_VERSION, $actual ), "la cabecera real declara una versión X.Y.Z ($actual)" );
comprobar(
	array(
		'cabecera'  => $actual,
		'constante' => $actual,
		'estable'   => $actual,
	),
	$declaradas,
	'la constante y el Stable tag reales coinciden con la cabecera'
);

$siguiente     = (string) calcular_version( $actual, 'minor' );
$php_fijado    = fijar_version_plugin( $php_real, $siguiente );
$readme_fijado = fijar_stable_tag( $readme_real, $siguiente );

comprobar(
	array(
		'cabecera'  => $siguiente,
		'constante' => $siguiente,
		'estable'   => $siguiente,
	),
	versiones_declaradas( $php_fijado, $readme_fijado ),
	"tras fijar $siguiente se lee $siguiente en los tres sitios"
);

$lineas_php     = explode( "\n", $php_real );
$cambios_php    = array_diff_assoc( explode( "\n", $php_fijado ), $lineas_php );
$lineas_readme  = explode( "\n", $readme_real );
$cambios_readme = array_diff_assoc( explode( "\n", $readme_fijado ), $lineas_readme );

comprobar( count( $lineas_php ), count( explode( "\n", $php_fijado ) ), 'escritoriowp.php conserva el número de líneas' );
comprobar( 2, count( $cambios_php ), 'en escritoriowp.php solo cambian dos líneas' );
comprobar_que(
	2 === count( array_filter( $cambios_php, fn( $linea ) => str_contains( $linea, 'Version:' ) || str_contains( $linea, 'const VERSION' ) ) ),
	'esas dos líneas son la cabecera Version: y la constante VERSION'
);
comprobar( count( $lineas_readme ), count( explode( "\n", $readme_fijado ) ), 'readme.txt conserva el número de líneas' );
comprobar( array( 'Stable tag: ' . $siguiente ), array_values( $cambios_readme ), 'en readme.txt solo cambia la línea Stable tag' );

$con_relleno = " * Version:           0.2.1\n */\nconst VERSION    = '0.2.1';\nconst VERSION_MINIMA = '0.0.1';\n";

comprobar(
	" * Version:           0.3.0\n */\nconst VERSION    = '0.3.0';\nconst VERSION_MINIMA = '0.0.1';\n",
	fijar_version_plugin( $con_relleno, '0.3.0' ),
	'se conserva el relleno de espacios y no se tocan constantes con nombre parecido'
);
comprobar(
	array(
		'cabecera'  => null,
		'constante' => null,
		'estable'   => null,
	),
	versiones_declaradas( "<?php\n", "=== X ===\n" ),
	'sin declaraciones se devuelve null en los tres sitios'
);

grupo( 'Nueva versión · sección del changelog' );

$changelog = <<<'MD'
# Registro de cambios

## [No publicado]

### Corregido

- Un arreglo.

## [0.2.1] - 2026-09-13

### Corregido

- Lo anterior.
MD;

$promovido = promover_no_publicado( $changelog, '0.3.0', '2026-10-11' );

comprobar(
	"# Registro de cambios\n\n## [No publicado]\n\n## [0.3.0] - 2026-10-11\n\n### Corregido\n\n- Un arreglo.\n\n## [0.2.1] - 2026-09-13\n\n### Corregido\n\n- Lo anterior.",
	$promovido,
	'deja [No publicado] vacía encima de la sección de la versión con el mismo cuerpo'
);
comprobar( "### Corregido\n\n- Un arreglo.", extraer_seccion_changelog( (string) $promovido, '0.3.0' ), 'las notas de la release salen del cuerpo promovido' );
comprobar( null, extraer_seccion_changelog( (string) $promovido, 'No publicado' ), 'la sección [No publicado] nueva queda vacía' );
comprobar( "### Corregido\n\n- Lo anterior.", extraer_seccion_changelog( (string) $promovido, '0.2.1' ), 'las secciones anteriores no cambian' );
comprobar(
	"## [No publicado]\n\n## [1.0.0] - 2026-10-11\n\n- Última sección del fichero.\n",
	promover_no_publicado( "## [No publicado]\n\n- Última sección del fichero.\n", '1.0.0', '2026-10-11' ),
	'funciona cuando [No publicado] es la última sección'
);
comprobar(
	"## [No publicado]\r\n\r\n## [1.0.0] - 2026-10-11\r\n\r\n- Con saltos de Windows.\r\n",
	promover_no_publicado( "## [No publicado]\r\n\r\n- Con saltos de Windows.\r\n", '1.0.0', '2026-10-11' ),
	'conserva los saltos de línea de Windows'
);
comprobar( null, promover_no_publicado( "## [No publicado]\n\n\n## [0.2.1] - 2026-09-13\n\n- Algo.\n", '0.3.0', '2026-10-11' ), '[No publicado] vacía devuelve null' );
comprobar( null, promover_no_publicado( "## [0.2.1] - 2026-09-13\n\n- Algo.\n", '0.3.0', '2026-10-11' ), 'sin [No publicado] devuelve null' );

grupo( 'Nueva versión · coherencia' );

$changelog_real = (string) file_get_contents( dirname( __DIR__ ) . '/CHANGELOG.md' );

comprobar( array(), problemas_de_coherencia( $php_real, $readme_real, $changelog_real ), "el repositorio actual ($actual) no tiene problemas" );

$php_prueba       = " * Version: 0.3.0\nconst VERSION = '0.3.0';\n";
$readme_prueba    = "Stable tag: 0.3.0\n\n== Changelog ==\n\n= 0.3.0 =\n* Algo.\n\n== Upgrade Notice ==\n\n= 0.3.0 =\nAviso.\n";
$changelog_prueba = "## [No publicado]\n\n## [0.3.0] - 2026-10-11\n\n- Algo.\n";

comprobar( array(), problemas_de_coherencia( $php_prueba, $readme_prueba, $changelog_prueba ), 'una versión completa no tiene problemas' );

$distintas = problemas_de_coherencia( $php_prueba, str_replace( 'Stable tag: 0.3.0', 'Stable tag: 0.2.1', $readme_prueba ), $changelog_prueba );

comprobar( 1, count( $distintas ), 'versiones distintas dan un solo problema' );
comprobar_que(
	str_contains( $distintas[0] ?? '', 'cabecera=0.3.0' ) && str_contains( $distintas[0] ?? '', 'VERSION=0.3.0' ) && str_contains( $distintas[0] ?? '', 'Stable tag=0.2.1' ),
	'el mensaje cita los tres valores leídos'
);
comprobar(
	array( 'CHANGELOG.md no tiene la sección «## [0.3.0]» con contenido.' ),
	problemas_de_coherencia( $php_prueba, $readme_prueba, "## [No publicado]\n\n- Algo.\n" ),
	'detecta que falta la sección de CHANGELOG.md'
);
comprobar(
	array( 'readme.txt no tiene la entrada «= 0.3.0 =» en su changelog.' ),
	problemas_de_coherencia( $php_prueba, "Stable tag: 0.3.0\n\n== Changelog ==\n\n= 0.2.1 =\n* Algo.\n\n== Upgrade Notice ==\n\n= 0.3.0 =\nAviso.\n", $changelog_prueba ),
	'detecta que falta la entrada del readme, aunque el aviso de actualización la tenga'
);
comprobar(
	array( 'La cabecera de escritoriowp/escritoriowp.php no declara ninguna versión.' ),
	problemas_de_coherencia( "<?php\n", $readme_prueba, $changelog_prueba ),
	'sin versión en la cabecera lo dice y no sigue'
);
