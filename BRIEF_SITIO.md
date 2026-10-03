# BRIEF del sitio — La Casa de los Gatos

Slug: `casa_gatos`  
Destino de build: `prospectos/casa_gatos/`  
Estado: listo para generación (datos de contacto **demo**, editables en `/control/`).

---

## 1. Objetivo del proyecto

- **Nombre del sitio / marca:** La Casa de los Gatos
- **Dominio previsto:** `https://lacasadelosgatos.org` (definido el 2026-10-02; antes `.mx`)
- **Modo de publicación:** `carpeta` (pruebas en tizawebs.com **antes** de comprar dominio)
- **URL de pruebas:** `https://tizawebs.com/prospectos/casa_gatos/`
- **Una frase de propuesta de valor:** Rescate, asistencia y adopción responsable; campañas y eventos que suman apoyo en la región.
- **Audiencia principal:** Personas que quieren adoptar, donar, compartir campañas o seguir la causa en Tizayuca, Tecámac, Zumpango y alrededores.
- **Problema que resuelve:** Dar un espacio web claro que complemente redes (Facebook fuerte), genere conciencia, anuncie campañas/eventos y canalice adopciones y contacto.
- **Tono de voz:** Cercano, emotivo, esperanzador, con mensaje claro `#AdoptaNoCompres` — sin tono corporativo frío.
- **Idioma del sitio:** `es-MX`
- **Geo / zona de servicio:** Tizayuca, Tecámac, Zumpango y zona (Hidalgo / Edo. Méx. según operación real)
- **Prioridad del sitio (orden):**
  1. Generar **conciencia**
  2. Mantener atención a **campañas y eventos**
  3. Conseguir **seguidores y apoyos** (redes + contacto/donación/adopción)

---

## 2. Datos de contacto y marca operativa (DEMO)

Todos deben poder editarse desde `/control/` → Configuración (y formulario desde Formularios).

- **Teléfono (E.164):** `+527791234567`
- **Teléfono para mostrar:** `779 123 4567`
- **Email de contacto:** `contacto@lacasadelosgatos.org`
- **Ubicación / ciudad:** Tizayuca, Hidalgo (operación regional)
- **WhatsApp (dígitos):** `527791234567`
- **Mensaje por defecto de WhatsApp:** `Hola La Casa de los Gatos, me interesa conocer más sobre adopción o cómo apoyar.`
- **Etiqueta del botón flotante WhatsApp:** `¿Quieres adoptar o ayudar?`
- **Facebook URL:** https://www.facebook.com/mx.lacasadelosgatos
- **Instagram URL:** `https://www.instagram.com/lacasadelosgatos` (demo)
- **TikTok URL:** `https://www.tiktok.com/@lacasadelosgatos` (demo)

---

## 3. Identidad visual

### Logos e insumos (ya en el folder)
- **Logo principal:** `assets/logo/casa_gatos_logo.jpg`
- **Portada / hero base:** `assets/images/portada.jpg`
- **Galería inicial:** resto de archivos en `assets/images/`
- **Versión cache logos:** `1`

### Paleta (derivada del logo / portada)

| Token | Uso | Hex |
|-------|-----|-----|
| `--ink` | Texto / fondos oscuros (tinta cálida) | `#1a1612` |
| `--paper` | Fondo de página (crema / kraft) | `#f6efe4` |
| `--blue` | Acento primario (verde de marca) | `#3f6f1c` |
| `--blue-dark` | Hover primario | `#2d5213` |
| `--yellow` | Acento cálido (caja / energía) | `#e07a28` |
| `--white` | Superficies | `#fffdf8` |
| Extra | Lima ojos / “NO” del hashtag | `#b8dc3a` |
| Extra | Kraft de marcos | `#d8c4a4` |

### Tipografía
- **Display:** Fraunces (serif cálida, “casa / historia”)
- **Body:** Nunito Sans
- **Enlace Google Fonts:** Fraunces + Nunito Sans (no heredar Manrope/DM Sans del repo base)

### Dirección visual
**Casa de cartón, no sitio de agencia.** Cream/kraft, tinta cálida, verde lima del logo y naranja de la caja. Header fijo claro (no overlay oscuro). Hero en dos columnas: copy + portada en marco tipo polaroid/caja. Botones con borde y sombra tipo sello, no píldoras con flecha. Cards y page-heroes claros. Evitar look Tizawebs recolorido y look “ONG genérica” en azul corporativo. Priorizar fotos reales del folder + logo.

**Directiva de generación:** reutilizar `lib/`, `api/` y `/control/`; **rediseñar** `style.css` y el chrome. No copiar el CSS del repo base.

### Hero (home)
- **Imagen:** `assets/images/portada.jpg` (o recorte full-bleed)
- **Eyebrow:** Rescate y adopción · Tizayuca y zona
- **H1:** Adopta. No compres.
- **H1 acento:** La Casa de los Gatos
- **Lead:** Organizamos rescate, asistencia, campañas de esterilización y vacunación, y eventos que suman apoyo. El sitio complementa nuestra comunidad en redes.
- **CTA primario:** `Síguenos en Facebook` → https://www.facebook.com/mx.lacasadelosgatos
- **CTA secundario:** `Quiero adoptar o ayudar` → `/contacto/` o `/como-adoptar/`
- **Línea de apoyo:** `#AdoptaNoCompres`

---

## 4. Páginas y menú

### Núcleo
| Página | Incluir | Notas |
|--------|---------|--------|
| Inicio `/` | sí | Conciencia + campañas destacadas + puente a redes |
| Contacto `/contacto/` | sí | Formulario (adopción / apoyo / info) |
| Aviso `/aviso-de-privacidad/` | sí | Demo con marca La Casa de los Gatos |

### Módulos / páginas pedidas
| Módulo | ¿Incluir? | Outline |
|--------|-----------|---------|
| Nosotros `/nosotros/` | **sí** | Quiénes somos, misión, zona de operación, mensaje #AdoptaNoCompres |
| Adopciones (estilo tienda) `/adopciones/` | **sí** | Catálogo de animales/historias disponibles; fichas; CTA a formulario o WhatsApp. **Administrable en backend** |
| Cómo adoptar `/como-adoptar/` | **sí** | Pasos del proceso, requisitos, qué esperar; enlace a formulario y redes |
| Campañas y eventos `/campanas/` | **sí** | Listado + detalle. **CRUD completo en backend** (crear, editar, publicar, fechas, cover, descripción) |
| Galerías | **sí** | Una o más galerías de fotos administrables (no solo un listado fijo) |
| Portafolio genérico | **no** | Sustituido por adopciones + campañas |

### Orden del menú sugerido
1. Inicio  
2. Nosotros  
3. Adopciones  
4. Cómo adoptar  
5. Campañas  
6. Galería  
7. Contacto (botón)

### Secciones del Home (además del hero)
1. **Por qué existimos** — conciencia y adopción responsable  
2. **Campañas y eventos activos** — teaser desde backend (2–3 ítems)  
3. **Adopciones destacadas** — teaser estilo “tienda” (2–4 fichas)  
4. **Comunidad en redes** — Facebook + Instagram/TikTok demo, embed o CTAs fuertes  
5. **Cómo apoyar** — adoptar, compartir, contactar  
6. **CTA final** — WhatsApp + Facebook  

---

## 5. Portafolio

- **No** usar módulo portafolio genérico. Usar **Adopciones** + **Campañas**.

---

## 6. Panel y features

Panel base siempre on. Además **obligatorio para este prospecto**:

| Feature | ¿Incluir? | Notas |
|---------|-----------|--------|
| Configuración marca/contacto/redes | sí | Datos demo editables |
| Galería(s) múltiples | sí | Extiende galería simple del stack base |
| Adopciones (catálogo) | sí | Nuevo módulo contenido + panel |
| Campañas / eventos | sí | Nuevo módulo contenido + panel |
| Formulario contacto | sí | |
| A/B hero | **no** | |
| GTM | vacío por ahora | Placeholder off |

- **GTM Container ID:** (vacío)
- **A/B hero:** no

### Extensiones de arquitectura (respecto al PROMPT base)
El generador **debe implementar** en este prospecto (y documentar en ENTREGA.md):

1. **Campañas/eventos** — JSON en `data/` + UI en `/control/` (listado público + detalle).  
2. **Adopciones estilo tienda** — JSON en `data/` + UI en `/control/` (grid público, ficha, estado disponible/adoptado).  
3. **Galerías múltiples** — más de un álbum (título, imágenes, publicar).  

Si se reutiliza el patrón `SiteStorage` + páginas PHP del repo base, mantener sin blog/MySQL.

---

## 7. Formulario de contacto

Servicios del select (editables en panel):

```
Quiero adoptar
Quiero ser hogar temporal
Quiero donar o apoyar una campaña
Información sobre eventos
Otro
```

- **Texto de éxito:** Gracias. Te contactaremos pronto. Mientras tanto síguenos en Facebook.
- **¿Redirigir a WhatsApp tras enviar?** `sí` (opcional según config; preferido para apoyo rápido)

---

## 8. SEO y contenido base

- **Title home:** La Casa de los Gatos | Adopción y rescate en Tizayuca y zona
- **Meta description home:** Rescate, adopción responsable, campañas de esterilización y eventos de apoyo. #AdoptaNoCompres — Tizayuca, Tecámac, Zumpango.
- **OG image:** `assets/images/portada.jpg`
- **robots:** `index, follow` (en demo local puede usarse noindex si se prefiere staging)
- **Notas:** Enlazar claramente a Facebook en home y footer; el tráfico social es prioritario.

---

## 9. Legal y restricciones

- **Razón social / responsable (demo):** La Casa de los Gatos
- **Finalidades:** solicitudes de adopción, voluntariado, donativos/apoyo, información de campañas y contacto.
- **Claims a evitar:** no prometer “adopción inmediata” sin proceso; enfatizar adopción responsable.
- **¿Staging / no indexar al entregar?** `no` (demo público ok); ajustar si el dominio aún no es real.

---

## 10. Entrega hosting

- **Tipo:** Apache + PHP 8.x (cPanel)
- **Modo de publicación:** `carpeta` → `https://tizawebs.com/prospectos/casa_gatos/` (`basePath: auto`).
- **Go-live:** cuando aprueben, `docs/replicacion/GO_LIVE.md` + `ENTREGA.md` §3.
  Pasar este campo a `raiz` en el mismo trabajo. No reescribir hrefs.
- **Repo/FTP:** según cliente
- **Usuario admin panel:** generar al primer arranque (rotar en ENTREGA.md)
- **Checklist extra:** verificar Facebook link, WhatsApp demo, alta de 1 campaña y 1 adopción de ejemplo, 1 galería con fotos de `assets/images/`.

---

## 11. Resumen para el agente

```yaml
proyecto: prospectos/casa_gatos
marca: La Casa de los Gatos
modo_publicacion: carpeta
url_pruebas: https://tizawebs.com/prospectos/casa_gatos/
prioridad: [conciencia, campañas, seguidores_apoyo]
modulos: [nosotros, adopciones_tienda, como_adoptar, campanas_eventos, galerias_multiples, contacto]
portafolio: no
ab_hero: no
facebook: https://www.facebook.com/mx.lacasadelosgatos
contacto: demo_editable_en_panel
identidad: Fraunces + Nunito Sans; cream/kraft; header claro; hero split + marco caja; no clonar CSS Tizawebs
```
