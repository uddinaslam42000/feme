/**
 * FeMe – Ultimate Luxury Closet
 * Master JavaScript Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  initPromoStrip();
  initMobileDrawer();
  initSearchModal();
  initHeroSlider();
  initNewsletterForm();
  initCountdownTimer();
  initCategoryFilters();
  initAjaxCart();
  initCartPage();
  initScrollAnimations();
});

/* 1. Promo Strip Dismissal */
function initPromoStrip() {
  const promoStrip = document.getElementById('promoStrip');
  const closeBtn = document.getElementById('closePromo');
  
  if (sessionStorage.getItem('feme_promo_closed') === 'true') {
    if (promoStrip) promoStrip.style.display = 'none';
  }

  if (closeBtn && promoStrip) {
    closeBtn.addEventListener('click', () => {
      promoStrip.style.display = 'none';
      sessionStorage.setItem('feme_promo_closed', 'true');
    });
  }
}

/* 2. Mobile Drawer Navigation */
function initMobileDrawer() {
  const toggleBtn = document.getElementById('mobileToggle');
  const closeBtn = document.getElementById('drawerClose');
  const overlay = document.getElementById('drawerOverlay');
  const drawer = document.getElementById('mobileDrawer');

  function openDrawer() {
    if (overlay && drawer) {
      overlay.classList.add('active');
      drawer.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeDrawer() {
    if (overlay && drawer) {
      overlay.classList.remove('active');
      drawer.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (overlay) overlay.addEventListener('click', closeDrawer);
}

/* 3. Search Overlay Modal */
function initSearchModal() {
  const searchBtn = document.getElementById('searchToggle');
  const searchModal = document.getElementById('searchModal');
  const searchClose = document.getElementById('searchClose');
  const searchInput = document.getElementById('searchInput');

  function openSearch() {
    if (searchModal) {
      searchModal.classList.add('active');
      if (searchInput) searchInput.focus();
      document.body.style.overflow = 'hidden';
    }
  }

  function closeSearch() {
    if (searchModal) {
      searchModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  if (searchBtn) searchBtn.addEventListener('click', openSearch);
  if (searchClose) searchClose.addEventListener('click', closeSearch);

  if (searchModal) {
    searchModal.addEventListener('click', (e) => {
      if (e.target === searchModal) closeSearch();
    });
  }
}

/* 4. Hero Slider Logic */
function initHeroSlider() {
  const slides = document.querySelectorAll('.hero-slide');
  const dotsContainer = document.getElementById('sliderDots');
  const prevBtn = document.getElementById('prevSlide');
  const nextBtn = document.getElementById('nextSlide');
  const sliderSection = document.querySelector('.hero-slider-section');

  if (!slides || slides.length === 0) return;

  let currentIndex = 0;
  let autoSlideTimer = null;

  // Create pagination dots
  if (dotsContainer) {
    dotsContainer.innerHTML = '';
    slides.forEach((_, idx) => {
      const dot = document.createElement('div');
      dot.classList.add('dot');
      if (idx === 0) dot.classList.add('active');
      dot.addEventListener('click', () => goToSlide(idx));
      dotsContainer.appendChild(dot);
    });
  }

  function goToSlide(index) {
    slides[currentIndex].classList.remove('active');
    
    const dots = dotsContainer ? dotsContainer.querySelectorAll('.dot') : [];
    if (dots[currentIndex]) dots[currentIndex].classList.remove('active');

    currentIndex = (index + slides.length) % slides.length;

    slides[currentIndex].classList.add('active');
    if (dots[currentIndex]) dots[currentIndex].classList.add('active');
    
    resetAutoSlide();
  }

  function nextSlide() {
    goToSlide(currentIndex + 1);
  }

  function prevSlide() {
    goToSlide(currentIndex - 1);
  }

  if (nextBtn) nextBtn.addEventListener('click', nextSlide);
  if (prevBtn) prevBtn.addEventListener('click', prevSlide);

  function startAutoSlide() {
    autoSlideTimer = setInterval(nextSlide, 5000);
  }

  function resetAutoSlide() {
    if (autoSlideTimer) clearInterval(autoSlideTimer);
    startAutoSlide();
  }

  // Touch Swipe Support for Mobile/Tablet
  if (sliderSection) {
    let startX = 0;
    let endX = 0;

    sliderSection.addEventListener('touchstart', (e) => {
      startX = e.changedTouches[0].screenX;
    }, { passive: true });

    sliderSection.addEventListener('touchend', (e) => {
      endX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const diff = startX - endX;
      if (Math.abs(diff) > 40) {
        if (diff > 0) {
          nextSlide();
        } else {
          prevSlide();
        }
      }
    }
  }

  startAutoSlide();
}

/* 5. AJAX Newsletter Signup Form */
function initNewsletterForm() {
  const form = document.getElementById('newsletterForm');
  const msgContainer = document.getElementById('newsletterMsg');

  if (!form) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(form);
    
    if (msgContainer) {
      msgContainer.className = 'newsletter-msg';
      msgContainer.textContent = 'Subscribing...';
    }

    fetch('includes/newsletter_handler.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (msgContainer) {
        if (data.status === 'success') {
          msgContainer.className = 'newsletter-msg success';
          msgContainer.textContent = data.message;
          form.reset();
        } else {
          msgContainer.className = 'newsletter-msg error';
          msgContainer.textContent = data.message;
        }
      }
    })
    .catch(() => {
      if (msgContainer) {
        msgContainer.className = 'newsletter-msg error';
        msgContainer.textContent = 'An error occurred. Please try again.';
      }
    });
  });
}

/* 6. Live Countdown Timer */
function initCountdownTimer() {
  const timerElem = document.getElementById('offerCountdown');
  if (!timerElem) return;

  const hoursDisplay = document.getElementById('timerHours');
  const minsDisplay = document.getElementById('timerMins');
  const secsDisplay = document.getElementById('timerSecs');

  const endDateStr = timerElem.getAttribute('data-end-date');
  let targetTime;

  if (endDateStr) {
    targetTime = new Date(endDateStr.replace(/-/g, '/')).getTime();
  }
  
  if (!targetTime || isNaN(targetTime)) {
    // Fallback: 24 hours from now
    targetTime = new Date().getTime() + (24 * 60 * 60 * 1000);
  }

  function updateTimer() {
    const now = new Date().getTime();
    const distance = targetTime - now;

    if (distance <= 0) {
      if (hoursDisplay) hoursDisplay.textContent = '00';
      if (minsDisplay) minsDisplay.textContent = '00';
      if (secsDisplay) secsDisplay.textContent = '00';
      return;
    }

    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)) + (Math.floor(distance / (1000 * 60 * 60 * 24)) * 24);
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    if (hoursDisplay) hoursDisplay.textContent = String(hours).padStart(2, '0');
    if (minsDisplay) minsDisplay.textContent = String(minutes).padStart(2, '0');
    if (secsDisplay) secsDisplay.textContent = String(seconds).padStart(2, '0');
  }

  updateTimer();
  setInterval(updateTimer, 1000);
}

/* 7. Category Mobile Filter Drawer */
function initCategoryFilters() {
  const toggleBtn = document.getElementById('mobileFilterToggle');
  const closeBtn = document.getElementById('closeFilterBtn');
  const sidebar = document.getElementById('filterSidebar');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
  }

  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', () => {
      sidebar.classList.remove('active');
      document.body.style.overflow = '';
    });
  }
}

/* 8. Product Detail Thumbnail Switcher */
function switchProductImage(imgSrc, thumbElement) {
  const mainImg = document.getElementById('mainProductImg');
  if (mainImg) {
    mainImg.style.opacity = '0.3';
    setTimeout(() => {
      mainImg.src = imgSrc;
      mainImg.style.opacity = '1';
    }, 150);
  }

  const thumbs = document.querySelectorAll('.thumb-box');
  thumbs.forEach(t => t.classList.remove('active'));
  if (thumbElement) thumbElement.classList.add('active');
}

/* 9. Product Detail Quantity Controls */
function updateQty(delta) {
  const qtyInput = document.getElementById('qtyInput');
  if (!qtyInput) return;

  let current = parseInt(qtyInput.value) || 1;
  const max = parseInt(qtyInput.getAttribute('max')) || 99;
  current = Math.max(1, Math.min(max, current + delta));
  qtyInput.value = current;
}

/* 10. AJAX Add to Cart Logic */
function initAjaxCart() {
  // Quick Add Buttons on Cards
  document.addEventListener('click', (e) => {
    const quickBtn = e.target.closest('.ajax-add-cart');
    if (quickBtn) {
      e.preventDefault();
      const productId = quickBtn.getAttribute('data-product-id');
      sendAddToCart(productId, 1, quickBtn);
    }
  });

  // Product Details Form Submit
  const detailForm = document.getElementById('addToCartForm');
  if (detailForm) {
    detailForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const productId = detailForm.querySelector('[name="product_id"]').value;
      const quantity = detailForm.querySelector('[name="quantity"]').value;
      const feedbackMsg = document.getElementById('cartFormMsg');
      sendAddToCart(productId, quantity, null, feedbackMsg);
    });
  }

  // Buy Now Button
  const buyNowBtn = document.getElementById('buyNowBtn');
  if (buyNowBtn && detailForm) {
    buyNowBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const productId = detailForm.querySelector('[name="product_id"]').value;
      const quantity = detailForm.querySelector('[name="quantity"]').value;
      sendAddToCart(productId, quantity, null, null, true);
    });
  }
}

function sendAddToCart(productId, quantity, btnElement = null, feedbackMsg = null, isBuyNow = false) {
  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);

  if (btnElement) btnElement.style.opacity = '0.6';
  if (feedbackMsg) {
    feedbackMsg.className = 'form-feedback-msg';
    feedbackMsg.textContent = 'Adding to cart...';
  }

  fetch('includes/cart_handler.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (btnElement) btnElement.style.opacity = '1';

    if (data.status === 'success') {
      // Update cart count badges
      const badges = document.querySelectorAll('#cartBadge, .cart-count-badge');
      badges.forEach(b => b.textContent = data.cart_count);

      if (feedbackMsg) {
        feedbackMsg.className = 'form-feedback-msg success';
        feedbackMsg.textContent = 'Item added to your cart!';
      }

      if (isBuyNow) {
        window.location.href = 'checkout.php';
      }
    } else {
      if (feedbackMsg) {
        feedbackMsg.className = 'form-feedback-msg error';
        feedbackMsg.textContent = data.message;
      } else {
        alert(data.message);
      }
    }
  })
  .catch(() => {
    if (btnElement) btnElement.style.opacity = '1';
    if (feedbackMsg) {
      feedbackMsg.className = 'form-feedback-msg error';
      feedbackMsg.textContent = 'An error occurred. Please try again.';
    }
  });
}

/* 11. Cart Page Interactive Logic */
function initCartPage() {
  // Quantity Change Buttons in Cart
  document.addEventListener('click', (e) => {
    const qtyBtn = e.target.closest('.cart-qty-change');
    if (qtyBtn) {
      const row = qtyBtn.closest('.cart-item-row');
      if (!row) return;

      const productId = row.getAttribute('data-product-id');
      const delta = parseInt(qtyBtn.getAttribute('data-delta')) || 0;
      const input = row.querySelector('.cart-qty-input');
      if (!input) return;

      let currentQty = parseInt(input.value) || 1;
      let newQty = Math.max(1, currentQty + delta);

      if (newQty !== currentQty) {
        // Sync all input fields for this product (desktop & mobile views)
        const allMatchingRows = document.querySelectorAll(`.cart-item-row[data-product-id="${productId}"]`);
        allMatchingRows.forEach(r => {
          const inp = r.querySelector('.cart-qty-input');
          if (inp) inp.value = newQty;
        });

        updateCartServer(productId, newQty, 'update');
      }
    }

    // Remove Item Button
    const removeBtn = e.target.closest('.cart-item-remove');
    if (removeBtn) {
      e.preventDefault();
      const row = removeBtn.closest('.cart-item-row');
      if (!row) return;

      const productId = row.getAttribute('data-product-id');
      const allMatchingRows = document.querySelectorAll(`.cart-item-row[data-product-id="${productId}"]`);

      allMatchingRows.forEach(r => {
        r.style.opacity = '0.3';
      });

      updateCartServer(productId, 0, 'remove', () => {
        allMatchingRows.forEach(r => r.remove());
        recalculateCartTotals();
      });
    }
  });
}

function updateCartServer(productId, quantity, action = 'update', callback = null) {
  const formData = new FormData();
  formData.append('action', action);
  formData.append('product_id', productId);
  formData.append('quantity', quantity);

  fetch('includes/cart_handler.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      const badges = document.querySelectorAll('#cartBadge, .cart-count-badge');
      badges.forEach(b => b.textContent = data.cart_count);
      recalculateCartTotals();
      if (callback) callback();
    }
  });
}

function recalculateCartTotals() {
  const rows = document.querySelectorAll('.desktop-cart-table .cart-item-row');
  let subtotal = 0;

  rows.forEach(row => {
    const price = parseFloat(row.getAttribute('data-price')) || 0;
    const qtyInput = row.querySelector('.cart-qty-input');
    const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
    const lineTotal = price * qty;

    const lineTotalElem = row.querySelector('.line-total-cell');
    if (lineTotalElem) lineTotalElem.textContent = '₹' + lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    subtotal += lineTotal;
  });

  const subtotalElem = document.getElementById('summarySubtotal');
  const shippingElem = document.getElementById('summaryShipping');
  const grandTotalElem = document.getElementById('summaryGrandTotal');

  if (subtotalElem) {
    subtotalElem.textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  let shipping = (subtotal > 5000 || subtotal === 0) ? 0 : 250;
  if (shippingElem) {
    shippingElem.innerHTML = shipping === 0 ? '<span style="color:#2e7d32; font-weight:600;">FREE</span>' : '₹' + shipping.toFixed(2);
  }

  let grandTotal = subtotal + shipping;
  if (grandTotalElem) {
    grandTotalElem.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // If cart is empty, reload page to display empty state
  if (subtotal === 0 && rows.length === 0) {
    window.location.reload();
  }
}

/* 12. Scroll Fade-in Animation */
function initScrollAnimations() {
  const elements = document.querySelectorAll('.fade-in-section, .section-header, .story-block, .curations-section, .new-arrivals-section, .offer-banner-section');
  if (!elements || elements.length === 0) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
      }
    });
  }, { threshold: 0.1 });

  elements.forEach(el => {
    el.classList.add('fade-in-section');
    observer.observe(el);
  });
}
