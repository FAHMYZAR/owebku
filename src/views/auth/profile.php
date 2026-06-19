<section class="max-w-xl space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f62fe]">Profile</p>
        <h1 class="mt-1 text-2xl font-semibold text-[#161616]">Profil Akun</h1>
        <p class="mt-1 text-sm text-[#525252]">Kelola informasi akun dan ganti password.</p>
    </div>

    <div class="border border-[#e0e0e0] bg-white p-6">
        <h2 class="mb-4 text-sm font-semibold text-[#161616]">Informasi Akun</h2>
        <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-[#f4f4f4] pb-3">
                <span class="text-sm text-[#525252]">Username</span>
                <span class="text-sm font-semibold text-[#161616]"><?php echo e($auth_user['username']); ?></span>
            </div>
            <div class="flex items-center justify-between pb-3">
                <span class="text-sm text-[#525252]">Email</span>
                <span class="text-sm font-semibold text-[#161616]"><?php echo e($auth_user['email']); ?></span>
            </div>
        </div>
    </div>

    <div class="border border-[#e0e0e0] bg-white p-6">
        <h2 class="mb-4 text-sm font-semibold text-[#161616]">Ganti Password</h2>
        <form action="<?php echo site_url('profile/update-password'); ?>" method="post" class="space-y-4">
            <?php echo csrf_field(); ?>
            <label class="block">
                <span class="mb-1 block text-sm text-[#525252]">Password Lama</span>
                <input type="password" name="old_password" required class="w-full border border-[#e0e0e0] px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm text-[#525252]">Password Baru</span>
                <input type="password" name="new_password" required minlength="6" class="w-full border border-[#e0e0e0] px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
            </label>
            <button type="submit" class="border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]">Update Password</button>
        </form>
    </div>
</section>
