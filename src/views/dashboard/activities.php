<section class="max-w-4xl space-y-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo site_url('dashboard'); ?>" class="inline-flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-white text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="arrow-left" class="h-4 w-4"></i></a>
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f62fe]">Activity</p>
            <h1 class="mt-1 text-2xl font-semibold text-[#161616]">Full Activity Log</h1>
            <p class="mt-1 text-sm text-[#525252]">Semua catatan aktivitas Anda di Owebku.</p>
        </div>
    </div>

    <div class="border border-[#e0e0e0] bg-white p-5 sm:p-6">
        <?php if (!empty($activities)): ?>
            <div class="divide-y divide-[#e0e0e0]">
                <?php foreach ($activities as $activity): ?>
                    <div class="py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#161616]">
                                    <?php echo e($activity['action']); ?>
                                    <span class="font-normal text-[#525252]"><?php echo e($activity['project_name'] ?? 'Project'); ?></span>
                                </p>
                                <p class="mt-1 text-sm text-[#525252]"><?php echo e($activity['description'] ?? '-'); ?></p>
                            </div>
                            <span class="shrink-0 text-xs font-medium text-[#8d8d8d]"><?php echo e(format_datetime_id($activity['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="py-8 text-center text-sm text-[#8d8d8d]">Belum ada aktivitas.</div>
        <?php endif; ?>
    </div>
</section>
