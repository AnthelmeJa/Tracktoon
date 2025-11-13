document.addEventListener('DOMContentLoaded', function () {
  /* ===== FAQ ===== */
  const items = document.querySelectorAll('.faq-item');

  items.forEach((item) => {
    const btn = item.querySelector('.faq-question');
    const panel = item.querySelector('.faq-answer');

    btn.addEventListener('click', function () {
      const isOpen = btn.getAttribute('aria-expanded') === 'true';

      // close
      items.forEach((other) => {
        if (other !== item) {
          const obtn = other.querySelector('.faq-question');
          const opanel = other.querySelector('.faq-answer');
          obtn.setAttribute('aria-expanded', 'false');
          opanel.style.maxHeight = 0;
          opanel.hidden = true;
        }
      });

      // open / fermer le bon
      if (!isOpen) {
        btn.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        panel.style.maxHeight = panel.scrollHeight + 'px';
      } else {
        btn.setAttribute('aria-expanded', 'false');
        panel.style.maxHeight = 0;
        setTimeout(() => {
          if (btn.getAttribute('aria-expanded') === 'false') {
            panel.hidden = true;
          }
        }, 250);
      }
    });
  });

  /* ===== MENU PARAMÈTRES HEADER ===== */
  const toggle = document.querySelector('.settings-toggle');
  const menu   = document.querySelector('.settings-menu');
  const BODY   = document.body;

  // on va s'en servir plus bas
  function setDarkLabel() {
    const btn = menu?.querySelector('[data-action="dark"]');
    if (!btn) return;
    const span = btn.querySelector('span');
    const icon = btn.querySelector('i');
    if (BODY.classList.contains('theme-dark')) {
      if (span) span.textContent = 'Clair';
      if (icon) icon.className = 'fa-solid fa-sun';
    } else {
      if (span) span.textContent = 'Sombre';
      if (icon) icon.className = 'fa-solid fa-moon';
    }
  }
  function setDysLabel() {
    const btn = menu?.querySelector('[data-action="dys"]');
    if (!btn) return;
    const span = btn.querySelector('span');
    if (BODY.classList.contains('theme-dys')) {
      if (span) span.textContent = 'Police normale';
    } else {
      if (span) span.textContent = 'Dyslexie';
    }
  }

  // restaurer préférences au chargement
  const savedTheme = localStorage.getItem('tt_theme');
  if (savedTheme === 'dark') {
    BODY.classList.add('theme-dark');
  }
  const savedDys = localStorage.getItem('tt_dys');
  if (savedDys === 'on') {
    BODY.classList.add('theme-dys');
  }

  setDarkLabel();
  setDysLabel();

  if (toggle && menu) {
    // ouvrir/fermer
    toggle.addEventListener('click', () => {
      const isOpen = menu.classList.contains('is-open');
      toggle.setAttribute('aria-expanded', String(!isOpen));
      menu.classList.toggle('is-open', !isOpen);
    });

    // clic dehors
    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !toggle.contains(e.target)) {
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
      }
    });

    // actions du menu
    menu.addEventListener('click', (e) => {
      const btn = e.target.closest('.settings-item');
      if (!btn) return;

      const action = btn.dataset.action;

      if (action === 'dark') {
        const willBeDark = !BODY.classList.contains('theme-dark');
        BODY.classList.toggle('theme-dark', willBeDark);
        localStorage.setItem('tt_theme', willBeDark ? 'dark' : 'light');
        setDarkLabel();
      }

      if (action === 'dys') {
        const willBeDys = !BODY.classList.contains('theme-dys');
        BODY.classList.toggle('theme-dys', willBeDys);
        localStorage.setItem('tt_dys', willBeDys ? 'on' : 'off');
        setDysLabel();
      }

      // fermer le menu
      toggle.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
    });
  }

  /* ===== RATING INTERACTIF (page book_user) ===== */
  const starContainers = document.querySelectorAll('.js-star-rating');

  starContainers.forEach((container) => {
    const parent = container.closest('.book-tools__right');
    if (!parent) return;

    const endpoint = parent.dataset.rateEndpoint;
    const bookId   = parent.dataset.bookId;
    const csrf     = parent.dataset.csrf;
    const buttons  = container.querySelectorAll('.star-btn');
    const feedback = parent.querySelector('.star-feedback');

    function setVisual(score) {
      buttons.forEach((btn) => {
        const btnScore = Number(btn.dataset.score);
        btn.classList.toggle('is-filled', btnScore <= score);
      });
    }

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const score = Number(btn.dataset.score);
        setVisual(score);

        if (!endpoint) return;

        fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          },
          body: new URLSearchParams({
            csrf_token: csrf,
            book_id: bookId,
            score: score
          })
        })
          .then((res) => {
            if (!res.ok) throw new Error('Erreur serveur');
            return res.text();
          })
          .then(() => {
            if (feedback) {
              feedback.textContent = 'Note enregistrée.';
              feedback.classList.remove('u-visually-hidden');
              setTimeout(() => {
                feedback.classList.add('u-visually-hidden');
              }, 2500);
            }
          })
          .catch(() => {
            if (feedback) {
              feedback.textContent = 'Impossible d’enregistrer la note.';
              feedback.classList.remove('u-visually-hidden');
              setTimeout(() => {
                feedback.classList.add('u-visually-hidden');
              }, 3000);
            }
          });
      });
    });
  });

  /* ===== AUTO-SAVE STATUT / FAVORI (book_user) ===== */
  const leftBlocks = document.querySelectorAll('.book-tools__left[data-save-endpoint]');

  leftBlocks.forEach((block) => {
    const form     = block.querySelector('.book-tools__form');
    const select   = block.querySelector('select[name="statut"]');
    const checkbox = block.querySelector('input[name="favori"]');
    const feedback = block.querySelector('.bt-feedback');

    const endpoint = block.dataset.saveEndpoint;
    const bookId   = block.dataset.bookId;
    const csrf     = block.dataset.csrf;

    if (!form || !endpoint) return;

    function showMsg(text, isError = false) {
      if (!feedback) return;
      feedback.textContent = text;
      feedback.classList.remove('u-visually-hidden');
      feedback.style.color = isError ? '#B00020' : '#0B7A0B';
      setTimeout(() => {
        feedback.classList.add('u-visually-hidden');
      }, 2000);
    }

    function sendSave() {
      const statut = select ? select.value : '';
      const favori = checkbox && checkbox.checked ? '1' : '';

      fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: new URLSearchParams({
          csrf_token: csrf,
          book_id: bookId,
          statut: statut,
          favori: favori
        })
      })
        .then((res) => {
          if (!res.ok) throw new Error('Erreur serveur');
          return res.text();
        })
        .then(() => {
          showMsg('Enregistré');
        })
        .catch(() => {
          showMsg('Erreur enregistrement', true);
        });
    }

    if (select) {
      select.addEventListener('change', sendSave);
    }
    if (checkbox) {
      checkbox.addEventListener('change', sendSave);
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      sendSave();
    });
  });
});