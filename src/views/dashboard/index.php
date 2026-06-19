<?php
$createUrl = site_url('projects/create');
$deleteUrl = site_url('projects/delete');
$renameUrl = site_url('projects/rename');
?>
<div id="wd-dashboard" data-create-url="<?php echo e($createUrl); ?>" data-delete-url="<?php echo e($deleteUrl); ?>" data-rename-url="<?php echo e($renameUrl); ?>" class="space-y-6 bg-white text-[#161616]">
    <section class="border border-[#e0e0e0] bg-white px-5 py-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-2">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f62fe]">Dashboard</p>
                <h1 class="text-2xl font-semibold tracking-[-0.32px] text-[#161616] sm:text-3xl">Selamat datang, <?php echo e($auth_user['username']); ?> 👋</h1>
                <p class="max-w-2xl text-sm text-[#525252]">Kelola project statis, buka editor, dan publish hasil kerja.</p>
            </div>
        </div>
        <?php
            $usageMb = ($account_usage_bytes ?? 0) / (1024 * 1024);
            $limitMb = 100;
            $percent = min(100, max(0, ($usageMb / $limitMb) * 100));
            $isNearLimit = $percent > 90;
        ?>
        <div class="mt-6 border-t border-[#e0e0e0] pt-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-[#161616]">Account Storage</span>
                <span class="text-[#525252]"><?php echo number_format($usageMb, 2); ?> MB / <?php echo $limitMb; ?> MB</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden bg-[#e0e0e0]">
                <div class="h-full <?php echo $isNearLimit ? 'bg-[#da1e28]' : 'bg-[#0f62fe]'; ?>" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-8">
            <section class="grid gap-4 sm:grid-cols-2">
                <div class="border border-[#e0e0e0] bg-white p-5 sm:col-span-2">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#525252]">Total Project</p>
                    <p class="mt-2 text-3xl font-semibold text-[#161616]"><?php echo count($projects); ?></p>
                </div>
                <div class="border border-[#e0e0e0] bg-white p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#525252]">Published</p>
                    <p class="mt-2 text-3xl font-semibold text-[#161616]"><?php echo count(array_filter($projects, fn($project) => ($project['status'] ?? 'draft') === 'published')); ?></p>
                </div>
                <div class="border border-[#e0e0e0] bg-white p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#525252]">Draft</p>
                    <p class="mt-2 text-3xl font-semibold text-[#161616]"><?php echo count(array_filter($projects, fn($project) => ($project['status'] ?? 'draft') === 'draft')); ?></p>
                </div>
            </section>
        </div>

        <div class="lg:col-span-4">
            <section class="border border-[#e0e0e0] bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-[#525252]">Recent Activity</h2>
                <?php if (!empty($activities)): ?>
                    <div class="mt-4 divide-y divide-[#e0e0e0]">
                        <?php foreach ($activities as $activity): ?>
                            <div class="py-3">
                                <p class="text-sm font-semibold text-[#161616]"><?php echo e($activity['action']); ?> <span class="font-normal text-[#525252]"><?php echo e($activity['project_name'] ?? 'Project'); ?></span></p>
                                <div class="mt-1 flex items-center justify-between gap-4">
                                    <p class="truncate text-sm text-[#525252]"><?php echo e($activity['description'] ?? '-'); ?></p>
                                    <span class="shrink-0 text-xs text-[#8d8d8d]"><?php echo e(format_datetime_id($activity['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo site_url('dashboard/activities'); ?>" class="mt-4 flex w-full items-center justify-center border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]">View Full Activity</a>
                <?php else: ?>
                    <p class="mt-4 text-sm text-[#8d8d8d]">Belum ada aktivitas.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <section class="border border-[#e0e0e0] bg-white px-4 py-3 sm:px-5" data-dashboard-toolbar>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-[#525252]">Projects</h2>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <label class="relative">
                    <span class="sr-only">Search projects</span>
                    <input type="search" data-project-search class="w-full min-w-[220px] border border-[#e0e0e0] bg-white px-3 py-2 pr-10 text-sm outline-none placeholder:text-[#8d8d8d] focus:border-[#0f62fe] sm:w-[240px]" placeholder="Search projects...">
                    <i data-lucide="search" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8d8d8d]"></i>
                </label>
                <div class="inline-flex overflow-hidden border border-[#e0e0e0] bg-white text-sm font-medium text-[#525252]" data-project-filter-group>
                    <button type="button" data-project-filter="all" class="border-r border-[#e0e0e0] px-3 py-2 hover:bg-[#f4f4f4] data-[active=true]:bg-[#f4f4f4] data-[active=true]:text-[#161616]">All</button>
                    <button type="button" data-project-filter="draft" class="border-r border-[#e0e0e0] px-3 py-2 hover:bg-[#f4f4f4] data-[active=true]:bg-[#f4f4f4] data-[active=true]:text-[#161616]">Draft</button>
                    <button type="button" data-project-filter="published" class="px-3 py-2 hover:bg-[#f4f4f4] data-[active=true]:bg-[#f4f4f4] data-[active=true]:text-[#161616]">Published</button>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($projects)): ?>
        <section class="border border-dashed border-[#e0e0e0] bg-white px-6 py-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center border border-[#0f62fe] bg-[#f4f4f4] text-[#0f62fe]"><i data-lucide="folder-plus" class="h-5 w-5"></i></div>
            <h2 class="mt-4 text-lg font-semibold text-[#161616]">Belum ada project</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-[#525252]">Mulai dengan membuat project website statis pertamamu.</p>
            <button type="button" data-create-project class="mt-5 inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0353e9]"><i data-lucide="plus" class="h-4 w-4"></i><span>Create Project</span></button>
        </section>
    <?php else: ?>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" data-project-grid>
            <button type="button" data-create-project class="group flex min-h-[230px] flex-col items-center justify-center border border-dashed border-[#c6c6c6] bg-white p-5 text-center transition hover:border-[#0f62fe] hover:bg-[#f9f9f9]">
                <div class="flex h-12 w-12 items-center justify-center bg-[#0f62fe] text-white transition group-hover:bg-[#0353e9]">
                    <i data-lucide="plus" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-semibold text-[#161616]">Buat Project</h3>
                <p class="mt-1 text-sm text-[#8d8d8d]">Mulai <span class="bg-[#0f62fe] px-1.5 text-white">project baru</span></p>
            </button>

            <?php foreach ($projects as $project): ?>
                <?php
                    $status = $project['status'] ?? 'draft';
                    $statusLabel = ucfirst($status);
                    $badgeClasses = [
                        'published' => 'border border-[#24a148] bg-white text-[#24a148]',
                        'draft' => 'border border-[#e0e0e0] bg-white text-[#525252]',
                    ];
                    $badgeClass = $badgeClasses[$status] ?? $badgeClasses['draft'];
                ?>
                <article class="group flex min-h-[230px] flex-col overflow-visible border border-[#e0e0e0] bg-white" data-project-card data-project-id="<?php echo (int) $project['id_project']; ?>" data-public-url="<?php echo e($project['public_url']); ?>" data-project-name="<?php echo e($project['project_name']); ?>" data-project-status="<?php echo e($status); ?>" data-project-updated-ts="<?php echo (int) strtotime($project['updated_at']); ?>">
                    <div class="relative h-32 overflow-hidden border-b border-[#e0e0e0] bg-gradient-to-br from-[#f4f4f4] to-[#e0e0e0]">
                        <div class="absolute inset-0 p-3">
                            <div class="flex h-full flex-col border border-[#e0e0e0] bg-white">
                                <div class="flex items-center gap-1 border-b border-[#e0e0e0] px-2 py-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#fa4d56]"></span>
                                    <span class="h-2 w-2 rounded-full bg-[#f1c21b]"></span>
                                    <span class="h-2 w-2 rounded-full bg-[#42be65]"></span>
                                    <span class="ml-2 truncate text-[10px] text-[#8d8d8d]"><?php echo e($project['project_name']); ?></span>
                                </div>
                                <div class="flex flex-1 items-center px-3">
                                    <span class="line-clamp-2 text-sm font-semibold text-[#161616]"><?php echo e($project['project_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-[#161616]"><?php echo e($project['project_name']); ?></h3>
                            </div>
                            <span class="shrink-0 px-2.5 py-1 text-[11px] font-semibold <?php echo e($badgeClass); ?>"><?php echo e($statusLabel); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-[#525252]">
                            <span><?php echo (int) ($project['total_files'] ?? 0); ?> files</span>
                            <span><?php echo (int) ($project['total_folders'] ?? 0); ?> folders</span>
                            <span><?php echo number_format(((int) ($project['total_size_bytes'] ?? 0)) / 1024, 1); ?> KB</span>
                        </div>
                        <p class="text-xs text-[#525252]">Updated <?php echo e(format_datetime_id($project['updated_at'])); ?></p>
                        <div class="mt-auto flex items-center gap-2">
                            <a href="<?php echo site_url('editor/' . $project['id_project']); ?>" class="inline-flex flex-1 items-center justify-center border border-[#0f62fe] bg-[#0f62fe] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0353e9]">Open Editor</a>
                            <div class="relative z-20" data-project-menu-wrap>
                                <button type="button" data-project-menu-toggle class="inline-flex h-10 w-10 items-center justify-center border border-[#e0e0e0] bg-white text-[#525252] hover:bg-[#f4f4f4]" aria-label="More actions">
                                    <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                                </button>
                                <div class="absolute right-0 top-full z-[9999] mt-2 hidden w-44 border border-[#e0e0e0] bg-white shadow-sm" data-project-menu>
                                    <?php if (!empty($project['public_url'])): ?>
                                        <a href="<?php echo e($project['public_url']); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 px-3 py-2 text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]"><i data-lucide="external-link" class="h-4 w-4"></i><span>Open Site</span></a>
                                        <button type="button" data-copy-link class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]"><i data-lucide="copy" class="h-4 w-4"></i><span>Copy Link</span></button>
                                    <?php endif; ?>
                                    <button type="button" data-rename-project class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]"><i data-lucide="pencil" class="h-4 w-4"></i><span>Rename</span></button>
                                    <button type="button" data-delete-project class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-[#da1e28] hover:bg-[#fff1f1]"><i data-lucide="trash-2" class="h-4 w-4"></i><span>Delete</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<div id="wd-create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#161616]/20 px-4 py-6">
    <div class="w-full max-w-md border border-[#e0e0e0] bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-[#161616]">Create Project</h2>
                <p class="mt-1 text-sm text-[#525252]">Buat project static baru dengan starter file.</p>
            </div>
            <button type="button" data-close-modal class="bg-[#f4f4f4] p-2 text-[#525252] hover:bg-[#e0e0e0]"><i data-lucide="x"></i></button>
        </div>
        <form class="mt-5 space-y-4" data-create-form>
            <label class="block text-sm font-medium text-[#161616]">
                <span class="mb-1 block">Project name</span>
                <input type="text" name="project_name" required maxlength="120" class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none ring-0 focus:border-[#0f62fe]" placeholder="Company Landing Page">
            </label>
            <div class="flex items-center justify-end gap-3">
                <button type="button" data-close-modal class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]"><i data-lucide="plus"></i> Create</button>
            </div>
        </form>
    </div>
</div>

<div id="wd-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#161616]/20 px-4 py-6">
    <div class="w-full max-w-md border border-[#e0e0e0] bg-white p-6">
        <h2 class="text-lg font-semibold text-[#161616]">Delete project?</h2>
        <p class="mt-2 text-sm text-[#525252]">Project dan workspace akan dihapus permanen.</p>
        <form class="mt-6 flex items-center justify-end gap-3" data-delete-form>
            <input type="hidden" name="id_project" value="">
            <button type="button" data-close-confirm class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Cancel</button>
            <button type="submit" class="border border-[#da1e28] bg-[#da1e28] px-4 py-2 text-sm font-semibold text-white hover:bg-[#a2191f]">Delete</button>
        </form>
    </div>
</div>

<div id="wd-rename-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#161616]/20 px-4 py-6">
    <div class="w-full max-w-md border border-[#e0e0e0] bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-[#161616]">Rename Project</h2>
                <p class="mt-1 text-sm text-[#525252]">Ubah nama project ini.</p>
            </div>
            <button type="button" data-close-rename-modal class="bg-[#f4f4f4] p-2 text-[#525252] hover:bg-[#e0e0e0]"><i data-lucide="x"></i></button>
        </div>
        <form class="mt-5 space-y-4" data-rename-form>
            <input type="hidden" name="id_project" value="">
            <label class="block text-sm font-medium text-[#161616]">
                <span class="mb-1 block">Project name</span>
                <input type="text" name="project_name" required maxlength="120" class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none ring-0 focus:border-[#0f62fe]">
            </label>
            <div class="flex items-center justify-end gap-3">
                <button type="button" data-close-rename-modal class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]"><i data-lucide="check"></i> Rename</button>
            </div>
        </form>
    </div>
</div>

<div id="wd-toast" class="fixed right-4 top-4 z-50 hidden border px-4 py-3 text-sm font-medium"></div>

<script src="<?php echo asset_url('js/dashboard.js'); ?>"></script>
