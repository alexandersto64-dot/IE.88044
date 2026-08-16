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

// Submenús desplegables (Nuestro Colegio / Niveles / Comunidad)
try {
  const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');

  dropdownItems.forEach(item => {
    const btn = item.querySelector('.nav-dropdown-btn');
    if (!btn) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = item.classList.contains('open');
      // Cierra los demás submenús abiertos
      dropdownItems.forEach(other => {
        other.classList.remove('open');
        other.querySelector('.nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // Cierra los submenús al hacer clic fuera del menú
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-item.has-dropdown')) {
      dropdownItems.forEach(item => {
        item.classList.remove('open');
        item.querySelector('.nav-dropdown-btn')?.setAttribute('aria-expanded', 'false');
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

