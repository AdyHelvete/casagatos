const SITE_PUBLIC_URL = String(window.TW_BASE || "").replace(/\/$/, "") + '/api/site-public.php';

function twAssetUrl(path) {
  const base = String(window.TW_BASE || "").replace(/\/$/, "");
  if (!path || /^(https?:|data:)/i.test(path)) return path;
  if (base && path.startsWith(base + "/")) return path;
  return path.charAt(0) === "/" ? base + path : path;
}

const SOCIAL_META = {
  facebook: {
    label: 'Facebook',
    icon: '<svg class="social-links__icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 22v-8.2h2.8l.4-3.2h-3.2V8.9c0-.9.3-1.6 1.7-1.6H17V4.1c-.3 0-1.5-.1-2.9-.1-2.9 0-4.9 1.8-4.9 5v2.8H7v3.2h2.9V22h3.6z"/></svg>',
  },
  instagram: {
    label: 'Instagram',
    icon: '<svg class="social-links__icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 7.1a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8zm0 8.1a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4zm5.3-8.4a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM12 2.2c-2.7 0-3 .01-4 .06-1.1.04-1.8.2-2.5.5-.7.2-1.3.6-1.9 1.2-.6.6-1 1.2-1.2 1.9-.3.7-.5 1.4-.5 2.5-.1 1-.1 1.3-.1 4s.01 3 .06 4c.04 1.1.2 1.8.5 2.5.2.7.6 1.3 1.2 1.9.6.6 1.2 1 1.9 1.2.7.3 1.4.5 2.5.5 1 .1 1.3.1 4 .1s3-.01 4-.06c1.1-.04 1.8-.2 2.5-.5.7-.2 1.3-.6 1.9-1.2.6-.6 1-1.2 1.2-1.9.3-.7.5-1.4.5-2.5.1-1 .1-1.3.1-4s-.01-3-.06-4c-.04-1.1-.2-1.8-.5-2.5-.2-.7-.6-1.3-1.2-1.9-.6-.6-1.2-1-1.9-1.2-.7-.3-1.4-.5-2.5-.5-1-.1-1.3-.1-4-.1zm0 1.8c2.6 0 2.9.01 3.9.06 1 .04 1.5.2 1.9.3.5.2.8.4 1.2.8.4.4.6.7.8 1.2.1.4.3.9.3 1.9.05 1 .06 1.3.06 3.9s-.01 2.9-.06 3.9c-.04 1-.2 1.5-.3 1.9-.2.5-.4.8-.8 1.2-.4.4-.7.6-1.2.8-.4.1-.9.3-1.9.3-1 .05-1.3.06-3.9.06s-2.9-.01-3.9-.06c-1-.04-1.5-.2-1.9-.3-.5-.2-.8-.4-1.2-.8-.4-.4-.6-.7-.8-1.2-.1-.4-.3-.9-.3-1.9-.05-1-.06-1.3-.06-3.9s.01-2.9.06-3.9c.04-1 .2-1.5.3-1.9.2-.5.4-.8.8-1.2.4-.4.7-.6 1.2-.8.4-.1.9-.3 1.9-.3 1-.05 1.3-.06 3.9-.06z"/></svg>',
  },
  tiktok: {
    label: 'TikTok',
    icon: '<svg class="social-links__icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.6 5.8a4.9 4.9 0 0 1 3.4-1.3V8a3.2 3.2 0 0 0-2-.7 3.3 3.3 0 0 0-3.3 3.3v6.6a5.2 5.2 0 1 1-5.2-5.2c.2 0 .5 0 .7.1v3.4a1.9 1.9 0 1 0 1.3 1.8V5.8h2.1z"/></svg>',
  },
};

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderSocialLinks(container, social, variant) {
  const links = Object.entries(SOCIAL_META)
    .map(([key, meta]) => {
      const url = (social[key] || '').trim();
      if (!url) return '';
      return `<a href="${escapeHtml(url)}" class="social-links__item social-links__item--${key}" target="_blank" rel="noopener noreferrer" aria-label="${escapeHtml(meta.label)}">${meta.icon}</a>`;
    })
    .filter(Boolean)
    .join('');

  if (!links) {
    container.hidden = true;
    return;
  }

  container.hidden = false;
  container.innerHTML = links;
  container.classList.add(`social-links--ready-${variant}`);
}

function applySiteConfig(config) {
  window.SITE_PUBLIC_CONFIG = config;

  document.querySelectorAll('[data-site-social]').forEach((container) => {
    renderSocialLinks(container, config.social || {}, container.dataset.siteSocialVariant || 'header');
  });

  const logos = config.logos || {};
  if (logos.primary) {
    document.querySelectorAll('.logo img, .footer-brand .logo img').forEach((img) => {
      img.src = twAssetUrl(logos.primary);
    });
  }

  if (logos.icon) {
    document.querySelectorAll('link[rel="icon"]').forEach((link) => {
      link.href = twAssetUrl(logos.icon);
    });
  }

  const brand = config.brand || {};
  if (brand.name) {
    document.querySelectorAll('[data-site-brand]').forEach((el) => {
      el.textContent = brand.name;
    });
    document.querySelectorAll('.logo img, .footer-brand .logo img').forEach((img) => {
      img.alt = brand.name;
    });
  }

  const contact = config.contact || {};
  const phoneHref = contact.phone ? `tel:${contact.phone.replace(/\s+/g, '')}` : '';
  if (contact.phoneDisplay) {
    document.querySelectorAll('[data-site-phone]').forEach((el) => {
      el.textContent = contact.phoneDisplay;
    });
  }
  if (contact.phoneDisplayIntl) {
    document.querySelectorAll('[data-site-phone-intl]').forEach((el) => {
      el.textContent = contact.phoneDisplayIntl;
    });
  }
  if (phoneHref) {
    document.querySelectorAll('[data-site-phone-link]').forEach((el) => {
      el.href = phoneHref;
    });
  }
  if (contact.email) {
    document.querySelectorAll('[data-site-email]').forEach((el) => {
      el.textContent = contact.email;
    });
    document.querySelectorAll('[data-site-email-link]').forEach((el) => {
      el.href = `mailto:${contact.email}`;
    });
  }
  if (contact.location) {
    document.querySelectorAll('[data-site-location]').forEach((el) => {
      el.textContent = contact.location;
    });
  }

  const whatsapp = config.whatsapp || {};
  if (whatsapp.phone) {
    window.WHATSAPP_PHONE = whatsapp.phone;
    const message = encodeURIComponent(whatsapp.defaultMessage || 'Hola La Casa de los Gatos');
    const href = `https://wa.me/${whatsapp.phone}?text=${message}`;
    document.querySelectorAll('.whatsapp-float').forEach((link) => {
      link.href = href;
    });
    if (whatsapp.floatLabel) {
      document.querySelectorAll('.whatsapp-float__label').forEach((label) => {
        label.textContent = whatsapp.floatLabel;
      });
    }
  }

  const footer = config.footer || {};
  if (footer.tagline) {
    document.querySelectorAll('[data-site-footer-tagline]').forEach((el) => {
      el.textContent = footer.tagline;
    });
  }
  if (footer.geoText) {
    document.querySelectorAll('[data-site-geo-text]').forEach((el) => {
      el.textContent = footer.geoText;
    });
  }
  if (footer.privacyLabel) {
    document.querySelectorAll('[data-site-privacy-label]').forEach((el) => {
      el.textContent = footer.privacyLabel;
    });
  }
  if (footer.bottomNote) {
    document.querySelectorAll('[data-site-footer-note]').forEach((el) => {
      el.textContent = footer.bottomNote;
    });
  }

  if (window.location.pathname.includes('/aviso-de-privacidad')) {
    document.querySelectorAll('[data-site-privacy-link]').forEach((el) => {
      el.setAttribute('aria-current', 'page');
    });
  }
}

async function loadSiteConfig() {
  try {
    const response = await fetch(SITE_PUBLIC_URL, { credentials: 'same-origin' });
    if (!response.ok) return;
    const payload = await response.json();
    if (payload && payload.config) {
      applySiteConfig(payload.config);
    }
  } catch (error) {
    // Keep static fallbacks if config API is unavailable.
  }
}

document.addEventListener('DOMContentLoaded', loadSiteConfig);
