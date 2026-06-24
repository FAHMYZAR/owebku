<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fahmyzzx | Mobile Developer & Freelancer</title>

  <style>
    :root {
      --bg: #070b14;
      --bg-soft: #0d1324;
      --card: rgba(255, 255, 255, 0.075);
      --card-border: rgba(255, 255, 255, 0.13);
      --text: #f8fafc;
      --muted: #94a3b8;
      --primary: #38bdf8;
      --secondary: #818cf8;
      --accent: #22c55e;
      --danger: #fb7185;
      --shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      --radius: 28px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      scroll-behavior: smooth;
    }

    body {
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:
        radial-gradient(circle at top left, rgba(56, 189, 248, 0.22), transparent 34%),
        radial-gradient(circle at top right, rgba(129, 140, 248, 0.2), transparent 32%),
        radial-gradient(circle at bottom, rgba(34, 197, 94, 0.09), transparent 40%),
        var(--bg);
      color: var(--text);
      overflow-x: hidden;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .container {
      width: min(1160px, calc(100% - 40px));
      margin: auto;
    }

    .noise {
      position: fixed;
      inset: 0;
      pointer-events: none;
      opacity: 0.055;
      z-index: 9999;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
    }

    header {
      position: fixed;
      top: 18px;
      left: 0;
      right: 0;
      z-index: 50;
    }

    .nav {
      width: min(1160px, calc(100% - 32px));
      margin: auto;
      padding: 13px 16px;
      border: 1px solid var(--card-border);
      background: rgba(7, 11, 20, 0.72);
      backdrop-filter: blur(18px);
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      letter-spacing: -0.03em;
    }

    .logo {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      box-shadow: 0 10px 30px rgba(56, 189, 248, 0.35);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .nav-links a {
      padding: 10px 15px;
      color: var(--muted);
      border-radius: 999px;
      font-size: 14px;
      transition: 0.25s ease;
    }

    .nav-links a:hover {
      color: var(--text);
      background: rgba(255, 255, 255, 0.09);
    }

    .nav-cta {
      padding: 10px 16px;
      border-radius: 999px;
      background: white;
      color: #0f172a;
      font-weight: 700;
      font-size: 14px;
    }

    .menu-btn {
      display: none;
      background: transparent;
      border: 0;
      color: white;
      font-size: 26px;
      cursor: pointer;
    }

    .hero {
      min-height: 100vh;
      padding: 150px 0 80px;
      display: flex;
      align-items: center;
    }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.08fr 0.92fr;
      align-items: center;
      gap: 56px;
    }

    .badge {
      width: fit-content;
      padding: 9px 13px;
      border-radius: 999px;
      border: 1px solid var(--card-border);
      background: rgba(255, 255, 255, 0.07);
      color: #dbeafe;
      font-size: 14px;
      margin-bottom: 22px;
    }

    .hero h1 {
      font-size: clamp(44px, 7vw, 84px);
      line-height: 0.96;
      letter-spacing: -0.08em;
      margin-bottom: 24px;
    }

    .hero h1 span {
      background: linear-gradient(135deg, #ffffff, #7dd3fc, #a5b4fc);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .hero p {
      color: var(--muted);
      font-size: 18px;
      line-height: 1.8;
      max-width: 660px;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 34px;
    }

    .btn {
      padding: 15px 21px;
      border-radius: 16px;
      font-weight: 800;
      border: 1px solid var(--card-border);
      transition: 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 9px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      box-shadow: 0 18px 45px rgba(56, 189, 248, 0.25);
    }

    .btn-ghost {
      background: rgba(255, 255, 255, 0.06);
      color: white;
    }

    .btn:hover {
      transform: translateY(-3px);
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-top: 42px;
      max-width: 650px;
    }

    .stat {
      padding: 18px;
      border: 1px solid var(--card-border);
      background: var(--card);
      border-radius: 20px;
      backdrop-filter: blur(15px);
    }

    .stat strong {
      display: block;
      font-size: 28px;
      letter-spacing: -0.04em;
    }

    .stat span {
      color: var(--muted);
      font-size: 13px;
    }

    .profile-card {
      position: relative;
      padding: 18px;
      border-radius: 38px;
      background:
        linear-gradient(145deg, rgba(255,255,255,0.16), rgba(255,255,255,0.04)),
        rgba(255,255,255,0.06);
      border: 1px solid var(--card-border);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .profile-card::before {
      content: "";
      position: absolute;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: rgba(56, 189, 248, 0.24);
      filter: blur(35px);
      top: -80px;
      right: -80px;
    }

    .profile-inner {
      position: relative;
      min-height: 520px;
      border-radius: 28px;
      background:
        linear-gradient(160deg, rgba(15, 23, 42, 0.25), rgba(15, 23, 42, 0.92)),
        radial-gradient(circle at top, rgba(56,189,248,0.33), transparent 42%);
      border: 1px solid rgba(255,255,255,0.12);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 28px;
    }

    .avatar {
      position: absolute;
      inset: 30px 30px 150px 30px;
      border-radius: 28px;
      background:
        linear-gradient(135deg, rgba(56,189,248,0.22), rgba(129,140,248,0.24)),
        linear-gradient(180deg, #111827, #020617);
      display: grid;
      place-items: center;
      overflow: hidden;
    }

    .avatar::before {
      content: "FN";
      width: 180px;
      height: 180px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-size: 58px;
      font-weight: 900;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      box-shadow: 0 30px 70px rgba(56,189,248,0.25);
    }

    .floating-chip {
      position: absolute;
      right: 28px;
      top: 28px;
      padding: 11px 13px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.75);
      border: 1px solid rgba(255,255,255,0.14);
      backdrop-filter: blur(12px);
      color: #bbf7d0;
      font-size: 13px;
    }

    .profile-info {
      position: relative;
      z-index: 2;
    }

    .profile-info h3 {
      font-size: 28px;
      letter-spacing: -0.04em;
      margin-bottom: 8px;
    }

    .profile-info p {
      color: var(--muted);
      line-height: 1.6;
    }

    .section {
      padding: 92px 0;
    }

    .section-head {
      margin-bottom: 42px;
      max-width: 720px;
    }

    .eyebrow {
      color: var(--primary);
      font-weight: 800;
      margin-bottom: 10px;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.16em;
    }

    .section h2 {
      font-size: clamp(32px, 5vw, 54px);
      letter-spacing: -0.06em;
      line-height: 1.05;
      margin-bottom: 16px;
    }

    .section-head p {
      color: var(--muted);
      line-height: 1.8;
      font-size: 17px;
    }

    .about-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 22px;
    }

    .glass-card {
      border: 1px solid var(--card-border);
      background: var(--card);
      backdrop-filter: blur(18px);
      border-radius: var(--radius);
      padding: 26px;
      box-shadow: 0 20px 65px rgba(0,0,0,0.18);
    }

    .glass-card h3 {
      font-size: 22px;
      margin-bottom: 14px;
      letter-spacing: -0.03em;
    }

    .glass-card p {
      color: var(--muted);
      line-height: 1.8;
    }

    .skill {
      margin-top: 18px;
    }

    .skill-top {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      color: #e2e8f0;
      font-weight: 700;
      font-size: 14px;
    }

    .bar {
      height: 10px;
      background: rgba(255,255,255,0.09);
      border-radius: 999px;
      overflow: hidden;
    }

    .bar span {
      display: block;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .service-grid,
    .portfolio-grid,
    .blog-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
    }

    .service-card,
    .project-card,
    .blog-card {
      position: relative;
      overflow: hidden;
      border: 1px solid var(--card-border);
      background: rgba(255,255,255,0.07);
      border-radius: var(--radius);
      padding: 24px;
      transition: 0.3s ease;
    }

    .service-card:hover,
    .project-card:hover,
    .blog-card:hover {
      transform: translateY(-8px);
      background: rgba(255,255,255,0.1);
    }

    .icon {
      width: 50px;
      height: 50px;
      display: grid;
      place-items: center;
      border-radius: 18px;
      background: linear-gradient(135deg, rgba(56,189,248,0.25), rgba(129,140,248,0.25));
      border: 1px solid rgba(255,255,255,0.12);
      margin-bottom: 18px;
      font-size: 24px;
    }

    .service-card h3,
    .project-card h3,
    .blog-card h3 {
      margin-bottom: 10px;
      font-size: 20px;
      letter-spacing: -0.03em;
    }

    .service-card p,
    .project-card p,
    .blog-card p {
      color: var(--muted);
      line-height: 1.75;
      font-size: 15px;
    }

    .project-preview {
      height: 190px;
      border-radius: 22px;
      margin-bottom: 18px;
      background:
        radial-gradient(circle at 30% 20%, rgba(56,189,248,0.8), transparent 24%),
        radial-gradient(circle at 80% 35%, rgba(129,140,248,0.85), transparent 25%),
        linear-gradient(145deg, #0f172a, #020617);
      border: 1px solid rgba(255,255,255,0.13);
      display: flex;
      align-items: flex-end;
      padding: 16px;
    }

    .preview-window {
      width: 100%;
      height: 105px;
      border-radius: 16px;
      background: rgba(2, 6, 23, 0.82);
      border: 1px solid rgba(255,255,255,0.15);
      padding: 13px;
    }

    .dot-row {
      display: flex;
      gap: 7px;
      margin-bottom: 14px;
    }

    .dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: rgba(255,255,255,0.28);
    }

    .line {
      height: 9px;
      border-radius: 999px;
      background: rgba(255,255,255,0.15);
      margin-bottom: 9px;
    }

    .line.short {
      width: 62%;
    }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 16px;
    }

    .tag {
      font-size: 12px;
      color: #dbeafe;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(56,189,248,0.12);
      border: 1px solid rgba(56,189,248,0.2);
    }

    .contact {
      border-radius: 38px;
      padding: 44px;
      background:
        linear-gradient(135deg, rgba(56,189,248,0.18), rgba(129,140,248,0.18)),
        rgba(255,255,255,0.07);
      border: 1px solid var(--card-border);
      display: grid;
      grid-template-columns: 1fr 0.85fr;
      gap: 30px;
      align-items: center;
    }

    .contact p {
      color: var(--muted);
      line-height: 1.8;
      margin-top: 12px;
    }

    .contact-box {
      display: grid;
      gap: 12px;
    }

    .contact-item {
      padding: 16px;
      border-radius: 18px;
      background: rgba(2, 6, 23, 0.35);
      border: 1px solid rgba(255,255,255,0.12);
      color: #e2e8f0;
    }

    footer {
      padding: 42px 0;
      border-top: 1px solid rgba(255,255,255,0.08);
      color: var(--muted);
    }

    .footer-grid {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }

    .socials {
      display: flex;
      gap: 10px;
    }

    .socials a {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      transition: 0.25s ease;
    }

    .socials a:hover {
      transform: translateY(-3px);
      color: white;
      background: rgba(56,189,248,0.18);
    }

    .reveal {
      opacity: 0;
      transform: translateY(24px);
      transition: 0.7s ease;
    }

    .reveal.show {
      opacity: 1;
      transform: translateY(0);
    }

    @media (max-width: 920px) {
      .hero-grid,
      .about-grid,
      .contact {
        grid-template-columns: 1fr;
      }

      .service-grid,
      .portfolio-grid,
      .blog-grid {
        grid-template-columns: 1fr 1fr;
      }

      .hero {
        padding-top: 130px;
      }

      .profile-inner {
        min-height: 470px;
      }
    }

    @media (max-width: 720px) {
      .container {
        width: min(100% - 28px, 1160px);
      }

      .nav {
        border-radius: 24px;
      }

      .menu-btn {
        display: block;
      }

      .nav-links {
        position: absolute;
        top: 74px;
        left: 16px;
        right: 16px;
        display: none;
        flex-direction: column;
        padding: 14px;
        border-radius: 24px;
        background: rgba(7, 11, 20, 0.94);
        border: 1px solid var(--card-border);
        backdrop-filter: blur(18px);
      }

      .nav-links.active {
        display: flex;
      }

      .nav-links a {
        width: 100%;
        text-align: center;
      }

      .nav-cta {
        display: none;
      }

      .stats,
      .service-grid,
      .portfolio-grid,
      .blog-grid {
        grid-template-columns: 1fr;
      }

      .hero h1 {
        font-size: 48px;
      }

      .hero p {
        font-size: 16px;
      }

      .contact {
        padding: 28px;
      }

      .profile-inner {
        min-height: 430px;
        padding: 22px;
      }

      .avatar {
        inset: 22px 22px 145px 22px;
      }
    }
  </style>
</head>

<body>
  <div class="noise"></div>

  <header>
    <nav class="nav">
      <a href="#home" class="brand">
        <span class="logo">F</span>
        <span>@fahmyzzx</span>
      </a>

      <button class="menu-btn" id="menuBtn">☰</button>

      <div class="nav-links" id="navLinks">
        <a href="#home">Home</a>
        <a href="#tentang">Tentang</a>
        <a href="#layanan">Layanan</a>
        <a href="#galeri">Galeri</a>
        <a href="#blog">Blog</a>
        <a href="#kontak">Kontak</a>
      </div>

      <a href="#kontak" class="nav-cta">Kolaborasi</a>
    </nav>
  </header>

  <main>
    <section class="hero" id="home">
      <div class="container hero-grid">
        <div class="reveal">
          <div class="badge">👋 Hallo Semua, saya</div>
          <h1>
            Nuriskha<br />
            <span>Ainun Fahmi</span>
          </h1>
          <p>
            Freelancer dan Mobile Developer yang fokus membangun aplikasi modern,
            cepat, rapi, dan mudah digunakan. Saya suka mengubah ide menjadi produk
            digital yang nyata, fungsional, dan punya pengalaman pengguna yang nyaman.
          </p>

          <div class="hero-actions">
            <a href="#galeri" class="btn btn-primary">Lihat Project 🚀</a>
            <a href="#blog" class="btn btn-ghost">Baca Artikel</a>
          </div>

          <div class="stats">
            <div class="stat">
              <strong>4+</strong>
              <span>Tahun Belajar & Berkarya</span>
            </div>
            <div class="stat">
              <strong>20+</strong>
              <span>Eksperimen Project</span>
            </div>
            <div class="stat">
              <strong>100%</strong>
              <span>Semangat Ngoding</span>
            </div>
          </div>
        </div>

        <div class="profile-card reveal">
          <div class="profile-inner">
            <div class="avatar"></div>
            <div class="floating-chip">● Available for project</div>

            <div class="profile-info">
              <h3>Mobile Developer</h3>
              <p>
                Android, Flutter, Backend, UI/UX, dan eksplorasi teknologi modern
                untuk membangun aplikasi yang stabil dan scalable.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="tentang">
      <div class="container">
        <div class="section-head reveal">
          <div class="eyebrow">Tentang Saya</div>
          <h2>Developer yang belajar cepat, berpikir sistematis, dan suka membangun produk.</h2>
          <p>
            Saya percaya teknologi bukan sekadar kode, tetapi alat untuk menyelesaikan
            masalah. Karena itu, setiap aplikasi harus punya alur yang jelas, tampilan
            nyaman, dan fondasi teknis yang kuat.
          </p>
        </div>

        <div class="about-grid">
          <div class="glass-card reveal">
            <h3>Fokus Pengembangan</h3>
            <p>
              Saat ini saya berfokus pada mobile development, web development,
              backend sederhana, integrasi API, dan eksplorasi sistem yang bisa
              digunakan secara nyata oleh pengguna.
            </p>
          </div>

          <div class="glass-card reveal">
            <h3>Keahlian & Pengalaman</h3>

            <div class="skill">
              <div class="skill-top">
                <span>Mobile Development</span>
                <span>90%</span>
              </div>
              <div class="bar"><span style="width: 90%"></span></div>
            </div>

            <div class="skill">
              <div class="skill-top">
                <span>Frontend Development</span>
                <span>80%</span>
              </div>
              <div class="bar"><span style="width: 80%"></span></div>
            </div>

            <div class="skill">
              <div class="skill-top">
                <span>Backend Development</span>
                <span>85%</span>
              </div>
              <div class="bar"><span style="width: 85%"></span></div>
            </div>

            <div class="skill">
              <div class="skill-top">
                <span>UI/UX Design</span>
                <span>75%</span>
              </div>
              <div class="bar"><span style="width: 75%"></span></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="layanan">
      <div class="container">
        <div class="section-head reveal">
          <div class="eyebrow">Layanan</div>
          <h2>Apa yang bisa saya bantu?</h2>
          <p>
            Dari ide awal sampai tampilan akhir, saya bisa membantu membuat pondasi
            produk digital yang rapi, modern, dan siap dikembangkan lebih lanjut.
          </p>
        </div>

        <div class="service-grid">
          <div class="service-card reveal">
            <div class="icon">📱</div>
            <h3>Mobile App</h3>
            <p>Membangun aplikasi Android atau cross-platform dengan alur yang jelas dan tampilan modern.</p>
          </div>

          <div class="service-card reveal">
            <div class="icon">🌐</div>
            <h3>Website</h3>
            <p>Membuat landing page, dashboard, company profile, atau web app dengan UI responsif.</p>
          </div>

          <div class="service-card reveal">
            <div class="icon">⚙️</div>
            <h3>Backend & API</h3>
            <p>Menyusun endpoint, database, authentication, dan logic aplikasi secara terstruktur.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="galeri">
      <div class="container">
        <div class="section-head reveal">
          <div class="eyebrow">Portfolio</div>
          <h2>Galeri Project Saya</h2>
          <p>
            Beberapa contoh kategori project yang bisa ditampilkan di portfolio ini.
            Kamu tinggal ganti judul, deskripsi, dan link sesuai project asli.
          </p>
        </div>

        <div class="portfolio-grid">
          <div class="project-card reveal">
            <div class="project-preview">
              <div class="preview-window">
                <div class="dot-row">
                  <span class="dot"></span>
                  <span class="dot"></span>
                  <span class="dot"></span>
                </div>
                <div class="line"></div>
                <div class="line short"></div>
                <div class="line"></div>
              </div>
            </div>
            <h3>Aplikasi Mobile</h3>
            <p>Prototype aplikasi mobile dengan halaman login, dashboard, profile, dan integrasi API.</p>
            <div class="tags">
              <span class="tag">Flutter</span>
              <span class="tag">Android</span>
              <span class="tag">API</span>
            </div>
          </div>

          <div class="project-card reveal">
            <div class="project-preview">
              <div class="preview-window">
                <div class="dot-row">
                  <span class="dot"></span>
                  <span class="dot"></span>
                  <span class="dot"></span>
                </div>
                <div class="line short"></div>
                <div class="line"></div>
                <div class="line short"></div>
              </div>
            </div>
            <h3>Dashboard Admin</h3>
            <p>Panel admin modern untuk mengelola data, transaksi, user, laporan, dan statistik.</p>
            <div class="tags">
              <span class="tag">PHP</span>
              <span class="tag">MySQL</span>
              <span class="tag">Dashboard</span>
            </div>
          </div>

          <div class="project-card reveal">
            <div class="project-preview">
              <div class="preview-window">
                <div class="dot-row">
                  <span class="dot"></span>
                  <span class="dot"></span>
                  <span class="dot"></span>
                </div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line short"></div>
              </div>
            </div>
            <h3>Landing Page</h3>
            <p>Website single-page elegant untuk personal branding, produk digital, atau bisnis kecil.</p>
            <div class="tags">
              <span class="tag">HTML</span>
              <span class="tag">CSS</span>
              <span class="tag">JS</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="blog">
      <div class="container">
        <div class="section-head reveal">
          <div class="eyebrow">Blog</div>
          <h2>Artikel Terbaru</h2>
          <p>
            Area ini bisa kamu pakai untuk menulis catatan belajar, pengalaman project,
            tutorial singkat, atau opini tentang teknologi.
          </p>
        </div>

        <div class="blog-grid">
          <article class="blog-card reveal">
            <div class="icon">🧠</div>
            <h3>Cara Belajar Coding Lebih Terarah</h3>
            <p>Mulai dari memahami problem, membuat roadmap kecil, lalu membangun project nyata.</p>
            <div class="tags">
              <span class="tag">Learning</span>
              <span class="tag">Programming</span>
            </div>
          </article>

          <article class="blog-card reveal">
            <div class="icon">🛠️</div>
            <h3>Membangun Project dari Nol</h3>
            <p>Kenapa struktur folder, flow aplikasi, dan dokumentasi penting sejak awal project.</p>
            <div class="tags">
              <span class="tag">Project</span>
              <span class="tag">Software</span>
            </div>
          </article>

          <article class="blog-card reveal">
            <div class="icon">🚀</div>
            <h3>Menjadi Fullstack Developer</h3>
            <p>Fullstack bukan harus tahu semuanya, tapi paham cara menyambungkan banyak bagian sistem.</p>
            <div class="tags">
              <span class="tag">Fullstack</span>
              <span class="tag">Career</span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="kontak">
      <div class="container">
        <div class="contact reveal">
          <div>
            <div class="eyebrow">Kontak</div>
            <h2>Mari berkolaborasi membangun sesuatu yang keren.</h2>
            <p>
              Ada ide aplikasi, website, dashboard, atau project digital?
              Kirim pesan dan kita bisa bahas kebutuhan, alur, fitur, serta teknologinya.
            </p>

            <div class="hero-actions">
              <a href="mailto:hello@fahmyzzx.my.id" class="btn btn-primary">Kirim Email ✉️</a>
              <a href="#" class="btn btn-ghost">Telegram</a>
            </div>
          </div>

          <div class="contact-box">
            <div class="contact-item">📍 Indonesia</div>
            <div class="contact-item">💼 Freelancer & Mobile Developer</div>
            <div class="contact-item">⚡ Available for remote project</div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container footer-grid">
      <div>
        <strong style="color:white;">@fahmyzzx</strong>
        <p>© 2026 Fahmyzzx. Dibuat dengan semangat belajar dan berkarya.</p>
      </div>

      <div class="socials">
        <a href="#" aria-label="Telegram">T</a>
        <a href="#" aria-label="Instagram">I</a>
        <a href="#" aria-label="GitHub">G</a>
        <a href="#" aria-label="LinkedIn">L</a>
      </div>
    </div>
  </footer>

  <script>
    const menuBtn = document.getElementById("menuBtn");
    const navLinks = document.getElementById("navLinks");

    menuBtn.addEventListener("click", () => {
      navLinks.classList.toggle("active");
      menuBtn.textContent = navLinks.classList.contains("active") ? "×" : "☰";
    });

    document.querySelectorAll(".nav-links a").forEach((link) => {
      link.addEventListener("click", () => {
        navLinks.classList.remove("active");
        menuBtn.textContent = "☰";
      });
    });

    const reveals = document.querySelectorAll(".reveal");

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("show");
          }
        });
      },
      {
        threshold: 0.14
      }
    );

    reveals.forEach((el) => observer.observe(el));

    window.addEventListener("scroll", () => {
      const header = document.querySelector(".nav");
      if (window.scrollY > 40) {
        header.style.background = "rgba(7, 11, 20, 0.88)";
      } else {
        header.style.background = "rgba(7, 11, 20, 0.72)";
      }
    });
  </script>
</body>
</html>