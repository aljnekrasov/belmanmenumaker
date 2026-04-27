/* =========================================================
   Radius Booking Form — Vanilla JS
   Usage: initBookingForm({ formSelector, type, apiBase })
   ========================================================= */

function initBookingForm(opts) {
  var root = document.querySelector(opts.formSelector);
  if (!root) return;

  var type    = opts.type || root.dataset.type || 'dinner';
  var apiBase = opts.apiBase || root.dataset.api || '/booking/api';

  /* ---- State ---- */
  var state = {
    step: 1,
    totalSteps: 4,
    availableDates: {},      // { 'YYYY-MM-DD': true }
    slots: [],               // fetched slots for selected date
    selectedDate: null,      // 'YYYY-MM-DD'
    selectedSlot: null,      // slot object
    guests: 1,
    maxGuests: 1,
    dietary: [],
    allergies: '',
    name: '',
    phone: '',
    email: '',
    comment: '',
    agreeTerms: false,
    agreePrivacy: false,
    submitting: false,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear()
  };

  var MONTHS = [
    'Январь','Февраль','Март','Апрель','Май','Июнь',
    'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'
  ];
  var WEEKDAYS = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];

  /* ---- DOM refs (populated after build) ---- */
  var els = {};

  /* ==========================================================
     BUILD HTML
     ========================================================== */
  function build() {
    root.innerHTML = '';

    /* Step indicators */
    var stepsHtml = '<div class="rb-steps">';
    for (var i = 1; i <= state.totalSteps; i++) {
      var cls = i === 1 ? ' is-active' : '';
      stepsHtml += '<div class="rb-steps__item' + cls + '" data-step="' + i + '">';
      if (i > 1) stepsHtml += '<div class="rb-steps__line"></div>';
      stepsHtml += '<div class="rb-steps__circle">' + i + '</div>';
      stepsHtml += '</div>';
    }
    stepsHtml += '</div>';

    /* Step 1: Date + slots */
    var step1 = '<div class="rb-step is-visible" data-step="1">' +
      '<h2>\u0412\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u0434\u0430\u0442\u0443</h2>' +
      '<div class="rb-calendar" id="rb-calendar"></div>' +
      '<div class="rb-slots" id="rb-slots" style="display:none"></div>' +
      '<div class="rb-buttons"><button class="rb-btn rb-btn--primary" data-action="next" disabled>\u0414\u0430\u043B\u0435\u0435</button></div>' +
      '</div>';

    /* Step 2: Guests (+ dietary for dinner) */
    var dietaryHtml = '';
    if (type === 'dinner') {
      dietaryHtml = '<div class="rb-dietary">' +
        '<label class="rb-label">\u041E\u0441\u043E\u0431\u0435\u043D\u043D\u043E\u0441\u0442\u0438 \u043F\u0438\u0442\u0430\u043D\u0438\u044F</label>' +
        '<div class="rb-dietary__grid">' +
          dietaryCheck('vegetarian', '\u0412\u0435\u0433\u0435\u0442\u0430\u0440\u0438\u0430\u043D\u0441\u043A\u043E\u0435') +
          dietaryCheck('vegan', '\u0412\u0435\u0433\u0430\u043D\u0441\u043A\u043E\u0435') +
          dietaryCheck('no_gluten', '\u0411\u0435\u0437 \u0433\u043B\u044E\u0442\u0435\u043D\u0430') +
          dietaryCheck('no_lactose', '\u0411\u0435\u0437 \u043B\u0430\u043A\u0442\u043E\u0437\u044B') +
        '</div>' +
        '<div class="rb-field">' +
          '<label class="rb-label">\u0410\u043B\u043B\u0435\u0440\u0433\u0438\u0438</label>' +
          '<textarea class="rb-textarea" id="rb-allergies" placeholder="\u041E\u043F\u0438\u0448\u0438\u0442\u0435 \u0430\u043B\u043B\u0435\u0440\u0433\u0438\u0438, \u0435\u0441\u043B\u0438 \u0435\u0441\u0442\u044C"></textarea>' +
        '</div>' +
      '</div>';
    }

    var step2 = '<div class="rb-step" data-step="2">' +
      '<h2>\u041A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u043E \u0433\u043E\u0441\u0442\u0435\u0439</h2>' +
      '<label class="rb-label">\u0413\u043E\u0441\u0442\u0438</label>' +
      '<div class="rb-counter">' +
        '<button class="rb-counter__btn" data-action="guests-minus" type="button">&minus;</button>' +
        '<span class="rb-counter__value" id="rb-guests-val">1</span>' +
        '<button class="rb-counter__btn" data-action="guests-plus" type="button">+</button>' +
        '<span class="rb-counter__label" id="rb-guests-label"></span>' +
      '</div>' +
      dietaryHtml +
      '<div class="rb-buttons">' +
        '<button class="rb-btn rb-btn--secondary" data-action="prev">\u041D\u0430\u0437\u0430\u0434</button>' +
        '<button class="rb-btn rb-btn--primary" data-action="next">\u0414\u0430\u043B\u0435\u0435</button>' +
      '</div>' +
      '</div>';

    /* Step 3: Contact info */
    var step3 = '<div class="rb-step" data-step="3">' +
      '<h2>\u041A\u043E\u043D\u0442\u0430\u043A\u0442\u043D\u044B\u0435 \u0434\u0430\u043D\u043D\u044B\u0435</h2>' +
      '<div class="rb-field">' +
        '<label class="rb-label">\u0418\u043C\u044F *</label>' +
        '<input class="rb-input" id="rb-name" type="text" placeholder="\u0412\u0430\u0448\u0435 \u0438\u043C\u044F" required>' +
        '<div class="rb-error-inline" id="rb-name-err"></div>' +
      '</div>' +
      '<div class="rb-field">' +
        '<label class="rb-label">\u0422\u0435\u043B\u0435\u0444\u043E\u043D *</label>' +
        '<input class="rb-input" id="rb-phone" type="tel" placeholder="+7(___) ___-__-__" required>' +
        '<div class="rb-error-inline" id="rb-phone-err"></div>' +
      '</div>' +
      '<div class="rb-field">' +
        '<label class="rb-label">Email *</label>' +
        '<input class="rb-input" id="rb-email" type="email" placeholder="you@example.com" required>' +
        '<div class="rb-error-inline" id="rb-email-err"></div>' +
      '</div>' +
      '<div class="rb-field">' +
        '<label class="rb-label">\u041A\u043E\u043C\u043C\u0435\u043D\u0442\u0430\u0440\u0438\u0439</label>' +
        '<textarea class="rb-textarea" id="rb-comment" placeholder="\u041F\u043E\u0436\u0435\u043B\u0430\u043D\u0438\u044F \u0438\u043B\u0438 \u0432\u043E\u043F\u0440\u043E\u0441\u044B"></textarea>' +
      '</div>' +
      '<div class="rb-buttons">' +
        '<button class="rb-btn rb-btn--secondary" data-action="prev">\u041D\u0430\u0437\u0430\u0434</button>' +
        '<button class="rb-btn rb-btn--primary" data-action="next">\u0414\u0430\u043B\u0435\u0435</button>' +
      '</div>' +
      '</div>';

    /* Step 4: Confirmation */
    var step4 = '<div class="rb-step" data-step="4">' +
      '<h2>\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435</h2>' +
      '<div class="rb-summary" id="rb-summary"></div>' +
      '<div class="rb-consent">' +
        '<label class="rb-check">' +
          '<input type="checkbox" id="rb-agree-terms">' +
          '<span class="rb-check__box"></span>' +
          '<span class="rb-check__text">\u0421\u043E\u0433\u043B\u0430\u0441\u0435\u043D(-\u043D\u0430) \u0441 <a href="/terms" target="_blank">\u0443\u0441\u043B\u043E\u0432\u0438\u044F\u043C\u0438 \u0431\u0440\u043E\u043D\u0438\u0440\u043E\u0432\u0430\u043D\u0438\u044F</a> *</span>' +
        '</label>' +
        '<label class="rb-check">' +
          '<input type="checkbox" id="rb-agree-privacy">' +
          '<span class="rb-check__box"></span>' +
          '<span class="rb-check__text">\u0421\u043E\u0433\u043B\u0430\u0441\u0435\u043D(-\u043D\u0430) \u043D\u0430 <a href="/privacy" target="_blank">\u043E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0443 \u043F\u0435\u0440\u0441\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445</a> *</span>' +
        '</label>' +
        '<div class="rb-error-inline" id="rb-consent-err"></div>' +
      '</div>' +
      '<div class="rb-buttons">' +
        '<button class="rb-btn rb-btn--secondary" data-action="prev">\u041D\u0430\u0437\u0430\u0434</button>' +
        '<button class="rb-btn rb-btn--submit" data-action="submit">' +
          '<span class="rb-spinner"></span>' +
          '\u0417\u0430\u0431\u0440\u043E\u043D\u0438\u0440\u043E\u0432\u0430\u0442\u044C' +
        '</button>' +
      '</div>' +
      '</div>';

    /* Success screen */
    var successScreen = '<div class="rb-success" id="rb-success" style="display:none;">' +
      '<div class="rb-success__icon">\u2713</div>' +
      '<h2 class="rb-success__title">\u0411\u0440\u043E\u043D\u0438\u0440\u043E\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u043E!</h2>' +
      '<p class="rb-success__text" id="rb-success-text"></p>' +
      '<div class="rb-success__details" id="rb-success-details"></div>' +
      '</div>';

    /* Toast container */
    var toast = '<div class="rb-toast" id="rb-toast"></div>';

    root.innerHTML = stepsHtml + step1 + step2 + step3 + step4 + successScreen + toast;
    cacheEls();
  }

  function dietaryCheck(value, label) {
    return '<label class="rb-check">' +
      '<input type="checkbox" value="' + value + '" data-dietary>' +
      '<span class="rb-check__box"></span>' +
      '<span class="rb-check__text">' + label + '</span>' +
      '</label>';
  }

  function cacheEls() {
    els.calendar   = root.querySelector('#rb-calendar');
    els.slots      = root.querySelector('#rb-slots');
    els.guestsVal  = root.querySelector('#rb-guests-val');
    els.guestsLbl  = root.querySelector('#rb-guests-label');
    els.allergies  = root.querySelector('#rb-allergies');
    els.name       = root.querySelector('#rb-name');
    els.phone      = root.querySelector('#rb-phone');
    els.email      = root.querySelector('#rb-email');
    els.comment    = root.querySelector('#rb-comment');
    els.agreeTerms = root.querySelector('#rb-agree-terms');
    els.agreePrivacy = root.querySelector('#rb-agree-privacy');
    els.summary    = root.querySelector('#rb-summary');
    els.toast      = root.querySelector('#rb-toast');
  }

  /* ==========================================================
     NAVIGATION
     ========================================================== */
  function goToStep(n) {
    if (n < 1 || n > state.totalSteps) return;
    state.step = n;

    root.querySelectorAll('.rb-step').forEach(function(el) {
      el.classList.toggle('is-visible', +el.dataset.step === n);
    });

    root.querySelectorAll('.rb-steps__item').forEach(function(el) {
      var s = +el.dataset.step;
      el.classList.remove('is-active', 'is-done');
      if (s === n) el.classList.add('is-active');
      else if (s < n) el.classList.add('is-done');
    });

    if (n === 4) renderSummary();
  }

  /* ==========================================================
     CALENDAR
     ========================================================== */
  function renderCalendar() {
    var y = state.calendarYear;
    var m = state.calendarMonth;
    var today = new Date();
    today.setHours(0,0,0,0);

    var firstDay = new Date(y, m, 1).getDay(); // 0=Sun
    var startIdx = (firstDay + 6) % 7; // shift to Mon=0
    var daysInMonth = new Date(y, m + 1, 0).getDate();

    var canPrev = !(y === today.getFullYear() && m === today.getMonth());
    var maxMonth = today.getMonth() + 1;
    var maxYear = today.getFullYear();
    if (maxMonth > 11) { maxMonth = 0; maxYear++; }
    var canNext = !(y === maxYear && m === maxMonth);

    var html = '<div class="rb-calendar__nav">' +
      '<button class="rb-calendar__nav-btn" data-action="cal-prev"' + (canPrev ? '' : ' disabled') + '>&larr;</button>' +
      '<span class="rb-calendar__title">' + MONTHS[m] + ' ' + y + '</span>' +
      '<button class="rb-calendar__nav-btn" data-action="cal-next"' + (canNext ? '' : ' disabled') + '>&rarr;</button>' +
      '</div>';

    html += '<div class="rb-calendar__grid">';
    for (var w = 0; w < 7; w++) {
      html += '<div class="rb-calendar__weekday">' + WEEKDAYS[w] + '</div>';
    }

    for (var e = 0; e < startIdx; e++) {
      html += '<div class="rb-calendar__day is-empty"></div>';
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var dateObj = new Date(y, m, d);
      var key = formatDate(dateObj);
      var isPast = dateObj < today;
      var isAvail = !!state.availableDates[key];
      var isToday = dateObj.getTime() === today.getTime();
      var isSelected = key === state.selectedDate;

      var cls = 'rb-calendar__day';
      if (isPast) cls += ' is-past';
      else if (isAvail) cls += ' is-available';
      else cls += ' is-unavailable';
      if (isToday) cls += ' is-today';
      if (isSelected) cls += ' is-selected';

      html += '<button class="' + cls + '" data-date="' + key + '"' +
        ((!isAvail || isPast) ? ' disabled' : '') + '>' + d + '</button>';
    }

    html += '</div>';
    els.calendar.innerHTML = html;
  }

  function fetchAvailability() {
    els.calendar.innerHTML = '<div class="rb-loading"><div class="rb-loading__spinner"></div>\u0417\u0430\u0433\u0440\u0443\u0437\u043A\u0430...</div>';

    fetch(apiBase + '/get-availability.php?type=' + encodeURIComponent(type))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        state.availableDates = {};
        if (data.ok && data.data && Array.isArray(data.data.dates)) {
          data.data.dates.forEach(function(d) {
            if (d.has_availability) state.availableDates[d.date] = true;
          });
        }
        renderCalendar();
      })
      .catch(function() {
        showToast('\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B\u0435 \u0434\u0430\u0442\u044B');
        renderCalendar();
      });
  }

  function fetchSlots(date) {
    els.slots.style.display = 'block';
    els.slots.innerHTML = '<div class="rb-loading"><div class="rb-loading__spinner"></div>\u0417\u0430\u0433\u0440\u0443\u0437\u043A\u0430 \u0441\u043B\u043E\u0442\u043E\u0432...</div>';

    fetch(apiBase + '/get-availability.php?type=' + encodeURIComponent(type) + '&date=' + encodeURIComponent(date))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        state.slots = (data.ok && data.data) ? (data.data.slots || []) : [];
        renderSlots();
      })
      .catch(function() {
        showToast('\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0441\u043B\u043E\u0442\u044B');
        els.slots.innerHTML = '';
      });
  }

  function renderSlots() {
    if (!state.slots.length) {
      els.slots.innerHTML = '<p style="color:var(--olive);text-align:center">\u041D\u0435\u0442 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B\u0445 \u0441\u043B\u043E\u0442\u043E\u0432 \u043D\u0430 \u044D\u0442\u0443 \u0434\u0430\u0442\u0443</p>';
      return;
    }

    if (type === 'dinner' && state.slots.length === 1) {
      /* Dinner: single event card, auto-select */
      state.selectedSlot = state.slots[0];
      state.maxGuests = state.selectedSlot.available_seats || 10;
      updateGuestsUI();
    }

    var html = '';
    state.slots.forEach(function(slot, idx) {
      var sel = state.selectedSlot && state.selectedSlot.event_id === slot.event_id;
      var seatsLow = slot.available_seats <= 3;
      html += '<div class="rb-slot' + (sel ? ' is-selected' : '') + '" data-slot-idx="' + idx + '">' +
        '<div class="rb-slot__time">' + escHtml(slot.time || '') + '</div>' +
        '<div class="rb-slot__title">' + escHtml(slot.title || '') + '</div>' +
        (slot.description ? '<div class="rb-slot__desc">' + escHtml(slot.description) + '</div>' : '') +
        '<div class="rb-slot__meta">' +
          '<span class="rb-slot__price">' + formatPrice(slot.price_rub) + ' \u20BD / \u0433\u043E\u0441\u0442\u044C</span>' +
          '<span class="rb-slot__seats' + (seatsLow ? ' is-low' : '') + '">\u041C\u0435\u0441\u0442: ' + slot.available_seats + '</span>' +
        '</div>' +
      '</div>';
    });
    els.slots.innerHTML = html;
    updateStep1Next();
  }

  /* ==========================================================
     GUEST COUNTER
     ========================================================== */
  function updateGuestsUI() {
    els.guestsVal.textContent = state.guests;
    var minBtn = root.querySelector('[data-action="guests-minus"]');
    var maxBtn = root.querySelector('[data-action="guests-plus"]');
    if (minBtn) minBtn.disabled = state.guests <= 1;
    if (maxBtn) maxBtn.disabled = state.guests >= state.maxGuests;
    if (els.guestsLbl) {
      els.guestsLbl.textContent = '\u0438\u0437 ' + state.maxGuests + ' \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B\u0445';
    }
  }

  /* ==========================================================
     PHONE MASK
     ========================================================== */
  function applyPhoneMask(input) {
    var val = input.value.replace(/\D/g, '');
    if (val.length === 0) { input.value = ''; return; }

    /* Strip leading 7 or 8 */
    if (val[0] === '7' || val[0] === '8') val = val.substring(1);
    if (val.length > 10) val = val.substring(0, 10);

    var formatted = '+7';
    if (val.length > 0) formatted += '(' + val.substring(0, 3);
    if (val.length >= 3) formatted += ') ';
    if (val.length > 3) formatted += val.substring(3, 6);
    if (val.length > 6) formatted += '-' + val.substring(6, 8);
    if (val.length > 8) formatted += '-' + val.substring(8, 10);

    input.value = formatted;
  }

  /* ==========================================================
     VALIDATION
     ========================================================== */
  function validateStep(n) {
    clearErrors();
    if (n === 1) {
      return !!state.selectedSlot;
    }
    if (n === 2) {
      return state.guests >= 1 && state.guests <= state.maxGuests;
    }
    if (n === 3) {
      var ok = true;
      if (!els.name.value.trim()) {
        showInlineError('rb-name-err', '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u0438\u043C\u044F');
        els.name.classList.add('is-error');
        ok = false;
      }
      var digits = els.phone.value.replace(/\D/g, '');
      if (digits.length < 11) {
        showInlineError('rb-phone-err', '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043A\u043E\u0440\u0440\u0435\u043A\u0442\u043D\u044B\u0439 \u043D\u043E\u043C\u0435\u0440 \u0442\u0435\u043B\u0435\u0444\u043E\u043D\u0430');
        els.phone.classList.add('is-error');
        ok = false;
      }
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRe.test(els.email.value.trim())) {
        showInlineError('rb-email-err', '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043A\u043E\u0440\u0440\u0435\u043A\u0442\u043D\u044B\u0439 email');
        els.email.classList.add('is-error');
        ok = false;
      }
      return ok;
    }
    if (n === 4) {
      if (!els.agreeTerms.checked || !els.agreePrivacy.checked) {
        showInlineError('rb-consent-err', '\u041D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u043F\u0440\u0438\u043D\u044F\u0442\u044C \u0432\u0441\u0435 \u0443\u0441\u043B\u043E\u0432\u0438\u044F');
        return false;
      }
      return true;
    }
    return true;
  }

  function clearErrors() {
    root.querySelectorAll('.rb-error-inline').forEach(function(el) {
      el.classList.remove('is-visible');
      el.textContent = '';
    });
    root.querySelectorAll('.is-error').forEach(function(el) {
      el.classList.remove('is-error');
    });
  }

  function showInlineError(id, msg) {
    var el = root.querySelector('#' + id);
    if (el) { el.textContent = msg; el.classList.add('is-visible'); }
  }

  function updateStep1Next() {
    var btn = root.querySelector('.rb-step[data-step="1"] [data-action="next"]');
    if (btn) btn.disabled = !state.selectedSlot;
  }

  /* ==========================================================
     SUMMARY
     ========================================================== */
  function renderSummary() {
    if (!els.summary || !state.selectedSlot) return;
    var slot = state.selectedSlot;
    var total = (slot.price_rub || 0) * state.guests;

    var html = '<div class="rb-summary__row"><span class="rb-summary__key">\u0421\u043E\u0431\u044B\u0442\u0438\u0435</span><span class="rb-summary__val">' + escHtml(slot.title || '') + '</span></div>' +
      '<div class="rb-summary__row"><span class="rb-summary__key">\u0414\u0430\u0442\u0430</span><span class="rb-summary__val">' + formatDateRu(state.selectedDate) + '</span></div>' +
      '<div class="rb-summary__row"><span class="rb-summary__key">\u0412\u0440\u0435\u043C\u044F</span><span class="rb-summary__val">' + escHtml(slot.time || '') + '</span></div>' +
      '<div class="rb-summary__row"><span class="rb-summary__key">\u0413\u043E\u0441\u0442\u0438</span><span class="rb-summary__val">' + state.guests + '</span></div>' +
      '<div class="rb-summary__row"><span class="rb-summary__key">\u0426\u0435\u043D\u0430 \u0437\u0430 \u0433\u043E\u0441\u0442\u044F</span><span class="rb-summary__val">' + formatPrice(slot.price_rub) + ' \u20BD</span></div>' +
      '<div class="rb-summary__total">' +
        '<span class="rb-summary__total-label">\u0418\u0442\u043E\u0433\u043E</span>' +
        '<span class="rb-summary__total-value">' + formatPrice(total) + ' \u20BD</span>' +
      '</div>';

    els.summary.innerHTML = html;
  }

  /* ==========================================================
     SUBMIT
     ========================================================== */
  function submit() {
    if (state.submitting) return;
    if (!validateStep(4)) return;

    state.submitting = true;
    var btn = root.querySelector('[data-action="submit"]');
    if (btn) btn.classList.add('is-loading');

    /* Collect dietary */
    state.dietary = [];
    root.querySelectorAll('[data-dietary]:checked').forEach(function(cb) {
      state.dietary.push(cb.value);
    });
    if (els.allergies) state.allergies = els.allergies.value.trim();

    state.name    = els.name.value.trim();
    state.phone   = els.phone.value.trim();
    state.email   = els.email.value.trim();
    state.comment = els.comment ? els.comment.value.trim() : '';

    var body = {
      type: type,
      event_id: state.selectedSlot.event_id,
      date: state.selectedDate,
      guests: state.guests,
      dietary: state.dietary,
      allergies: state.allergies,
      name: state.name,
      phone: state.phone,
      email: state.email,
      comment: state.comment,
      agree_terms: els.agreeTerms.checked,
      agree_privacy: els.agreePrivacy.checked
    };

    fetch(apiBase + '/create-booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok && data.data && data.data.confirmed) {
        showSuccess(data.data);
      } else {
        showToast((data.error && data.error.message) || '\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0437\u0434\u0430\u043D\u0438\u0438 \u0431\u0440\u043E\u043D\u0438\u0440\u043E\u0432\u0430\u043D\u0438\u044F');
        state.submitting = false;
        if (btn) btn.classList.remove('is-loading');
      }
    })
    .catch(function() {
      showToast('\u041E\u0448\u0438\u0431\u043A\u0430 \u0441\u0435\u0442\u0438. \u041F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.');
      state.submitting = false;
      if (btn) btn.classList.remove('is-loading');
    });
  }

  /* ==========================================================
     SUCCESS SCREEN
     ========================================================== */
  function showSuccess(data) {
    // Hide steps and stepper
    root.querySelectorAll('.rb-step, .rb-steps').forEach(function(el) {
      el.style.display = 'none';
    });
    var successEl = root.querySelector('#rb-success');
    if (successEl) {
      successEl.style.display = 'block';
      var textEl = root.querySelector('#rb-success-text');
      if (textEl) {
        textEl.textContent = '\u041F\u0438\u0441\u044C\u043C\u043E \u0441 \u043F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435\u043C \u043E\u0442\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u043E \u043D\u0430 ' + escHtml(data.email || '');
      }
      var detailsEl = root.querySelector('#rb-success-details');
      if (detailsEl) {
        detailsEl.innerHTML =
          '<div class="rb-summary__row"><span class="rb-summary__key">\u0421\u043E\u0431\u044B\u0442\u0438\u0435</span><span class="rb-summary__val">' + escHtml(data.event_title || '') + '</span></div>' +
          '<div class="rb-summary__row"><span class="rb-summary__key">\u0414\u0430\u0442\u0430</span><span class="rb-summary__val">' + formatDateRu(data.event_date || '') + '</span></div>' +
          '<div class="rb-summary__row"><span class="rb-summary__key">\u0412\u0440\u0435\u043C\u044F</span><span class="rb-summary__val">' + escHtml(data.event_time || '') + '</span></div>' +
          '<div class="rb-summary__row"><span class="rb-summary__key">\u0413\u043E\u0441\u0442\u0438</span><span class="rb-summary__val">' + (data.guests || '') + '</span></div>' +
          '<div class="rb-summary__row"><span class="rb-summary__key">\u0418\u043C\u044F</span><span class="rb-summary__val">' + escHtml(data.name || '') + '</span></div>';
      }
    }
  }

  /* ==========================================================
     TOAST
     ========================================================== */
  function showToast(msg) {
    if (!els.toast) return;
    els.toast.textContent = msg;
    els.toast.classList.add('is-visible');
    setTimeout(function() {
      els.toast.classList.remove('is-visible');
    }, 4000);
  }

  /* ==========================================================
     HELPERS
     ========================================================== */
  function formatDate(d) {
    var mm = ('0' + (d.getMonth() + 1)).slice(-2);
    var dd = ('0' + d.getDate()).slice(-2);
    return d.getFullYear() + '-' + mm + '-' + dd;
  }

  function formatDateRu(str) {
    if (!str) return '';
    var parts = str.split('-');
    var monthsGen = [
      '\u044F\u043D\u0432\u0430\u0440\u044F','\u0444\u0435\u0432\u0440\u0430\u043B\u044F','\u043C\u0430\u0440\u0442\u0430','\u0430\u043F\u0440\u0435\u043B\u044F','\u043C\u0430\u044F','\u0438\u044E\u043D\u044F',
      '\u0438\u044E\u043B\u044F','\u0430\u0432\u0433\u0443\u0441\u0442\u0430','\u0441\u0435\u043D\u0442\u044F\u0431\u0440\u044F','\u043E\u043A\u0442\u044F\u0431\u0440\u044F','\u043D\u043E\u044F\u0431\u0440\u044F','\u0434\u0435\u043A\u0430\u0431\u0440\u044F'
    ];
    return parseInt(parts[2], 10) + ' ' + monthsGen[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
  }

  function formatPrice(n) {
    if (n == null) return '0';
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00A0');
  }

  function escHtml(s) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(s));
    return div.innerHTML;
  }

  /* ==========================================================
     EVENT DELEGATION
     ========================================================== */
  root.addEventListener('click', function(e) {
    var target = e.target.closest('[data-action]');
    if (!target) {
      /* Slot click */
      var slotEl = e.target.closest('.rb-slot');
      if (slotEl) {
        var idx = +slotEl.dataset.slotIdx;
        state.selectedSlot = state.slots[idx] || null;
        if (state.selectedSlot) {
          state.maxGuests = state.selectedSlot.available_seats || 10;
          if (state.guests > state.maxGuests) state.guests = state.maxGuests;
          updateGuestsUI();
        }
        renderSlots();
        return;
      }

      /* Date click */
      var dayEl = e.target.closest('.rb-calendar__day.is-available');
      if (dayEl && !dayEl.disabled) {
        state.selectedDate = dayEl.dataset.date;
        state.selectedSlot = null;
        renderCalendar();
        fetchSlots(state.selectedDate);
        return;
      }
      return;
    }

    var action = target.dataset.action;
    e.preventDefault();

    if (action === 'next') {
      if (validateStep(state.step)) goToStep(state.step + 1);
    } else if (action === 'prev') {
      goToStep(state.step - 1);
    } else if (action === 'cal-prev') {
      state.calendarMonth--;
      if (state.calendarMonth < 0) { state.calendarMonth = 11; state.calendarYear--; }
      renderCalendar();
    } else if (action === 'cal-next') {
      state.calendarMonth++;
      if (state.calendarMonth > 11) { state.calendarMonth = 0; state.calendarYear++; }
      renderCalendar();
    } else if (action === 'guests-minus') {
      if (state.guests > 1) state.guests--;
      updateGuestsUI();
    } else if (action === 'guests-plus') {
      if (state.guests < state.maxGuests) state.guests++;
      updateGuestsUI();
    } else if (action === 'submit') {
      submit();
    }
  });

  /* Phone mask */
  root.addEventListener('input', function(e) {
    if (e.target.id === 'rb-phone') applyPhoneMask(e.target);
  });

  /* Focus clear error */
  root.addEventListener('focusin', function(e) {
    if (e.target.classList.contains('is-error')) {
      e.target.classList.remove('is-error');
    }
  });

  /* ==========================================================
     INIT
     ========================================================== */
  build();
  fetchAvailability();
  updateGuestsUI();
}
