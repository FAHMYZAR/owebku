<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found | Owebku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['IBM Plex Sans', 'Helvetica Neue', 'Arial', 'sans-serif'],
                        mono: ['IBM Plex Mono', 'monospace']
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-[#f4f4f4] text-[#161616] min-h-screen grid place-items-center p-4">

    <!-- Main -->
    <main class="w-full max-w-[1000px]">

        <!-- Error Card -->
        <section class="border border-[#e0e0e0] bg-white px-8 py-10 md:px-10 md:py-12 shadow-sm">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">

                <!-- Left Content -->
                <div class="flex-1">

                    <p class="font-mono text-[#0f62fe] text-xs font-bold tracking-[0.35em] mb-4">
                        ERROR PAGE
                    </p>

                    <h1 class="text-[72px] md:text-[96px] leading-none font-bold tracking-[-0.05em]">
                        404
                    </h1>

                    <h2 class="mt-5 text-2xl md:text-3xl font-semibold">
                        Halaman tidak ditemukan
                    </h2>

                    <p class="mt-3 max-w-xl text-[15px] leading-7 text-[#525252]">
                        Halaman yang kamu cari mungkin sudah dipindahkan, dihapus,
                        atau URL yang dimasukkan tidak sesuai.
                    </p> 

                </div>

                <!-- Right Info Box -->
                <div class="w-full lg:w-[360px] border border-[#e0e0e0] p-6 shrink-0">

                    <p class="font-mono text-xs font-bold tracking-[0.35em] text-[#393939] mb-6">
                        REQUEST INFO
                    </p>

                    <div class="space-y-5 text-sm">

                        <div class="flex items-start justify-between gap-4 border-b border-[#e0e0e0] pb-4">
                            <span class="text-[#525252]">Status</span>
                            <span class="font-semibold text-red-600">Not Found</span>
                        </div>

                        <div class="flex items-start justify-between gap-4 border-b border-[#e0e0e0] pb-4">
                            <span class="text-[#525252]">Code</span>
                            <span class="font-mono font-semibold">404</span>
                        </div>

                        <div class="flex items-start justify-between gap-4 border-b border-[#e0e0e0] pb-4">
                            <span class="text-[#525252]">System</span>
                            <span class="font-semibold">Owebku</span>
                        </div>

                        <div>
                            <span class="text-[#525252] block mb-2">Suggestion</span>
                            <p class="text-[#161616] leading-6">
                                Periksa kembali ejaan URL atau gunakan tombol di atas untuk kembali.
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>