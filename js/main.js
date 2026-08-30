// ============================================================
// NAVEGACIÓN PRINCIPAL — se ejecuta primero y siempre, protegida
// con try/catch, para que un error en cualquier otra función de
// este archivo (más abajo) nunca pueda impedir que el menú móvil
// o los submenús desplegables funcionen.
// ============================================================

// Menú móvil
try {
  const navToggle = document.getElementById('navToggle');
  const siteNav = document.getElementById('siteNav');

  if (navToggle && siteNav) {
    navToggle.addEventListener('click', () => {
      const isOpen = siteNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });

    // Cierra el menú al elegir una opción (útil en móvil)
    siteNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        siteNav.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }
} catch (err) {
  console.error('[main.js] Error en menú móvil:', err);
}

// Submenús desplegables (menú principal, con soporte para subniveles anidados)
try {
  const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');

  // Cierra cualquier otro desplegable (no ancestros ni descendientes)
  // cuando el puntero entra a un nuevo item. Esto evita que, en
  // escritorio, un desplegable abierto por clic se quede visible
  // mientras el usuario pasa el cursor (hover) a otro menú distinto:
  // así solo hay un desplegable abierto a la vez.
  function closeOtherDropdowns(item) {
    const ancestors = [];
    let p = item.parentElement;
    while (p) {
      if (p.classList && p.classList.contains('has-dropdown')) ancestors.push(p);
      p = p.parentElement;
    }
    dropdownItems.forEach(other => {
      if (other === item || ancestors.includes(other) || item.contains(other)) return;
      other.classList.remove('open');
      other.querySelector(':scope > .nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
    });
  }

  dropdownItems.forEach(item => {
    const btn = item.querySelector(':scope > .nav-dropdown-btn');
    if (!btn) return;

    item.addEventListener('mouseenter', () => closeOtherDropdowns(item));
    btn.addEventListener('focus', () => closeOtherDropdowns(item));

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = item.classList.contains('open');

      // Ancestros del item actual (para no cerrarlos si es un submenú anidado)
      const ancestors = [];
      let p = item.parentElement;
      while (p) {
        if (p.classList && p.classList.contains('has-dropdown')) ancestors.push(p);
        p = p.parentElement;
      }

      dropdownItems.forEach(other => {
        if (other === item || ancestors.includes(other)) return;
        other.classList.remove('open');
        other.querySelector(':scope > .nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
      });

      item.classList.toggle('open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));

      if (isOpen) {
        item.querySelectorAll('.nav-item.has-dropdown.open').forEach(d => {
          d.classList.remove('open');
          d.querySelector(':scope > .nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
        });
      }
    });
  });

  // Cierra los submenús al hacer clic fuera del menú
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-item.has-dropdown')) {
      dropdownItems.forEach(item => {
        item.classList.remove('open');
        item.querySelector(':scope > .nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
      });
    }
  });
} catch (err) {
  console.error('[main.js] Error en submenús desplegables:', err);
}

// ============================================================
// MEJORAS VISUALES — cada bloque va protegido individualmente
// ============================================================

// Header que se encoge al hacer scroll
try {
  const siteHeader = document.querySelector('.site-header');
  if (siteHeader) {
    const onScroll = () => {
      if (window.scrollY > 40) {
        siteHeader.classList.add('is-shrunk');
      } else {
        siteHeader.classList.remove('is-shrunk');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
} catch (err) {
  console.error('[main.js] Error en header que se encoge:', err);
}

// Detecta preferencia de "reducir movimiento" (usada por varias funciones más abajo)
let prefersReducedMotion = false;
try {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
} catch (err) {
  console.error('[main.js] Error al detectar prefers-reduced-motion:', err);
}

// Formulario de contacto — envío vía Formspree
// IMPORTANTE: reemplazar "TU_ID_DE_FORMSPREE" en el atributo action del
// <form> en contacto.html por el ID real que te da Formspree al crear
// tu formulario (gratis en https://formspree.io).
try {
  const contactForm = document.getElementById('contactForm');
  const formStatus = document.getElementById('formStatus');

  if (contactForm) {
    contactForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const btnLabel = submitBtn?.querySelector('.btn-label');
      const originalLabel = btnLabel ? btnLabel.textContent : '';
      const actionUrl = contactForm.getAttribute('action');

      if (!actionUrl || actionUrl.includes('TU_ID_DE_FORMSPREE')) {
        formStatus.textContent = 'Formulario aún no conectado: falta reemplazar el ID de Formspree en contacto.html.';
        formStatus.classList.add('form-status-error');
        return;
      }

      submitBtn.disabled = true;
      if (btnLabel) btnLabel.textContent = 'Enviando...';
      formStatus.classList.remove('form-status-error', 'form-status-success');
      formStatus.textContent = '';

      try {
        const response = await fetch(actionUrl, {
          method: 'POST',
          body: new FormData(contactForm),
          headers: { 'Accept': 'application/json' }
        });

        if (response.ok) {
          formStatus.textContent = '¡Gracias! Tu mensaje fue enviado, te responderemos pronto.';
          formStatus.classList.add('form-status-success');
          contactForm.reset();
        } else {
          const data = await response.json().catch(() => null);
          const detail = data?.errors?.map(err => err.message).join(', ');
          formStatus.textContent = detail
            ? `No se pudo enviar: ${detail}`
            : 'No se pudo enviar el mensaje. Intenta de nuevo en unos minutos.';
          formStatus.classList.add('form-status-error');
        }
      } catch (error) {
        formStatus.textContent = 'Error de conexión. Verifica tu internet e intenta de nuevo.';
        formStatus.classList.add('form-status-error');
      } finally {
        submitBtn.disabled = false;
        if (btnLabel) btnLabel.textContent = originalLabel;
      }
    });
  }
} catch (err) {
  console.error('[main.js] Error en formulario de contacto:', err);
}


// FAQ — acordeón de preguntas frecuentes
try {
  const faqItems = document.querySelectorAll('.faq-item');

  faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    if (!question) return;

    question.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      // Cierra las demás preguntas abiertas (un acordeón a la vez)
      faqItems.forEach(other => {
        other.classList.remove('open');
        other.querySelector('.faq-question')?.setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        item.classList.add('open');
        question.setAttribute('aria-expanded', 'true');
      }
    });
  });
} catch (err) {
  console.error('[main.js] Error en FAQ:', err);
}


// Galería — lightbox
try {
  const galleryItems = Array.from(document.querySelectorAll('.gallery-item'));
  const lightbox = document.getElementById('lightbox');

  if (galleryItems.length && lightbox) {
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const btnClose = lightbox.querySelector('.lightbox-close');
    const btnPrev = lightbox.querySelector('.lightbox-prev');
    const btnNext = lightbox.querySelector('.lightbox-next');
    let currentIndex = 0;

    function showImage(index) {
      currentIndex = (index + galleryItems.length) % galleryItems.length;
      const item = galleryItems[currentIndex];
      const img = item.querySelector('img');
      lightboxImg.src = img.src;
      lightboxImg.alt = img.alt;
      lightboxCaption.textContent = item.dataset.caption || img.alt || '';
    }

    function openLightbox(index) {
      showImage(index);
      lightbox.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.remove('open');
      document.body.style.overflow = '';
    }

    galleryItems.forEach((item, index) => {
      item.addEventListener('click', () => openLightbox(index));
    });

    btnClose?.addEventListener('click', closeLightbox);
    btnPrev?.addEventListener('click', () => showImage(currentIndex - 1));
    btnNext?.addEventListener('click', () => showImage(currentIndex + 1));

    // Cierra al hacer clic en el fondo (fuera de la imagen)
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) closeLightbox();
    });

    // Navegación por teclado
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') showImage(currentIndex + 1);
      if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
    });
  }
} catch (err) {
  console.error('[main.js] Error en galería (lightbox):', err);
}

// Carrusel de galería (solo existe en galeria.html — en el resto de
// páginas el selector no encuentra nada y este bloque no hace nada,
// sin afectar al resto del sitio). Reutiliza los mismos .gallery-item
// que ya usa el lightbox de arriba; no crea imágenes nuevas.
try {
  const carousel = document.getElementById('galleryCarousel');
  const carouselWrap = document.getElementById('galleryCarouselWrap');

  if (carousel && carouselWrap) {
    const slides = Array.from(carousel.querySelectorAll('.gallery-item'));
    const dotsWrap = document.getElementById('carouselDots');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    let slideIndex = 0;
    let autoplayTimer = null;

    // Genera los puntos indicadores (uno por foto ya existente)
    const dots = slides.map((_, i) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'carousel-dot';
      dot.setAttribute('role', 'tab');
      dot.setAttribute('aria-label', `Ir a la foto ${i + 1} de ${slides.length}`);
      dot.addEventListener('click', () => { goToSlide(i); restartAutoplay(); });
      dotsWrap.appendChild(dot);
      return dot;
    });

    function goToSlide(index) {
      slideIndex = (index + slides.length) % slides.length;
      carousel.style.transform = `translateX(-${slideIndex * 100}%)`;
      dots.forEach((dot, i) => dot.classList.toggle('active', i === slideIndex));
    }

    function nextSlide() { goToSlide(slideIndex + 1); }
    function prevSlide() { goToSlide(slideIndex - 1); }

    function startAutoplay() {
      if (prefersReducedMotion) return; // respeta la preferencia del usuario
      stopAutoplay();
      autoplayTimer = setInterval(nextSlide, 5000);
    }
    function stopAutoplay() {
      if (autoplayTimer) { clearInterval(autoplayTimer); autoplayTimer = null; }
    }
    function restartAutoplay() { stopAutoplay(); startAutoplay(); }

    prevBtn?.addEventListener('click', () => { prevSlide(); restartAutoplay(); });
    nextBtn?.addEventListener('click', () => { nextSlide(); restartAutoplay(); });

    // Pausa el cambio automático mientras el usuario interactúa
    ['mouseenter', 'touchstart', 'focusin'].forEach(evt => {
      carouselWrap.addEventListener(evt, stopAutoplay, { passive: true });
    });
    ['mouseleave', 'touchend', 'focusout'].forEach(evt => {
      carouselWrap.addEventListener(evt, startAutoplay, { passive: true });
    });

    // Navegación por teclado cuando el carrusel tiene el foco
    carouselWrap.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { nextSlide(); restartAutoplay(); }
      if (e.key === 'ArrowLeft') { prevSlide(); restartAutoplay(); }
    });

    goToSlide(0);
    startAutoplay();
  }
} catch (err) {
  console.error('[main.js] Error en carrusel de galería:', err);
}


// ============ ANIMACIÓN AL HACER SCROLL ============
try {
  if (!prefersReducedMotion && 'IntersectionObserver' in window) {
    const revealSelectors = [
      '.about-card', '.info-card', '.why-card', '.news-card', '.event-card',
      '.teacher-card', '.gallery-item', '.faq-item', '.level-card',
      '.contact-card', '.next-event-banner', '.testimonial-card'
    ];
    const revealEls = document.querySelectorAll(revealSelectors.join(','));

    const revealObserver = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach((el, i) => {
      el.classList.add('reveal');
      el.style.transitionDelay = Math.min(i % 4, 3) * 70 + 'ms';
      revealObserver.observe(el);
    });
  }
} catch (err) {
  console.error('[main.js] Error en animación al hacer scroll:', err);
}


// Barra de progreso de lectura
try {
  const readingProgress = document.getElementById('readingProgress');
  if (readingProgress) {
    const updateProgress = () => {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      readingProgress.style.width = pct + '%';
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
    updateProgress();
  }
} catch (err) {
  console.error('[main.js] Error en barra de progreso de lectura:', err);
}


// Botón "volver arriba"
try {
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    const toggleBackToTop = () => {
      backToTop.classList.toggle('visible', window.scrollY > 480);
    };
    window.addEventListener('scroll', toggleBackToTop, { passive: true });
    toggleBackToTop();
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
  }
} catch (err) {
  console.error('[main.js] Error en botón volver arriba:', err);
}


// Botón "Arriba" dentro del footer
try {
  const footerArriba = document.getElementById('footerArriba');
  if (footerArriba) {
    footerArriba.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    });
  }
} catch (err) {
  console.error('[main.js] Error en botón Arriba del footer:', err);
}


// ============ INTRANET (panel de acceso) ============
const intranetToggle = document.getElementById('intranetToggle');
if (intranetToggle) {
  const panel = document.createElement('div');
  panel.className = 'intranet-panel';
  panel.id = 'intranetPanel';
  panel.innerHTML = `
    <form id="intranetForm">
      <p class="intranet-title">Acceso Intranet</p>
      <label>Usuario
        <input type="text" name="usuario" autocomplete="username" required>
      </label>
      <label>Clave
        <input type="password" name="clave" autocomplete="current-password" required>
      </label>
      <button type="submit" class="btn btn-primary intranet-submit">Ingresar</button>
      <p class="intranet-note placeholder-text">[Intranet institucional — módulo pendiente de implementación por el colegio.]</p>
    </form>
  `;
  intranetToggle.insertAdjacentElement('afterend', panel);

  const closeIntranet = () => {
    panel.classList.remove('open');
    intranetToggle.setAttribute('aria-expanded', 'false');
  };
  intranetToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = panel.classList.toggle('open');
    intranetToggle.setAttribute('aria-expanded', String(isOpen));
  });
  panel.addEventListener('click', (e) => e.stopPropagation());
  document.addEventListener('click', closeIntranet);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeIntranet(); });
  panel.querySelector('#intranetForm').addEventListener('submit', (e) => {
    e.preventDefault();
    panel.querySelector('.intranet-note').textContent = 'Formulario de prueba: la intranet aún no está conectada a un sistema real.';
  });
}

// ============ BUSCADOR INTERNO ============
// Índice de páginas y secciones del sitio. Al agregar contenido real,
// conviene actualizar "keywords" y "snippet" para mejores resultados.
const SEARCH_INDEX = [
  { title: 'Bienvenida', url: 'bienvenida.html', category: 'Nuestro Colegio', keywords: 'bienvenida mensaje director inicio presentación', snippet: 'Mensaje de bienvenida y datos institucionales.' },
  { title: 'Nuestra Historia', url: 'historia.html', category: 'Nuestro Colegio', keywords: 'historia fundación reseña trayectoria', snippet: 'Reseña histórica de la institución.' },
  { title: 'Nuestros Valores', url: 'valores.html', category: 'Nuestro Colegio', keywords: 'valores misión visión principios', snippet: 'Misión, visión y valores institucionales.' },
  { title: 'Nuestro Espacio', url: 'espacio.html', category: 'Nuestro Colegio', keywords: 'espacio infraestructura aulas patio innovación pedagógica instalaciones', snippet: 'Infraestructura y espacios del colegio.' },
  { title: 'Galería', url: 'galeria.html', category: 'Nuestro Colegio', keywords: 'galería fotos imágenes fotografías', snippet: 'Fotos del colegio con vista ampliada.' },
  { title: 'Preescolar', url: 'preescolar.html', category: 'Niveles', keywords: 'preescolar inicial niños jardín', snippet: 'Información del nivel Preescolar.' },
  { title: 'Primaria', url: 'primaria.html', category: 'Niveles', keywords: 'primaria estudiantes grados', snippet: 'Información del nivel Primaria.' },
  { title: 'Padres de Familia', url: 'padres.html', category: 'Comunidad', keywords: 'padres familia matrícula calendario preguntas frecuentes faq comunicación', snippet: 'Matrícula, calendario y preguntas frecuentes.' },
  { title: 'Docentes', url: 'docentes.html', category: 'Comunidad', keywords: 'docentes profesores maestros equipo personal aula innovación pedagógica', snippet: 'Equipo docente y administrativo.' },
  { title: 'Noticias', url: 'noticias.html', category: 'Más', keywords: 'noticias comunicados eventos calendario próximos', snippet: 'Comunicados y próximos eventos del colegio.' },
  { title: 'Contacto', url: 'contacto.html', category: 'Más', keywords: 'contacto dirección ubicación teléfono correo mapa formulario', snippet: 'Formulario, dirección y ubicación del colegio.' },
];

try {
  const searchToggle = document.getElementById('searchToggle');
  if (searchToggle) {
    const overlay = document.createElement('div');
    overlay.className = 'search-overlay';
    overlay.id = 'searchOverlay';
    overlay.innerHTML = `
      <div class="search-box">
        <div class="search-box-header">
          <span class="search-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></span>
          <input type="text" id="searchInput" placeholder="Buscar en el sitio…" autocomplete="off" aria-label="Buscar en el sitio">
          <button type="button" id="searchCloseBtn" aria-label="Cerrar buscador"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="search-results" id="searchResults"></div>
        <p class="search-hint">Presiona <kbd>Esc</kbd> para cerrar · <kbd>Ctrl</kbd>+<kbd>K</kbd> para buscar</p>
      </div>
    `;
    document.body.appendChild(overlay);

    const searchInput = overlay.querySelector('#searchInput');
    const searchResults = overlay.querySelector('#searchResults');
    const searchCloseBtn = overlay.querySelector('#searchCloseBtn');

    function renderResults(query) {
      const q = query.trim().toLowerCase();
      if (!q) {
        searchResults.innerHTML = '<p class="search-empty">Escribe para buscar páginas del sitio (ej. "matrícula", "docentes", "galería").</p>';
        return;
      }
      const matches = SEARCH_INDEX.filter(item =>
        item.title.toLowerCase().includes(q) ||
        item.keywords.toLowerCase().includes(q) ||
        item.snippet.toLowerCase().includes(q)
      );
      if (!matches.length) {
        searchResults.innerHTML = '<p class="search-empty">Sin resultados para "' + query + '".</p>';
        return;
      }
      searchResults.innerHTML = matches.map(item => `
        <a class="search-result" href="${item.url}">
          <strong>${item.title}</strong>
          <span>${item.category} · ${item.snippet}</span>
        </a>
      `).join('');
    }

    function openSearch() {
      overlay.classList.add('open');
      renderResults('');
      searchInput.value = '';
      setTimeout(() => searchInput.focus(), 30);
    }
    function closeSearch() {
      overlay.classList.remove('open');
    }

    searchToggle.addEventListener('click', openSearch);
    searchCloseBtn.addEventListener('click', closeSearch);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeSearch(); });
    searchInput.addEventListener('input', () => renderResults(searchInput.value));

    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        overlay.classList.contains('open') ? closeSearch() : openSearch();
      }
      if (e.key === 'Escape' && overlay.classList.contains('open')) closeSearch();
    });
  }
} catch (err) {
  console.error('[main.js] Error en buscador interno:', err);
}


// ============ EVENTOS / CALENDARIO ESCOLAR ============
// Fechas de ejemplo — reemplazar por el calendario real que confirme el colegio.
const SCHOOL_EVENTS = [
  { date: '2026-08-30', title: '[Simulacro Nacional de Sismo — fecha referencial]', desc: '[Descripción pendiente de confirmar con el colegio.]' },
  { date: '2026-09-23', title: '[Día de la Primavera — fecha referencial]', desc: '[Descripción pendiente de confirmar con el colegio.]' },
  { date: '2026-10-08', title: '[Aniversario del colegio — fecha referencial]', desc: '[Descripción pendiente de confirmar con el colegio.]' },
  { date: '2026-11-20', title: '[Semana de la Educación Vial — fecha referencial]', desc: '[Descripción pendiente de confirmar con el colegio.]' },
  { date: '2026-12-18', title: '[Clausura del año escolar — fecha referencial]', desc: '[Descripción pendiente de confirmar con el colegio.]' },
];

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

function diasRestantes(dateStr) {
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const fecha = new Date(dateStr + 'T00:00:00');
  return Math.round((fecha - hoy) / 86400000);
}

function eventosOrdenados() {
  return [...SCHOOL_EVENTS].sort((a, b) => new Date(a.date) - new Date(b.date));
}

// --- Tarjetas de eventos con filtro (noticias.html) ---
try {
  const eventsGrid = document.getElementById('eventsGrid');
  if (eventsGrid) {
    const filterBtns = document.querySelectorAll('.events-filter-btn');
    const proximo = eventosOrdenados().find(ev => diasRestantes(ev.date) >= 0);

    function pintarEventos(filtro) {
      const ahora = new Date();
      let lista = eventosOrdenados().filter(ev => diasRestantes(ev.date) >= 0);

      if (filtro === 'mes') {
        lista = lista.filter(ev => {
          const f = new Date(ev.date + 'T00:00:00');
          return f.getMonth() === ahora.getMonth() && f.getFullYear() === ahora.getFullYear();
        });
      } else if (filtro === '30') {
        lista = lista.filter(ev => diasRestantes(ev.date) <= 30);
      }

      if (!lista.length) {
        eventsGrid.innerHTML = '<p class="events-empty">No hay eventos programados en este filtro por ahora.</p>';
        return;
      }

      eventsGrid.innerHTML = lista.map(ev => {
        const f = new Date(ev.date + 'T00:00:00');
        const dias = diasRestantes(ev.date);
        const esProximo = proximo && ev.date === proximo.date;
        const textoDias = dias === 0 ? '¡Es hoy!' : dias === 1 ? 'Falta 1 día' : `Faltan ${dias} días`;
        return `
          <article class="event-card ${esProximo ? 'is-next' : ''}">
            <div class="event-date-badge">
              <strong>${f.getDate()}</strong>
              <span>${MESES[f.getMonth()]}</span>
            </div>
            <div class="event-body">
              <h3 class="placeholder-text">${ev.title}</h3>
              <p class="placeholder-text">${ev.desc}</p>
              <span class="event-countdown">${textoDias}</span>
            </div>
          </article>
        `;
      }).join('');
    }

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        pintarEventos(btn.dataset.filter);
      });
    });

    pintarEventos('proximos');
  }
} catch (err) {
  console.error('[main.js] Error en tarjetas de eventos con filtro:', err);
}


// --- Banner de cuenta regresiva (index.html) ---
try {
  const nextEventBanner = document.getElementById('nextEventBanner');
  if (nextEventBanner) {
    const proximo = eventosOrdenados().find(ev => diasRestantes(ev.date) >= 0);
    if (proximo) {
      const dias = diasRestantes(proximo.date);
      const f = new Date(proximo.date + 'T00:00:00');
      const fechaLegible = `${f.getDate()} de ${['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][f.getMonth()]}`;
      nextEventBanner.innerHTML = `
        <div>
          <p class="label">Próximo evento</p>
          <h3 class="placeholder-text">${proximo.title}</h3>
          <p style="margin:2px 0 0; font-size:.85rem; color:#55646f;">${fechaLegible}</p>
        </div>
        <div class="countdown-days">
          <strong>${dias}</strong>
          <span>${dias === 1 ? 'día para el evento' : 'días para el evento'}</span>
        </div>
      `;
    } else {
      nextEventBanner.innerHTML = '<p class="placeholder-text">No hay eventos próximos programados por el momento.</p>';
    }
  }
} catch (err) {
  console.error('[main.js] Error en banner de cuenta regresiva:', err);
}


// ============ TARJETAS DE DOCENTES (modal) ============
try {
  const teacherCards = document.querySelectorAll('.teacher-card');
  const teacherModalOverlay = document.getElementById('teacherModalOverlay');

  if (teacherCards.length && teacherModalOverlay) {
    const teacherModalPhoto = document.getElementById('teacherModalPhoto');
    const teacherModalName = document.getElementById('teacherModalName');
    const teacherModalRole = document.getElementById('teacherModalRole');
    const teacherModalBio = document.getElementById('teacherModalBio');
    const teacherModalClose = teacherModalOverlay.querySelector('.teacher-modal-close');

    teacherCards.forEach(card => {
      card.addEventListener('click', () => {
        teacherModalPhoto.textContent = card.querySelector('.teacher-photo').textContent;
        teacherModalName.textContent = card.querySelector('h3').textContent;
        teacherModalRole.textContent = card.querySelector('.teacher-role').textContent;
        teacherModalBio.textContent = card.dataset.bio || '';
        teacherModalOverlay.classList.add('open');
      });
    });

    function closeTeacherModal() { teacherModalOverlay.classList.remove('open'); }
    teacherModalClose?.addEventListener('click', closeTeacherModal);
    teacherModalOverlay.addEventListener('click', (e) => { if (e.target === teacherModalOverlay) closeTeacherModal(); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && teacherModalOverlay.classList.contains('open')) closeTeacherModal();
    });
  }
} catch (err) {
  console.error('[main.js] Error en tarjetas de docentes (modal):', err);
}

// ============ HORARIO POR DOCENTE (modal reutilizable) ============
// Cada botón "Ver horario" trae sus datos en atributos data-*:
//   data-teacher-name, data-teacher-role  -> encabezado del modal
//   data-schedule  -> JSON con las filas: [{"dia":"","hora":"","curso":"","grado":""}]
// El mismo modal se reutiliza para todos los profesores de la página:
// al pulsar "Ver horario" en otro profesor, el contenido se reemplaza
// por completo (nunca se mezcla con el horario anterior).
try {
  const scheduleButtons = document.querySelectorAll('.teacher-schedule-btn');
  const scheduleModalOverlay = document.getElementById('scheduleModalOverlay');

  if (scheduleButtons.length && scheduleModalOverlay) {
    const scheduleModalName = document.getElementById('scheduleModalName');
    const scheduleModalRole = document.getElementById('scheduleModalRole');
    const scheduleModalBody = document.getElementById('scheduleModalBody');
    const scheduleModalClose = scheduleModalOverlay.querySelector('.teacher-modal-close');

    function openScheduleModal(btn) {
      scheduleModalName.textContent = btn.dataset.teacherName || '';
      scheduleModalRole.textContent = btn.dataset.teacherRole || '';

      let rows = [];
      try { rows = JSON.parse(btn.dataset.schedule || '[]'); } catch (e) { rows = []; }

      scheduleModalBody.innerHTML = rows.length
        ? rows.map(r => `<tr><td>${r.dia || ''}</td><td>${r.hora || ''}</td><td>${r.curso || ''}</td><td>${r.grado || ''}</td></tr>`).join('')
        : `<tr><td colspan="4" class="placeholder-text">[Horario pendiente de confirmar con el colegio]</td></tr>`;

      scheduleModalOverlay.classList.add('open');
    }

    function closeScheduleModal() { scheduleModalOverlay.classList.remove('open'); }

    scheduleButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation(); // evita que también dispare el modal de biografía del docente
        openScheduleModal(btn);
      });
    });

    scheduleModalClose?.addEventListener('click', closeScheduleModal);
    scheduleModalOverlay.addEventListener('click', (e) => { if (e.target === scheduleModalOverlay) closeScheduleModal(); });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && scheduleModalOverlay.classList.contains('open')) closeScheduleModal();
    });
  }
} catch (err) {
  console.error('[main.js] Error en modal de horario por docente:', err);
}

// ============ VISOR DE DOCUMENTOS PDF (modal reutilizable) ============
// Intercepta los enlaces al FUT y al Reglamento Interno (en el menú
// "Documentos Institucionales" y en documentos.html) para que, en vez
// de abrir/descargar el PDF directamente, se muestre un modal con:
// nombre del documento, vista previa (iframe) y botón de descarga.
// El modal se crea una sola vez (inyectado en <body>) y se reutiliza
// para ambos documentos, reemplazando su contenido cada vez.
// No modifica ningún otro enlace ni funcionalidad existente.
try {
  // Mapa de rutas conocidas -> nombre visible del documento.
  // Solo estos dos documentos abren el modal; cualquier otro enlace
  // (incluidos otros PDF que pudieran añadirse en el futuro) sigue
  // funcionando como hasta ahora.
  const DOC_MODAL_FILES = {
    'FUT-PRUEBA.pdf': 'FUT (Formulario Único de Trámite)',
    'Reglamento-Interno-PRUEBA.pdf': 'Reglamento Interno',
    'Calendarizacion-2026.pdf': 'Calendarización 2026',
    'Normas-de-Convivencia-2026.pdf': 'Normas de Convivencia 2026'
  };

  function matchDocModalFile(href) {
    if (!href) return null;
    const cleanHref = href.split('?')[0].split('#')[0];
    const fileName = cleanHref.substring(cleanHref.lastIndexOf('/') + 1);
    return DOC_MODAL_FILES[fileName] ? { fileName, title: DOC_MODAL_FILES[fileName] } : null;
  }

  const docModalLinks = Array.from(document.querySelectorAll('a[href$=".pdf"]'))
    .filter(link => matchDocModalFile(link.getAttribute('href')));

  if (docModalLinks.length) {
    let docModalOverlay, docModalTitle, docModalFrame, docModalDownload, docModalClose;

    function buildDocModal() {
      if (docModalOverlay) return;

      docModalOverlay = document.createElement('div');
      docModalOverlay.className = 'teacher-modal-overlay doc-modal-overlay';
      docModalOverlay.id = 'docModalOverlay';
      docModalOverlay.innerHTML = `
        <div class="teacher-modal doc-modal" role="dialog" aria-modal="true" aria-labelledby="docModalTitle">
          <button type="button" class="teacher-modal-close" aria-label="Cerrar"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
          <h3 id="docModalTitle">Documento</h3>
          <div class="doc-modal-viewer">
            <iframe id="docModalFrame" title="Vista previa del documento PDF" src=""></iframe>
          </div>
          <div class="doc-modal-actions">
            <a id="docModalDownload" class="btn btn-primary" href="#" download>Descargar PDF</a>
          </div>
        </div>`;
      document.body.appendChild(docModalOverlay);

      docModalTitle = document.getElementById('docModalTitle');
      docModalFrame = document.getElementById('docModalFrame');
      docModalDownload = document.getElementById('docModalDownload');
      docModalClose = docModalOverlay.querySelector('.teacher-modal-close');

      docModalClose.addEventListener('click', closeDocModal);
      docModalOverlay.addEventListener('click', (e) => { if (e.target === docModalOverlay) closeDocModal(); });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && docModalOverlay.classList.contains('open')) closeDocModal();
      });
    }

    function openDocModal(href, title) {
      buildDocModal();
      docModalTitle.textContent = title;
      docModalFrame.src = href;
      docModalDownload.setAttribute('href', href);
      docModalDownload.setAttribute('download', '');
      docModalOverlay.classList.add('open');
    }

    function closeDocModal() {
      if (!docModalOverlay) return;
      docModalOverlay.classList.remove('open');
      // Detiene la carga del PDF al cerrar (evita audio/descargas en segundo plano)
      docModalFrame.src = '';
    }

    docModalLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        const match = matchDocModalFile(link.getAttribute('href'));
        if (!match) return;
        e.preventDefault();
        openDocModal(link.getAttribute('href'), match.title);
      });
    });
  }
} catch (err) {
  console.error('[main.js] Error en visor de documentos PDF:', err);
}

/* =========================================================
   NOTICIA INSTITUCIONAL DESTACADA
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

  const modal = document.getElementById("institutionalNews");
  const backdrop = document.getElementById("institutionalNewsBackdrop");
  const closeButton = document.getElementById("institutionalNewsClose");
  const laterButton = document.getElementById("institutionalNewsLater");

  if (!modal) {
    return;
  }

  let lastFocusedElement = null;

  /* -------------------------------------------------------
     ABRIR
  ------------------------------------------------------- */

  function openNews() {

    lastFocusedElement = document.activeElement;

    modal.classList.add("is-open");

    modal.setAttribute(
      "aria-hidden",
      "false"
    );

    document.body.classList.add(
      "institutional-news-open"
    );

    /* Enfocar botón cerrar */
    setTimeout(function () {

      if (closeButton) {
        closeButton.focus();
      }

    }, 100);
  }


  /* -------------------------------------------------------
     CERRAR
  ------------------------------------------------------- */

  function closeNews() {

    modal.classList.remove("is-open");

    modal.setAttribute(
      "aria-hidden",
      "true"
    );

    document.body.classList.remove(
      "institutional-news-open"
    );

    /* Devolver foco al elemento anterior */
    if (
      lastFocusedElement &&
      typeof lastFocusedElement.focus === "function"
    ) {

      lastFocusedElement.focus();

    }
  }


  /* -------------------------------------------------------
     MOSTRAR DESPUÉS DE CARGAR
  ------------------------------------------------------- */

  setTimeout(function () {

    openNews();

  }, 900);


  /* -------------------------------------------------------
     BOTÓN X
  ------------------------------------------------------- */

  if (closeButton) {

    closeButton.addEventListener(
      "click",
      closeNews
    );

  }


  /* -------------------------------------------------------
     BOTÓN CERRAR
  ------------------------------------------------------- */

  if (laterButton) {

    laterButton.addEventListener(
      "click",
      closeNews
    );

  }


  /* -------------------------------------------------------
     CLIC EN EL FONDO
  ------------------------------------------------------- */

  if (backdrop) {

    backdrop.addEventListener(
      "click",
      closeNews
    );

  }


  /* -------------------------------------------------------
     TECLA ESC
  ------------------------------------------------------- */

  document.addEventListener(
    "keydown",
    function (event) {

      if (
        event.key === "Escape" &&
        modal.classList.contains("is-open")
      ) {

        closeNews();

      }

    }
  );

});
