document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    
    const editorEl = document.getElementById('ow-editor');
    if (!editorEl) return;

    const projectId = editorEl.dataset.projectId;
    const getUrl = editorEl.dataset.getUrl;
    const saveUrl = editorEl.dataset.saveUrl;
    const createUrl = editorEl.dataset.createUrl;
    const renameUrl = editorEl.dataset.renameUrl;
    const moveUrl = editorEl.dataset.moveUrl;
    const deleteUrl = editorEl.dataset.deleteUrl;
    const publishUrl = editorEl.dataset.publishUrl;
    const importZipUrl = editorEl.dataset.importZipUrl;
    
    const textarea = editorEl.querySelector('[data-editor]');
    const fileBtnsContainer = editorEl.querySelector('.wd-scrollbar');
    let fileBtns = Array.from(editorEl.querySelectorAll('[data-file-path]'));
    const saveBtn = editorEl.querySelector('[data-save-file]');
    const publishBtn = editorEl.querySelector('[data-publish-project]');
    
    // Create menu dropdown
    const createTrigger = editorEl.querySelector('[data-sidebar-create-trigger]');
    const createMenu = editorEl.querySelector('[data-sidebar-create-menu]');
    
    const newFileBtn = editorEl.querySelector('[data-new-file]');
    const newFolderBtn = editorEl.querySelector('[data-new-folder]');
    const uploadTrigger = editorEl.querySelector('[data-upload-trigger]');
    const uploadInput = editorEl.querySelector('[data-upload-input]');
    const zipImportTrigger = editorEl.querySelector('[data-zip-import-trigger]');
    const zipImportInput = editorEl.querySelector('[data-zip-import-input]');
    
    const toggleSidebarBtn = editorEl.querySelector('[data-toggle-sidebar]');
    const sidebar = editorEl.querySelector('[data-sidebar]');
    const togglePreviewBtn = editorEl.querySelector('[data-toggle-preview]');
    const previewPane = editorEl.querySelector('[data-preview-pane]');
    const previewIframe = editorEl.querySelector('[data-preview-iframe]');
    const currentLabel = editorEl.querySelector('[data-current-label]');
    const toast = document.getElementById('editor-toast');

    // Path modal
    const pathModal = document.querySelector('[data-path-modal]');
    const pathModalTitle = document.querySelector('[data-path-modal-title]');
    const pathModalDesc = document.querySelector('[data-path-modal-desc]');
    const pathModalInput = document.querySelector('[data-path-modal-input]');
    const pathModalExample = document.querySelector('[data-path-modal-example]');
    const pathModalForm = document.querySelector('[data-path-modal-form]');
    const pathModalClose = document.querySelector('[data-path-modal-close]');
    const pathModalCancel = document.querySelector('[data-path-modal-cancel]');
    const pathModalSubmit = document.querySelector('[data-path-modal-submit]');

    let currentFile = editorEl.dataset.currentFile;
    let currentIsFolder = editorEl.dataset.currentIsFolder === '1';

    // Syntax highlight editor (CodeMirror)
    const getMode = (path) => {
        const ext = (path || '').split('.').pop().toLowerCase();
        return {
            html: 'htmlmixed',
            htm: 'htmlmixed',
            css: 'css',
            js: 'javascript',
            json: 'javascript',
            xml: 'xml'
        }[ext] || 'htmlmixed';
    };

    let codeEditor = null;
    if (window.CodeMirror) {
        codeEditor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: getMode(currentFile),
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            lineWrapping: false,
            extraKeys: {
                'Ctrl-S': () => saveBtn.click(),
                'Cmd-S': () => saveBtn.click()
            }
        });
        codeEditor.setSize('100%', '100%');
    }

    let isProgrammaticChange = false;
    const getEditorValue = () => codeEditor ? codeEditor.getValue() : textarea.value;
    const setEditorValue = (content, path) => {
        isProgrammaticChange = true;
        if (codeEditor) {
            codeEditor.setOption('mode', getMode(path));
            codeEditor.setValue(content || '');
            codeEditor.clearHistory();
            setTimeout(() => codeEditor.refresh(), 0);
        } else {
            textarea.value = content || '';
        }
        isProgrammaticChange = false;
    };

    // Save All tracking: simpan hanya file yang benar-benar berubah.
    const originalFiles = new Map();
    const unsavedFiles = new Map();
    let isSavingAll = false;

    if (currentFile && !currentIsFolder) {
        originalFiles.set(currentFile, getEditorValue());
    }

    const setDirty = (path, content) => {
        if (!path) return;
        if (!originalFiles.has(path)) {
            originalFiles.set(path, content);
        }

        if (content === originalFiles.get(path)) {
            unsavedFiles.delete(path);
            return;
        }

        unsavedFiles.set(path, content);
    };

    const markDirty = () => {
        if (currentFile && !currentIsFolder) {
            setDirty(currentFile, getEditorValue());
        }
    };

    if (codeEditor) {
        codeEditor.on('change', () => {
            if (!isProgrammaticChange && currentFile && !currentIsFolder) {
                setDirty(currentFile, codeEditor.getValue());
            }
        });
    } else {
        textarea.addEventListener('input', markDirty);
    }

    const getActiveFolder = () => {
        if (!currentFile) return '';
        if (currentIsFolder) return currentFile.replace(/\/$/, '');
        return currentFile.includes('/') ? currentFile.substring(0, currentFile.lastIndexOf('/')) : '';
    };

    const normalizePath = (path, activeFolder) => {
        const wantsRoot = path.trim().startsWith('/');
        const clean = path.replace(/^\/+|\/+$/g, '');
        if (!activeFolder || wantsRoot) return clean;
        if (clean === activeFolder || clean.startsWith(activeFolder + '/')) return clean;
        return activeFolder + '/' + clean;
    };

    const showToast = (msg, isError = false) => {
        toast.textContent = msg;
        toast.className = 'fixed right-4 top-4 z-50 border px-4 py-3 text-sm font-medium ' + 
            (isError ? 'border-[#da1e28] bg-white text-[#da1e28]' : 'border-[#24a148] bg-white text-[#161616]');
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3000);
    };

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token-hash"]').getAttribute('content');
    const updateCsrfToken = (hash) => document.querySelector('meta[name="csrf-token-hash"]').setAttribute('content', hash);

    // Dropdown toggle
    document.addEventListener('click', (e) => {
        if (createTrigger && createMenu) {
            if (createTrigger.contains(e.target)) {
                createMenu.classList.toggle('hidden');
            } else if (!createMenu.contains(e.target)) {
                createMenu.classList.add('hidden');
            }
        }
    });

    const loadFile = async (path) => {
        markDirty(); // Simpan yang aktif ke Map sebelum pindah

        const formData = new FormData();
        formData.append('id_project', projectId);
        formData.append('relative_path', path);
        formData.append('_csrf_token', getCsrfToken());

        try {
            const res = await fetch(getUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) updateCsrfToken(data.csrf.hash);
            
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal load file');
            
            if (!originalFiles.has(path)) {
                originalFiles.set(path, data.content);
            }

            const content = unsavedFiles.has(path) ? unsavedFiles.get(path) : data.content;
            currentFile = path;
            currentIsFolder = false;
            currentLabel.textContent = path;
            setEditorValue(content, path);

            fileBtns.forEach(btn => {
                const parentRow = btn.parentElement;
                if (btn.dataset.filePath === path) {
                    parentRow.className = 'group flex w-full items-center justify-between px-2 py-1 text-sm bg-[#f4f4f4] text-[#161616]';
                } else {
                    parentRow.className = 'group flex w-full items-center justify-between px-2 py-1 text-sm text-[#525252] hover:bg-[#f4f4f4]';
                }
            });
        } catch (e) {
            showToast(e.message, true);
        }
    };

    fileBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const path = btn.dataset.filePath;
            const isFolder = btn.dataset.isFolder === '1';

            markDirty();

            if (isFolder) {
                currentFile = path;
                currentIsFolder = true;
                currentLabel.textContent = path;
                fileBtns.forEach(item => {
                    item.parentElement.className = item.dataset.filePath === path
                        ? 'group flex w-full items-center justify-between px-2 py-1 text-sm bg-[#f4f4f4] text-[#161616]'
                        : 'group flex w-full items-center justify-between px-2 py-1 text-sm text-[#525252] hover:bg-[#f4f4f4]';
                });
                showToast(`Folder aktif: ${path}`);
                return;
            }

            if (path !== currentFile || currentIsFolder) loadFile(path);
        });
    });

    // Inline Rename & Delete Action
    const setupInlineActions = () => {
        const rows = editorEl.querySelectorAll('.group');
        rows.forEach(row => {
            const btn = row.querySelector('[data-file-path]');
            const path = btn.dataset.filePath;
            const isFolder = btn.dataset.isFolder === '1';
            
            const renameBtn = row.querySelector('[data-inline-rename]');
            const deleteBtn = row.querySelector('[data-inline-delete]');
            const moveBtn = row.querySelector('[data-inline-move]');
            const nameSpan = row.querySelector('[data-file-name-span]');

            moveBtn?.addEventListener('click', async (e) => {
                e.stopPropagation();
                let to = await showPathModal(`Pindah ${isFolder ? 'Folder' : 'File'}`, `Pindahkan '${path}' ke lokasi baru:`, 'assets/new-folder/', path);
                if (!to || to === path) return;

                const doMove = async (overwrite) => {
                    const formData = new FormData();
                    formData.append('id_project', projectId);
                    formData.append('from', path);
                    formData.append('to', normalizePath(to, ''));
                    formData.append('overwrite', overwrite ? 1 : 0);
                    formData.append('_csrf_token', getCsrfToken());

                    try {
                        const res = await fetch(moveUrl, { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.csrf) updateCsrfToken(data.csrf.hash);
                        if (!res.ok) {
                            if (data.message === 'File/folder tujuan sudah ada.' && !overwrite) {
                                if (confirm(`Peringatan: File/folder tujuan sudah ada. Overwrite?`)) {
                                    return doMove(true);
                                } else {
                                    return;
                                }
                            }
                            throw new Error(data.message || 'Gagal memindahkan file/folder');
                        }
                        
                        showToast(data.message);
                        setTimeout(() => window.location.reload(), 500);
                    } catch (err) {
                        showToast(err.message, true);
                    }
                };
                doMove(false);
            });

            renameBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (row.querySelector('input[data-inline-rename-input]')) return; // already renaming

                const currentName = path.split('/').pop();
                const parentDir = path.includes('/') ? path.substring(0, path.lastIndexOf('/')) + '/' : '';
                
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentName;
                input.className = 'w-full bg-white border border-[#0f62fe] px-1 text-sm text-[#161616] outline-none';
                input.setAttribute('data-inline-rename-input', 'true');

                nameSpan.style.display = 'none';
                nameSpan.parentNode.insertBefore(input, nameSpan.nextSibling);
                input.focus();
                input.select();

                const commitRename = async () => {
                    const newName = input.value.trim();
                    if (!newName || newName === currentName) {
                        input.remove();
                        nameSpan.style.display = '';
                        return;
                    }

                    const newPath = parentDir + newName;
                    const formData = new FormData();
                    formData.append('id_project', projectId);
                    formData.append('old_path', path);
                    formData.append('new_path', newPath);
                    formData.append('_csrf_token', getCsrfToken());

                    try {
                        input.disabled = true;
                        const res = await fetch(renameUrl, { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.csrf) updateCsrfToken(data.csrf.hash);
                        if (!res.ok || !data.success) throw new Error(data.message || 'Gagal rename');
                        
                        showToast(data.message);
                        setTimeout(() => window.location.reload(), 500);
                    } catch (err) {
                        showToast(err.message, true);
                        input.remove();
                        nameSpan.style.display = '';
                    }
                };

                input.addEventListener('keydown', (e2) => {
                    if (e2.key === 'Enter') commitRename();
                    if (e2.key === 'Escape') {
                        input.remove();
                        nameSpan.style.display = '';
                    }
                });
                input.addEventListener('blur', commitRename);
            });

            deleteBtn?.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (!confirm(`Hapus ${isFolder ? 'folder' : 'file'}: ${path}?`)) return;

                const formData = new FormData();
                formData.append('id_project', projectId);
                formData.append('relative_path', path);
                formData.append('_csrf_token', getCsrfToken());

                try {
                    const res = await fetch(deleteUrl, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.csrf) updateCsrfToken(data.csrf.hash);
                    if (!res.ok || !data.success) throw new Error(data.message || 'Gagal hapus file');
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 500);
                } catch (err) {
                    showToast(err.message, true);
                }
            });
        });
    };
    setupInlineActions();

    saveBtn.addEventListener('click', async () => {
        if (isSavingAll) return;
        markDirty(); // Pastikan file terakhir ikut tersimpan

        if (unsavedFiles.size === 0) {
            showToast('Tidak ada perubahan.');
            return;
        }
        
        isSavingAll = true;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i><span>Saving All...</span>';
        lucide.createIcons();

        let saved = 0, failed = 0;
        const entries = [...unsavedFiles];

        for (const [path, content] of entries) {
            const formData = new FormData();
            formData.append('id_project', projectId);
            formData.append('relative_path', path);
            formData.append('content', content);
            formData.append('_csrf_token', getCsrfToken());

            try {
                const res = await fetch(saveUrl, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf) updateCsrfToken(data.csrf.hash);
                if (!res.ok || !data.success) throw new Error(data.message);
                originalFiles.set(path, content);
                unsavedFiles.delete(path);
                saved++;
            } catch (e) {
                failed++;
            }
        }

        isSavingAll = false;
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i data-lucide="save" class="h-4 w-4"></i><span>Save</span>';
        lucide.createIcons();

        if (failed > 0) showToast(`${saved} berhasil, ${failed} gagal disimpan.`, true);
        else showToast(`${saved} file berhasil disimpan.`);
    });

    publishBtn?.addEventListener('click', async () => {
        publishBtn.disabled = true;
        publishBtn.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i><span>Publishing...</span>';
        lucide.createIcons();

        const formData = new FormData();
        formData.append('id_project', projectId);
        formData.append('_csrf_token', getCsrfToken());

        try {
            const res = await fetch(publishUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) updateCsrfToken(data.csrf.hash);
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal publish project');
            showToast(data.message);
            if (previewIframe) {
                previewIframe.src = previewIframe.src;
            } else {
                setTimeout(() => window.location.reload(), 1000);
            }
        } catch (e) {
            showToast(e.message, true);
        } finally {
            publishBtn.disabled = false;
            publishBtn.innerHTML = '<i data-lucide="globe" class="h-4 w-4"></i><span>Publish</span>';
            lucide.createIcons();
        }
    });

    const showPathModal = (title, desc, example, defValue = '') => {
        return new Promise((resolve) => {
            pathModalTitle.textContent = title;
            pathModalDesc.textContent = desc;
            pathModalExample.textContent = example;
            pathModalInput.value = defValue;
            pathModalSubmit.textContent = 'Simpan';
            pathModal.classList.remove('hidden');
            pathModal.classList.add('flex');
            lucide.createIcons();
            pathModalInput.focus();

            const cleanup = () => {
                pathModal.classList.add('hidden');
                pathModal.classList.remove('flex');
                pathModalForm.removeEventListener('submit', onSubmit);
                pathModalClose.removeEventListener('click', onCancel);
                pathModalCancel.removeEventListener('click', onCancel);
            };

            const onSubmit = (e) => {
                e.preventDefault();
                const val = pathModalInput.value.trim();
                if (val) {
                    cleanup();
                    resolve(val);
                }
            };

            const onCancel = () => {
                cleanup();
                resolve(null);
            };

            pathModalForm.addEventListener('submit', onSubmit);
            pathModalClose.addEventListener('click', onCancel);
            pathModalCancel.addEventListener('click', onCancel);
        });
    };

    const createEntry = async (isFolder) => {
        const activeFolder = getActiveFolder();
        const title = isFolder ? 'Tambah Folder' : 'Tambah File';
        const desc = activeFolder
            ? `Buat ${isFolder ? 'folder' : 'file'} baru di dalam folder '${activeFolder}'. (Awali path dengan / untuk buat di root)`
            : `Masukkan path relative ${isFolder ? 'folder' : 'file'} baru.`;
        const example = activeFolder
            ? (isFolder ? `css atau utils` : `style.css`)
            : (isFolder ? 'assets' : 'about.html');
        
        let path = await showPathModal(title, desc, example, '');
        if (!path) return;

        path = normalizePath(path, activeFolder);

        const formData = new FormData();
        formData.append('id_project', projectId);
        formData.append('relative_path', path);
        formData.append('is_folder', isFolder ? 1 : 0);
        formData.append('_csrf_token', getCsrfToken());

        try {
            const res = await fetch(createUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) updateCsrfToken(data.csrf.hash);
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal membuat file');
            showToast(data.message);
            if (createMenu) createMenu.classList.add('hidden');
            setTimeout(() => window.location.reload(), 1000);
        } catch (e) {
            showToast(e.message, true);
        }
    };

    newFileBtn?.addEventListener('click', () => createEntry(false));
    newFolderBtn?.addEventListener('click', () => createEntry(true));

    uploadTrigger?.addEventListener('click', () => {
        if (createMenu) createMenu.classList.add('hidden');
        uploadInput?.click();
    });
    uploadInput?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const destDir = getActiveFolder();
        let path = await showPathModal('Upload File', destDir ? `Upload file ke dalam '${destDir}'` : 'Upload file ke project root', destDir ? file.name : file.name, file.name);
        if (!path) {
            e.target.value = ''; // reset
            return;
        }

        path = normalizePath(path, destDir);

        const formData = new FormData();
        formData.append('id_project', projectId);
        formData.append('relative_path', path);
        formData.append('file', file);
        formData.append('_csrf_token', getCsrfToken());

        try {
            uploadTrigger.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>';
            lucide.createIcons();
            
            const res = await fetch(editorEl.dataset.uploadUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) updateCsrfToken(data.csrf.hash);
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal upload file');
            
            showToast(data.message);
            setTimeout(() => window.location.reload(), 1000);
        } catch (err) {
            showToast(err.message, true);
            uploadTrigger.innerHTML = '<i data-lucide="upload" class="h-4 w-4"></i>';
            lucide.createIcons();
        }
        e.target.value = ''; // reset
    });

    zipImportTrigger?.addEventListener('click', () => {
        if (createMenu) createMenu.classList.add('hidden');
        zipImportInput?.click();
    });
    zipImportInput?.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 100 * 1024 * 1024) {
            showToast('Ukuran ZIP maksimal 100MB.', true);
            e.target.value = '';
            return;
        }

        const destDir = getActiveFolder();
        let path = await showPathModal('Import ZIP', destDir ? `Ekstrak isi ZIP ke dalam '${destDir}'? (Kosongkan jika ingin ekstrak di root)` : 'Ekstrak isi ZIP ke project root?', destDir ? destDir : '', destDir);
        
        // Null means cancelled. Empty string means root, so we proceed if path !== null
        if (path === null) {
            e.target.value = ''; // reset
            return;
        }

        path = normalizePath(path, ''); // base on root

        const formData = new FormData();
        formData.append('id_project', projectId);
        formData.append('target_dir', path);
        formData.append('file', file);
        formData.append('_csrf_token', getCsrfToken());

        try {
            zipImportTrigger.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Import...';
            lucide.createIcons();
            
            const res = await fetch(importZipUrl, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf) updateCsrfToken(data.csrf.hash);
            if (!res.ok || !data.success) throw new Error(data.message || 'Gagal import ZIP');
            
            showToast(data.message);
            setTimeout(() => window.location.reload(), 1500);
        } catch (err) {
            showToast(err.message, true);
        } finally {
            zipImportTrigger.innerHTML = '<i data-lucide="archive" class="h-3.5 w-3.5"></i> Import ZIP';
            lucide.createIcons();
        }
        e.target.value = ''; // reset
    });

    toggleSidebarBtn?.addEventListener('click', () => {
        sidebar?.classList.toggle('hidden');
        sidebar?.classList.toggle('flex');
    });

    togglePreviewBtn?.addEventListener('click', () => {
        previewPane?.classList.toggle('hidden');
        textarea.classList.toggle('flex-1');
        textarea.classList.toggle('w-1/2');
    });
    // Indent tab textarea
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + '    ' + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + 4;
        }
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveBtn.click();
        }
    });
});