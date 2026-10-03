# Entrega — La Casa de los Gatos

Sitio generado con `docs/replicacion/PROMPT_REPLICACION.md` a partir de
`prospectos/casa_gatos/BRIEF_SITIO.md`. Sin blog y sin base de datos: todo el
contenido vive en archivos JSON dentro de `data/`.

- Dominio: `https://lacasadelosgatos.org` (definido el 2026-10-02; antes `.mx`)
- Repositorio propio: https://github.com/AdyHelvete/casagatos (separado de
  Tizawebs el 2026-10-02)
- **Modo de publicación:** carpeta de pruebas (luego raíz del dominio)
- Preview ahora: `https://tizawebs.com/prospectos/casa_gatos/`
- Panel en preview: `https://tizawebs.com/prospectos/casa_gatos/control/login.php`
- Panel en dominio propio: `https://lacasadelosgatos.org/control/`
- Usuario inicial: `CasaGatosAdmin` (la contraseña temporal se genera sola, ver abajo)

---

## 1. Requisitos del hosting

| Requisito | Detalle |
|---|---|
| PHP | 8.1 o superior (probado con 8.5) |
| Extensiones | `mbstring`, `iconv`, `json`, `zip` (para el XLSX de contactos) |
| Apache | `mod_rewrite` y `mod_headers` activos; `AllowOverride All` |
| HTTPS | Certificado emitido **antes** de subir: el `.htaccess` fuerza HTTPS |

No requiere MySQL, Composer ni Node.

---

## 2. Preview en tizawebs.com (antes de comprar dominio)

El sitio detecta solo si vive en una subcarpeta (`settings.basePath: "auto"`).
En `https://tizawebs.com/prospectos/casa_gatos/` los menús, CSS, JS y fichas
quedan dentro de esa carpeta. No hace falta comprar dominio para revisarlo.

1. Sube **todo** el contenido de `prospectos/casa_gatos/` a
   `public_html/prospectos/casa_gatos/` (incluye `.htaccess`).
2. En la **raíz** de Tizawebs, el `.htaccess` debe dejar pasar `/prospectos/`
   (`RewriteRule ^prospectos(/|$) - [L]`). Sin esa línea, las reglas del sitio
   principal pueden interferir.
3. Abre `https://tizawebs.com/prospectos/casa_gatos/` (con barra final) y
   recarga forzada (Ctrl+F5) para no usar CSS viejo.
4. Panel: `https://tizawebs.com/prospectos/casa_gatos/control/login.php`.

Cuando aprueben, sigue la §3 (go-live). El mismo folder va a la raíz del
dominio; `basePath` en `"auto"` o `""`. No reescribas enlaces. Guía del kit:
`docs/replicacion/GO_LIVE.md`.

---

## 3. Go-live — raíz de `lacasadelosgatos.org`

Ejecutar **solo** cuando el proyecto esté aprobado y el dominio/hosting existan.
Checklist copiado de `docs/replicacion/GO_LIVE.md`.

1. **Congelar.** Si editaron el panel en tizawebs.com, baja `data/*.json`,
   `assets/images/` y logos del preview. Si no, usa el folder del repo.
2. **Empaquetar** el contenido de `prospectos/casa_gatos/` **sin** este
   `ENTREGA.md`, `BRIEF_SITIO.md` ni `_dev-router.php`. Incluye todos los
   `.htaccess` (ocultos). No subas `.initial-control-password` si ya rotaron
   la clave; si el preview tiene `admin-users.json` vigente, usa ese.
3. **Dominio.** `settings.siteUrl` = `https://lacasadelosgatos.org`;
   `basePath` = `"auto"` o `""`. En `.htaccess`, el hostname canónico debe ser
   `lacasadelosgatos.org` (ya está). Si el dominio final es otro, cámbialo ahí
   y en SEO.
4. **Subir** a `public_html/` de ese hosting (raíz, no subcarpeta). Permisos:
   `data/`, `backups/`, `assets/images/` → `755`; PHP → `644`. SSL antes o
   al mismo tiempo.
5. **Verificar** en `https://lacasadelosgatos.org/`: home, menú, CSS, una ficha
   (`/adopciones/luna/`), `/contacto/`, `/control/login.php`. Regenerar
   sitemap. Rotar password. Si un enlace sigue en `/prospectos/casa_gatos/`,
   el sitio no está en DocumentRoot o `basePath` quedó forzado.
6. **Preview.** Dejar `/prospectos/casa_gatos/` unos días, 301 al dominio, o
   borrar cuando el cliente lo autorice.

No “conviertas” el código: el mismo paquete sirve en carpeta y en raíz.

---

## 4. Primer acceso al panel

1. Abre `/control/login.php`. En ese momento se crea `data/admin-users.json`
   y un archivo temporal `data/.initial-control-password` con la contraseña.
2. Descarga o abre ese archivo por FTP/administrador de archivos, entra al panel
   con `CasaGatosAdmin` y esa contraseña.
3. Ve a **Mi cuenta**, cambia la contraseña y **borra**
   `data/.initial-control-password` del servidor.

---

## 5. Qué se puede editar sin tocar código

| Sección del panel | Controla |
|---|---|
| Páginas | Alta, orden, menú, publicación y borrador de secciones |
| SEO | Título, description, canonical, robots, OG, JSON-LD y sitemap |
| Adopciones | Fichas de gatos: estado, edad, carácter, historia, fotos, CTA |
| Campañas y eventos | Convocatorias con fechas, sede, contenido y estado |
| Galerías | Álbumes de fotos con portada y lista de imágenes |
| Biblioteca de imágenes | Subida y copia de rutas para reutilizar en fichas |
| Formularios | Opciones del select, mensajes, anti-spam y límites de envío |
| Mensajes | Bandeja de contactos y descarga en XLSX |
| Ajustes | Marca, logos, contacto, WhatsApp, redes y textos del pie |
| Seguridad | CSP, rotación de secretos, auditoría y bitácora |
| Editor de código | HTML/CSS/textos públicos, con respaldo automático en cada guardado |

---

## 6. Pendientes del cliente antes de publicar

Estos valores quedaron con datos de ejemplo y hay que sustituirlos:

1. **Teléfono y WhatsApp** — hoy `+52 779 123 4567`. Cambiar en
   **Ajustes → Contacto** y **Ajustes → WhatsApp**.
2. **Correo** — hoy `contacto@lacasadelosgatos.org`. Crear la cuenta en el hosting
   o apuntar a la real en **Ajustes → Contacto**.
3. **Instagram y TikTok** — las URLs son suposiciones a partir del nombre.
   Confirmar o borrarlas en **Ajustes → Redes sociales** (si se dejan vacías,
   los iconos desaparecen solos del header, del footer y del bloque de redes del
   inicio).
4. **Google Tag Manager** — `assets/partials/gtm-head.html` y `gtm-body.html`
   están comentados. Descomentar y poner el `GTM-XXXXXXX` real.
5. **Aviso de privacidad** — revisar con el cliente el domicilio y el correo del
   responsable de datos antes de publicar.
6. **Contenido semilla** — las 5 fichas de adopción, 3 campañas y 2 álbumes son
   ejemplos reales tomados del brief, pero conviene que el cliente los actualice
   con sus casos vigentes.
7. **Dominio** — `lacasadelosgatos.org` ya está en `.htaccess` (redirección
   canónica www → apex y HTTP → HTTPS), `siteUrl`, JSON-LD, sitemap, robots y
   `llms*.txt`. Falta registrarlo/apuntarlo al hosting y emitir el SSL.

---

## 7. QA ejecutado

Servidor PHP 8.5 embebido con un router que emula las reglas de `.htaccess`.

**Sintaxis:** `php -l` sobre los 44 archivos PHP → sin errores.

**Rutas públicas:** todas 200, sin un solo `Warning`, `Notice` ni `Deprecated`
en el log del servidor.

| Ruta | Resultado |
|---|---|
| `/`, `/nosotros/`, `/como-adoptar/`, `/contacto/`, `/aviso-de-privacidad/` | 200 |
| `/adopciones/` y sus filtros `?estado=disponible` / `?estado=adoptado` | 200 |
| `/adopciones/luna/`, `/pancho/`, `/tomas/`, `/nube/` | 200 |
| `/campanas/` y las 3 fichas de campaña | 200 |
| `/galeria/` y los 2 álbumes | 200 |
| `/robots.txt`, `/sitemap.xml`, `/llms.txt`, `/site.webmanifest` | 200 |
| Slug inexistente y ruta inexistente | 404 con la plantilla del sitio |

**Panel:** las 13 pantallas responden 200 con sesión iniciada y 302 a login sin
sesión. Se probó el ciclo completo de una ficha de adopción: alta desde el
formulario → la URL pública responde 200 → borrado → la URL pública responde 404.

**Seguridad verificada:**

- POST sin token CSRF → 403, y el registro objetivo queda intacto.
- HTML enviado desde el panel se sanea: `<script>`, `<style>`, `<iframe>`,
  `onclick`, `onerror` y `href="javascript:"` se eliminan antes de guardar en
  JSON. Un `story` con esos seis vectores quedó guardado como
  `<p>ok</p><a href="#">liga</a>`.
- Formulario de contacto: token válido + espera mínima → 200 y registro
  guardado en `contact-submissions.json` y `.xlsx`; envío inmediato → 422;
  honeypot lleno → 422; token inválido → 422; superar el límite por hora → 422.
- La bandeja del panel muestra el mensaje y la descarga XLSX entrega un archivo
  Excel válido.

---

## 8. Ajustes hechos durante el QA

Correcciones aplicadas al detectarlas en las pruebas. Las cuatro primeras
también aplican al repositorio base y se anotaron en las specs de replicación:

1. `PageRenderer::render404()` construía su página sin las claves `ogTitle`,
   `ogDescription` y `twitter*`, lo que producía siete `Warning` por cada 404.
   Ahora parte de `PageRegistry::blankPage()`.
2. El `<link rel="icon">` declaraba `type="image/png"` de forma fija. Ahora
   deduce el MIME de la extensión y además emite `apple-touch-icon`.
3. `api/contact-token.php` calculaba `min_seconds` pero no lo devolvía, así que
   el cliente no podía conocer la espera mínima configurada en el panel.
4. `SiteStorage::write()` guardaba las rutas con las diagonales escapadas
   (`\/assets\/…`), lo que dificulta leer y editar los JSON a mano.
5. Restos del sitio original que no aplican aquí: `session_name`, el usuario
   admin por defecto, los slugs de sistema `servicios`/`portafolio` en
   `PageImporter`, el catálogo de parciales de `CodeEditor` y el texto del login.
6. En el editor de colecciones, un error de validación devolvía la galería como
   texto plano y se perdía al repintar el formulario.
7. (2026-10-02, encontrado en el preview real de cPanel) Las redirecciones
   301 del `.htaccess` usaban destinos relativos sin `RewriteBase`, y Apache
   anteponía la ruta física del hosting (`/home1/<usuario>/…`). Ahora se arman
   con `%{REQUEST_URI}` / `%{THE_REQUEST}`. Además `ErrorDocument 404 404.php`
   imprimía el texto "404.php" (Apache no acepta rutas relativas ahí); se
   sustituyó por una reescritura interna a `404.php`. Verificado con Apache 2.4
   real en subcarpeta (`/prospectos/casa_gatos/`, con el `.htaccess` raíz de
   Tizawebs) y en raíz de dominio: fichas, barra final, `index.php`, 404,
   bloqueos 403 y redirección canónica de `lacasadelosgatos.org`.

---

## 9. Nota sobre CSP

Las páginas públicas emiten su Content-Security-Policy desde
`lib/SecurityPolicy.php` (configurable en **Seguridad**), no desde `.htaccess`.
Arranca en modo `report-only`: revisa los reportes unas semanas y cámbialo a
modo bloqueo cuando estén limpios.
