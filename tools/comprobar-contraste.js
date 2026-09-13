/**
 * Comprueba que la paleta de EscritorioWP cumple el contraste AA de WCAG en los dos temas.
 *
 * Uso: node tools/comprobar-contraste.js
 *
 * Mínimos: 4.5:1 para texto normal y 3:1 para los bordes de los controles.
 */
'use strict';

const fs = require('fs');
const css = fs.readFileSync('escritoriowp/assets/css/comun.css', 'utf8');

function bloque(selector) {
  const i = css.indexOf(selector);
  if (i === -1) return null;
  const fin = css.indexOf('}', i);
  const tokens = {};
  css.slice(i, fin).replace(/--([\w-]+):\s*([^;]+);/g, (m, k, v) => { tokens[k] = v.trim(); return m; });
  return tokens;
}
function rgb(hex) {
  const h = hex.replace('#','');
  return [0,2,4].map(i => parseInt(h.slice(i,i+2),16));
}
function lum(hex) {
  return rgb(hex).map(c => c/255).map(c => c <= 0.03928 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4))
    .reduce((a,c,i) => a + c*[0.2126,0.7152,0.0722][i], 0);
}
function ratio(a, b) {
  const [l1, l2] = [lum(a), lum(b)].sort((x,y)=>y-x);
  return (l1 + 0.05) / (l2 + 0.05);
}

const temas = {
  claro: bloque('.escritoriowp,\n.escritoriowp-lanzador {'),
  oscuro: bloque('.escritoriowp[data-tema="oscuro"],'),
};

const pares = [
  ['texto', 'superficie', 4.5, 'texto principal sobre tarjeta'],
  ['texto', 'fondo', 4.5, 'texto principal sobre fondo'],
  ['texto-suave', 'superficie', 4.5, 'texto secundario sobre tarjeta'],
  ['texto-tenue', 'superficie', 4.5, 'texto terciario sobre tarjeta'],
  ['texto-suave', 'superficie-2', 4.5, 'texto secundario sobre cabecera'],
  ['acento', 'superficie', 4.5, 'enlace sobre tarjeta'],
  ['acento', 'acento-suave', 4.5, 'texto de acento sobre fondo de acento'],
  ['verde', 'verde-suave', 4.5, 'etiqueta verde'],
  ['ambar', 'ambar-suave', 4.5, 'etiqueta ámbar'],
  ['rojo', 'rojo-suave', 4.5, 'etiqueta roja'],
  ['azul', 'azul-suave', 4.5, 'etiqueta azul'],
  ['morado', 'morado-suave', 4.5, 'etiqueta morada'],
  ['borde-fuerte', 'superficie', 3, 'borde de campo sobre tarjeta'],
];

let fallos = 0;
for (const [tema, tokens] of Object.entries(temas)) {
  console.log('\n  Tema ' + tema);
  for (const [a, b, minimo, desc] of pares) {
    const ca = tokens['ewp-' + a], cb = tokens['ewp-' + b];
    if (!ca || !cb || !ca.startsWith('#') || !cb.startsWith('#')) { console.log('    ? ' + desc + ' (token no resuelto)'); continue; }
    const r = ratio(ca, cb);
    const ok = r >= minimo;
    if (!ok) fallos++;
    console.log('    ' + (ok ? '✓' : '✗') + ' ' + desc + ': ' + r.toFixed(2) + ':1 (mínimo ' + minimo + ')');
  }
}
console.log('\n  ' + (fallos === 0 ? 'todas las combinaciones cumplen AA' : fallos + ' combinaciones por debajo del mínimo'));
process.exit(fallos === 0 ? 0 : 1);
