/**
 * RAR Consultant - Core Static JavaScript
 * Pure vanilla JavaScript with 0 external dependencies
 */

document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initTestimonialSlider();
  initFaqAccordion();
  initContactForm();
  initCookieConsent();
  initSmoothScroll();
});

/* ----------------------------------------------------
 * 1. NAVBAR & NAVIGATION
 * ---------------------------------------------------- */
function initNavbar() {
  const header = document.querySelector('header');
  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mobileDrawer = document.getElementById('mobile-menu-drawer');
  const menuIcon = document.getElementById('menu-icon');
  const closeIcon = document.getElementById('close-icon');

  // Sticky header scroll behavior
  const handleScroll = () => {
    if (window.scrollY > 20) {
      header?.classList.add('bg-[#070D1E]/95', 'backdrop-blur-xl', 'shadow-xl', 'shadow-black/20', 'border-b', 'border-cyan-500/10', 'py-3.5');
      header?.classList.remove('bg-[#070D1E]', 'py-4', 'sm:py-5', 'border-b', 'border-white/5');
    } else {
      header?.classList.remove('bg-[#070D1E]/95', 'backdrop-blur-xl', 'shadow-xl', 'shadow-black/20', 'border-b', 'border-cyan-500/10', 'py-3.5');
      header?.classList.add('bg-[#070D1E]', 'py-4', 'sm:py-5', 'border-b', 'border-white/5');
    }
  };
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  // Mobile menu toggle
  if (mobileBtn && mobileDrawer) {
    mobileBtn.addEventListener('click', () => {
      const isHidden = mobileDrawer.classList.contains('hidden');
      if (isHidden) {
        mobileDrawer.classList.remove('hidden');
        menuIcon?.classList.add('hidden');
        closeIcon?.classList.remove('hidden');
        mobileBtn.setAttribute('aria-expanded', 'true');
      } else {
        mobileDrawer.classList.add('hidden');
        menuIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
        mobileBtn.setAttribute('aria-expanded', 'false');
      }
    });

    // Close on click of any mobile nav link
    const mobileLinks = mobileDrawer.querySelectorAll('a');
    mobileLinks.forEach(link => {
      link.addEventListener('click', () => {
        mobileDrawer.classList.add('hidden');
        menuIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
        mobileBtn.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Active nav link highlight
  const currentPath = window.location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav-desktop-link');
  navLinks.forEach(link => {
    const linkPath = link.getAttribute('href');
    if (linkPath === currentPath || (currentPath === '' && linkPath === 'index.html')) {
      link.classList.add('text-white', 'bg-gradient-to-r', 'from-blue-600/90', 'to-cyan-600/90', 'shadow-md', 'shadow-blue-500/20');
      link.classList.remove('text-slate-300', 'hover:text-white', 'hover:bg-white/5');
    }
  });
}

/* ----------------------------------------------------
 * 2. TESTIMONIALS SLIDER (Homepage)
 * ---------------------------------------------------- */
function initTestimonialSlider() {
  const slider = document.getElementById('testimonial-slider');
  if (!slider) return;

  const testimonials = [
    {
      quote: "“The team made our website project feel clear and manageable, from the first conversation to launch.”",
      source: "Sample testimonial — replace with an approved client review."
    },
    {
      quote: "“We appreciated the practical guidance and the way the solution was shaped around our business.”",
      source: "Sample testimonial — replace with an approved client review."
    },
    {
      quote: "“A responsive partner who helped us understand the next step in our digital setup.”",
      source: "Sample testimonial — replace with an approved client review."
    }
  ];

  let currentIndex = 0;
  const quoteEl = document.getElementById('testimonial-quote');
  const sourceEl = document.getElementById('testimonial-source');
  const counterEl = document.getElementById('testimonial-counter');
  const prevBtn = document.getElementById('testimonial-prev');
  const nextBtn = document.getElementById('testimonial-next');

  function updateSlide(index) {
    if (!quoteEl || !sourceEl || !counterEl) return;
    quoteEl.style.opacity = '0';
    quoteEl.style.transform = 'translateY(6px)';
    setTimeout(() => {
      quoteEl.textContent = testimonials[index].quote;
      sourceEl.textContent = testimonials[index].source;
      counterEl.innerHTML = `<span class="text-blue-600 text-sm font-extrabold">${String(index + 1).padStart(2, '0')}</span> / ${String(testimonials.length).padStart(2, '0')}`;
      quoteEl.style.opacity = '1';
      quoteEl.style.transform = 'translateY(0)';
    }, 150);
  }

  prevBtn?.addEventListener('click', () => {
    currentIndex = (currentIndex + testimonials.length - 1) % testimonials.length;
    updateSlide(currentIndex);
  });

  nextBtn?.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % testimonials.length;
    updateSlide(currentIndex);
  });
}

/* ----------------------------------------------------
 * 3. FAQ ACCORDION (Services Page)
 * ---------------------------------------------------- */
function initFaqAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');
  if (!faqItems.length) return;

  faqItems.forEach(item => {
    const btn = item.querySelector('.faq-btn');
    const answer = item.querySelector('.faq-answer');
    const icon = item.querySelector('.faq-icon');

    btn?.addEventListener('click', () => {
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';

      // Close all others
      faqItems.forEach(other => {
        if (other !== item) {
          const otherBtn = other.querySelector('.faq-btn');
          const otherAns = other.querySelector('.faq-answer');
          const otherIcon = other.querySelector('.faq-icon');
          otherBtn?.setAttribute('aria-expanded', 'false');
          otherAns?.classList.add('hidden');
          otherIcon?.classList.remove('rotate-180', 'bg-blue-600', 'text-white');
          otherIcon?.classList.add('bg-slate-100', 'text-slate-600');
          other.classList.remove('shadow-md', 'border-blue-400/50', 'ring-1', 'ring-blue-400/30');
        }
      });

      // Toggle current
      if (isExpanded) {
        btn.setAttribute('aria-expanded', 'false');
        answer?.classList.add('hidden');
        icon?.classList.remove('rotate-180', 'bg-blue-600', 'text-white');
        icon?.classList.add('bg-slate-100', 'text-slate-600');
        item.classList.remove('shadow-md', 'border-blue-400/50', 'ring-1', 'ring-blue-400/30');
      } else {
        btn.setAttribute('aria-expanded', 'true');
        answer?.classList.remove('hidden');
        icon?.classList.add('rotate-180', 'bg-blue-600', 'text-white');
        icon?.classList.remove('bg-slate-100', 'text-slate-600');
        item.classList.add('shadow-md', 'border-blue-400/50', 'ring-1', 'ring-blue-400/30');
      }
    });
  });
}

/* ----------------------------------------------------
 * 4. CONTACT FORM & PHP MAILER INTEGRATION
 * ---------------------------------------------------- */
function initContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;

  const MAILER_ENDPOINT = 'api/contact-mailer.php';
  const THROTTLE_KEY = 'rar_enquiry_sent_at';
  const THROTTLE_MS = 30000;

  // ----------------------------------------------------
  // Toast Notification Controller
  // ----------------------------------------------------
  let toastTimer = null;

  function showToast(type, title, message, duration = 5000) {
    const toast = document.getElementById('toast-popup');
    if (!toast) return;

    const titleEl = document.getElementById('toast-title');
    const msgEl = document.getElementById('toast-message');
    const iconBox = document.getElementById('toast-icon-box');
    const iconSvg = document.getElementById('toast-icon-svg');
    const accentBar = document.getElementById('toast-accent-bar');
    const progressBar = document.getElementById('toast-progress');
    const card = document.getElementById('toast-card');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;

    if (type === 'success') {
      if (accentBar) accentBar.className = 'absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500';
      if (iconBox) iconBox.className = 'rounded-xl bg-emerald-100/90 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5';
      if (iconSvg) {
        iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />';
      }
      if (progressBar) progressBar.className = 'h-full bg-emerald-500 transition-all ease-linear';
      if (card) {
        card.className = 'bg-white/95 backdrop-blur-md rounded-2xl p-4 sm:p-5 shadow-2xl border border-emerald-200/90 ring-1 ring-emerald-500/10 flex items-start gap-3.5 relative overflow-hidden transition-all duration-300';
      }
    } else {
      if (accentBar) accentBar.className = 'absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 via-pink-500 to-red-500';
      if (iconBox) iconBox.className = 'rounded-xl bg-rose-100/90 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0 mt-0.5';
      if (iconSvg) {
        iconSvg.innerHTML = '<circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>';
      }
      if (progressBar) progressBar.className = 'h-full bg-rose-500 transition-all ease-linear';
      if (card) {
        card.className = 'bg-white/95 backdrop-blur-md rounded-2xl p-4 sm:p-5 shadow-2xl border border-rose-200/90 ring-1 ring-rose-500/10 flex items-start gap-3.5 relative overflow-hidden transition-all duration-300';
      }
    }

    if (toastTimer) clearTimeout(toastTimer);

    if (progressBar) {
      progressBar.style.transition = 'none';
      progressBar.style.width = '100%';
    }

    toast.classList.remove('hidden');
    toast.classList.remove('toast-leave');
    toast.classList.add('toast-enter');

    // Trigger smooth progress bar depletion
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (progressBar) {
          progressBar.style.transition = `width ${duration}ms linear`;
          progressBar.style.width = '0%';
        }
      });
    });

    toastTimer = setTimeout(() => {
      hideToast();
    }, duration);
  }

  function hideToast() {
    const toast = document.getElementById('toast-popup');
    if (!toast || toast.classList.contains('hidden')) return;

    toast.classList.remove('toast-enter');
    toast.classList.add('toast-leave');
    setTimeout(() => {
      toast.classList.add('hidden');
      toast.classList.remove('toast-leave');
    }, 300);
  }

  const toastClose = document.getElementById('toast-close');
  if (toastClose) {
    toastClose.addEventListener('click', hideToast);
  }

  // Pre-select service from URL param
  const urlParams = new URLSearchParams(window.location.search);
  const serviceParam = urlParams.get('service');
  const serviceSelect = document.getElementById('service');
  if (serviceParam && serviceSelect) {
    const slugMap = {
      'website-development': 'Website Development',
      'custom-software-development': 'Custom Software Development',
      'search-engine-optimization': 'Search Engine Optimization (SEO)',
      'e-commerce-platform-portal': 'E-Commerce Platform / Portal',
      'local-business-digital-setup': 'Local Business Digital Setup',
      'website-maintenance': 'Website Maintenance'
    };
    const targetTitle = slugMap[serviceParam] || serviceParam;
    for (let opt of serviceSelect.options) {
      if (opt.value.toLowerCase() === targetTitle.toLowerCase()) {
        serviceSelect.value = opt.value;
        break;
      }
    }
  }

  // Character counter for message textarea
  const messageEl = document.getElementById('message');
  const countEl = document.getElementById('message-count');
  if (messageEl && countEl) {
    const updateCount = () => {
      const len = messageEl.value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim().length;
      countEl.textContent = `${len}/500`;
      if (len > 470) {
        countEl.classList.add('text-amber-600');
        countEl.classList.remove('text-slate-400');
      } else {
        countEl.classList.remove('text-amber-600');
        countEl.classList.add('text-slate-400');
      }
    };
    messageEl.addEventListener('input', updateCount);
    updateCount();
  }

  // Auto-format mobile input as 5 digits + space + 5 digits
  const mobileInput = document.getElementById('mobile');
  if (mobileInput) {
    mobileInput.addEventListener('input', () => {
      let digits = mobileInput.value.replace(/\D/g, '');
      if (digits.length > 10) digits = digits.slice(0, 10);
      if (digits.length > 5) {
        mobileInput.value = `${digits.slice(0, 5)} ${digits.slice(5)}`;
      } else {
        mobileInput.value = digits;
      }
    });
  }

  // ----------------------------------------------------
  // Granular Field Validation Engine
  // ----------------------------------------------------
  const validateField = (fieldId) => {
    const field = document.getElementById(fieldId);
    const errEl = document.getElementById(`${fieldId}-error`);
    if (!field) return true;

    let errorMsg = '';
    const val = (field.value || '').trim();

    switch (fieldId) {
      case 'fullName':
        if (!val) {
          errorMsg = 'Please enter your full name.';
        } else if (val.length < 2) {
          errorMsg = 'Full name must be at least 2 characters.';
        } else if (val.length > 80) {
          errorMsg = 'Full name must be under 80 characters.';
        } else if (!/^[\p{L} .'-]+$/u.test(val)) {
          errorMsg = 'Use letters, spaces, dots, hyphens, or apostrophes only.';
        }
        break;

      case 'mobile':
        if (!val) {
          errorMsg = 'Please enter your mobile number.';
        } else {
          let digits = val.replace(/\D/g, '');
          if (digits.length === 12 && digits.startsWith('91')) digits = digits.slice(2);
          if (digits.length === 11 && digits.startsWith('0')) digits = digits.slice(1);
          if (digits.length !== 10) {
            errorMsg = 'Please enter a valid 10-digit mobile number.';
          } else if (!/^[6-9]\d{9}$/.test(digits)) {
            errorMsg = 'Mobile number must start with 6, 7, 8, or 9.';
          }
        }
        break;

      case 'email':
        if (!val) {
          errorMsg = 'Please enter your email address.';
        } else if (val.length > 100) {
          errorMsg = 'Email address must be 100 characters or fewer.';
        } else {
          const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
          if (!emailRegex.test(val.toLowerCase())) {
            errorMsg = 'Please enter a valid email address (e.g. name@example.com).';
          }
        }
        break;

      case 'service':
        if (!field.value) {
          errorMsg = 'Please select a service interest.';
        }
        break;

      case 'message': {
        const cleanMsg = field.value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim();
        if (!cleanMsg) {
          errorMsg = 'Please describe your project or requirement.';
        } else if (cleanMsg.length < 10) {
          errorMsg = 'Please share at least 10 characters so we can understand your needs.';
        } else if (cleanMsg.length > 500) {
          errorMsg = 'Please keep your description under 500 characters.';
        }
        break;
      }

      case 'consent-checkbox':
        if (!field.checked) {
          errorMsg = 'Please agree to the Privacy Policy and Terms and Conditions to proceed.';
        }
        break;
    }

    if (errorMsg) {
      field.classList.add('border-rose-400', 'bg-rose-50/30', 'ring-1', 'ring-rose-200');
      field.classList.remove('border-slate-200', 'border-emerald-400', 'bg-emerald-50/15', 'bg-[#F8FAFC]');
      if (errEl) {
        errEl.innerHTML = `<span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>${errorMsg}</span>`;
        errEl.classList.remove('hidden');
      }
      return false;
    } else {
      field.classList.remove('border-rose-400', 'bg-rose-50/30', 'ring-1', 'ring-rose-200');
      if (val && fieldId !== 'service' && fieldId !== 'consent-checkbox') {
        field.classList.add('border-emerald-400', 'bg-emerald-50/15');
        field.classList.remove('border-slate-200', 'bg-[#F8FAFC]');
      } else {
        field.classList.remove('border-emerald-400', 'bg-emerald-50/15');
        field.classList.add('border-slate-200', 'bg-[#F8FAFC]');
      }
      if (errEl) {
        errEl.textContent = '';
        errEl.classList.add('hidden');
      }
      return true;
    }
  };

  // Attach live real-time validation listeners
  ['fullName', 'mobile', 'email', 'service', 'message'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('blur', () => validateField(id));
    el.addEventListener('input', () => {
      const errEl = document.getElementById(`${id}-error`);
      if (errEl && !errEl.classList.contains('hidden')) {
        validateField(id);
      }
    });
  });

  const consentBox = document.getElementById('consent-checkbox');
  if (consentBox) {
    consentBox.addEventListener('change', () => validateField('consent-checkbox'));
  }

  // Throttle check
  const throttleMsg = document.getElementById('throttle-message');
  const submitBtn = document.getElementById('submit-btn');

  function checkThrottle() {
    try {
      const last = Number(localStorage.getItem(THROTTLE_KEY));
      if (Number.isFinite(last) && last > 0) {
        const remaining = Math.max(0, THROTTLE_MS - (Date.now() - last));
        if (remaining > 0) {
          if (throttleMsg) {
            throttleMsg.textContent = `Please wait ${Math.ceil(remaining / 1000)} seconds before sending another enquiry.`;
            throttleMsg.classList.remove('hidden');
          }
          if (submitBtn) submitBtn.disabled = true;
          return true;
        }
      }
    } catch (e) {}
    if (throttleMsg) throttleMsg.classList.add('hidden');
    if (submitBtn) submitBtn.disabled = false;
    return false;
  }
  checkThrottle();
  setInterval(checkThrottle, 1000);

  // Form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (checkThrottle()) return;

    // Validate all fields
    const fieldsToValidate = ['fullName', 'mobile', 'email', 'service', 'message', 'consent-checkbox'];
    let firstInvalid = null;
    let formValid = true;

    fieldsToValidate.forEach(id => {
      const ok = validateField(id);
      if (!ok) {
        formValid = false;
        if (!firstInvalid) {
          firstInvalid = document.getElementById(id);
        }
      }
    });

    if (!formValid) {
      if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus();
      }
      showToast('error', 'Please Check Form', 'Please fill in all required fields correctly before submitting.', 4000);
      return;
    }

    const fullName = document.getElementById('fullName');
    const mobile = document.getElementById('mobile');
    const email = document.getElementById('email');
    const service = document.getElementById('service');
    const message = document.getElementById('message');
    const botcheck = document.getElementById('botcheck');

    const nameVal = fullName.value.trim();
    const mobVal = mobile.value.trim();
    const emailVal = email.value.trim().toLowerCase();
    const serviceVal = service.value;
    const msgVal = message.value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim();

    // Honeypot bot check
    if (botcheck && botcheck.value) {
      showToast('success', `Thank you, ${nameVal}!`, 'Your enquiry has been received successfully. We will get back to you within 24 hours.', 5000);
      form.reset();
      return;
    }

    function formatMobile(num) {
      const d = num.replace(/\D/g, '');
      if (d.length === 10) return `+91 ${d}`;
      return `+${d}`;
    }

    // Build FormData payload
    const formData = new FormData();
    formData.append('full_name', nameVal);
    formData.append('mobile_number', formatMobile(mobVal));
    formData.append('corp_email', emailVal);
    formData.append('practice_domain', serviceVal);
    formData.append('scope_overview', msgVal);
    formData.append('dpdp_consent', '1');
    formData.append('honeypot_node', botcheck ? botcheck.value : '');

    // Aliases
    formData.append('name', nameVal);
    formData.append('mobile', formatMobile(mobVal));
    formData.append('email', emailVal);
    formData.append('service', serviceVal);
    formData.append('message', msgVal);
    formData.append('consent', 'Yes');

    // Loading state on button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <svg class="animate-spin h-4 w-4 mr-2" style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span>Sending enquiry…</span>
      `;
    }

    const errorBanner = document.getElementById('error-banner');
    const errorText = document.getElementById('error-banner-text');
    if (errorBanner) errorBanner.classList.add('hidden');

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000);

      const res = await fetch(MAILER_ENDPOINT, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json'
        },
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      let result = null;
      try {
        result = await res.json();
      } catch (jsonErr) {}

      if (res.ok && result && result.success) {
        try {
          localStorage.setItem(THROTTLE_KEY, String(Date.now()));
        } catch (e) {}
        checkThrottle();

        // Display small thank-you popup for a few seconds
        const thankYouTitle = `Thank you, ${result.lead?.name || nameVal}!`;
        const thankYouMessage = result.message || `We have received your enquiry regarding ${serviceVal}. Our engineering & consulting team in Pune is reviewing your details and will get back to you within 24 hours. A confirmation reply email has also been sent to ${emailVal}.`;
        
        showToast('success', thankYouTitle, thankYouMessage, 5500);

        // Reset form and neutral styling
        form.reset();
        ['fullName', 'mobile', 'email', 'service', 'message'].forEach(id => {
          const el = document.getElementById(id);
          if (el) {
            el.classList.remove('border-emerald-400', 'bg-emerald-50/15', 'border-rose-400', 'bg-rose-50/30', 'ring-1', 'ring-rose-200');
            el.classList.add('border-slate-200', 'bg-[#F8FAFC]');
          }
        });
        if (countEl) countEl.textContent = '0/500';

      } else {
        const errMsg = (result && result.message)
          ? result.message
          : 'Could not send your enquiry right now. Please try again or reach out directly to anand@rarconsultant.co.in or +91 91580 30333.';
        
        showToast('error', 'Submission Failed', errMsg, 6000);
        if (errorBanner && errorText) {
          errorText.textContent = errMsg;
          errorBanner.classList.remove('hidden');
        }
      }
    } catch (err) {
      const errMsg = (err.name === 'AbortError')
        ? 'The request took longer than expected to confirm delivery. Please verify your connection or email anand@rarconsultant.co.in directly.'
        : 'Could not connect to the mailer gateway. Please email us directly at anand@rarconsultant.co.in or call +91 91580 30333.';
      
      showToast('error', 'Connection Error', errMsg, 6000);
      if (errorBanner && errorText) {
        errorText.textContent = errMsg;
        errorBanner.classList.remove('hidden');
      }
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `
          <span>Send enquiry</span>
          <svg class="w-4 h-4" style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        `;
      }
    }
  });
}

/* ----------------------------------------------------
 * 5. COOKIE BANNER & PREFERENCES MODAL
 * ---------------------------------------------------- */
function initCookieConsent() {
  const STORAGE_KEY = 'rar_cookie_consent_v1';
  const banner = document.getElementById('cookie-banner');
  const simpleView = document.getElementById('cookie-simple-view');
  const manageView = document.getElementById('cookie-manage-view');
  const bannerTitle = document.getElementById('cookie-banner-title');

  const acceptAllBtn = document.getElementById('cookie-accept-all');
  const rejectBtn = document.getElementById('cookie-reject');
  const manageBtn = document.getElementById('cookie-manage');
  const savePrefsBtn = document.getElementById('cookie-save-prefs');
  const closeBannerBtn = document.getElementById('cookie-close');

  const prefCheckbox = document.getElementById('cookie-pref-checkbox');
  const analyticsCheckbox = document.getElementById('cookie-analytics-checkbox');

  function saveConsent(prefs) {
    try {
      const payload = {
        preferences: {
          essential: true,
          preferences: !!prefs.preferences,
          analytics: !!prefs.analytics
        },
        timestamp: new Date().toISOString()
      };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch (e) {}
    if (banner) banner.classList.add('hidden');
  }

  // Check stored
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (!stored) {
      setTimeout(() => {
        if (banner) banner.classList.remove('hidden');
      }, 1200);
    } else {
      const parsed = JSON.parse(stored);
      if (parsed?.preferences) {
        if (prefCheckbox) prefCheckbox.checked = !!parsed.preferences.preferences;
        if (analyticsCheckbox) analyticsCheckbox.checked = !!parsed.preferences.analytics;
      }
    }
  } catch (e) {
    if (banner) banner.classList.remove('hidden');
  }

  // Open preferences view
  manageBtn?.addEventListener('click', () => {
    if (simpleView) simpleView.classList.add('hidden');
    if (manageView) manageView.classList.remove('hidden');
    if (bannerTitle) bannerTitle.textContent = 'Your privacy choices';
  });

  // Reopen from footer button (any page)
  const reopenTriggers = document.querySelectorAll('.open-cookie-settings');
  reopenTriggers.forEach(btn => {
    btn.addEventListener('click', () => {
      if (banner) banner.classList.remove('hidden');
      if (simpleView) simpleView.classList.add('hidden');
      if (manageView) manageView.classList.remove('hidden');
      if (bannerTitle) bannerTitle.textContent = 'Your privacy choices';
    });
  });

  acceptAllBtn?.addEventListener('click', () => {
    saveConsent({ preferences: true, analytics: true });
  });

  rejectBtn?.addEventListener('click', () => {
    saveConsent({ preferences: false, analytics: false });
  });

  savePrefsBtn?.addEventListener('click', () => {
    saveConsent({
      preferences: prefCheckbox ? prefCheckbox.checked : false,
      analytics: analyticsCheckbox ? analyticsCheckbox.checked : false
    });
  });

  closeBannerBtn?.addEventListener('click', () => {
    if (banner) banner.classList.add('hidden');
  });
}

/* ----------------------------------------------------
 * 6. SMOOTH SCROLL FOR HASH LINKS
 * ---------------------------------------------------- */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#' || !targetId) return;
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
}

