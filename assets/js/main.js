const WHATSAPP_DEFAULT_TEXT = "Hola La Casa de los Gatos, me interesa conocer más sobre adopción o cómo apoyar.";
const CONTACT_LOG_URL = String(window.TW_BASE || "").replace(/\/$/, "") + "/api/contact-log.php";
const CONTACT_TOKEN_URL = String(window.TW_BASE || "").replace(/\/$/, "") + "/api/contact-token.php";

function getWhatsAppPhone() {
  return window.WHATSAPP_PHONE || "527791234567";
}
const CONTACT_FORM_DEFAULTS = {
  minSeconds: 3,
  minMessageLength: 10,
  maxLinks: 3,
  services: [
    "Quiero adoptar",
    "Quiero ser hogar temporal",
    "Quiero donar o apoyar una campaña",
    "Información sobre eventos",
    "Otro",
  ],
};

function getContactFormConfig() {
  const remote = (window.SITE_PUBLIC_CONFIG && window.SITE_PUBLIC_CONFIG.contactForm) || {};
  return { ...CONTACT_FORM_DEFAULTS, ...remote };
}

function isAllowedService(service) {
  const services = getContactFormConfig().services;
  return Array.isArray(services) && services.includes(service);
}
const INJECTION_PATTERNS = [
  /<\s*script/i,
  /javascript\s*:/i,
  /<\s*iframe/i,
  /on\w+\s*=/i,
  /<\?php/i,
  /\beval\s*\(/i,
];

function sanitizeContactValue(value, maxLength = 4000) {
  return String(value || "")
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, "")
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, maxLength);
}

function containsInjection(value) {
  return INJECTION_PATTERNS.some((pattern) => pattern.test(value));
}

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
}

function isValidPhone(value) {
  const digits = value.replace(/\D/g, "");
  return digits.length >= 10 && digits.length <= 15;
}

function isValidName(value) {
  return /^[\p{L}\p{M}\s'.-]{2,120}$/u.test(value);
}

function clearHoneypot(form) {
  const hp = form.querySelector('input[name="_hp"]');
  if (hp) {
    hp.value = "";
    hp.defaultValue = "";
  }
}

function collectContactPayload(form) {
  clearHoneypot(form);
  const data = new FormData(form);
  return {
    nombre: sanitizeContactValue(data.get("nombre"), 120),
    email: sanitizeContactValue(data.get("email"), 160),
    telefono: sanitizeContactValue(data.get("telefono"), 40),
    servicio: sanitizeContactValue(data.get("servicio"), 80),
    mensaje: sanitizeContactValue(data.get("mensaje"), 4000),
    _hp: sanitizeContactValue(data.get("_hp"), 120),
    form_token: sanitizeContactValue(form.dataset.formToken, 255),
    form_loaded_at: Number(form.dataset.formLoadedAt || 0),
  };
}

function validateContactPayload(payload, options = {}) {
  if (payload._hp) {
    return "Envío bloqueado por verificación anti-spam.";
  }

  if (!payload.form_token && !options.securityOptional) {
    return "No se pudo validar el formulario. Recarga la página.";
  }

  const formConfig = getContactFormConfig();
  const elapsed = Math.floor(Date.now() / 1000) - Number(payload.form_loaded_at || 0);
  if (!payload.form_loaded_at || elapsed < Number(formConfig.minSeconds)) {
    return "Espera unos segundos antes de enviar el formulario.";
  }

  if (!isValidName(payload.nombre)) {
    return "Ingresa un nombre válido.";
  }

  if (!isValidEmail(payload.email)) {
    return "Ingresa un correo electrónico válido.";
  }

  if (!isValidPhone(payload.telefono)) {
    return "Ingresa un teléfono válido de 10 dígitos.";
  }

  if (!isAllowedService(payload.servicio)) {
    return "Selecciona un servicio válido.";
  }

  if (payload.mensaje.length < Number(formConfig.minMessageLength)) {
    return "Cuéntanos un poco más sobre tu proyecto.";
  }

  for (const value of [payload.nombre, payload.email, payload.telefono, payload.servicio, payload.mensaje]) {
    if (containsInjection(value)) {
      return "Se detectó contenido no permitido en el formulario.";
    }
  }

  if ((payload.mensaje.match(/https?:\/\//gi) || []).length > Number(formConfig.maxLinks)) {
    return "El mensaje contiene demasiados enlaces.";
  }

  return "";
}

async function loadContactSecurity(form) {
  form.dataset.formLoadedAt = String(Math.floor(Date.now() / 1000));

  try {
    const response = await fetch(CONTACT_TOKEN_URL, {
      method: "GET",
      headers: { Accept: "application/json" },
      cache: "no-store",
    });

    if (!response.ok) {
      form.dataset.securityOptional = "true";
      return false;
    }

    const result = await response.json();
    if (!result.ok || !result.token) {
      form.dataset.securityOptional = "true";
      return false;
    }

    form.dataset.formToken = result.token;
    form.dataset.securityOptional = "false";
    return true;
  } catch (error) {
    form.dataset.securityOptional = "true";
    return false;
  }
}

async function logContactSubmission(payload) {
  const body = JSON.stringify(payload);

  try {
    const response = await fetch(CONTACT_LOG_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body,
      keepalive: true,
    });

    if (!response.ok) {
      return { ok: false, error: `Registro HTTP ${response.status}` };
    }

    const result = await response.json();
    return { ok: Boolean(result.ok), error: result.error || "" };
  } catch (error) {
    try {
      if (navigator.sendBeacon) {
        navigator.sendBeacon(CONTACT_LOG_URL, new Blob([body], { type: "application/json" }));
        return { ok: true, error: "" };
      }
    } catch (beaconError) {
      // Ignore secondary logging failure.
    }

    return {
      ok: false,
      error: error instanceof Error ? error.message : "No se pudo registrar el envío.",
    };
  }
}

function pushDataLayer(eventName, data = {}) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({
    event: eventName,
    page_path: window.location.pathname,
    page_title: document.title,
    ...data,
  });
}

function getButtonLabel(element) {
  const ariaLabel = element.getAttribute("aria-label");
  if (ariaLabel) return ariaLabel.replace(/\s+/g, " ").trim().slice(0, 120);

  const text = (element.textContent || "").replace(/\s+/g, " ").trim();
  if (text) return text.slice(0, 120);

  return element.getAttribute("type") || "button";
}

function getButtonLocation(element) {
  const section = element.closest(
    ".hero, .page-hero, .cta-band, .site-header, .site-footer, .need-card, .portfolio-card, .contact-form, .button-row, .faq, main, form"
  );

  if (!section) return "page";
  if (section.classList.contains("site-header")) return "header";
  if (section.classList.contains("site-footer")) return "footer";
  if (section.classList.contains("hero")) return "hero";
  if (section.classList.contains("page-hero")) return "page_hero";
  if (section.classList.contains("cta-band")) return "cta_band";
  if (section.classList.contains("need-card")) return "need_card";
  if (section.classList.contains("portfolio-card")) return "portfolio_card";
  if (section.classList.contains("contact-form") || section.tagName === "FORM") return "contact_form";
  if (section.classList.contains("button-row")) return "button_row";
  if (section.classList.contains("faq")) return "faq";

  return section.className.split(/\s+/).find(Boolean) || "page";
}

function getButtonType(element) {
  if (element.classList.contains("whatsapp-float")) return "whatsapp";
  if (element.tagName === "A" && /wa\.me/i.test(element.href || "")) return "whatsapp";
  if (element.classList.contains("menu-toggle")) return "menu";
  if (element.type === "submit" || (element.tagName === "BUTTON" && element.closest("form"))) return "submit";
  if (element.classList.contains("btn")) return "cta";
  return "button";
}

function getLinkUrl(element) {
  if (element.tagName === "A") return element.getAttribute("href") || "";
  if (element.type === "submit") {
    const form = element.closest("form");
    return form?.getAttribute("action") || window.location.pathname;
  }
  return "";
}

function isOutboundLink(element) {
  if (element.tagName !== "A") return false;
  try {
    const url = new URL(element.href, window.location.origin);
    return url.hostname !== window.location.hostname;
  } catch (error) {
    return false;
  }
}

function initButtonTracking() {
  document.addEventListener(
    "click",
    (event) => {
      const element = event.target.closest('button, a.btn, .whatsapp-float, input[type="submit"]');
      if (!element) return;

      pushDataLayer("button_click", {
        button_text: getButtonLabel(element),
        button_type: getButtonType(element),
        button_location: getButtonLocation(element),
        button_classes: Array.from(element.classList).join(" "),
        link_url: getLinkUrl(element),
        link_target: element.getAttribute("target") || "",
        outbound: isOutboundLink(element),
      });
    },
    true
  );
}

document.addEventListener("DOMContentLoaded", () => {
  initButtonTracking();
  const header = document.querySelector(".site-header");
  const menuButton = document.querySelector(".menu-toggle");
  const menu = document.querySelector(".nav-links");
  const year = document.querySelector("[data-year]");

  if (year) year.textContent = new Date().getFullYear();

  const setHeader = () => {
    if (!header || document.body.classList.contains("menu-open")) return;
    header.classList.toggle("is-scrolled", window.scrollY > 24);
  };
  setHeader();
  window.addEventListener("scroll", setHeader, { passive: true });

  const setMenuOpen = (open) => {
    if (!menuButton || !menu) return;
    menuButton.setAttribute("aria-expanded", open ? "true" : "false");
    menuButton.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
    menu.classList.toggle("is-open", open);
    document.body.classList.toggle("menu-open", open);
    if (!open) {
      setHeader();
    }
  };

  menuButton?.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    const open = menuButton.getAttribute("aria-expanded") === "true";
    setMenuOpen(!open);
  });

  menu?.addEventListener("click", (event) => {
    const link = event.target.closest("a");
    if (!link || !menu.contains(link)) return;
    setMenuOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setMenuOpen(false);
  });

  window.addEventListener("resize", () => {
    if (window.matchMedia("(min-width: 901px)").matches) {
      setMenuOpen(false);
    }
  });

  document.documentElement.classList.add("js-reveal");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const items = document.querySelectorAll(".reveal");
  if (reducedMotion || !("IntersectionObserver" in window)) {
    items.forEach((item) => item.classList.add("is-visible"));
  } else {
    const observer = new IntersectionObserver((entries, instance) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          instance.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    items.forEach((item) => observer.observe(item));
  }

  document.querySelectorAll("[data-contact-form]").forEach((form) => {
    let invalidLogPending = false;
    clearHoneypot(form);
    void loadContactSecurity(form);

    // Algunos navegadores rellenan campos ocultos al cargar; limpiar de nuevo.
    window.setTimeout(() => clearHoneypot(form), 500);
    window.setTimeout(() => clearHoneypot(form), 2000);

    form.addEventListener("invalid", (event) => {
      if (invalidLogPending) return;
      invalidLogPending = true;

      const field = event.target;
      if (field?.name === "_hp") return;
      clearHoneypot(form);
      const payload = collectContactPayload(form);
      const fieldName = field?.name || field?.id || "campo";
      const fieldMessage = field?.validationMessage || "Valor inválido";

      void logContactSubmission({
        ...payload,
        estado: "error",
        error: `Validación: ${fieldName} — ${fieldMessage}`,
      }).finally(() => {
        invalidLogPending = false;
      });
    }, true);

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      const status = form.querySelector(".form-status");
      const payload = collectContactPayload(form);
      const validationError = validateContactPayload(payload, {
        securityOptional: form.dataset.securityOptional === "true",
      });

      if (validationError) {
        if (status) status.textContent = validationError;
        pushDataLayer("contact_form_error", {
          form_error: validationError,
        });
        if (form.dataset.securityOptional !== "true") {
          await logContactSubmission({
            ...payload,
            estado: "error",
            error: validationError,
          });
        }
        return;
      }

      const service = payload.servicio || "conocer más";
      const text = `Hola La Casa de los Gatos, soy ${payload.nombre}. Motivo: ${service}. ${payload.mensaje}`;

      if (status) status.textContent = "Guardando tu solicitud…";

      if (form.dataset.securityOptional === "true") {
        window.open(
          `https://wa.me/${getWhatsAppPhone()}?text=${encodeURIComponent(text)}`,
          "_blank",
          "noopener,noreferrer"
        );
        if (status) status.textContent = "Abriendo WhatsApp. El registro en servidor no está disponible en este entorno.";
        return;
      }

      const logResult = await logContactSubmission({
        ...payload,
        estado: "exitoso",
        error: "",
      });

      if (!logResult.ok) {
        if (status) status.textContent = logResult.error || "No se pudo registrar tu solicitud. Intenta de nuevo.";
        pushDataLayer("contact_form_error", {
          form_error: logResult.error || "No se pudo guardar el registro en el servidor.",
        });
        await logContactSubmission({
          ...payload,
          estado: "error",
          error: logResult.error || "No se pudo guardar el registro en el servidor.",
        });
        return;
      }

      pushDataLayer("generate_lead", {
        lead_source: "contact_form",
        service_interest: payload.servicio,
        button_text: "Enviar solicitud",
        button_type: "submit",
        button_location: "contact_form",
      });

      const whatsappWindow = window.open(
        `https://wa.me/${getWhatsAppPhone()}?text=${encodeURIComponent(text)}`,
        "_blank",
        "noopener,noreferrer"
      );

      if (!whatsappWindow) {
        await logContactSubmission({
          ...payload,
          estado: "error",
          error: "WhatsApp bloqueado por el navegador (ventana emergente).",
        });

        if (status) {
          status.textContent = "Registro guardado. Permite ventanas emergentes o usa el botón de WhatsApp.";
        }
        return;
      }

      if (status) {
        status.textContent = "Abriendo WhatsApp para continuar la conversación…";
      }
    });
  });

  if (!document.querySelector(".whatsapp-float")) {
    const link = document.createElement("a");
    link.className = "whatsapp-float";
    link.href = `https://wa.me/${getWhatsAppPhone()}?text=${encodeURIComponent(WHATSAPP_DEFAULT_TEXT)}`;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.setAttribute("aria-label", "Contactar por WhatsApp");
    link.innerHTML = `
      <span class="whatsapp-float__label">¿Quieres adoptar o ayudar?</span>
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>`;
    document.body.appendChild(link);
  }
});
