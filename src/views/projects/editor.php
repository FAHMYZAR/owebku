<?php
$projectId = (int) $project['id_project'];
$projectName = $project['project_name'];

function getFileIcon(array $file): string {
    if ((int)$file['is_folder'] === 1) return 'folder';
    $ext = strtolower(pathinfo($file['relative_path'], PATHINFO_EXTENSION));
    return match($ext) {
        'html', 'htm' => 'file-code-2',
        'css' => 'file-code',
        'js' => 'file-json',
        'json', 'txt', 'md' => 'file-text',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp' => 'image',
        default => 'file'
    };
}
?>
<div id="ow-editor" class="h-screen flex flex-col overflow-hidden bg-white text-[#161616]"
     data-project-id="<?php echo $projectId; ?>"
     data-current-file="<?php echo e($selected_path); ?>"
     data-current-is-folder="<?php echo (isset($selected_file['is_folder']) ? $selected_file['is_folder'] : 0); ?>"
     data-get-url="<?php echo site_url('files/get-content'); ?>"
     data-save-url="<?php echo site_url('files/save-content'); ?>"
     data-create-url="<?php echo site_url('files/create'); ?>"
     data-rename-url="<?php echo site_url('files/rename'); ?>"
     data-move-url="<?php echo site_url('files/move'); ?>"
     data-delete-url="<?php echo site_url('files/delete'); ?>"
     data-upload-url="<?php echo site_url('files/upload'); ?>"
     data-import-zip-url="<?php echo site_url('files/import-zip'); ?>"
     data-publish-url="<?php echo site_url('publish'); ?>">
    <header class="border-b border-[#e0e0e0] bg-white">
        <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="<?php echo site_url('dashboard'); ?>" class="inline-flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-white text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="arrow-left" class="h-4 w-4"></i></a>
                <div class="min-w-0">
                    <div class="flex min-w-0 items-center gap-2 text-sm text-[#525252]">
                        <span class="font-bold text-[#161616]">Owebku</span>
                        <span>/</span>
                        <span class="truncate font-semibold text-[#161616]"><?php echo e($projectName); ?></span>
                    </div>
                    <p class="truncate text-xs text-[#8d8d8d]">Editor project static</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-toggle-sidebar class="inline-flex h-9 w-9 items-center justify-center border border-[#e0e0e0] bg-white text-[#525252] hover:bg-[#f4f4f4] lg:hidden"><i data-lucide="menu" class="h-4 w-4"></i></button>
                <button type="button" data-save-file class="inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]"><i data-lucide="save" class="h-4 w-4"></i><span class="hidden sm:inline">Save</span></button>
                <button type="button" data-publish-project class="inline-flex items-center gap-2 border border-[#24a148] bg-[#24a148] px-4 py-2 text-sm font-semibold text-white hover:bg-[#198038]"><i data-lucide="globe" class="h-4 w-4"></i><span class="hidden sm:inline">Publish</span></button>
                <?php if (!empty($preview_url)): ?>
                    <a href="<?php echo e($preview_url); ?>" target="_blank" class="inline-flex items-center gap-2 border border-[#e0e0e0] bg-white px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="external-link" class="h-4 w-4"></i><span class="hidden sm:inline">Open</span></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="relative flex min-h-0 flex-1 overflow-hidden">
        <aside data-sidebar class="absolute inset-y-0 left-0 z-40 hidden w-72 shrink-0 flex-col border-r border-[#e0e0e0] bg-white lg:static lg:flex">
            <div class="border-b border-[#e0e0e0] px-4 py-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#525252]">Files</p>
                    <div class="relative">
                        <button type="button" data-sidebar-create-trigger class="inline-flex h-8 px-2 items-center gap-1 border border-[#e0e0e0] text-[#525252] hover:bg-[#f4f4f4] text-xs font-semibold">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Baru
                        </button>
                        <div data-sidebar-create-menu class="absolute right-0 top-9 z-50 hidden w-44 border border-[#e0e0e0] bg-white py-1 shadow-lg">
                            <button type="button" data-new-file class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-semibold text-[#161616] hover:bg-[#f4f4f4]">
                                <i data-lucide="file-plus" class="h-3.5 w-3.5"></i> File Baru
                            </button>
                            <button type="button" data-new-folder class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-semibold text-[#161616] hover:bg-[#f4f4f4]">
                                <i data-lucide="folder-plus" class="h-3.5 w-3.5"></i> Folder Baru
                            </button>
                            <button type="button" data-upload-trigger class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-semibold text-[#161616] hover:bg-[#f4f4f4]">
                                <i data-lucide="upload" class="h-3.5 w-3.5"></i> Upload File
                            </button>
                            <button type="button" data-zip-import-trigger class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs font-semibold text-[#161616] hover:bg-[#f4f4f4]">
                                <i data-lucide="archive" class="h-3.5 w-3.5"></i> Import ZIP
                            </button>
                            <input type="file" data-upload-input class="hidden">
                            <input type="file" data-zip-import-input accept=".zip,application/zip,application/x-zip-compressed" class="hidden">
                        </div>
                    </div>
                </div>
            </div>
            <div class="wd-scrollbar h-full overflow-auto p-3 space-y-0.5">
                <?php foreach ($files as $file):
                    $depth = substr_count(rtrim($file['relative_path'], '/'), '/');
                    $displayName = basename($file['relative_path']);
                    $indentPx = $depth * 16;
                ?>
                    <div class="group flex w-full items-center justify-between px-2 py-1 text-sm <?php echo $file['relative_path'] === $selected_path ? 'bg-[#f4f4f4] text-[#161616]' : 'text-[#525252] hover:bg-[#f4f4f4]'; ?>">
                        <button type="button"
                                data-file-path="<?php echo e($file['relative_path']); ?>"
                                data-is-folder="<?php echo $file['is_folder']; ?>"
                                class="flex flex-1 items-center gap-2 text-left min-w-0 pl-0"
                                style="padding-left: <?php echo $indentPx; ?>px;">
                            <i data-lucide="<?php echo getFileIcon($file); ?>" class="h-4 w-4 shrink-0"></i>
                            <span class="truncate" data-file-name-span title="<?php echo e($file['relative_path']); ?>"><?php echo e($displayName); ?></span>
                        </button>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" data-inline-move class="inline-flex h-6 w-6 items-center justify-center text-[#525252] hover:bg-[#e0e0e0] hover:text-[#161616]" title="Move"><i data-lucide="move" class="h-3 w-3"></i></button>
                            <button type="button" data-inline-rename class="inline-flex h-6 w-6 items-center justify-center text-[#525252] hover:bg-[#e0e0e0] hover:text-[#161616]" title="Rename"><i data-lucide="pencil" class="h-3 w-3"></i></button>
                            <button type="button" data-inline-delete class="inline-flex h-6 w-6 items-center justify-center text-[#da1e28] hover:bg-[#fff1f1]" title="Hapus"><i data-lucide="trash-2" class="h-3 w-3"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="flex min-w-0 flex-1 flex-col overflow-hidden bg-white">
            <div class="flex items-center justify-between border-b border-[#e0e0e0] px-4 py-2">
                <span class="text-sm font-semibold text-[#525252]" data-current-label><?php echo e($selected_path); ?></span>
            </div>
            <div class="flex min-h-0 flex-1">
                <textarea data-editor class="min-h-0 flex-1 resize-none border-0 bg-[#f9f9f9] p-4 font-mono text-sm leading-6 text-[#161616] outline-none" spellcheck="false"><?php echo e($selected_content); ?></textarea>
                <div data-preview-pane class="hidden flex-1 border-l border-[#e0e0e0] bg-white">
                    <?php if (!empty($preview_url)): ?>
                        <iframe data-preview-iframe src="<?php echo e($preview_url); ?>" class="h-full w-full border-0"></iframe>
                    <?php else: ?>
                        <div class="flex h-full items-center justify-center text-sm text-[#8d8d8d]">Publish dulu untuk melihat preview.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <div data-path-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-md border border-[#e0e0e0] bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-[#e0e0e0] px-5 py-4">
                <div>
                    <h2 data-path-modal-title class="text-lg font-semibold text-[#161616]">Tambah File</h2>
                    <p data-path-modal-desc class="mt-1 text-sm text-[#6f6f6f]">Masukkan path relative.</p>
                </div>
                <button type="button" data-path-modal-close class="inline-flex h-9 w-9 items-center justify-center border border-[#e0e0e0] text-[#525252] hover:bg-[#f4f4f4]">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form data-path-modal-form class="space-y-4 px-5 py-5">
                <div>
                    <label data-path-modal-label class="mb-2 block text-sm font-semibold text-[#161616]">Path</label>
                    <input type="text" data-path-modal-input class="w-full border border-[#8d8d8d] bg-white px-3 py-3 text-sm text-[#161616] outline-none focus:border-[#0f62fe]" placeholder="..." autocomplete="off">
                    <p class="mt-2 text-xs text-[#6f6f6f]">Contoh: <span data-path-modal-example>about.html</span></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" data-path-modal-cancel class="border border-[#e0e0e0] bg-white px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Batal</button>
                    <button type="submit" data-path-modal-submit class="border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editor-toast" class="fixed right-4 top-4 z-50 hidden border px-4 py-3 text-sm font-medium"></div>
</div>

<!-- CodeMirror Dependencies -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
<style>
.CodeMirror { height: 100%; font-family: 'IBM Plex Mono', monospace; font-size: 14px; background: #f9f9f9; }
.CodeMirror-gutters { border-right: 1px solid #e0e0e0; background: #f4f4f4; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>

<script src="<?php echo asset_url('js/editor.js'); ?>"></script>
