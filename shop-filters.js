(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('catalog-products-grid');
    if (!grid) return;

    const allCards = Array.from(grid.querySelectorAll('.product-card'));
    const filterNames = ['collection', 'cat', 'metal', 'occasion'];
    const priceSlider = document.getElementById('price-slider');
    const priceLabel = document.getElementById('price-slider-val');
    const sortSelect = document.getElementById('sort-select');
    const countText = document.getElementById('showing-count-text');
    const chipsBar = document.getElementById('active-filter-chips-bar');
    const chipsContainer = document.getElementById('active-chips-container');
    const pagination = document.querySelector('.pagination-container');
    const clearButton = document.getElementById('btn-clear-all-filters');
    const clearChipsButton = document.getElementById('btn-clear-chips');
    const pageSize = 12;
    const maxAllowedPrice = 5000;
    let currentPage = 1;
    let filteredCards = allCards.slice();
    let allMetalsWasClicked = false;

    if (priceSlider) {
      priceSlider.min = '0';
      priceSlider.max = String(maxAllowedPrice);
      priceSlider.step = '100';
      if (!priceSlider.value || Number(priceSlider.value) > maxAllowedPrice) priceSlider.value = String(maxAllowedPrice);
    }

    function checkedValues(name) {
      return Array.from(document.querySelectorAll('input[name="' + name + '"]:checked')).map(function (input) {
        return input.value;
      });
    }

    function slugify(value) {
      return String(value || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    function normalizeCategory(value) {
      let normalized = String(value || '')
        .normalize('NFKC')
        .toLocaleLowerCase()
        .replace(/&/g, ' and ')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim()
        .replace(/\s+/g, ' ');

      if (!normalized) return '';

      const parts = normalized.split(' ');
      const last = parts[parts.length - 1];
      if (last.length > 3 && last.endsWith('ies')) {
        parts[parts.length - 1] = last.slice(0, -3) + 'y';
      } else if (last.length > 2 && last.endsWith('s') && !last.endsWith('ss')) {
        parts[parts.length - 1] = last.slice(0, -1);
      }
      return parts.join(' ');
    }

    function matchesCategory(card, selectedValue) {
      const selectedInput = Array.from(document.querySelectorAll('input[name="cat"]')).find(function (input) {
        return normalizeCategory(input.value) === normalizeCategory(selectedValue);
      });
      const productCategory = normalizeCategory(card.dataset.productCategory || '');
      const selectedCategory = normalizeCategory(selectedValue);
      const productSlug = slugify(card.dataset.productCategorySlug || '');
      const selectedSlug = selectedInput ? slugify(selectedInput.dataset.categorySlug || '') : '';

      // The admin-assigned products.category field is authoritative. Slugs are used only
      // to compare the same category when labels differ by singular/plural formatting.
      return (productCategory !== '' && productCategory === selectedCategory)
        || (productSlug !== '' && selectedSlug !== '' && productSlug === selectedSlug);
    }

    function cardDate(card) {
      const value = String(card.dataset.productCreated || '').replace(' ', 'T');
      const timestamp = Date.parse(value);
      return Number.isFinite(timestamp) ? timestamp : 0;
    }

    function formatRupees(value) {
      return '₹ ' + Number(value).toLocaleString('en-IN');
    }

    function normalizeProductAttribute(value) {
      return String(value || '').normalize('NFKC').trim().replace(/\s+/g, ' ').toLocaleLowerCase();
    }

    function matchesMetal(card, value) {
      // Match only the Metal Type selected by the admin and saved in products.metal_type.
      return normalizeProductAttribute(card.dataset.productMetalType) === normalizeProductAttribute(value);
    }

    function matchesOccasion(card, value) {
      // Match only the Occasion selected by the admin and saved in products.occasion.
      return normalizeProductAttribute(card.dataset.productOccasion) === normalizeProductAttribute(value);
    }

    function matchesAnySelected(card, name, matcher) {
      const selected = checkedValues(name);
      if (!selected.length) return true;
      return selected.some(function (value) { return matcher(card, value); });
    }

    function matchesCollection(card, value) {
      if (value === 'new') return card.dataset.collectionNew === '1';
      if (value === 'bestseller') return card.dataset.collectionBestseller === '1';
      if (value === 'offers') return card.dataset.collectionOffers === '1';
      return false;
    }

    const searchTerm = (new URLSearchParams(window.location.search).get('q') || '').trim().toLowerCase();

    function matchesSearch(card) {
      if (!searchTerm) return true;
      const haystack = [
        card.dataset.productTitle, card.dataset.productCategory, card.dataset.productMetalType,
        card.dataset.productOccasion, card.dataset.productDescription
      ].join(' ').toLowerCase();
      return searchTerm.split(/\s+/).every(function (word) { return haystack.indexOf(word) !== -1; });
    }

    function isMatching(card) {
      if (!matchesSearch(card)) return false;
      if (!matchesAnySelected(card, 'collection', matchesCollection)) return false;
      if (!matchesAnySelected(card, 'cat', matchesCategory)) return false;
      if (!matchesAnySelected(card, 'metal', matchesMetal)) return false;
      if (!matchesAnySelected(card, 'occasion', matchesOccasion)) return false;

      const maxPrice = priceSlider ? Number(priceSlider.value) : maxAllowedPrice;
      const price = Number(card.dataset.productPrice || 0);
      return Number.isFinite(price) && price <= maxPrice;
    }

    function sortCards(cards) {
      const mode = sortSelect ? sortSelect.value : 'recommended';
      const selectedCollections = checkedValues('collection');
      return cards.slice().sort(function (a, b) {
        if (mode === 'price-asc') return Number(a.dataset.productPrice) - Number(b.dataset.productPrice);
        if (mode === 'price-desc') return Number(b.dataset.productPrice) - Number(a.dataset.productPrice);
        if (mode === 'ordered') {
          return Number(b.dataset.productOrderQty) - Number(a.dataset.productOrderQty) || cardDate(b) - cardDate(a);
        }
        if (mode === 'discount') {
          return Number(b.dataset.productDiscount) - Number(a.dataset.productDiscount) || cardDate(b) - cardDate(a);
        }
        if (mode === 'rating') {
          return Number(b.dataset.productRating) - Number(a.dataset.productRating)
            || Number(b.dataset.productOrderQty) - Number(a.dataset.productOrderQty)
            || cardDate(b) - cardDate(a);
        }
        if (mode === 'recommended') {
          if (selectedCollections.includes('bestseller')) {
            return Number(b.dataset.productOrderQty) - Number(a.dataset.productOrderQty) || cardDate(b) - cardDate(a);
          }
          if (selectedCollections.includes('offers')) {
            return Number(b.dataset.productDiscount) - Number(a.dataset.productDiscount) || cardDate(b) - cardDate(a);
          }
        }
        return cardDate(b) - cardDate(a) || Number(b.dataset.productId) - Number(a.dataset.productId);
      });
    }

    function updatePriceLabel() {
      if (!priceSlider || !priceLabel) return;
      priceLabel.textContent = formatRupees(priceSlider.value);
    }

    function getFilterLabel(input) {
      const label = input.closest('label');
      if (!label) return input.value;
      const clone = label.cloneNode(true);
      const checkbox = clone.querySelector('input');
      if (checkbox) checkbox.remove();
      const count = clone.querySelector('.filter-count');
      if (count) count.remove();
      return clone.textContent.replace(/[()]/g, '').trim() || input.value;
    }

    function updateChips() {
      if (!chipsBar || !chipsContainer) return;
      chipsContainer.innerHTML = '';
      let chipCount = 0;

      filterNames.forEach(function (name) {
        document.querySelectorAll('input[name="' + name + '"]:checked').forEach(function (input) {
          const chip = document.createElement('span');
          chip.className = 'active-filter-chip';
          const text = document.createElement('span');
          text.textContent = getFilterLabel(input);
          const remove = document.createElement('button');
          remove.type = 'button';
          remove.setAttribute('aria-label', 'Remove ' + text.textContent + ' filter');
          remove.textContent = '×';
          remove.addEventListener('click', function () {
            input.checked = false;
            if (name === 'metal') allMetalsWasClicked = false;
            syncMetalPills();
            applyFilters();
          });
          chip.appendChild(text);
          chip.appendChild(remove);
          chipsContainer.appendChild(chip);
          chipCount++;
        });
      });

      if (priceSlider && Number(priceSlider.value) < maxAllowedPrice) {
        const chip = document.createElement('span');
        chip.className = 'active-filter-chip';
        const text = document.createElement('span');
        text.textContent = 'Up to ' + formatRupees(priceSlider.value);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Remove price filter');
        remove.textContent = '×';
        remove.addEventListener('click', function () {
          priceSlider.value = String(maxAllowedPrice);
          updatePriceLabel();
          applyFilters();
        });
        chip.appendChild(text);
        chip.appendChild(remove);
        chipsContainer.appendChild(chip);
        chipCount++;
      }
      chipsBar.style.display = chipCount ? 'flex' : 'none';
    }

    function renderPagination(total) {
      if (!pagination) return;
      pagination.innerHTML = '';
      const pages = Math.ceil(total / pageSize);
      if (pages <= 1) {
        pagination.style.display = 'none';
        return;
      }
      pagination.style.display = 'flex';

      const previous = document.createElement('button');
      previous.className = 'pagination-btn';
      previous.id = 'page-prev';
      previous.title = 'Previous';
      previous.type = 'button';
      previous.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
      previous.disabled = currentPage <= 1;
      previous.addEventListener('click', function () {
        currentPage = Math.max(1, currentPage - 1);
        renderProducts();
      });
      pagination.appendChild(previous);

      for (let page = 1; page <= pages; page++) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pagination-btn' + (page === currentPage ? ' active' : '');
        button.dataset.page = String(page);
        button.textContent = String(page);
        button.addEventListener('click', function () {
          currentPage = page;
          renderProducts();
        });
        pagination.appendChild(button);
      }

      const next = document.createElement('button');
      next.className = 'pagination-btn';
      next.id = 'page-next';
      next.title = 'Next';
      next.type = 'button';
      next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
      next.disabled = currentPage >= pages;
      next.addEventListener('click', function () {
        currentPage = Math.min(pages, currentPage + 1);
        renderProducts();
      });
      pagination.appendChild(next);
    }

    function renderProducts() {
      const start = (currentPage - 1) * pageSize;
      const visibleCards = filteredCards.slice(start, start + pageSize);
      // The KINZ stylesheet intentionally declares .product-card with
      // display:flex !important. A normal card.style.display='none' is therefore
      // overridden and filtered-out cards remain visible. Use an inline important
      // declaration for hidden cards; remove it from matching cards so the existing
      // stylesheet controls their normal flex layout.
      allCards.forEach(function (card) {
        card.style.setProperty('display', 'none', 'important');
        card.setAttribute('aria-hidden', 'true');
      });
      visibleCards.forEach(function (card) {
        card.style.removeProperty('display');
        card.removeAttribute('aria-hidden');
        grid.appendChild(card);
      });

      if (countText) countText.textContent = 'Showing ' + filteredCards.length + ' products';
      let emptyMessage = document.getElementById('shop-no-results');
      if (!emptyMessage) {
        emptyMessage = document.createElement('p');
        emptyMessage.id = 'shop-no-results';
        emptyMessage.className = 'shop-no-results';
        emptyMessage.style.cssText = 'grid-column:1 / -1;padding:28px 18px;text-align:center;color:var(--text-muted);border:1px dashed rgba(154,117,77,.35);border-radius:12px;';
        emptyMessage.textContent = 'No products match your selected filters. Try adjusting or clearing your filters.';
        grid.appendChild(emptyMessage);
      }
      emptyMessage.style.display = filteredCards.length ? 'none' : 'block';
      renderPagination(filteredCards.length);
    }

    function updateFacetCounts() {
      const definitions = {
        collection: function (card, value) { return matchesCollection(card, value); },
        cat: matchesCategory,
        metal: matchesMetal,
        occasion: matchesOccasion
      };
      Object.keys(definitions).forEach(function (name) {
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
          const label = input.closest('label');
          const count = label ? label.querySelector('.filter-count') : null;
          if (count) {
            const total = allCards.filter(function (card) { return definitions[name](card, input.value); }).length;
            count.textContent = '(' + total + ')';
          }
        });
      });
    }

    function syncMetalPills() {
      const selectedMetal = checkedValues('metal');

      document.querySelectorAll('.metal-pill').forEach(function (pill) {
        const value = pill.dataset.metal || 'all';
        const active = value === 'all'
          ? allMetalsWasClicked && selectedMetal.length === 0
          : selectedMetal.length === 1 && selectedMetal[0] === value;
        pill.classList.toggle('active', active);
      });
    }

    function updateCategoryHeading() {
      const heading = document.getElementById('catalog-title-heading');
      if (!heading) return;
      const selected = checkedValues('cat');
      if (!selected.length) {
        heading.textContent = checkedValues('collection').length ? 'Selected Collection' : 'All Jewellery';
        return;
      }
      const labels = selected.map(function (value) {
        const input = Array.from(document.querySelectorAll('input[name="cat"]')).find(function (item) {
          return item.value === value;
        });
        return input ? getFilterLabel(input) : value;
      });
      heading.textContent = labels.join(' & ');
    }

    function applyFilters() {
      currentPage = 1;
      filteredCards = sortCards(allCards.filter(isMatching));
      updatePriceLabel();
      syncMetalPills();
      updateChips();
      updateCategoryHeading();
      renderProducts();
    }

    function clearAllFilters() {
      filterNames.forEach(function (name) {
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) { input.checked = false; });
      });
      if (priceSlider) priceSlider.value = String(maxAllowedPrice);
      if (sortSelect) sortSelect.value = 'recommended';
      allMetalsWasClicked = false;
      try { window.history.replaceState({}, '', window.location.pathname); } catch (error) {}
      applyFilters();
    }

    function restoreFiltersFromUrl() {
      const params = new URLSearchParams(window.location.search);
      ['collection', 'cat', 'metal', 'occasion'].forEach(function (name) {
        params.getAll(name).forEach(function (value) {
          const input = Array.from(document.querySelectorAll('input[name="' + name + '"]')).find(function (item) {
            if (name === 'cat') {
              return normalizeCategory(item.value) === normalizeCategory(value)
                || slugify(item.dataset.categorySlug || '') === slugify(value);
            }
            return normalizeCategory(item.value) === normalizeCategory(value);
          });
          if (input) input.checked = true;
        });
      });

      const categoryValues = params.getAll('category');
      categoryValues.forEach(function (value) {
        const input = Array.from(document.querySelectorAll('input[name="cat"]')).find(function (item) {
          return normalizeCategory(item.value) === normalizeCategory(value)
            || slugify(item.dataset.categorySlug || '') === slugify(value);
        });
        if (input) input.checked = true;
      });

      const legacyFilter = (params.get('filter') || '').toLowerCase();
      const legacyCollection = legacyFilter === 'popular' ? 'bestseller' : (legacyFilter === 'new' ? 'new' : (legacyFilter === 'offers' ? 'offers' : ''));
      if (legacyCollection) {
        const input = document.querySelector('input[name="collection"][value="' + legacyCollection + '"]');
        if (input) input.checked = true;
      }

      if (params.get('metal') && normalizeProductAttribute(params.get('metal')) === 'all') {
        allMetalsWasClicked = true;
      }

      const maxPrice = params.get('max_price');
      if (maxPrice && priceSlider) priceSlider.value = String(Math.min(maxAllowedPrice, Math.max(0, Number(maxPrice) || maxAllowedPrice)));
      const requestedSort = params.get('sort');
      if (requestedSort && sortSelect && Array.from(sortSelect.options).some(function (option) { return option.value === requestedSort; })) {
        sortSelect.value = requestedSort;
      }
    }

    filterNames.forEach(function (name) {
      document.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
        input.addEventListener('change', function () {
          if (name === 'metal') allMetalsWasClicked = false;
          applyFilters();
        });
      });
    });
    if (priceSlider) {
      priceSlider.addEventListener('input', applyFilters);
      priceSlider.addEventListener('change', applyFilters);
    }
    if (sortSelect) sortSelect.addEventListener('change', applyFilters);
    if (clearButton) clearButton.addEventListener('click', clearAllFilters);
    if (clearChipsButton) clearChipsButton.addEventListener('click', clearAllFilters);

    document.querySelectorAll('.metal-pill').forEach(function (pill) {
      pill.addEventListener('click', function () {
        const metal = pill.dataset.metal || 'all';
        if (metal === 'all') {
          // All Metals removes only the Metal Type restriction. Category, Occasion,
          // Collection and Price filters remain active and continue to combine with it.
          document.querySelectorAll('input[name="metal"]').forEach(function (input) {
            input.checked = false;
          });
          allMetalsWasClicked = true;
          applyFilters();
          return;
        }

        allMetalsWasClicked = false;
        document.querySelectorAll('input[name="metal"]').forEach(function (input) {
          input.checked = input.value === metal;
        });
        applyFilters();
      });
    });

    window.scrollToCatalogSection = function () {
      const section = document.querySelector('.catalog-content-section') || document.getElementById('catalog-filters');
      if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    restoreFiltersFromUrl();
    updateFacetCounts();
    applyFilters();
  });
})();
