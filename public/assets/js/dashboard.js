document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    
    const dashboard = document.getElementById('wd-dashboard');
    if (!dashboard) return;

    const createUrl = dashboard.dataset.createUrl;
    const renameUrl = dashboard.dataset.renameUrl;
    const deleteUrl = dashboard.dataset.deleteUrl;

    const createModal = document.getElementById('wd-create-modal');
    const renameModal = document.getElementById('wd-rename-modal');
    const confirmModal = document.getElementById('wd-confirm-modal');
    const toast = document.getElementById('wd-toast');
    const searchInput = document.querySelector('[data-project-search]');
    const filterBtns = Array.from(document.querySelectorAll('[data-project-filter]'));
    const projectCards = Array.from(document.querySelectorAll('[data-project-card]'));
    let activeFilter = 'all';

    const showToast = (message, isError = false) => {
        toast.textContent = message;
        toast.className = 'fixed right-4 top-4 z-50 border px-4 py-3 text-sm font-medium ' + 
            (isError ? 'border-[#da1e28] bg-white text-[#da1e28]' : 'border-[#24a148] bg-white text-[#161616]');
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };

    const submitForm = async (url, form) => {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token-hash"]').getAttribute('content');
        formData.append('_csrf_token', csrfToken);
        
        try {
            const res = await fetch(url, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) {
                document.querySelector('meta[name="csrf-token-hash"]').setAttribute('content', data.csrf.hash);
            }
            if (!res.ok || !data.success) throw new Error(data.message || 'Error occurred');
            return data;
        } catch (err) {
            throw err;
        }
    };

    // Modal toggles
    const openModal = (m) => { m.classList.remove('hidden'); m.classList.add('flex'); };
    const closeModal = (m) => { m.classList.add('hidden'); m.classList.remove('flex'); };

    document.querySelectorAll('[data-create-project]').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = createModal.querySelector('form');
            form.reset();
            openModal(createModal);
            setTimeout(() => form.querySelector('input').focus(), 100);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(createModal)));
    document.querySelectorAll('[data-close-rename-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(renameModal)));
    document.querySelectorAll('[data-close-confirm]').forEach(btn => btn.addEventListener('click', () => closeModal(confirmModal)));

    // Menus
    document.querySelectorAll('[data-project-menu-toggle]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = e.currentTarget.nextElementSibling;
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('[data-project-menu]').forEach(m => m.classList.add('hidden'));
            if (isHidden) menu.classList.remove('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-project-menu]').forEach(m => m.classList.add('hidden'));
    });

    // Actions
    document.querySelectorAll('[data-rename-project]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const card = e.currentTarget.closest('[data-project-card]');
            const form = renameModal.querySelector('form');
            form.elements['id_project'].value = card.dataset.projectId;
            form.elements['project_name'].value = card.dataset.projectName;
            openModal(renameModal);
            setTimeout(() => form.elements['project_name'].focus(), 100);
        });
    });

    document.querySelectorAll('[data-delete-project]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const card = e.currentTarget.closest('[data-project-card]');
            const form = confirmModal.querySelector('form');
            form.elements['id_project'].value = card.dataset.projectId;
            openModal(confirmModal);
        });
    });

    document.querySelectorAll('[data-copy-link]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const card = e.currentTarget.closest('[data-project-card]');
            const url = card.dataset.publicUrl;
            if (!url) return;
            try {
                await navigator.clipboard.writeText(url);
                showToast('Link berhasil disalin.');
            } catch (err) {
                showToast('Gagal menyalin link.', true);
            }
        });
    });

    const applyProjectFilter = () => {
        const keyword = (searchInput?.value || '').toLowerCase().trim();
        projectCards.forEach(card => {
            const name = (card.dataset.projectName || '').toLowerCase();
            const status = card.dataset.projectStatus || 'draft';
            const matchKeyword = name.includes(keyword);
            const matchFilter = activeFilter === 'all' || status === activeFilter;
            card.classList.toggle('hidden', !(matchKeyword && matchFilter));
        });
    };

    searchInput?.addEventListener('input', applyProjectFilter);
    filterBtns.forEach(btn => {
        btn.dataset.active = btn.dataset.projectFilter === activeFilter ? 'true' : 'false';
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.projectFilter;
            filterBtns.forEach(item => item.dataset.active = item === btn ? 'true' : 'false');
            applyProjectFilter();
        });
    });

    // Forms submit
    document.querySelector('[data-create-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await submitForm(createUrl, e.target);
            showToast(res.message);
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            showToast(err.message, true);
        }
    });

    document.querySelector('[data-rename-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await submitForm(renameUrl, e.target);
            showToast(res.message);
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            showToast(err.message, true);
        }
    });

    document.querySelector('[data-delete-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await submitForm(deleteUrl, e.target);
            showToast(res.message);
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            showToast(err.message, true);
        }
    });
});