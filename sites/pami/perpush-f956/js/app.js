const books = [
  {
    id: 'world-catalog',
    title: 'Katalog Perpustakaan Dunia',
    author: 'Global Library Team',
    year: '2026',
    region: 'Global',
    category: 'History',
    pages: 1,
    cover: 'images/cover-world.svg',
    pdf: 'assets/pdfs/katalog-perpustakaan-dunia.pdf',
    description: 'Peta ringkas tentang perpustakaan besar, arsip, dan budaya literasi global.'
  },
  {
    id: 'alexandria',
    title: 'Jejak Perpustakaan Alexandria',
    author: 'Archive Notes',
    year: '2026',
    region: 'Egypt',
    category: 'History',
    pages: 1,
    cover: 'images/cover-alexandria.svg',
    pdf: 'assets/pdfs/alexandria-archive.pdf',
    description: 'Catatan edukatif singkat tentang simbol pengetahuan kuno dan relevansinya.'
  },
  {
    id: 'nusantara',
    title: 'Manuskrip Nusantara',
    author: 'Ruang Aksara',
    year: '2026',
    region: 'Indonesia',
    category: 'Culture',
    pages: 1,
    cover: 'images/cover-nusantara.svg',
    pdf: 'assets/pdfs/manuskrip-nusantara.pdf',
    description: 'Contoh buku pendek tentang arsip, aksara, dan pelestarian lokal.'
  },
  {
    id: 'digital-guide',
    title: 'Panduan Perpustakaan Digital',
    author: 'Static Web Lab',
    year: '2026',
    region: 'Online',
    category: 'Digital',
    pages: 1,
    cover: 'images/cover-digital.svg',
    pdf: 'assets/pdfs/panduan-perpustakaan-digital.pdf',
    description: 'Prinsip sederhana membangun katalog digital ringan dengan HTML, CSS, dan JS.'
  },
  {
    id: 'children-world',
    title: 'Cerita Anak dari Dunia',
    author: 'Open Story Club',
    year: '2026',
    region: 'Global',
    category: 'Children',
    pages: 1,
    cover: 'images/cover-children.svg',
    pdf: 'assets/pdfs/cerita-anak-dunia.pdf',
    description: 'Buku contoh berisi gagasan cerita edukatif lintas budaya untuk pembaca muda.'
  },
  {
    id: 'open-science',
    title: 'Sains Terbuka untuk Pembaca',
    author: 'Open Knowledge Desk',
    year: '2026',
    region: 'Global',
    category: 'Science',
    pages: 1,
    cover: 'images/cover-science.svg',
    pdf: 'assets/pdfs/sains-terbuka.pdf',
    description: 'Ringkasan tentang ilmu terbuka, arsip, akses publik, dan cara membaca kritis.'
  }
];

const state = {
  filter: 'all',
  query: '',
  selectedId: books[0].id
};

const bookGrid = document.querySelector('#bookGrid');
const searchInput = document.querySelector('#searchInput');
const channelButtons = document.querySelectorAll('.channel');
const totalBooks = document.querySelector('#totalBooks');
const readerTitle = document.querySelector('#readerTitle');
const readerMeta = document.querySelector('#readerMeta');
const pdfFrame = document.querySelector('#pdfFrame');
const openPdf = document.querySelector('#openPdf');

totalBooks.textContent = books.length;

function normalize(value) {
  return String(value).toLowerCase().trim();
}

function getVisibleBooks() {
  const query = normalize(state.query);
  return books.filter((book) => {
    const filterMatch = state.filter === 'all' || book.category === state.filter;
    const queryBlob = normalize(`${book.title} ${book.author} ${book.region} ${book.category} ${book.description}`);
    const queryMatch = !query || queryBlob.includes(query);
    return filterMatch && queryMatch;
  });
}

function renderBooks() {
  const visibleBooks = getVisibleBooks();

  if (!visibleBooks.length) {
    bookGrid.innerHTML = `
      <div class="empty-state">
        <h3>Tidak ada buku yang cocok.</h3>
        <p>Coba ganti keyword pencarian atau pilih kategori lain.</p>
      </div>
    `;
    return;
  }

  bookGrid.innerHTML = visibleBooks.map((book) => `
    <article class="book-card ${book.id === state.selectedId ? 'is-selected' : ''}" data-id="${book.id}" tabindex="0" role="button" aria-label="Buka ${book.title}">
      <div class="book-cover">
        <img src="${book.cover}" alt="Cover ${book.title}" loading="lazy" />
      </div>
      <div class="book-body">
        <div class="tag-row">
          <span class="tag">${book.category}</span>
          <span class="tag region">${book.region}</span>
        </div>
        <h3>${book.title}</h3>
        <p>${book.description}</p>
        <div class="book-footer">
          <span>${book.author}</span>
          <span>${book.year}</span>
        </div>
      </div>
    </article>
  `).join('');

  document.querySelectorAll('.book-card').forEach((card) => {
    card.addEventListener('click', () => selectBook(card.dataset.id));
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        selectBook(card.dataset.id);
      }
    });
  });
}

function selectBook(id) {
  const book = books.find((item) => item.id === id) || books[0];
  state.selectedId = book.id;

  readerTitle.textContent = book.title;
  readerMeta.innerHTML = `
    <strong>${book.author}</strong> · ${book.region} · ${book.category}<br>
    ${book.description}<br>
    <span>${book.pages} halaman contoh · file: <code>${book.pdf}</code></span>
  `;
  pdfFrame.src = book.pdf;
  openPdf.href = book.pdf;

  renderBooks();
}

searchInput.addEventListener('input', (event) => {
  state.query = event.target.value;
  renderBooks();
});

channelButtons.forEach((button) => {
  button.addEventListener('click', () => {
    channelButtons.forEach((item) => item.classList.remove('is-active'));
    button.classList.add('is-active');
    state.filter = button.dataset.filter;
    renderBooks();
  });
});

selectBook(state.selectedId);
