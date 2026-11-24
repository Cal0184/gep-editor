const header = document.getElementById('siteHeader');
const btn = document.getElementById('menuBtn');
const nav = document.getElementById('navLinks');

if (header) {
  const updateHeader = () => header.classList.toggle('scrolled', window.scrollY > 36);
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive:true });
}

if (btn && nav) {
  btn.addEventListener('click', () => nav.classList.toggle('open'));
  nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => nav.classList.remove('open')));
}

/* Visual A · 2026 motion system */
/*
 * IMPORTANTE: en móvil no activamos el modo que oculta contenido antes de
 * animarlo. En iOS/Safari un observer interrumpido, una pestaña restaurada o
 * un error de JS podía dejar bloques completos con opacity:0.
 * La prioridad en <=700px es que TODO el contenido sea visible.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const smallScreen = window.matchMedia('(max-width: 700px)').matches;
const canUseRevealMotion = !reduceMotion && !smallScreen && ('IntersectionObserver' in window);

if (canUseRevealMotion) {
  document.documentElement.classList.add('motion-ready');
} else {
  document.documentElement.classList.remove('motion-ready');
}

// Minimal reading-progress indicator, injected globally so every page gets it.
const progress = document.createElement('div');
progress.className = 'scroll-progress';
progress.setAttribute('aria-hidden', 'true');
document.body.prepend(progress);

const updateProgress = () => {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  const value = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
  progress.style.transform = `scaleX(${value})`;
};
updateProgress();
window.addEventListener('scroll', updateProgress, { passive:true });
window.addEventListener('resize', updateProgress, { passive:true });

// Section headings reveal as the visitor reaches them.
const headingBlocks = document.querySelectorAll(
  '.section-head, .intro-grid, .network-copy, .definition-card, .archive-note'
);
headingBlocks.forEach(el => el.classList.add('reveal-heading'));

// Stagger cards and visual modules in groups; no HTML duplication is needed.
const revealGroups = [
  '.research-grid',
  '.portal-grid',
  '.resource-grid',
  '.card-grid',
  '.course-groups',
  '.course-card-grid',
  '.project-grid',
  '.current-projects-grid',
  '.content-hub-grid',
  '.comms-video-grid',
  '.comms-events-list',
  '.comms-press-list',
  '.publication-grid',
  '.people-grid',
  '.contact-grid',
  '.definition-grid',
  '.topic-cloud',
  '.network-names'
];

revealGroups.forEach(selector => {
  document.querySelectorAll(selector).forEach(group => {
    Array.from(group.children).forEach((child, index) => {
      child.classList.add('reveal-item');
      child.style.setProperty('--reveal-delay', `${Math.min(index, 7) * 70}ms`);
    });
  });
});

// Standalone media / compositions.
document.querySelectorAll('.split-media, .project-feature, .network-logo').forEach((el, index) => {
  el.classList.add('reveal-item');
  el.style.setProperty('--reveal-delay', `${index * 60}ms`);
});

if (canUseRevealMotion) {
  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('in-view');
      obs.unobserve(entry.target);
    });
  }, {
    threshold:0.12,
    rootMargin:'0px 0px -8% 0px'
  });

  document.querySelectorAll('.reveal-heading, .reveal-item, .landscape-content').forEach(el => observer.observe(el));
} else {
  document.querySelectorAll('.reveal-heading, .reveal-item, .landscape-content').forEach(el => el.classList.add('in-view'));
}

// Hero copy has a short staged entrance on every page.
requestAnimationFrame(() => {
  requestAnimationFrame(() => {
    document.querySelectorAll('.hero-inner, .page-hero-content').forEach(el => el.classList.add('hero-ready'));
  });
});

// Safety net: content must never remain hidden because of the motion system.
// This is especially important when Safari restores a tab from memory.
window.setTimeout(() => {
  document.querySelectorAll('.reveal-heading, .reveal-item, .landscape-content').forEach(el => el.classList.add('in-view'));
  document.querySelectorAll('.hero-inner, .page-hero-content').forEach(el => el.classList.add('hero-ready'));
}, 1600);


/* =========================================================
   GEP · LEP 2026 — navigation, filters and progressive UX
   ========================================================= */

// Accessible mobile navigation state.
if (btn && nav) {
  btn.addEventListener('click', () => {
    const open = nav.classList.contains('open');
    btn.setAttribute('aria-expanded', String(open));
  });
}

// Highlight current section in the sticky secondary navigation.
const sectionNav = document.querySelector('.section-nav');
if (sectionNav && 'IntersectionObserver' in window) {
  const links = [...sectionNav.querySelectorAll('a[href^="#"]')];
  const targets = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
  const sectionObserver = new IntersectionObserver(entries => {
    const visible = entries.filter(e => e.isIntersecting).sort((a,b) => b.intersectionRatio - a.intersectionRatio)[0];
    if (!visible) return;
    links.forEach(a => a.classList.toggle('active-section', a.getAttribute('href') === `#${visible.target.id}`));
  }, {rootMargin:'-28% 0px -62% 0px', threshold:[0,.1,.5]});
  targets.forEach(t => sectionObserver.observe(t));
  links.forEach(a => a.addEventListener('click', () => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target && target.tagName === 'DETAILS') target.open = true;
  }));
}

// Projects: era chips + live text search.
const projectTimeline = document.getElementById('projectTimeline');
if (projectTimeline) {
  const projects = [...projectTimeline.querySelectorAll('.project-row')];
  const search = document.getElementById('projectSearch');
  const pills = [...document.querySelectorAll('[data-era]')];
  const count = document.getElementById('projectCount');
  const empty = document.getElementById('projectEmpty');
  let era = 'all';
  const apply = () => {
    const q = (search?.value || '').trim().toLocaleLowerCase('es');
    let visible = 0;
    projects.forEach(p => {
      const okEra = era === 'all' || p.dataset.era === era;
      const okText = !q || p.textContent.toLocaleLowerCase('es').includes(q);
      const show = okEra && okText;
      p.classList.toggle('is-hidden', !show);
      if (show) visible++;
    });
    if (count) count.textContent = `${visible} ${visible === 1 ? 'proyecto' : 'proyectos'}`;
    if (empty) empty.hidden = visible !== 0;
  };
  pills.forEach(p => p.addEventListener('click', () => {
    era = p.dataset.era;
    pills.forEach(x => {x.classList.toggle('active', x===p); x.setAttribute('aria-pressed', String(x===p));});
    apply();
  }));
  search?.addEventListener('input', apply);
  apply();
}

// Publications: category + year + free text, with empty-section handling.
const publicationTools = document.querySelector('.publication-tools');
if (publicationTools) {
  const items = [...document.querySelectorAll('.publication-section .pub, .publication-section .book')];
  const sections = [...document.querySelectorAll('.publication-section')];
  const cats = [...document.querySelectorAll('[data-pub-category]')];
  const search = document.getElementById('publicationSearch');
  const year = document.getElementById('publicationYear');
  const count = document.getElementById('publicationCount');
  const empty = document.getElementById('publicationEmpty');
  let category = 'all';
  const apply = () => {
    const q = (search?.value || '').trim().toLocaleLowerCase('es');
    const y = year?.value || 'all';
    let visible = 0;
    items.forEach(item => {
      const okCat = category === 'all' || item.dataset.category === category;
      const okYear = y === 'all' || item.dataset.year === y;
      const hay = (item.dataset.search || item.textContent.toLocaleLowerCase('es'));
      const okText = !q || hay.includes(q);
      const show = okCat && okYear && okText;
      item.classList.toggle('is-hidden', !show);
      if (show) visible++;
    });
    sections.forEach(sec => {
      const any = [...sec.querySelectorAll('.pub,.book')].some(i => !i.classList.contains('is-hidden'));
      sec.classList.toggle('is-hidden', !any);
    });
    if (count) count.textContent = `${visible} ${visible === 1 ? 'resultado' : 'resultados'}`;
    if (empty) empty.hidden = visible !== 0;
  };
  cats.forEach(c => c.addEventListener('click', () => {
    category = c.dataset.pubCategory;
    cats.forEach(x => {x.classList.toggle('active', x===c); x.setAttribute('aria-pressed', String(x===c));});
    apply();
  }));
  search?.addEventListener('input', apply);
  year?.addEventListener('change', apply);
  apply();
}

// Opening a team hash target also opens its accordion group.
const openDirectoryHash = () => {
  if (!location.hash) return;
  const target = document.querySelector(location.hash);
  if (target?.matches('details.directory-section')) target.open = true;
};
openDirectoryHash();
window.addEventListener('hashchange', openDirectoryHash);


// Reveal newly added 2026 modules with the same restrained motion language.
const modernReveal = [...document.querySelectorAll('.impact-item,.learning-step,.theme-card,.mini-stat,.directory-section')];
modernReveal.forEach((el,i) => {
  el.classList.add('reveal-item');
  el.style.setProperty('--reveal-delay', `${Math.min(i % 4, 3) * 70}ms`);
});
if (canUseRevealMotion) {
  const modernObserver = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('in-view');
      obs.unobserve(entry.target);
    });
  }, {threshold:.10, rootMargin:'0px 0px -6% 0px'});
  modernReveal.forEach(el => modernObserver.observe(el));
} else {
  modernReveal.forEach(el => el.classList.add('in-view'));
}

/* GEP v10.1 · Galería ampliada de proyecto */
const projectLightbox = document.getElementById('projectLightbox');
if (projectLightbox) {
  const galleryLinks = [...document.querySelectorAll('[data-project-lightbox]')];
  const image = projectLightbox.querySelector('img');
  const caption = projectLightbox.querySelector('figcaption');
  const close = projectLightbox.querySelector('.project-lightbox-close');
  const prev = projectLightbox.querySelector('.project-lightbox-nav.is-prev');
  const next = projectLightbox.querySelector('.project-lightbox-nav.is-next');
  let activeIndex = 0;
  let lastFocus = null;

  const showImage = index => {
    if (!galleryLinks.length) return;
    activeIndex = (index + galleryLinks.length) % galleryLinks.length;
    const link = galleryLinks[activeIndex];
    const thumb = link.querySelector('img');
    image.src = link.getAttribute('href') || thumb?.src || '';
    image.alt = thumb?.alt || '';
    caption.textContent = `${activeIndex + 1} / ${galleryLinks.length}`;
  };
  const openLightbox = index => {
    lastFocus = document.activeElement;
    showImage(index);
    projectLightbox.hidden = false;
    document.body.classList.add('is-lightbox-open');
    close?.focus();
  };
  const closeLightbox = () => {
    projectLightbox.hidden = true;
    document.body.classList.remove('is-lightbox-open');
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  };

  galleryLinks.forEach((link, index) => link.addEventListener('click', event => {
    event.preventDefault();
    openLightbox(index);
  }));
  close?.addEventListener('click', closeLightbox);
  prev?.addEventListener('click', () => showImage(activeIndex - 1));
  next?.addEventListener('click', () => showImage(activeIndex + 1));
  projectLightbox.addEventListener('click', event => { if (event.target === projectLightbox) closeLightbox(); });
  document.addEventListener('keydown', event => {
    if (projectLightbox.hidden) return;
    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowLeft') showImage(activeIndex - 1);
    if (event.key === 'ArrowRight') showImage(activeIndex + 1);
  });
}

/* =========================================================
   GEP v10.2.0 · navegación responsive endurecida
   Mantiene aria-expanded/body state sincronizados y evita menús atrapados.
========================================================= */
(() => {
  const menuButton = document.getElementById('menuBtn');
  const menu = document.getElementById('navLinks');
  if (!menuButton || !menu) return;

  const syncMenuState = () => {
    const open = menu.classList.contains('open');
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'Cerrar navegación' : 'Abrir navegación');
    document.body.classList.toggle('menu-open', open && window.matchMedia('(max-width: 1120px)').matches);
  };
  const closeMenu = () => {
    menu.classList.remove('open');
    syncMenuState();
  };

  menuButton.addEventListener('click', () => requestAnimationFrame(syncMenuState));
  menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && menu.classList.contains('open')) {
      closeMenu();
      menuButton.focus();
    }
  });
  document.addEventListener('click', event => {
    if (!menu.classList.contains('open')) return;
    if (menu.contains(event.target) || menuButton.contains(event.target)) return;
    closeMenu();
  });
  window.addEventListener('resize', () => {
    if (!window.matchMedia('(max-width: 1120px)').matches) closeMenu();
    else syncMenuState();
  }, {passive:true});
  syncMenuState();
})();

/* =========================================================
   GEP v9.18.0 · video de cabecera en iPhone / Safari
   Safari solo reproduce solo un video silenciado e "inline"; se fuerza por
   JavaScript porque el atributo no siempre basta. Si el teléfono lo bloquea
   (modo ahorro de batería), se reintenta con el primer toque o scroll y,
   mientras tanto, se deja ver la imagen de respaldo.
   v9.25.0 · sin espera artificial: el video se pide tras el primer pintado de
   la página (antes, recién al terminar de cargar todas las fotos o a los
   1,5 s, lo que en iPhone sumaba ~1 s). El teléfono elige NOMBRE-movil.mp4 con
   <source media>. El video queda transparente hasta tener su primer cuadro
   pintado y aparece encima de la imagen de respaldo con un fundido corto.
   Se pausa cuando la cabecera sale de la pantalla.
========================================================= */
(() => {
  const videos = Array.from(document.querySelectorAll('video.gep-hero-video-bg'));
  if (!videos.length) return;

  // v9.19.0: con "reducir movimiento" o "ahorro de datos" no se descarga el
  // video (el script junto al video ya lo vació) y se deja la imagen.
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = !!(navigator.connection && navigator.connection.saveData);
  if (reduceMotion || saveData) {
    videos.forEach(video => {
      video.removeAttribute('autoplay');
      try { video.pause(); } catch (error) {}
      video.querySelectorAll('source').forEach(source => source.remove());
      video.removeAttribute('src');
      try { video.load(); } catch (error) {}
      if (!video.poster) video.classList.add('is-video-blocked');
    });
    return;
  }

  const isPhone = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
  const visible = new WeakMap();

  // v9.21.4 · el primer cuadro del video se guarda en el navegador y la próxima
  // vez se muestra al instante como imagen de respaldo (si no hay una subida).
  const posterKey = video => {
    const source = video.querySelector('source:not([media])') || video.querySelector('source');
    const src = video.getAttribute('data-src') || (source && source.getAttribute('src')) || '';
    return src ? 'gep-hero-poster:' + src.replace(/[?#].*$/, '').replace(/^https?:\/\/[^/]+/i, '') : '';
  };
  const readPoster = key => { try { return key ? window.localStorage.getItem(key) : null; } catch (error) { return null; } };
  const savePoster = (key, data) => {
    try {
      const store = window.localStorage;
      const keys = [];
      for (let i = 0; i < store.length; i++) { const k = store.key(i); if (k && k.indexOf('gep-hero-poster:') === 0 && k !== key) keys.push(k); }
      while (keys.length > 5) store.removeItem(keys.shift());
      store.setItem(key, data);
    } catch (error) {}
  };
  const capturePoster = video => {
    const key = posterKey(video);
    if (!key || readPoster(key) || !video.videoWidth) return;
    try {
      const width = Math.min(960, video.videoWidth);
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = Math.round(video.videoHeight * width / video.videoWidth);
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      savePoster(key, canvas.toDataURL('image/jpeg', 0.72));
    } catch (error) {}
  };

  // Muestra el video recién cuando su primer cuadro ya está en pantalla.
  const reveal = video => {
    if (!video.classList.contains('is-video-waiting')) return;
    let shown = false;
    const show = () => { if (shown) return; shown = true; video.classList.remove('is-video-waiting', 'is-video-blocked'); capturePoster(video); };
    // Respaldo por si el navegador no avisa del cuadro pintado.
    window.setTimeout(() => { if (!video.paused && video.readyState >= 2) show(); }, 700);
    if (typeof video.requestVideoFrameCallback === 'function') video.requestVideoFrameCallback(() => show());
    else {
      const check = () => { if (video.currentTime > 0 && video.readyState >= 2) { video.removeEventListener('timeupdate', check); show(); } };
      video.addEventListener('timeupdate', check);
      check();
    }
  };

  const prepare = video => {
    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
  };
  const tryPlay = video => {
    prepare(video);
    let result;
    try { result = video.play(); } catch (error) { result = null; }
    if (result && typeof result.then === 'function') {
      return result.then(() => true).catch(() => { if (!video.poster) video.classList.add('is-video-blocked'); return false; });
    }
    return Promise.resolve(!video.paused);
  };

  videos.forEach(video => {
    prepare(video);
    if (!video.poster) {
      const cached = readPoster(posterKey(video));
      if (cached) video.poster = cached;
    }
    // Navegadores antiguos que no entienden <source media> en video podrían
    // tomar la versión de teléfono en un PC: se corrige solo en ese caso.
    const mobile = video.getAttribute('data-src-mobile');
    const main = video.getAttribute('data-src');
    if (!isPhone && mobile && main && video.currentSrc && video.currentSrc.indexOf(mobile.replace(/^https?:\/\/[^/]+/i, '')) !== -1) {
      video.querySelectorAll('source[media]').forEach(source => source.remove());
      try { video.load(); } catch (error) {}
    }
    video.addEventListener('playing', () => reveal(video));
    if (!video.paused && video.readyState >= 2) reveal(video);
    // v9.23.0: si el video falla, se deja la imagen de respaldo.
    video.addEventListener('error', () => { if (!video.poster) video.classList.add('is-video-blocked'); }, true);
    video.querySelectorAll('source').forEach(source => source.addEventListener('error', () => { if (!video.poster) video.classList.add('is-video-blocked'); }));
    // El script junto al video lo inicia tras el primer pintado. Si por algún
    // motivo no corrió (por ejemplo, otra plantilla), se inicia aquí.
    if (!video.getAttribute('data-gep-started')) {
      video.setAttribute('data-gep-started', '1');
      video.preload = 'auto';
      if (!video.classList.contains('is-video-waiting')) video.classList.add('is-video-waiting');
      tryPlay(video);
    }
  });

  // Pausa el video cuando la cabecera no se ve (ahorra batería y datos).
  if ('IntersectionObserver' in window) {
    const watcher = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        const video = entry.target;
        visible.set(video, entry.isIntersecting);
        if (entry.isIntersecting) { if (video.paused) tryPlay(video); }
        else { try { video.pause(); } catch (error) {} }
      });
    }, {threshold: 0});
    videos.forEach(video => watcher.observe(video));
  }

  // iPhone en modo ahorro de batería bloquea el autoplay: el primer toque o
  // desplazamiento lo vuelve a intentar.
  const retry = () => {
    videos.forEach(video => { if (video.paused && visible.get(video) !== false) tryPlay(video); });
  };
  ['touchstart', 'click', 'scroll'].forEach(type => {
    window.addEventListener(type, retry, {passive: true, once: true});
  });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) retry(); });
  window.addEventListener('pageshow', retry);
})();

/* =========================================================
   GEP v9.26.0 · cabecera con video de YouTube / Vimeo
   El reproductor queda transparente sobre la portada hasta que él mismo
   avisa que empezó a reproducir (mensajes oficiales de YouTube y Vimeo).
   No agrega espera: si el aviso no llega, se muestra al cargar + 4 s, y la
   portada sigue visible mientras tanto.
========================================================= */
(() => {
  const frames = Array.from(document.querySelectorAll('iframe.gep-hero-video-iframe.is-video-waiting'));
  if (!frames.length) return;
  const show = frame => frame.classList.remove('is-video-waiting');
  const hello = frame => {
    try {
      if (frame.getAttribute('data-gep-embed') === 'youtube') {
        frame.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 1, channel: 'widget' }), '*');
      } else {
        frame.contentWindow.postMessage(JSON.stringify({ method: 'addEventListener', value: 'play' }), '*');
        frame.contentWindow.postMessage(JSON.stringify({ method: 'addEventListener', value: 'timeupdate' }), '*');
      }
    } catch (error) {}
  };
  window.addEventListener('message', event => {
    if (!/youtube\.com|youtube-nocookie\.com|vimeo\.com/.test(String(event.origin || ''))) return;
    let data = event.data;
    if (typeof data === 'string') { try { data = JSON.parse(data); } catch (error) { return; } }
    if (!data || typeof data !== 'object') return;
    const frame = frames.find(f => f.contentWindow === event.source);
    if (!frame) return;
    const ytPlaying = (data.event === 'onStateChange' && data.info === 1) || (data.event === 'infoDelivery' && data.info && data.info.playerState === 1);
    const vimeoPlaying = data.event === 'play' || data.event === 'playProgress' || data.event === 'timeupdate';
    if (data.event === 'ready') hello(frame);
    if (ytPlaying || vimeoPlaying) show(frame);
  });
  frames.forEach(frame => {
    let tries = 0;
    const ping = () => { if (!frame.classList.contains('is-video-waiting') || tries++ > 12) return; hello(frame); window.setTimeout(ping, 400); };
    frame.addEventListener('load', () => { ping(); window.setTimeout(() => show(frame), 4000); });
    ping();
  });
})();
