/**
 * Kinz Jewellery Admin - Full Interactive Controller
 * Real-time MySQL database synchronization with zero popup alerts
 * Instant image preview, product edit modal, drawers, and live search
 */

// ============================================================================
// 1. Toast Notification System (Zero Browser Alert Dialogs)
// ============================================================================
function showAlert(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (container) {
    const toast = document.createElement('div');
    toast.className = `toast-message toast-${type}`;
    toast.style.cssText = `
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 20px;
      margin-bottom: 10px;
      border-radius: 8px;
      background: var(--forest-green, #18352D);
      color: var(--champagne-beige, #F4E2C5);
      border: 1px solid var(--border-gold, rgba(200, 121, 39, 0.4));
      box-shadow: 0 8px 24px rgba(6, 44, 34, 0.28);
      font-size: 0.88rem;
      font-family: 'Poppins', sans-serif;
      animation: fadeInToast 0.3s ease;
      z-index: 99999;
    `;

    const icon = type === 'error' ? '&#9888;' : '&#9670;';
    const color = type === 'error' ? '#ef4444' : 'var(--warm-gold, #D4A62A)';

    toast.innerHTML = `
      <span style="color:${color}; font-size:1.1rem; line-height:1;">${icon}</span>
      <span>${message}</span>
    `;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-6px)';
      toast.style.transition = 'all 0.35s ease';
      setTimeout(() => toast.remove(), 350);
    }, 3200);
  }
}

window.showAlert = showAlert;
window.showToast = showAlert;

// Currency Formatter Helper (INR)
function formatCurrency(amount) {
  const num = parseFloat(amount) || 0;
  return '₹' + num.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}
window.formatCurrency = formatCurrency;

// ============================================================================
// 2. Navigation & View Controller
// ============================================================================
function navigateTo(viewId) {
  // Update sidebar active link
  document.querySelectorAll('.sidebar-nav .nav-item').forEach(el => {
    el.classList.remove('active');
    if (el.getAttribute('data-view') === viewId || el.id === `nav-${viewId}`) {
      el.classList.add('active');
    }
  });

  // Switch visible page view
  document.querySelectorAll('.page-view').forEach(view => {
    view.classList.remove('active-view');
  });

  const targetView = document.getElementById(`view-${viewId}`);
  if (targetView) {
    targetView.classList.add('active-view');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Update browser URL query parameter without full page reload
  try {
    const url = new URL(window.location);
    url.searchParams.set('view', viewId);
    window.history.replaceState({}, '', url);
  } catch (e) {}

  // Trigger charts if dashboard view is activated
  if (viewId === 'dashboard' && window.kinzChart) {
    setTimeout(() => {
      window.kinzChart.initRevenueChart('revenueChartCanvas');
      window.kinzChart.initDonutChart('donutChartCanvas');
    }, 60);
  }

  // Close mobile sidebar if open
  document.getElementById('sidebar')?.classList.remove('mobile-open');
}

window.navigateTo = navigateTo;
window.switchView = navigateTo;

// ============================================================================
// 3. Modal & Drawer Windows Management
// ============================================================================
function ensureCornerCloseButtons() {
  document.querySelectorAll('.modal-overlay, .drawer-overlay').forEach(overlay => {
    const overlayId = overlay.id;
    if (!overlayId) return;

    let closeBtn = overlay.querySelector('.btn-card-close-outside');
    if (!closeBtn) {
      const card = overlay.querySelector('.modal-box, .drawer-content');
      if (!card) return;

      let wrapper = card.closest('.modal-card-wrapper');
      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.className = 'modal-card-wrapper';
        if (card.style.maxWidth) wrapper.style.maxWidth = card.style.maxWidth;
        card.parentNode.insertBefore(wrapper, card);
        wrapper.appendChild(card);
      }

      closeBtn = document.createElement('button');
      closeBtn.type = 'button';
      closeBtn.className = 'btn-card-close-outside';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.setAttribute('title', 'Close');
      closeBtn.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      `;
      closeBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (overlayId === 'customer-drawer') {
          if (typeof closeDrawer === 'function') closeDrawer('customer-drawer');
          else overlay.classList.remove('active');
        } else {
          if (typeof closeModal === 'function') closeModal(overlayId);
          else overlay.classList.remove('active');
        }
      };
      wrapper.insertBefore(closeBtn, wrapper.firstChild);
    }
  });
}

function openModal(modalId) {
  const el = document.getElementById(modalId);
  if (el) {
    el.classList.add('active');
    ensureCornerCloseButtons();
  }
}

function closeModal(modalId) {
  const el = document.getElementById(modalId);
  if (el) el.classList.remove('active');
}

function openDrawer(drawerId) {
  const el = document.getElementById(drawerId);
  if (el) {
    el.classList.add('active');
    ensureCornerCloseButtons();
  }
}

function closeDrawer(drawerId) {
  const el = document.getElementById(drawerId);
  if (el) el.classList.remove('active');
}

window.ensureCornerCloseButtons = ensureCornerCloseButtons;
window.openModal = openModal;
window.closeModal = closeModal;
window.openDrawer = openDrawer;
window.closeDrawer = closeDrawer;

// Customer Drawer Tab Switcher
function switchCustomerDrawerTab(tabId) {
  document.querySelectorAll('#cust-detail-tabs .cust-tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
  });
  document.querySelectorAll('.cust-tab-pane').forEach(pane => {
    pane.classList.toggle('active', pane.id === `cust-pane-${tabId}`);
  });
}
window.switchCustomerDrawerTab = switchCustomerDrawerTab;

// ============================================================================
// 4. Products Management & Live Image Preview
// ============================================================================
function openAddProductModal() {
  const form = document.getElementById('product-form');
  if (form) form.reset();
  
  const titleEl = document.getElementById('product-modal-title');
  if (titleEl) titleEl.textContent = 'Add New Product';

  const actionInput = document.getElementById('prod-form-action');
  if (actionInput) actionInput.value = 'add';

  const idInput = document.getElementById('prod-form-id');
  if (idInput) idInput.value = '';

  const submitBtn = document.getElementById('prod-modal-submit-btn');
  if (submitBtn) submitBtn.textContent = 'Save Product';

  currentExistingImages = [];
  currentSelectedFiles = [];
  syncProductFileInput();
  renderProductImagesGrid();

  openModal('product-modal');
}

function openEditProductModalManual(productId) {
  const products = (window.DB && window.DB.products) ? window.DB.products : [];
  let p = products.find(prod => String(prod.id) === String(productId) || prod.prod_code === productId || prod.sku === productId);

  if (!p) {
    // Fallback: try finding info directly from table row attributes
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (row) {
      p = {
        id: productId,
        title: row.dataset.title,
        sku: row.dataset.sku,
        category: row.dataset.category,
        base_price: row.dataset.price,
        stock: row.dataset.stock,
        image: row.dataset.image
      };
    }
  }

  if (!p) {
    showAlert('Product record not found', 'error');
    return;
  }

  const titleEl = document.getElementById('product-modal-title');
  if (titleEl) titleEl.textContent = `Edit Product: ${p.title}`;

  const actionInput = document.getElementById('prod-form-action');
  if (actionInput) actionInput.value = 'edit';

  const idInput = document.getElementById('prod-form-id');
  if (idInput) idInput.value = p.id;

  const submitBtn = document.getElementById('prod-modal-submit-btn');
  if (submitBtn) submitBtn.textContent = 'Save Changes';

  // Populate form fields
  if (document.getElementById('prod-form-title')) document.getElementById('prod-form-title').value = p.title || '';
  if (document.getElementById('prod-form-sku')) document.getElementById('prod-form-sku').value = p.sku || '';
  if (p.category) {
    updateCategoryDropdowns(p.category);
    if (document.getElementById('prod-form-category')) {
      document.getElementById('prod-form-category').value = p.category;
    }
  }
  if (document.getElementById('prod-form-metal-type')) document.getElementById('prod-form-metal-type').value = p.metal_type || 'Gold Plated';
  if (document.getElementById('prod-form-guarantee')) document.getElementById('prod-form-guarantee').value = p.guarantee || '1 Year Guarantee';
  if (document.getElementById('prod-form-occasion')) document.getElementById('prod-form-occasion').value = p.occasion || 'Daily Wear';
  if (document.getElementById('prod-form-base-price')) document.getElementById('prod-form-base-price').value = p.base_price || 0;
  if (document.getElementById('prod-form-discount')) document.getElementById('prod-form-discount').value = p.discount_pct || 0;
  if (document.getElementById('prod-form-stock')) document.getElementById('prod-form-stock').value = p.stock || 0;
  if (document.getElementById('prod-form-reorder')) document.getElementById('prod-form-reorder').value = p.reorder_level || 5;
  if (document.getElementById('prod-form-desc')) document.getElementById('prod-form-desc').value = p.description || '';
  if (document.getElementById('prod-form-status')) document.getElementById('prod-form-status').value = p.status || 'Active';

  // Populate interactive plus-button gallery with existing images
  currentSelectedFiles = [];
  currentExistingImages = [];
  if (p.images) {
    if (typeof p.images === 'string') {
      try { currentExistingImages = JSON.parse(p.images); } catch(e) { currentExistingImages = p.images.split(',').map(s=>s.trim()).filter(Boolean); }
    } else if (Array.isArray(p.images)) {
      currentExistingImages = [...p.images];
    }
  }
  if (currentExistingImages.length === 0 && p.image) {
    currentExistingImages = [p.image];
  }

  syncProductFileInput();
  renderProductImagesGrid();

  openModal('product-modal');
}

// ============================================================================
// Interactive Multi-Image Gallery with Plus (+) Button
// ============================================================================
let currentExistingImages = [];
let currentSelectedFiles = [];

function syncProductFileInput() {
  const fileInput = document.getElementById('prod-file-input');
  if (fileInput) {
    try {
      const dt = new DataTransfer();
      currentSelectedFiles.forEach(f => dt.items.add(f));
      fileInput.files = dt.files;
    } catch (e) {
      console.warn('DataTransfer sync note:', e);
    }
  }
  const existingInput = document.getElementById('prod-existing-images-json');
  if (existingInput) {
    existingInput.value = JSON.stringify(currentExistingImages);
  }
}

function triggerProductImageUpload() {
  const picker = document.getElementById('prod-file-picker');
  if (picker) {
    picker.click();
  } else {
    const fileInput = document.getElementById('prod-file-input');
    if (fileInput) fileInput.click();
  }
}
window.triggerProductImageUpload = triggerProductImageUpload;

function removeExistingProductImage(index) {
  if (index >= 0 && index < currentExistingImages.length) {
    currentExistingImages.splice(index, 1);
    syncProductFileInput();
    renderProductImagesGrid();
  }
}
window.removeExistingProductImage = removeExistingProductImage;

function removeSelectedProductFile(index) {
  if (index >= 0 && index < currentSelectedFiles.length) {
    currentSelectedFiles.splice(index, 1);
    syncProductFileInput();
    renderProductImagesGrid();
  }
}
window.removeSelectedProductFile = removeSelectedProductFile;

function clearAllProductImages() {
  currentExistingImages = [];
  currentSelectedFiles = [];
  syncProductFileInput();
  renderProductImagesGrid();
}
window.clearAllProductImages = clearAllProductImages;

function renderProductImagesGrid() {
  const grid = document.getElementById('product-images-grid');
  if (!grid) return;

  grid.innerHTML = '';
  let overallIndex = 0;

  // 1. Render retained existing photos from database
  currentExistingImages.forEach((url, idx) => {
    const isCover = (overallIndex === 0);
    const tile = document.createElement('div');
    tile.className = 'image-preview-tile';
    tile.innerHTML = `
      <img src="${url}" alt="Product Photo ${overallIndex + 1}" onerror="this.onerror=null; this.src='assets/ring.jpg';">
      <button type="button" class="tile-remove-btn" onclick="removeExistingProductImage(${idx})" title="Remove photo">&times;</button>
      ${isCover ? '<div class="tile-cover-badge">Cover Photo</div>' : ''}
    `;
    grid.appendChild(tile);
    overallIndex++;
  });

  // 2. Render newly added files via Plus (+) button
  currentSelectedFiles.forEach((file, idx) => {
    const isCover = (overallIndex === 0);
    const tile = document.createElement('div');
    tile.className = 'image-preview-tile';
    const objUrl = URL.createObjectURL(file);
    tile.innerHTML = `
      <img src="${objUrl}" alt="${file.name}">
      <button type="button" class="tile-remove-btn" onclick="removeSelectedProductFile(${idx})" title="Remove photo">&times;</button>
      ${isCover ? '<div class="tile-cover-badge">Cover Photo</div>' : ''}
    `;
    grid.appendChild(tile);
    overallIndex++;
  });

  // 3. Render Plus (+) Button Tile to add more images
  const plusBtn = document.createElement('button');
  plusBtn.type = 'button';
  plusBtn.className = 'btn-add-image-tile';
  plusBtn.id = 'btn-add-image-tile';
  plusBtn.onclick = triggerProductImageUpload;
  plusBtn.title = 'Add another photo of this product';
  plusBtn.innerHTML = `
    <div class="plus-icon-circle">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </div>
    <span class="plus-tile-label">+ Add Image</span>
    <span class="plus-tile-subtext">${overallIndex === 0 ? 'Click to choose' : 'Add more'}</span>
  `;
  grid.appendChild(plusBtn);

  // Update counter badge & clear button
  const badge = document.getElementById('prod-images-count-badge');
  if (badge) {
    badge.textContent = `${overallIndex} Photo${overallIndex === 1 ? '' : 's'}`;
  }
  const clearBtn = document.getElementById('btn-clear-all-photos');
  if (clearBtn) {
    clearBtn.style.display = overallIndex > 0 ? 'inline-block' : 'none';
  }
}
window.renderProductImagesGrid = renderProductImagesGrid;

// ============================================================================
// Product Image Gallery Lightbox Modal ("Touch product to view all images")
// ============================================================================
// ============================================================================
// Product Showcase & Gallery Card Modal (Matching Reference UI)
// ============================================================================
let activeGalleryImages = [];
let activeGalleryIndex = 0;
let activeGalleryProductId = null;

function openProductGalleryModal(productId, initialIndex = 0) {
  const products = (window.DB && window.DB.products) ? window.DB.products : [];
  const p = products.find(prod => String(prod.id) === String(productId) || prod.prod_code === productId || prod.sku === productId);

  if (!p) {
    showAlert('Product record not found', 'error');
    return;
  }

  activeGalleryProductId = p.id;
  activeGalleryImages = [];
  activeGalleryIndex = Number(initialIndex) || 0;

  // 1. Gather all photos from images column (JSON array or string list)
  if (p.images) {
    if (typeof p.images === 'string') {
      try {
        const parsed = JSON.parse(p.images);
        if (Array.isArray(parsed)) {
          activeGalleryImages = parsed.map(s => String(s).trim()).filter(Boolean);
        }
      } catch (e) {
        activeGalleryImages = p.images.split(',').map(s => s.trim()).filter(Boolean);
      }
    } else if (Array.isArray(p.images)) {
      activeGalleryImages = p.images.map(s => String(s).trim()).filter(Boolean);
    }
  }

  // 2. Fallback to image column if activeGalleryImages is empty
  if (activeGalleryImages.length === 0 && p.image) {
    const trimmed = String(p.image).trim();
    if (trimmed) activeGalleryImages.push(trimmed);
  }

  // Populate Top Header Info
  const titleEl = document.getElementById('gallery-product-title');
  if (titleEl) titleEl.textContent = p.title || 'Product Showcase';
  
  const skuEl = document.getElementById('gallery-product-sku');
  if (skuEl) skuEl.textContent = p.sku ? p.sku : 'KJ-PROD';

  const metalTypeEl = document.getElementById('gallery-product-metal-type');
  if (metalTypeEl) metalTypeEl.textContent = p.metal_type || 'Gold Plated';

  const guaranteeEl = document.getElementById('gallery-product-guarantee');
  if (guaranteeEl) guaranteeEl.textContent = p.guarantee || '1 Year Guarantee';

  // Populate Right Column Details
  const priceEl = document.getElementById('gallery-price-val');
  if (priceEl) priceEl.textContent = `₹${Number(p.base_price || 0).toLocaleString('en-IN')}`;

  const discountNote = document.getElementById('gallery-discount-note');
  if (discountNote) {
    const disc = Number(p.discount_pct || 0);
    discountNote.innerHTML = (disc > 0 ? `${disc}% Discount Applied &bull; ` : '') + 'Incl. Assurance Certification &amp; GST';
  }

  const specCat = document.getElementById('gallery-spec-category');
  if (specCat) specCat.textContent = p.category || 'Jewellery';

  const specMetalType = document.getElementById('gallery-spec-metal-type');
  if (specMetalType) specMetalType.textContent = p.metal_type || 'Gold Plated';

  const specGuarantee = document.getElementById('gallery-spec-guarantee');
  if (specGuarantee) specGuarantee.textContent = p.guarantee || '1 Year Guarantee';

  const specOccasion = document.getElementById('gallery-spec-occasion');
  if (specOccasion) specOccasion.textContent = p.occasion || 'Daily Wear';

  const specStock = document.getElementById('gallery-spec-stock');
  if (specStock) {
    const stock = Number(p.stock || 0);
    specStock.textContent = `${stock} Units Available`;
    if (stock <= 0) {
      specStock.className = 'status-pill status-out-of-stock';
      specStock.textContent = 'Out of Stock';
    } else {
      specStock.className = 'luxury-stock-pill';
    }
  }

  const specDesc = document.getElementById('gallery-spec-desc');
  if (specDesc) {
    specDesc.textContent = p.description || 'Anti-tarnish, skin-friendly guaranteed jewellery piece crafted with high-polish micro gold plating.';
  }

  // Render Left Column Gallery
  renderGalleryStage();
  openModal('product-gallery-modal');
}
window.openProductGalleryModal = openProductGalleryModal;

function renderGalleryStage() {
  const mainCard = document.getElementById('gallery-main-card');
  const emptyCard = document.getElementById('gallery-empty-card');
  const mainImg = document.getElementById('gallery-main-img');
  const counterBadge = document.getElementById('gallery-photo-counter-badge');
  const anglesCount = document.getElementById('gallery-angles-count');
  const prevBtn = document.getElementById('gallery-btn-prev');
  const nextBtn = document.getElementById('gallery-btn-next');
  const strip = document.getElementById('gallery-angles-strip');
  const btnSetCover = document.getElementById('btn-set-cover-photo');
  const btnDeletePhoto = document.getElementById('btn-delete-active-photo');

  const totalPhotos = activeGalleryImages.length;
  if (anglesCount) anglesCount.textContent = totalPhotos;

  if (totalPhotos === 0) {
    if (mainCard) mainCard.style.display = 'none';
    if (emptyCard) emptyCard.style.display = 'flex';
    if (counterBadge) counterBadge.textContent = '0 Photos';
    if (strip) strip.innerHTML = '<span style="font-size:0.75rem; color:var(--color-text-muted); padding:6px 0;">No angles uploaded yet.</span>';
    if (btnSetCover) btnSetCover.style.display = 'none';
    if (btnDeletePhoto) btnDeletePhoto.style.display = 'none';
    return;
  }

  if (emptyCard) emptyCard.style.display = 'none';
  if (mainCard) mainCard.style.display = 'flex';
  if (btnSetCover) btnSetCover.style.display = 'inline-flex';
  if (btnDeletePhoto) btnDeletePhoto.style.display = 'inline-flex';

  // Ensure index in bounds
  if (activeGalleryIndex < 0) activeGalleryIndex = 0;
  if (activeGalleryIndex >= totalPhotos) activeGalleryIndex = totalPhotos - 1;

  const currentSrc = activeGalleryImages[activeGalleryIndex];
  if (mainImg) {
    mainImg.src = currentSrc;
    mainImg.alt = `Product Angle ${activeGalleryIndex + 1}`;
  }

  if (counterBadge) {
    counterBadge.textContent = `Photo ${activeGalleryIndex + 1} of ${totalPhotos}`;
  }

  if (prevBtn) prevBtn.style.visibility = (totalPhotos > 1) ? 'visible' : 'hidden';
  if (nextBtn) nextBtn.style.visibility = (totalPhotos > 1) ? 'visible' : 'hidden';

  // Disable "Set as Cover" if current photo is already cover (index 0)
  if (btnSetCover) {
    if (activeGalleryIndex === 0) {
      btnSetCover.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Current Cover`;
      btnSetCover.style.opacity = '0.7';
      btnSetCover.disabled = true;
    } else {
      btnSetCover.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Set as Cover Photo`;
      btnSetCover.style.opacity = '1';
      btnSetCover.disabled = false;
    }
  }

  // Render Thumbnails Strip
  if (strip) {
    strip.innerHTML = '';
    activeGalleryImages.forEach((imgUrl, idx) => {
      const thumb = document.createElement('div');
      thumb.className = `luxury-angle-thumb-item ${idx === activeGalleryIndex ? 'active' : ''}`;
      thumb.onclick = () => {
        activeGalleryIndex = idx;
        renderGalleryStage();
      };
      thumb.innerHTML = `
        <img src="${imgUrl}" alt="Photo ${idx + 1}" onerror="this.onerror=null; this.src='assets/ring.jpg';">
        ${idx === 0 ? '<span class="luxury-thumb-cover-badge">COVER</span>' : ''}
        <span class="luxury-thumb-num-badge">${idx + 1}</span>
      `;
      strip.appendChild(thumb);
    });
  }
}

function galleryPrevImage() {
  if (activeGalleryImages.length <= 1) return;
  activeGalleryIndex = (activeGalleryIndex - 1 + activeGalleryImages.length) % activeGalleryImages.length;
  renderGalleryStage();
}
window.galleryPrevImage = galleryPrevImage;

function galleryNextImage() {
  if (activeGalleryImages.length <= 1) return;
  activeGalleryIndex = (activeGalleryIndex + 1) % activeGalleryImages.length;
  renderGalleryStage();
}
window.galleryNextImage = galleryNextImage;

function gallerySetCoverPhoto() {
  if (!activeGalleryProductId || activeGalleryIndex === 0) return;
  const formData = new FormData();
  formData.append('action', 'set_cover');
  formData.append('id', activeGalleryProductId);
  formData.append('index', activeGalleryIndex);

  fetch('actions/product_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res && res.success) {
        activeGalleryImages = res.images;
        activeGalleryIndex = 0;
        // Update local DB cache
        if (window.DB && window.DB.products) {
          const p = window.DB.products.find(prod => String(prod.id) === String(activeGalleryProductId));
          if (p) {
            p.image = res.image;
            p.images = JSON.stringify(res.images);
          }
        }
        showAlert('Cover photo updated successfully!', 'success');
        renderGalleryStage();
        // Update table row thumbnail if visible
        const row = document.querySelector(`tr[data-product-id="${activeGalleryProductId}"]`);
        if (row) {
          const rowImg = row.querySelector('.jewel-thumbnail');
          if (rowImg && rowImg.tagName === 'IMG') rowImg.src = res.image;
        }
      } else {
        showAlert(res.error || 'Could not update cover photo', 'error');
      }
    })
    .catch(() => showAlert('Failed to set cover photo', 'error'));
}
window.gallerySetCoverPhoto = gallerySetCoverPhoto;

function galleryDeleteActivePhoto() {
  if (!activeGalleryProductId || activeGalleryImages.length === 0) return;
  if (!confirm('Are you sure you want to remove this photo?')) return;

  const formData = new FormData();
  formData.append('action', 'delete_single_image');
  formData.append('id', activeGalleryProductId);
  formData.append('index', activeGalleryIndex);

  fetch('actions/product_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res && res.success) {
        activeGalleryImages = res.images;
        if (activeGalleryIndex >= activeGalleryImages.length) {
          activeGalleryIndex = Math.max(0, activeGalleryImages.length - 1);
        }
        // Update local DB cache
        if (window.DB && window.DB.products) {
          const p = window.DB.products.find(prod => String(prod.id) === String(activeGalleryProductId));
          if (p) {
            p.image = res.image;
            p.images = JSON.stringify(res.images);
          }
        }
        showAlert('Photo removed from product.', 'info');
        renderGalleryStage();
        const row = document.querySelector(`tr[data-product-id="${activeGalleryProductId}"]`);
        if (row) {
          const rowImg = row.querySelector('.jewel-thumbnail');
          if (rowImg && rowImg.tagName === 'IMG') {
            if (res.image) rowImg.src = res.image;
          }
        }
      } else {
        showAlert(res.error || 'Could not delete photo', 'error');
      }
    })
    .catch(() => showAlert('Failed to delete photo', 'error'));
}
window.galleryDeleteActivePhoto = galleryDeleteActivePhoto;

function galleryTriggerMoreUpload() {
  const input = document.getElementById('gallery-direct-file-input');
  if (input) input.click();
}
window.galleryTriggerMoreUpload = galleryTriggerMoreUpload;

function galleryHandleDirectUpload(input) {
  if (!input.files || input.files.length === 0 || !activeGalleryProductId) return;
  const formData = new FormData();
  formData.append('action', 'upload_more_images');
  formData.append('id', activeGalleryProductId);
  Array.from(input.files).forEach(f => formData.append('more_images[]', f));

  showAlert('Uploading photos...', 'info');

  fetch('actions/product_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      input.value = '';
      if (res && res.success) {
        activeGalleryImages = res.images;
        activeGalleryIndex = activeGalleryImages.length - 1; // switch to newly uploaded photo
        if (window.DB && window.DB.products) {
          const p = window.DB.products.find(prod => String(prod.id) === String(activeGalleryProductId));
          if (p) {
            p.image = res.image;
            p.images = JSON.stringify(res.images);
          }
        }
        showAlert('New photo(s) added successfully!', 'success');
        renderGalleryStage();
      } else {
        showAlert(res.error || 'Upload failed', 'error');
      }
    })
    .catch(() => {
      input.value = '';
      showAlert('Failed to upload photos', 'error');
    });
}
window.galleryHandleDirectUpload = galleryHandleDirectUpload;

function galleryEditCurrentProduct() {
  closeModal('product-gallery-modal');
  if (activeGalleryProductId) {
    openEditProductModalManual(activeGalleryProductId);
  }
}
window.galleryEditCurrentProduct = galleryEditCurrentProduct;

function galleryStockCurrentProduct() {
  closeModal('product-gallery-modal');
  if (activeGalleryProductId) {
    const products = (window.DB && window.DB.products) ? window.DB.products : [];
    const p = products.find(prod => String(prod.id) === String(activeGalleryProductId));
    if (p) {
      openStockAdjustModal(p.sku);
    }
  }
}
window.galleryStockCurrentProduct = galleryStockCurrentProduct;

// Helper to keep category dropdowns updated across application
function updateCategoryDropdowns(newCategoryName) {
  if (!newCategoryName || typeof newCategoryName !== 'string') return;
  const trimmed = newCategoryName.trim();
  if (!trimmed) return;

  const dropdownIds = [
    'prod-form-category',
    'filter-product-category',
    'filter-inventory-category',
    'coupon-form-applicable'
  ];

  dropdownIds.forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const exists = Array.from(sel.options).some(opt => opt.value.toLowerCase() === trimmed.toLowerCase());
    if (!exists) {
      const opt = document.createElement('option');
      opt.value = trimmed;
      opt.textContent = trimmed;
      sel.appendChild(opt);
    }
  });

  if (window.DB && Array.isArray(window.DB.categories)) {
    const existsInDB = window.DB.categories.some(c => (c.name || '').toLowerCase() === trimmed.toLowerCase());
    if (!existsInDB) {
      window.DB.categories.push({
        name: trimmed,
        slug: trimmed.toLowerCase().replace(/[^a-z0-9-]+/g, '-'),
        status: 'Active'
      });
    }
  }
}
window.updateCategoryDropdowns = updateCategoryDropdowns;

// Real-Time Table Search & Multi-Filter
function renderProductsTable() {
  const search = (document.getElementById('filter-product-search')?.value || '').toLowerCase().trim();
  const cat = (document.getElementById('filter-product-category')?.value || '').trim();
  const stock = (document.getElementById('filter-product-stock')?.value || '').trim();
  const status = (document.getElementById('filter-product-status')?.value || '').trim();

  const tbody = document.getElementById('products-table-tbody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const matchesSearch = !search || text.includes(search);
    
    // Category match
    const categoryCell = row.cells[1]?.textContent.trim() || '';
    const matchesCat = !cat || categoryCell === cat;

    // Stock match
    const stockCell = row.cells[4]?.textContent.toLowerCase() || '';
    let matchesStock = true;
    if (stock === 'out') matchesStock = stockCell.includes('out of stock') || stockCell.includes('(0)');
    else if (stock === 'low') matchesStock = stockCell.includes('low') || stockCell.includes('reorder');
    else if (stock === 'in') matchesStock = !stockCell.includes('out of stock') && !stockCell.includes('low');

    // Status match
    const statusCell = row.cells[5]?.textContent.trim() || '';
    const matchesStatus = !status || statusCell.toLowerCase().includes(status.toLowerCase());

    row.style.display = (matchesSearch && matchesCat && matchesStock && matchesStatus) ? '' : 'none';
  });
}

window.openAddProductModal = openAddProductModal;
window.openEditProductModalManual = openEditProductModalManual;
window.openEditProductModal = openEditProductModalManual;
window.renderProductsTable = renderProductsTable;

// ============================================================================
// 5. Inventory Management & Stock Adjustment
// ============================================================================
function openStockAdjustModal(sku) {
  const products = (window.DB && window.DB.products) ? window.DB.products : [];
  const p = products.find(prod => prod.sku === sku || String(prod.id) === String(sku));

  const titleEl = document.getElementById('stock-adjust-title');
  if (titleEl) titleEl.textContent = `Stock Adjustment: ${p ? p.title : sku}`;

  const currentValEl = document.getElementById('stock-current-val');
  if (currentValEl) currentValEl.textContent = `${p ? p.stock : 0} Units`;

  const skuHidden = document.getElementById('stock-modal-sku');
  if (skuHidden) skuHidden.value = sku;

  const idHidden = document.getElementById('stock-modal-id');
  if (idHidden) idHidden.value = p ? p.id : '';

  openModal('stock-modal');
}

function handleSaveStockAdjustment() {
  const form = document.getElementById('stock-adjust-form');
  if (form) form.submit();
}

function renderInventoryTable() {
  const search = (document.getElementById('filter-inventory-search')?.value || '').toLowerCase().trim();
  const cat = (document.getElementById('filter-inventory-category')?.value || '').trim();
  const stock = (document.getElementById('filter-inventory-stock')?.value || '').trim();

  const tbody = document.getElementById('inventory-table-tbody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const matchesSearch = !search || text.includes(search);
    
    const categoryCell = row.cells[1]?.textContent.trim() || '';
    const matchesCat = !cat || categoryCell === cat;

    const statusCell = row.cells[4]?.textContent.toLowerCase() || '';
    let matchesStock = true;
    if (stock === 'outofstock') matchesStock = statusCell.includes('out of stock');
    else if (stock === 'lowstock') matchesStock = statusCell.includes('low');
    else if (stock === 'instock') matchesStock = statusCell.includes('optimal') || statusCell.includes('in stock');

    row.style.display = (matchesSearch && matchesCat && matchesStock) ? '' : 'none';
  });
}

window.openStockAdjustModal = openStockAdjustModal;
window.handleSaveStockAdjustment = handleSaveStockAdjustment;
window.renderInventoryTable = renderInventoryTable;

// ============================================================================
// 6. Orders Management & Order Details Drawer
// ============================================================================
let currentDrawerOrder = null;

function orderHtmlEscape(value) {
  return String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[character]);
}

function openOrderDrawerManual(orderCode) {
  const orders = (window.DB && window.DB.orders) ? window.DB.orders : [];
  const order = orders.find(item => String(item.order_code) === String(orderCode) || String(item.id) === String(orderCode));

  if (!order) {
    showAlert(`Order ${orderCode} not found in database`, 'error');
    return;
  }

  currentDrawerOrder = order;
  document.getElementById('drawer-order-id').textContent = `Order Details: ${order.order_code || ''}`;
  document.getElementById('drawer-order-db-id').value = order.id || '';

  const statusSelect = document.getElementById('drawer-order-status');
  if (statusSelect) statusSelect.value = order.status || 'Pending';

  const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) element.textContent = value == null || value === '' ? '—' : String(value);
  };

  setText('drawer-customer-name', order.customer_name || 'Customer');
  setText('drawer-customer-email', order.customer_email || '—');
  setText('drawer-customer-phone', order.customer_phone || '—');
  setText('drawer-customer-address', order.shipping_address || '—');
  setText('drawer-packaging', order.packaging || 'KINZ Jewellery Gift Packaging');
  setText('drawer-payment-method', order.payment_method || 'Not specified');
  setText('drawer-payment-status', order.payment_status || 'Unpaid');
  setText('drawer-razorpay-payment-id', order.razorpay_payment_id);
  setText('drawer-paid-at', order.paid_at);
  setText('drawer-razorpay-refund-id', order.razorpay_refund_id);

  const trackingInput = document.getElementById('drawer-tracking-input');
  if (trackingInput) trackingInput.value = order.tracking_number || '';

  let items = [];
  if (order.items_json) {
    try {
      const parsed = typeof order.items_json === 'string' ? JSON.parse(order.items_json) : order.items_json;
      if (Array.isArray(parsed)) items = parsed;
    } catch (error) {
      items = [];
    }
  }
  if (!items.length && Array.isArray(order.line_items)) items = order.line_items;

  const itemsContainer = document.getElementById('drawer-items-list');
  if (itemsContainer) {
    if (items.length) {
      itemsContainer.innerHTML = items.map(item => {
        const title = item.title || item.product_title || item.name || 'Jewellery Piece';
        const sku = item.sku || '';
        const quantity = Number(item.quantity ?? item.qty ?? 1) || 1;
        const unitPrice = Number(item.unit_price ?? item.price ?? 0) || 0;
        const lineTotal = Number(item.line_total ?? item.price ?? (unitPrice * quantity)) || 0;
        return `<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:11px 0;border-bottom:1px dashed var(--border-beige);">
          <div style="min-width:0;">
            <div style="font-weight:600;color:var(--deep-charcoal-green);">${orderHtmlEscape(title)}</div>
            <div style="font-size:0.75rem;color:var(--color-text-muted);font-family:monospace;">${sku ? orderHtmlEscape(sku) + ' &bull; ' : ''}Qty: ${quantity} × ${formatCurrency(unitPrice)}</div>
          </div>
          <div style="font-weight:700;color:var(--forest-green);white-space:nowrap;">${formatCurrency(lineTotal)}</div>
        </div>`;
      }).join('');
    } else {
      itemsContainer.innerHTML = `<div style="padding:12px 0;color:var(--color-text-muted);">${Number(order.items_count) || 0} item(s). Detailed item lines are not available for this order.</div>`;
    }
  }

  const numberOrZero = value => Number.isFinite(Number(value)) ? Number(value) : 0;
  const total = numberOrZero(order.total_amount);
  const subtotal = order.subtotal !== undefined && order.subtotal !== null ? numberOrZero(order.subtotal) : total;
  const discount = numberOrZero(order.discount_amount);
  const tax = numberOrZero(order.tax_amount ?? order.tax);
  const shipping = numberOrZero(order.shipping_amount);
  setText('drawer-subtotal', formatCurrency(subtotal));
  setText('drawer-discount', discount > 0 ? `−${formatCurrency(discount)}` : formatCurrency(0));
  setText('drawer-coupon', order.coupon_code || '—');
  setText('drawer-tax', formatCurrency(tax));
  setText('drawer-shipping', formatCurrency(shipping));
  setText('drawer-total', formatCurrency(total));

  openModal('order-drawer');
}

function renderOrdersTable() {
  const search = (document.getElementById('filter-order-search')?.value || '').toLowerCase().trim();
  const status = (document.getElementById('filter-order-status')?.value || '').trim().toLowerCase();
  const paymentFilter = (document.getElementById('filter-order-payment')?.value || '').trim().toLowerCase();
  const dateFilter = (document.getElementById('filter-order-date')?.value || '').trim();
  const sortDirection = document.getElementById('filter-order-date-sort')?.value || 'desc';
  const tbody = document.getElementById('orders-table-tbody');
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll('tr'));
  rows.sort((a, b) => {
    const aDate = new Date((a.cells[2]?.textContent || '').trim().replace(' ', 'T')).getTime() || 0;
    const bDate = new Date((b.cells[2]?.textContent || '').trim().replace(' ', 'T')).getTime() || 0;
    return sortDirection === 'asc' ? aDate - bDate : bDate - aDate;
  });

  rows.forEach(row => {
    const rowText = row.textContent.toLowerCase();
    const rowStatus = (row.cells[5]?.textContent || '').trim().toLowerCase();
    const rowDate = (row.cells[2]?.textContent || '').trim().slice(0, 10);
    const matchesSearch = !search || rowText.includes(search);
    const matchesStatus = !status || rowStatus === status;
    const rowPayment = (row.cells[6]?.textContent || '').trim().toLowerCase();
    const matchesPayment = !paymentFilter || rowPayment === paymentFilter;
    const matchesDate = !dateFilter || rowDate === dateFilter;
    row.style.display = matchesSearch && matchesStatus && matchesPayment && matchesDate ? '' : 'none';
    tbody.appendChild(row);
  });
}

function clearOrderDateFilter() {
  const input = document.getElementById('filter-order-date');
  if (input) input.value = '';
  renderOrdersTable();
}
window.clearOrderDateFilter = clearOrderDateFilter;
window.renderOrdersTable = renderOrdersTable;

function printOrderInvoice() {
  if (!currentDrawerOrder) {
    window.print();
    return;
  }

  const o = currentDrawerOrder;
  const printableContainer = document.getElementById('printable-invoice-container');
  if (printableContainer) {
    printableContainer.innerHTML = `
      <div style="padding:40px; font-family:'Poppins', sans-serif; color:#18352D; background:#fff; max-width:750px; margin:0 auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #C87927; padding-bottom:16px; margin-bottom:24px;">
          <div>
            <h1 style="margin:0; font-family:'Cinzel Decorative', serif; font-size:24px; color:#18352D;">KINZ JEWELLERY</h1>
            <p style="margin:4px 0 0; font-size:12px; color:#6B7280;">A Hidden Treasure &bull; Anti-Tarnish Gold Plated Jewellery</p>
          </div>
          <div style="text-align:right;">
            <h2 style="margin:0; font-size:18px; color:#C87927;">TAX INVOICE</h2>
            <div style="font-size:12px; font-family:monospace;">${o.order_code}</div>
            <div style="font-size:12px; color:#6B7280;">Date: ${o.order_date}</div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; font-size:13px; line-height:1.6;">
          <div>
            <strong>BILLED TO:</strong><br>
            ${o.customer_name}<br>
            ${o.customer_email}<br>
            ${o.customer_phone || ''}<br>
            ${o.shipping_address || ''}
          </div>
          <div style="text-align:right;">
            <strong>STORE DETAILS:</strong><br>
            Kinz Guarantee Jewellery Store<br>
            support@kinzjewellery.com<br>
            GST Rate: 5.0% Standard Luxury<br>
            Payment: ${o.payment_method || 'UPI Direct'}
          </div>
        </div>

        <table style="width:100%; border-collapse:collapse; margin-bottom:24px; font-size:13px;">
          <thead>
            <tr style="background:#F4E2C5; border-bottom:1px solid #C87927;">
              <th style="padding:10px; text-align:left;">Item Description</th>
              <th style="padding:10px; text-align:center;">Qty</th>
              <th style="padding:10px; text-align:right;">Total Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:12px 10px; border-bottom:1px solid #E5E7EB;">
                <strong>KINZ Jewellery Order</strong><br>
                <span style="font-size:11px; color:#6B7280;">Includes 1-Year Polish & Color Guarantee Certificate</span>
              </td>
              <td style="padding:12px 10px; text-align:center; border-bottom:1px solid #E5E7EB;">${o.items_count || 1}</td>
              <td style="padding:12px 10px; text-align:right; font-weight:bold; border-bottom:1px solid #E5E7EB;">${formatCurrency(o.total_amount)}</td>
            </tr>
          </tbody>
        </table>

        <div style="display:flex; justify-content:flex-end; font-size:14px; margin-bottom:30px;">
          <div style="width:240px; line-height:2;">
            <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span> <span>${formatCurrency(o.subtotal || (o.total_amount / 1.05))}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>GST (5%):</span> <span>${formatCurrency(o.tax || (o.total_amount - (o.total_amount / 1.05)))}</span></div>
            <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:16px; border-top:2px solid #C87927; padding-top:6px; color:#18352D;">
              <span>Grand Total:</span> <span>${formatCurrency(o.total_amount)}</span>
            </div>
          </div>
        </div>

        <div style="text-align:center; font-size:11px; color:#6B7280; border-top:1px dashed #C87927; padding-top:16px;">
          Thank you for trusting Kinz Guarantee Jewellery &bull; Authenticity & Polish Guaranteed
        </div>
      </div>
    `;
  }

  window.print();
}

window.openOrderDrawerManual = openOrderDrawerManual;
window.openOrderDrawer = openOrderDrawerManual;
window.renderOrdersTable = renderOrdersTable;
window.printOrderInvoice = printOrderInvoice;

// ============================================================================
// 7. Customers Management & Customer Drawer
// ============================================================================
function openCustomerDrawerManual(custCode) {
  const customers = (window.DB && window.DB.customers) ? window.DB.customers : [];
  const c = customers.find(cust => cust.cust_code === custCode || String(cust.id) === String(custCode) || cust.email === custCode);

  if (!c) {
    showAlert(`Customer record #${custCode} not found`, 'error');
    return;
  }

  // Profile Identity
  const initials = (c.name || 'C').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
  if (document.getElementById('cust-drawer-avatar')) document.getElementById('cust-drawer-avatar').textContent = initials;
  if (document.getElementById('cust-drawer-name')) document.getElementById('cust-drawer-name').textContent = c.name;
  if (document.getElementById('cust-drawer-header-title')) document.getElementById('cust-drawer-header-title').textContent = c.name;
  if (document.getElementById('cust-drawer-header-subtitle')) document.getElementById('cust-drawer-header-subtitle').textContent = `${c.tier} &bull; ${c.status}`;

  // Overview Tab Fields
  if (document.getElementById('cust-reg-full-date')) document.getElementById('cust-reg-full-date').textContent = c.registered_date || '2025-01-15';
  if (document.getElementById('cust-reg-code')) document.getElementById('cust-reg-code').textContent = c.reg_code || c.cust_code || `KINZ-REG-${c.id}`;
  if (document.getElementById('cust-drawer-email')) document.getElementById('cust-drawer-email').textContent = c.email || '—';
  if (document.getElementById('cust-drawer-phone')) document.getElementById('cust-drawer-phone').textContent = c.phone || '—';
  if (document.getElementById('cust-drawer-reg-date')) document.getElementById('cust-drawer-reg-date').textContent = c.registered_date || '—';
  if (document.getElementById('cust-drawer-address')) document.getElementById('cust-drawer-address').textContent = c.primary_address || 'Registered Address';

  // Block/Unblock Button
  const blockBtn = document.getElementById('cust-drawer-block-btn');
  if (blockBtn) {
    blockBtn.href = `actions/customer_action.php?action=toggle_block&id=${c.id}`;
    if (c.is_blocked && c.is_blocked != '0') {
      blockBtn.textContent = 'Unblock Customer';
      blockBtn.style.color = 'var(--forest-green)';
      blockBtn.style.borderColor = 'var(--forest-green)';
    } else {
      blockBtn.textContent = 'Block Customer';
      blockBtn.style.color = 'var(--status-danger)';
      blockBtn.style.borderColor = 'var(--status-danger)';
    }
  }

  // Order History Tab Population
  const orders = (window.DB && window.DB.orders) ? window.DB.orders : [];
  const custOrders = orders.filter(o => o.customer_email === c.email || o.customer_name === c.name);
  
  const badgeEl = document.getElementById('cust-tab-orders-badge');
  if (badgeEl) badgeEl.textContent = custOrders.length;

  const countPill = document.getElementById('cust-order-count-pill');
  if (countPill) countPill.textContent = `${custOrders.length} Orders`;

  const ordersListEl = document.getElementById('cust-drawer-orders-list');
  if (ordersListEl) {
    if (custOrders.length > 0) {
      ordersListEl.innerHTML = custOrders.map(o => `
        <div style="background:var(--warm-ivory); border:1px solid var(--border-beige); border-radius:8px; padding:12px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-weight:700; color:var(--deep-charcoal-green); font-size:0.92rem;">${o.order_code}</div>
            <div style="font-size:0.75rem; color:var(--color-text-muted); margin-top:2px;">${o.order_date} &bull; ${o.items_count} item(s)</div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700; color:var(--forest-green); font-size:0.95rem;">${formatCurrency(o.total_amount)}</div>
            <span class="status-pill status-${(o.status || '').toLowerCase().replace(/\s+/g, '')}" style="font-size:0.68rem; padding:2px 6px;">${o.status}</span>
          </div>
        </div>
      `).join('');
    } else {
      ordersListEl.innerHTML = '<div style="text-align:center; padding:24px; color:var(--color-text-muted); font-size:0.84rem;">No purchase history found for this account.</div>';
    }
  }

  // Address Tab Population
  const addrListEl = document.getElementById('cust-drawer-addresses-list');
  if (addrListEl) {
    addrListEl.innerHTML = `
      <div style="background:var(--warm-ivory); border:1px solid var(--border-beige); border-radius:8px; padding:14px; line-height:1.6; font-size:0.84rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
          <strong style="color:var(--forest-green);">Primary Delivery Address</strong>
          <span class="status-pill status-optimal" style="font-size:0.68rem;">Default</span>
        </div>
        <div>${c.primary_address || 'No address specified'}</div>
        <div style="font-size:0.78rem; color:var(--color-text-muted); margin-top:4px;">Recipient: ${c.name} &bull; ${c.phone || ''}</div>
      </div>
    `;
  }

  switchCustomerDrawerTab('overview');
  openDrawer('customer-drawer');
}

function handleCustomerSearch(query) {
  const search = (query || '').toLowerCase().trim();
  const tbody = document.getElementById('customers-table-tbody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = (!search || text.includes(search)) ? '' : 'none';
  });
}

function handleCustomerFilterChange() {
  const status = (document.getElementById('cust-filter-status')?.value || '').toLowerCase().trim();
  const delivery = (document.getElementById('cust-filter-delivery')?.value || '').toLowerCase().trim();
  const search = (document.getElementById('cust-search-input')?.value || '').toLowerCase().trim();

  const tbody = document.getElementById('customers-table-tbody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const matchesSearch = !search || text.includes(search);
    const matchesStatus = !status || text.includes(status);
    const matchesDelivery = !delivery || text.includes(delivery);

    row.style.display = (matchesSearch && matchesStatus && matchesDelivery) ? '' : 'none';
  });
}

function resetCustomerFilters() {
  const input = document.getElementById('cust-search-input');
  if (input) input.value = '';
  const sFilter = document.getElementById('cust-filter-status');
  if (sFilter) sFilter.value = '';
  const dFilter = document.getElementById('cust-filter-delivery');
  if (dFilter) dFilter.value = '';

  handleCustomerFilterChange();
}

window.openCustomerDrawerManual = openCustomerDrawerManual;
window.openCustomerDrawer = openCustomerDrawerManual;
window.handleCustomerSearch = handleCustomerSearch;
window.handleCustomerFilterChange = handleCustomerFilterChange;
window.resetCustomerFilters = resetCustomerFilters;

// ============================================================================
// 8. CSV Exporters & Direct Backend File Downloads
// ============================================================================
function exportOrdersCSV() {
  showAlert('Downloading Orders CSV...', 'info');
  window.location.href = 'actions/export_csv.php?type=orders';
}

function exportProductsCSV() {
  showAlert('Downloading Products Catalog CSV...', 'info');
  window.location.href = 'actions/export_csv.php?type=products';
}

function exportCustomersCSV() {
  showAlert('Downloading Customer Directory CSV...', 'info');
  window.location.href = 'actions/export_csv.php?type=customers';
}

function exportReportPDF() {
  window.print();
}

function printReport() {
  window.print();
}

window.exportOrdersCSV = exportOrdersCSV;
window.exportProductsCSV = exportProductsCSV;
window.exportCustomersCSV = exportCustomersCSV;
window.exportReportPDF = exportReportPDF;
window.printReport = printReport;

// ============================================================================
// 9. Modals Shortcuts (Categories, Admin, Coupons)
// ============================================================================
function resetCategoryForm() {
  const form = document.getElementById('category-form');
  if (form) form.reset();
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
  set('cat-form-action', 'add');
  set('cat-form-id', '');
  const preview = document.getElementById('cat-upload-preview');
  if (preview) preview.innerHTML = '';
  const title = document.getElementById('cat-modal-title');
  if (title) title.textContent = 'Add Category';
  const hint = document.getElementById('cat-image-hint');
  if (hint) hint.textContent = '';
}

function openAddCategoryModal() {
  resetCategoryForm();
  openModal('category-modal');
}

function openEditCategoryModal(id) {
  const cats = (window.DB && Array.isArray(window.DB.categories)) ? window.DB.categories : [];
  const cat = cats.find(c => Number(c.id) === Number(id));
  if (!cat) return;
  resetCategoryForm();
  const set = (elId, v) => { const el = document.getElementById(elId); if (el) el.value = v || ''; };
  set('cat-form-action', 'update');
  set('cat-form-id', cat.id);
  set('cat-form-name', cat.name);
  set('cat-form-seo-title', cat.seo_title);
  set('cat-form-seo-desc', cat.seo_description);
  const title = document.getElementById('cat-modal-title');
  if (title) title.textContent = 'Edit Category';
  const hint = document.getElementById('cat-image-hint');
  if (hint) hint.textContent = '(leave empty to keep the current image)';
  const preview = document.getElementById('cat-upload-preview');
  if (preview && cat.image) {
    preview.innerHTML = '<div style="display:flex; align-items:center; gap:10px; padding:10px; background:#f8faf9; border:1.5px solid var(--border-light, #ddd); border-radius:6px; margin-top:8px;">' +
      '<img src="' + String(cat.image).replace(/"/g, '&quot;') + '" alt="Current image" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">' +
      '<span style="font-size:0.8rem; font-weight:600;">Current image</span></div>';
  }
  openModal('category-modal');
}

function openAddCustomerModal() {
  openModal('customer-modal');
}

function openAddAdminModal() {
  openModal('admin-add-modal');
}

function openAddCouponModal() {
  openModal('coupon-modal');
}

window.openAddCategoryModal = openAddCategoryModal;
window.openEditCategoryModal = openEditCategoryModal;
window.openAddCustomerModal = openAddCustomerModal;
window.openAddAdminModal = openAddAdminModal;
window.openAddCouponModal = openAddCouponModal;

// ============================================================================
// 10. Reports Tab Switcher & Presets
// ============================================================================
function switchReportTab(tabKey) {
  const paneId = `pane-report-${tabKey}`;
  document.querySelectorAll('.report-pane').forEach(p => p.classList.remove('active'));
  const activePane = document.getElementById(paneId);
  if (activePane) activePane.classList.add('active');

  const select = document.getElementById('report-section-select');
  if (select && select.value !== tabKey) select.value = tabKey;
}

window.switchReportTab = switchReportTab;

// ============================================================================
// 11. Page Initialization & Live Event Binding
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
  // 1. Sidebar Nav Item Click Binding
  document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const viewId = item.getAttribute('data-view');
      if (viewId) {
        navigateTo(viewId);
      }
    });
  });

  // 2. Mobile Sidebar Toggle & Close
  const toggleBtn = document.getElementById('sidebar-toggle');
  const closeBtn = document.getElementById('sidebar-close-btn');
  const backdrop = document.getElementById('sidebar-backdrop');
  const mobileFloating = document.getElementById('mobile-floating-menu');
  const sidebar = document.getElementById('sidebar');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
  }
  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', () => sidebar.classList.remove('mobile-open'));
  }
  if (backdrop && sidebar) {
    backdrop.addEventListener('click', () => sidebar.classList.remove('mobile-open'));
  }
  if (mobileFloating && sidebar) {
    mobileFloating.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
  }

  // 3. Modal Overlay Click Outside to Close
  document.querySelectorAll('.modal-overlay, .drawer-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal || e.target.classList?.contains('modal-card-wrapper')) {
        modal.classList.remove('active');
      }
    });
  });

  // 4. File Input Change Listener for Plus (+) Button Multi-Image Uploader
  const filePicker = document.getElementById('prod-file-picker');
  if (filePicker) {
    filePicker.addEventListener('change', (e) => {
      const files = Array.from(e.target.files || []);
      if (files.length > 0) {
        files.forEach(f => currentSelectedFiles.push(f));
        syncProductFileInput();
        renderProductImagesGrid();
      }
      filePicker.value = ''; // Safely reset only the temporary picker without clearing prod-file-input!
    });
  }

  // Fallback listener on prod-file-input if selected directly
  const fileInput = document.getElementById('prod-file-input');
  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const files = Array.from(e.target.files || []);
      if (files.length > 0) {
        files.forEach(f => currentSelectedFiles.push(f));
        syncProductFileInput();
        renderProductImagesGrid();
      }
    });
  }

  // Ensure prod-file-input.files is strictly in sync with currentSelectedFiles right before form submission
  const prodForm = document.getElementById('product-form');
  if (prodForm) {
    prodForm.addEventListener('submit', () => {
      syncProductFileInput();
    });
  }

  // Keyboard navigation for Product Gallery Lightbox
  window.addEventListener('keydown', (e) => {
    const galleryModal = document.getElementById('product-gallery-modal');
    if (galleryModal && galleryModal.classList.contains('active')) {
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        galleryPrevImage();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        galleryNextImage();
      } else if (e.key === 'Escape') {
        closeModal('product-gallery-modal');
      }
    }
  });

  // 5. Category Image File Input Live Preview
  const catFileInput = document.getElementById('cat-file-input');
  if (catFileInput) {
    catFileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
          const previewContainer = document.getElementById('cat-upload-preview');
          if (previewContainer) {
            previewContainer.innerHTML = `
              <div style="display:flex; align-items:center; gap:10px; padding:10px; background:#f4fbf7; border:1.5px solid var(--forest-green); border-radius:6px; margin-top:8px;">
                <img src="${evt.target.result}" alt="Category Preview" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                <span style="font-size:0.8rem; font-weight:600; color:var(--forest-green);">Selected: ${file.name}</span>
              </div>
            `;
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 6. Initialize Luxury Canvas Charts
  if (window.kinzChart) {
    setTimeout(() => {
      window.kinzChart.initRevenueChart('revenueChartCanvas');
      window.kinzChart.initDonutChart('donutChartCanvas');
    }, 120);
  }

  // 7. Report Section Dropdown Tab Switcher
  const reportSelect = document.getElementById('report-section-select');
  if (reportSelect) {
    reportSelect.addEventListener('change', (e) => {
      switchReportTab(e.target.value);
    });
  }

  // 8. Report Date Filter Preset Pills
  document.querySelectorAll('.report-date-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.report-date-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
    });
  });

  // 9. Synchronize all categories into all dropdowns across the application
  if (window.DB) {
    if (Array.isArray(window.DB.categories)) {
      window.DB.categories.forEach(c => {
        if (c && c.name) updateCategoryDropdowns(c.name);
      });
    }
    if (Array.isArray(window.DB.products)) {
      window.DB.products.forEach(p => {
        if (p && p.category) updateCategoryDropdowns(p.category);
      });
    }
  }

  // Live update category dropdowns when adding a category
  const catForm = document.getElementById('category-form');
  if (catForm) {
    catForm.addEventListener('submit', () => {
      const nameInput = document.getElementById('cat-form-name');
      if (nameInput && nameInput.value) {
        updateCategoryDropdowns(nameInput.value.trim());
      }
    });
  }

  // Ensure all modal & drawer cards have corner close buttons initialized
  ensureCornerCloseButtons();

  console.log('Kinz Jewellery Admin initialized: Fully interactive MySQL connection active.');
});

// ============================================================================
// Customer Reviews Search & Filter Controller
// ============================================================================
function handleReviewSearch(query) {
  const q = (query || '').toLowerCase().trim();
  const rows = document.querySelectorAll('#reviews-table-tbody tr');
  const ratingFilter = document.getElementById('reviews-filter-rating')?.value || 'all';

  rows.forEach(r => {
    // Skip empty state row if present
    if (r.cells.length === 1) return;

    const text = r.textContent.toLowerCase();
    const matches = !q || text.includes(q);
    const rowRating = r.getAttribute('data-review-rating');

    const ratingMatch = (ratingFilter === 'all' || rowRating === ratingFilter);

    r.style.display = (matches && ratingMatch) ? '' : 'none';
  });
}

function handleReviewFilterChange() {
  const query = document.getElementById('reviews-search-input')?.value || '';
  handleReviewSearch(query);
}

function resetReviewFilters() {
  const searchInput = document.getElementById('reviews-search-input');
  const ratingSelect = document.getElementById('reviews-filter-rating');
  if (searchInput) searchInput.value = '';
  if (ratingSelect) ratingSelect.value = 'all';
  const rows = document.querySelectorAll('#reviews-table-tbody tr');
  rows.forEach(r => r.style.display = '');
}

window.handleReviewSearch = handleReviewSearch;
window.handleReviewFilterChange = handleReviewFilterChange;
window.resetReviewFilters = resetReviewFilters;


