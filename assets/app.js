(() => {
  'use strict';
  const sidebar = document.getElementById('appSidebar');
  const menu = document.getElementById('menuToggle');
  const overlay = document.querySelector('.nav-overlay');
  const mobile = window.matchMedia('(max-width: 760px)');
  function setMenu(open, returnFocus = false) {
    if (!sidebar || !menu || !overlay) return;
    const expanded = mobile.matches && open;
    document.body.classList.toggle('menu-open', expanded);
    menu.setAttribute('aria-expanded', String(expanded));
    menu.setAttribute('aria-label', expanded ? 'Cerrar menú' : 'Abrir menú');
    sidebar.inert = mobile.matches && !expanded;
    overlay.hidden = !expanded;
    const workspace = document.querySelector('.workspace');
    if (workspace) workspace.inert = expanded;
    if (expanded) sidebar.querySelector('button, a')?.focus();
    else if (returnFocus) menu.focus();
  }
  menu?.addEventListener('click', () => setMenu(menu.getAttribute('aria-expanded') !== 'true'));
  document.querySelectorAll('[data-menu-close]').forEach(button => button.addEventListener('click', () => setMenu(false, true)));
  mobile.addEventListener('change', () => setMenu(false));
  document.body.classList.add('js-ready');
  setMenu(false);

  const dialog = document.getElementById('shortcutsDialog');
  const openShortcuts = document.getElementById('openShortcuts');
  openShortcuts?.addEventListener('click', () => dialog?.showModal());
  document.getElementById('closeShortcuts')?.addEventListener('click', () => dialog?.close());
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('menu-open')) setMenu(false, true);
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && dialog) {
      event.preventDefault();
      if (dialog.open) dialog.close();
      else dialog.showModal();
    }
  });

  const grid = document.getElementById('bookGrid');
  if (grid) {
    const cards = [...grid.querySelectorAll('.book-card')];
    const search = document.getElementById('bookSearch');
    const type = document.getElementById('bookType');
    const sort = document.getElementById('bookSort');
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    // Capture only book information; button labels must not affect the search.
    const searchable = new Map(cards.map(card => [card, normalize([
      card.querySelector('h3').textContent,
      card.querySelector('.eyebrow').textContent,
      card.querySelector('.muted').textContent,
    ].join(' '))]));
    function filterBooks() {
      const words = normalize(search.value).split(/\s+/).filter(Boolean);
      let count = 0;
      cards.forEach(card => {
        card.hidden = (type.value !== '' && card.dataset.type !== type.value) || !words.every(word => searchable.get(card).includes(word));
        if (!card.hidden) count++;
      });
      [...cards].sort((a, b) => {
        if (sort.value === 'number') return a.dataset.number.localeCompare(b.dataset.number, 'es', {numeric: true});
        if (sort.value === 'records') return Number(b.dataset.records) - Number(a.dataset.records);
        const year = Number(a.dataset.year) - Number(b.dataset.year);
        return sort.value === 'oldest' ? year : -year;
      }).forEach(card => grid.appendChild(card));
      document.getElementById('bookCount').textContent = `${count} de ${cards.length} libros`;
      document.getElementById('noMatchingBooks').hidden = count > 0 || cards.length === 0;
    }
    search.addEventListener('input', filterBooks);
    type.addEventListener('change', filterBooks);
    sort.addEventListener('change', filterBooks);
    document.getElementById('resetBooks').addEventListener('click', () => {
      search.value = ''; type.value = ''; sort.value = 'recent'; filterBooks(); search.focus();
    });
    document.getElementById('bookFilters').hidden = false;
    filterBooks();
  }
})();
