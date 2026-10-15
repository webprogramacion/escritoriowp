# EscritorioWP

Plugin de WordPress que sustituye el escritorio nativo por una interfaz propia y reemplaza la
paleta de comandos de wp-admin por un lanzador que además busca contenido real del sitio
(entradas, páginas, usuarios, productos y pedidos).

## Qué hace

- **Escritorio propio**: indicadores (entradas, páginas, usuarios, comentarios, actualizaciones y,
  con WooCommerce, productos, pedidos e ingresos del mes) y paneles con los últimos elementos de
  cada tipo.
- **Creación rápida**: crear entradas, páginas, usuarios y productos desde un modal sin salir del
  escritorio.
- **Lanzador ⌘K / Ctrl+K**: sustituye la paleta nativa del administrador. Busca pantallas del menú,
  acciones rápidas y contenido por título, email, SKU o número de pedido.
- **Personalización por usuario**: mostrar u ocultar paneles, reordenarlos y elegir tema claro,
  oscuro o automático.
- **Revisión de administradores**: la pantalla EscritorioWP › Administradores lista a los
  administradores y marca en rojo a quien tiene permisos de administrador sin el rol, guardados en la
  base de datos, o está oculto en la pantalla Usuarios. Un número rojo en el menú avisa, y el botón
  «Retirar permisos» quita los asignados directamente al usuario, tras confirmarlo.
- **Avisos por correo**: un correo cada vez que alguien activa, desactiva, instala o borra un plugin,
  con una casilla por evento, los destinatarios que elijas y un asunto y un mensaje con marcadores
  (`{evento}`, `{plugins}`, `{usuario}`, `{ip}`…). Un cambio en lote manda un solo correo. Con otra
  casilla, una revisión cada 6 horas (WP-Cron) avisa de los administradores nuevos o que dejan de
  serlo y de los usuarios que ganan o pierden permisos de administrador sin el rol, con el mismo
  criterio que la pantalla Administradores.
- **Registro de avisos**: cada aviso por correo queda guardado en el sitio con el resultado del envío
  («Enviado», «Fallido» con su motivo o «Sin confirmar»), también si el correo falla. La pantalla
  EscritorioWP › Avisos los lista con filtros por tipo y estado y muestra el mensaje completo de cada
  uno, y el escritorio de los administradores enseña los cinco últimos en el bloque «Avisos por
  correo», arriba a la derecha. Los que cada administrador no ha visto se marcan en rojo: campana con
  número, punto en cada aviso y contador en el menú lateral.
- **WooCommerce opcional**: si está activo añade productos y pedidos; compatible con HPOS.

## Estructura del repositorio

```
<raíz>              Material de desarrollo. NO se sube a WordPress.
├── escritoriowp/   EL PLUGIN: esto es lo que se copia a wp-content/plugins/
├── openspec/       Specs y propuestas de cambio
├── tests/          Pruebas unitarias sin WordPress (php y node)
├── tools/          Utilidades de desarrollo (generación del .pot)
└── docs/           Documentación interna (lista de QA manual)
```

## Requisitos

- WordPress 6.7 o superior
- PHP 8.1 o superior
- WooCommerce 8.0 o superior (opcional)

## Instalación para desarrollo

1. Copia o enlaza el directorio `escritoriowp/` en `wp-content/plugins/` de tu instalación.
2. Actívalo desde Plugins.
3. Ajusta su comportamiento en el menú EscritorioWP › Ajustes.

No hay paso de compilación: el CSS y el JavaScript se escriben a mano y se sirven tal cual.

## Comandos útiles

```bash
composer lint          # phpcs con el estándar WordPress
composer lint:fix      # phpcbf
composer make-pot      # regenera languages/escritoriowp.pot (requiere wp-cli)
php tools/generar-pot.php   # alternativa sin wp-cli
php tests/ejecutar.php      # pruebas unitarias PHP
node tests/ejecutar.js      # pruebas unitarias JavaScript

php tools/empaquetar.php                  # dist/escritoriowp-X.Y.Z.zip, el zip que se sube a WordPress
php tools/empaquetar.php --wordpress-org  # la misma versión sin el actualizador desde GitHub
php tools/notas-release.php 0.2.0         # imprime las notas de esa versión desde CHANGELOG.md
php tools/nueva-version.php minor --simular   # imprime la versión que saldría, sin escribir nada
php tools/nueva-version.php --comprobar       # versiones y changelogs coherentes, como el workflow
```

## Publicación

Las releases se publican solas. Al empujar a `main` un commit cuya versión de cabecera todavía no
tiene tag, el workflow pasa las comprobaciones, crea el tag `vX.Y.Z` y publica la release con
`escritoriowp-X.Y.Z.zip`, que es el fichero que se sube a WordPress. Las instalaciones con el
plugin ya activo detectan esa release y se actualizan desde el propio escritorio.

Para preparar una versión, ejecuta `/nueva-version` en Claude Code (opcionalmente con `patch`,
`minor`, `major` o un número `X.Y.Z`). Comprueba que `main` está al día y redacta las novedades a
partir de lo que ha cambiado desde el último tag. Te las enseña antes de escribir nada. Después sube
la versión, actualiza `CHANGELOG.md` y `readme.txt`, regenera el `.pot`, pasa las pruebas, `phpcs` y
el empaquetado, y hace el commit `Release X.Y.Z` en `main`. El push lo hace solo si se lo confirmas,
porque es lo que publica.

Sin la skill, los mismos pasos se hacen a mano; `php tools/nueva-version.php --comprobar` verifica
al final que versiones y changelogs cuadran. Los pasos completos están en [CLAUDE.md](CLAUDE.md),
sección «Publicar una versión». Para enviar el plugin al directorio oficial de WordPress.org,
consulta [docs/publicar-wordpress-org.md](docs/publicar-wordpress-org.md).

Consulta [CLAUDE.md](CLAUDE.md) para la arquitectura y las convenciones del proyecto.
