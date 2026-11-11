/* KINZ shared UI behaviour.
   Hero and Shop carousels auto-rotate every 3000 ms.
   Navigation shows the sliding golden underline indicator. */
(function () {
  'use strict';

function initHeroCarousel() {
  const carousel = document.getElementById('hero-carousel');
  if (!carousel) return;

  const slides = carousel.querySelectorAll('.hero-slide');
  const dots = carousel.querySelectorAll('.hero-dot');
  const prevBtn = document.getElementById('hero-prev-btn');
  const nextBtn = document.getElementById('hero-next-btn');
  if (slides.length <= 1) return;

  let currentIndex = 0;
  let autoplayTimer = null;

  function showSlide(index) {
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;
    currentIndex = index;

    slides.forEach((slide, idx) => {
      if (idx === currentIndex) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
    });

    dots.forEach((dot, idx) => {
      if (idx === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  function nextSlide() {
    showSlide(currentIndex + 1);
  }

  function prevSlide() {
    showSlide(currentIndex - 1);
  }

  function startAutoplay() {
    stopAutoplay();
    // Dynamic carousel rotation interval (3.0s / 3000ms) per user specification
    autoplayTimer = setInterval(nextSlide, 3000);
  }

  function stopAutoplay() {
    if (autoplayTimer) {
      clearInterval(autoplayTimer);
      autoplayTimer = null;
    }
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      startAutoplay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      startAutoplay();
    });
  }

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const slideIdx = parseInt(dot.getAttribute('data-slide') || '0', 10);
      showSlide(slideIdx);
      startAutoplay();
    });
  });

  carousel.addEventListener('mouseenter', stopAutoplay);
  carousel.addEventListener('mouseleave', startAutoplay);

  startAutoplay();
}

function initShopCarousel() {
  const carousel = document.getElementById('shop-carousel');
  if (!carousel) return;

  const slides = carousel.querySelectorAll('.shop-carousel-slide');
  const prevBtn = document.getElementById('shop-carousel-prev');
  const nextBtn = document.getElementById('shop-carousel-next');
  const dots = carousel.querySelectorAll('.shop-dot');

  if (slides.length <= 1) return;

  let currentIndex = 0;
  let autoplayTimer = null;

  function showSlide(index) {
    currentIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, idx) => {
      if (idx === currentIndex) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
    });

    dots.forEach((dot, idx) => {
      if (idx === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  function nextSlide() {
    showSlide(currentIndex + 1);
  }

  function prevSlide() {
    showSlide(currentIndex - 1);
  }

  function startAutoplay() {
    stopAutoplay();
    // Dynamic carousel rotation interval (3.0s / 3000ms) per user specification
    autoplayTimer = setInterval(nextSlide, 3000);
  }

  function stopAutoplay() {
    if (autoplayTimer) {
      clearInterval(autoplayTimer);
      autoplayTimer = null;
    }
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      startAutoplay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      startAutoplay();
    });
  }

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const slideIdx = parseInt(dot.getAttribute('data-slide') || '0', 10);
      showSlide(slideIdx);
      startAutoplay();
    });
  });

  // Keep the shop carousel rotating even while the pointer is over it.
  startAutoplay();
}

function initNavIndicator() {
  const navMenu = document.getElementById('primary-nav');
  if (!navMenu) return;

  let indicator = navMenu.querySelector('.nav-indicator');
  if (!indicator) {
    indicator = document.createElement('li');
    indicator.className = 'nav-indicator';
    indicator.id = 'nav-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    navMenu.appendChild(indicator);
  }

  const links = Array.from(navMenu.querySelectorAll('.nav-link'));

  function moveIndicatorTo(linkEl) {
    if (!linkEl || window.innerWidth <= 860) {
      indicator.style.opacity = '0';
      return;
    }
    const navRect = navMenu.getBoundingClientRect();
    const linkRect = linkEl.getBoundingClientRect();
    const left = linkRect.left - navRect.left;
    const width = linkRect.width;

    indicator.style.opacity = '1';
    indicator.style.left = `${left}px`;
    indicator.style.width = `${width}px`;
  }

  function getActiveLink() {
    return navMenu.querySelector('.nav-link.active') || links[0];
  }

  // PHP pages: mark the link that matches the current file (Home/Shop/About/Contact)
  (function markCurrentPage() {
    const file = (window.location.pathname.split('/').pop() || 'index.php').toLowerCase();
    const map = { 'index.php': 'home', 'afterloginindex.html': 'home', 'shop.php': 'shop', 'product-details.php': 'shop', 'about.html': 'about', 'contact.html': 'contact' };
    const key = map[file];
    if (!key || window.location.hash) return;
    const match = links.find(l => l.getAttribute('data-nav') === key);
    if (match) { links.forEach(l => l.classList.remove('active')); match.classList.add('active'); }
  })();

  // Initial positioning after layout calculation
  setTimeout(() => moveIndicatorTo(getActiveLink()), 80);
  window.addEventListener('resize', () => {
    if (window.innerWidth <= 860) {
      indicator.style.opacity = '0';
    } else {
      moveIndicatorTo(getActiveLink());
    }
  });

  let isUserClickScrolling = false;

  // Smooth traveling when user clicks any navigation link
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');

      // Check if hash matches an element on current page (e.g. #bestsellers, #featured-collections, #new-arrivals)
      if (href && (href.startsWith('#') || href.startsWith('index.php#'))) {
        const hash = href.includes('#') ? href.split('#')[1] : '';
        const targetEl = document.getElementById(hash);

        if (targetEl) {
          e.preventDefault();
          isUserClickScrolling = true;
          links.forEach(l => l.classList.remove('active'));
          link.classList.add('active');
          moveIndicatorTo(link);
          targetEl.scrollIntoView({ behavior: 'smooth' });
          if (history.pushState) {
            history.pushState(null, null, '#' + hash);
          }
          setTimeout(() => {
            isUserClickScrolling = false;
          }, 800);
          return;
        }
      }

      // External / standard page change
      links.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      moveIndicatorTo(link);
    });

    // Elegant preview glide on hover
    link.addEventListener('mouseenter', () => {
      moveIndicatorTo(link);
    });
  });

  navMenu.addEventListener('mouseleave', () => {
    moveIndicatorTo(getActiveLink());
  });

  // Landing Page Scroll-Spy: Traveling underline tracks sections during scroll
  // (e.g. traveling dynamically to Collections, Best Sellers, New Arrivals)
  const isLandingPage = /(^|\/)index\.php$/i.test(window.location.pathname) || 
                        window.location.pathname.endsWith('/') || 
                        window.location.pathname === '' || 
                        (!window.location.pathname.includes('.html') && document.getElementById('featured-collections') !== null);

  if (isLandingPage || document.getElementById('featured-collections')) {
    const scrollSectionMap = [
      { id: 'new-arrivals', navKey: 'new-arrivals' },
      { id: 'bestsellers', navKey: 'bestsellers' },
      { id: 'featured-collections', navKey: 'collections' },
      { id: 'hero-carousel', navKey: 'home' }
    ];

    let scrollTicking = false;

    function handleScrollSpy() {
      scrollTicking = false;
      if (isUserClickScrolling) return;

      const scrollPos = window.scrollY + 220; // 220px offset accounts for header + top buffer

      if (window.scrollY < 260) {
        const homeLink = navMenu.querySelector('.nav-link[data-nav="home"]');
        if (homeLink && !homeLink.classList.contains('active')) {
          links.forEach(l => l.classList.remove('active'));
          homeLink.classList.add('active');
          moveIndicatorTo(homeLink);
        }
        return;
      }

      let activeNavKey = null;
      for (const item of scrollSectionMap) {
        const sec = document.getElementById(item.id);
        if (sec) {
          const top = sec.offsetTop;
          const height = sec.offsetHeight;
          if (scrollPos >= top && scrollPos < top + height) {
            activeNavKey = item.navKey;
            break;
          }
        }
      }

      // Fallback: nearest section above scroll position
      if (!activeNavKey) {
        for (const item of scrollSectionMap) {
          const sec = document.getElementById(item.id);
          if (sec && scrollPos >= sec.offsetTop) {
            activeNavKey = item.navKey;
            break;
          }
        }
      }

      if (activeNavKey) {
        const targetLink = navMenu.querySelector(`.nav-link[data-nav="${activeNavKey}"]`);
        if (targetLink && !targetLink.classList.contains('active')) {
          links.forEach(l => l.classList.remove('active'));
          targetLink.classList.add('active');
          moveIndicatorTo(targetLink);
        }
      }
    }

    window.addEventListener('scroll', () => {
      if (!scrollTicking) {
        window.requestAnimationFrame(handleScrollSpy);
        scrollTicking = true;
      }
    }, { passive: true });
  }

  // Handle direct hash navigation on page load
  if (window.location.hash) {
    const targetHash = window.location.hash;
    const matchingLink = links.find(l => {
      const h = l.getAttribute('href');
      return h === targetHash || h === 'index.php' + targetHash;
    });
    if (matchingLink) {
      links.forEach(l => l.classList.remove('active'));
      matchingLink.classList.add('active');
      setTimeout(() => moveIndicatorTo(matchingLink), 150);
      const targetSec = document.getElementById(targetHash.replace('#', ''));
      if (targetSec) {
        setTimeout(() => targetSec.scrollIntoView({ behavior: 'smooth' }), 200);
      }
    }
  }
}


  /* ---------- Header search (all pages) ---------- */
  function initHeaderSearch() {
    var input = document.getElementById('site-search-input');
    var dropdown = document.getElementById('search-dropdown');
    if (!input) return;
    var wrapper = input.closest('.search-input-wrapper');
    var icon = wrapper ? wrapper.querySelector('.search-icon') : null;
    var timer = null, lastQuery = '';

    function esc(v) {
      return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
    function goToShop() {
      var q = input.value.trim();
      if (!q) { input.focus(); return; }
      window.location.href = 'shop.php?q=' + encodeURIComponent(q);
    }
    function hide() { if (dropdown) dropdown.classList.remove('show'); }
    function render(results, q) {
      if (!dropdown) return;
      if (!results.length) {
        dropdown.innerHTML = '<div class="search-result-item" style="cursor:default"><div class="search-result-info"><h4>No jewellery found</h4><p>Try a different keyword</p></div></div>';
      } else {
        dropdown.innerHTML = results.map(function (r) {
          return '<a class="search-result-item" href="product-details.php?product_id=' + r.id + '" style="text-decoration:none">' +
            '<img class="search-result-img" src="' + esc(r.image) + '" alt="">' +
            '<div class="search-result-info"><h4>' + esc(r.title) + '</h4><p>\u20B9' + Number(r.price).toLocaleString('en-IN') + ' \u00B7 ' + esc(r.category) + '</p></div></a>';
        }).join('') +
          '<a class="search-result-item" href="shop.php?q=' + encodeURIComponent(q) + '" style="text-decoration:none;justify-content:center"><div class="search-result-info"><h4>View all results</h4></div></a>';
      }
      dropdown.classList.add('show');
    }
    async function lookup(q) {
      try {
        var res = await fetch('account_api.php?action=search_products&q=' + encodeURIComponent(q), {
          credentials: 'same-origin', headers: { 'Accept': 'application/json' }
        });
        var data = await res.json();
        if (q !== input.value.trim()) return;
        render(data && data.results ? data.results : [], q);
      } catch (e) { hide(); }
    }

    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { hide(); return; }
      if (q === lastQuery) return;
      lastQuery = q;
      timer = setTimeout(function () { lookup(q); }, 250);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); goToShop(); }
      if (e.key === 'Escape') hide();
    });
    input.addEventListener('focus', function () {
      if (dropdown && dropdown.innerHTML && input.value.trim().length >= 2) dropdown.classList.add('show');
    });
    if (icon) {
      icon.style.pointerEvents = 'auto';
      icon.style.cursor = 'pointer';
      icon.addEventListener('click', goToShop);
    }
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.header-search')) hide();
    });
  }

  /* ---------- Notification bell (all pages) ---------- */
  function initNotificationBell() {
    document.addEventListener('click', function (e) {
      var bell = e.target.closest ? e.target.closest('.notification-action-btn') : null;
      if (!bell) return;
      e.preventDefault();
      var onAccount = /(^|\/)account\.html$/i.test(window.location.pathname);
      if (onAccount && typeof window.switchAccountTab === 'function') {
        window.switchAccountTab('notifications');
        return;
      }
      window.location.href = 'account.html#notifications';
    });
  }

  function boot() {
    try { initHeaderSearch(); } catch (e) { console.warn('KINZ search', e); }
    try { initNotificationBell(); } catch (e) { console.warn('KINZ bell', e); }
    try { initHeroCarousel(); } catch (e) { console.warn('KINZ hero carousel', e); }
    try { initShopCarousel(); } catch (e) { console.warn('KINZ shop carousel', e); }
    try { initNavIndicator(); } catch (e) { console.warn('KINZ nav indicator', e); }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
