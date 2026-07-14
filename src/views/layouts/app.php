<?php
$layoutVariant = isset($layout_variant) ? $layout_variant : 'app';
$flash_error = $_SESSION['flash_error'] ?? null;
$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

require __DIR__ . '/header.php';
?>
<?php if ($layoutVariant === 'auth'): ?>
    <main class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md space-y-4">
            <?php if ($flash_error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo e($flash_error); ?></div>
            <?php endif; ?>
            <?php if ($flash_success): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo e($flash_success); ?></div>
            <?php endif; ?>
            <?php require $content_view; ?>
        </div>
    </main>
<?php elseif ($layoutVariant === 'editor'): ?>
    <div class="h-screen flex flex-col overflow-hidden bg-white text-[#161616]">
        <?php if ($flash_error): ?>
            <div class="mx-4 mt-4 border border-[#da1e28] bg-white px-4 py-3 text-sm text-[#da1e28] md:mx-6"><?php echo e($flash_error); ?></div>
        <?php endif; ?>
        <?php if ($flash_success): ?>
            <div class="mx-4 mt-4 border border-[#24a148] bg-white px-4 py-3 text-sm text-[#161616] md:mx-6"><?php echo e($flash_success); ?></div>
        <?php endif; ?>
        <?php require $content_view; ?>
    </div>
<?php else: ?>
    <div class="min-h-screen bg-white text-[#161616]">
        <header class="border-b border-[#e0e0e0] bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="<?php echo site_url(is_authenticated() ? 'dashboard' : 'login'); ?>" class="flex items-center gap-2 font-semibold tracking-[0.16px] text-[#161616]">
                    <span class="flex h-9 w-9 items-center justify-center border border-[#0f62fe] bg-[#0f62fe] text-white">OW</span>
                    <span>Owebku</span>
                </a>
                <?php if ($auth_user): ?>
                    <nav class="hidden items-center gap-1 text-sm font-medium text-[#525252] md:flex">
                        <a href="<?php echo site_url('dashboard'); ?>" class="px-3 py-2 hover:bg-[#f4f4f4] hover:text-[#161616]">Dashboard</a>
                        <a href="<?php echo site_url('profile'); ?>" class="px-3 py-2 hover:bg-[#f4f4f4] hover:text-[#161616]">Profile</a>
                        <form method="post" action="<?php echo site_url('logout'); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-3 py-2 text-[#da1e28] hover:bg-[#f4f4f4]">Logout</button>
                        </form>
                    </nav>
                <?php endif; ?>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <?php if ($flash_error): ?>
                <div class="mb-4 border border-[#da1e28] bg-white px-4 py-3 text-sm text-[#da1e28]"><?php echo e($flash_error); ?></div>
            <?php endif; ?>
            <?php if ($flash_success): ?>
                <div class="mb-4 border border-[#24a148] bg-white px-4 py-3 text-sm text-[#161616]"><?php echo e($flash_success); ?></div>
            <?php endif; ?>
            <?php require $content_view; ?>
        </main>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
