# Enviar EscritorioWP al directorio de WordPress.org

Lista de comprobación para publicar el plugin en <https://wordpress.org/plugins/>. El repositorio
ya está preparado para generar la variante que exige el directorio, pero **hoy el plugin todavía no
se puede enviar**: hay dos requisitos que dependen de una decisión, no de código. Están los primeros.

## 0. Antes de nada: dos decisiones pendientes

### El nombre y el slug no son publicables

El directorio prohíbe la palabra «wp» en el nombre y en el slug de un plugin. Plugin Check lo marca
tres veces (`trademarked_term`): en el nombre «EscritorioWP» y en el slug `escritoriowp`.

No hay forma de sortearlo: para publicar hay que **renombrar el plugin**. Eso afecta al slug del
directorio, al nombre de la carpeta, al dominio de texto, al prefijo de opciones y metadatos, y a
las URL de las pantallas. Es un cambio grande y conviene decidirlo antes de escribir nada más.

Nombres posibles que no usan el término prohibido: «Escritorio Propio», «Panel Escritorio»,
«Escritorio y Lanzador». Conviene comprobar antes que el slug esté libre en el directorio.

### El readme tiene que estar en inglés

Desde julio de 2025 el directorio exige que el `readme.txt` esté escrito en inglés
(`readme_short_description_non_official_language` y `readme_description_non_official_language`).
El de este plugin está en español, como el resto del proyecto.

Hay que traducir al inglés la descripción corta, la descripción larga, las preguntas frecuentes y
las notas de actualización. El resto del plugin puede seguir en español: la traducción del readme no
obliga a cambiar el idioma de origen del código ni de la interfaz, que se traduce con los `.po`.

Mientras esas dos cosas no se resuelvan, el workflow las mantiene excluidas en Plugin Check con su
motivo escrito (`.github/workflows/release.yml`), para que el resto de comprobaciones siga siendo
útil. Excluirlas **no** es darlas por buenas.

## 1. Generar la variante del directorio

El directorio no permite que un plugin se actualice desde un servidor propio (directriz 8), así que
la copia que se envía no lleva el actualizador desde GitHub:

```bash
php tools/empaquetar.php --wordpress-org
```

Genera `dist/escritoriowp-X.Y.Z-wordpress-org.zip`, que se diferencia del zip normal en tres cosas:

- No incluye `includes/actualizaciones/`.
- La cabecera no tiene `Update URI`.
- La constante `REPOSITORIO` está vacía, y por eso el plugin no registra ningún enganche de
  actualizaciones ni consulta GitHub.

El árbol de trabajo no se toca: la transformación ocurre solo dentro del zip.

## 2. Pasar Plugin Check sobre esa variante

Plugin Check es la misma revisión automática que aplica el directorio. Sobre el zip anterior no debe
aparecer ningún problema salvo los dos pendientes del punto 0.

```bash
# Con una instalación de pruebas y wp-cli (ver docs/qa.md para montarla sin Docker):
wp plugin check escritoriowp --format=csv
```

Ejecútalo desde fuera del repositorio: si el directorio de trabajo es este repositorio, Plugin Check
mezcla sus ficheros con los de la instalación y da resultados falsos.

## 3. Comprobar el readme

- `Stable tag` coincide con la versión de la cabecera y con la constante `VERSION`.
- `Tested up to` es la última versión de WordPress con la que se ha probado de verdad.
- `Requires at least` y `Requires PHP` coinciden con la cabecera del plugin.
- La descripción corta no pasa de 150 caracteres.
- Hay sección `== Changelog ==` con la versión que se envía.
- Hay sección `== External services ==` describiendo los servicios externos. Ojo: en la variante del
  directorio el plugin **no** consulta GitHub, así que esa sección hay que ajustarla o quitarla en
  la copia que se envía, porque describiría algo que esa copia no hace.
- `Contributors` usa nombres de usuario reales de WordPress.org.
- `License` es compatible con GPL y coincide con la cabecera.

## 4. Recursos gráficos

No van dentro del zip: se suben a la carpeta `assets/` del repositorio SVN.

- `icon-128x128.png` y `icon-256x256.png`.
- `banner-772x250.png` y `banner-1544x500.png`.
- `screenshot-1.png` … `screenshot-5.png`, en el mismo orden que la sección `== Screenshots ==`
  del readme. Hoy esa sección enumera cinco capturas que todavía hay que hacer.

## 5. Enviar

1. Crear cuenta en WordPress.org si no la hay.
2. Subir el zip en <https://wordpress.org/plugins/developers/add/>.
3. Esperar la revisión manual, que suele tardar semanas y puede pedir cambios.
4. Cuando la aprueben, llega por correo la URL del repositorio SVN del plugin.

## 6. Publicar por SVN

```bash
svn co https://plugins.svn.wordpress.org/<slug> svn-plugin
cd svn-plugin
# El contenido del zip va en trunk/, sin la carpeta del plugin:
rsync -a --delete ../dist/desempaquetado/<slug>/ trunk/
svn add --force trunk assets
svn ci -m "Versión X.Y.Z"
svn cp trunk tags/X.Y.Z
svn ci -m "Etiquetar X.Y.Z"
```

`Stable tag` en el `readme.txt` de `trunk/` es lo que decide qué versión se sirve: hasta que apunte
a `X.Y.Z`, el directorio no entrega la versión nueva.

## 7. Convivencia con la versión de GitHub

Las dos distribuciones pueden coexistir, pero **no en la misma instalación**: son el mismo plugin y
ocuparían la misma carpeta. Quien lo instale desde el directorio recibirá las actualizaciones de
WordPress.org; quien lo instale desde GitHub, las de GitHub. Al publicar una versión hay que
acordarse de subir las dos: el push a `main` publica la de GitHub, y la del directorio se sube a
mano siguiendo esta guía.
