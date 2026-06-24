# Global Library - Static PDF Website

Project web statis bertema dashboard seperti Discord untuk perpustakaan digital.

## Isi Folder

- `index.html` - halaman utama
- `css/style.css` - semua styling UI
- `js/app.js` - data buku, search, filter, dan kontrol iframe PDF
- `images/` - logo, hero image, dan cover buku SVG
- `assets/pdfs/` - file PDF contoh yang ditampilkan di iframe

## Cara Menjalankan

1. Extract file ZIP.
2. Buka `index.html` langsung di browser.
3. Klik card buku untuk mengganti PDF di panel reader.

## Cara Mengganti PDF Buku

1. Masukkan PDF baru ke folder `assets/pdfs/`.
2. Buka `js/app.js`.
3. Edit properti `pdf`, `title`, `author`, `category`, `region`, dan `description` pada array `books`.

## Catatan

Konten PDF di project ini adalah sample/dummy buatan untuk template. Jika dipakai publik, gunakan PDF legal: karya sendiri, open license, public domain, atau koleksi yang memang Anda punya hak distribusinya.
