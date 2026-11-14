<?php
// views/post_form.php — Form Tambah/Edit Artikel
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$id = (int)($_GET['id'] ?? 0);
$post = [
    'id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 
    'status' => 'draft', 'published_at' => ''
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Artikel tidak ditemukan.');
        redirect('/tokopi/views/posts.php');
    }
    $post = $existing;
}

$page_title = $id > 0 ? 'Edit Artikel' : 'Tulis Artikel Baru';
$active_menu = 'posts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= $page_title ?></h4>
        <small class="text-muted">Isi formulir di bawah ini</small>
    </div>
    <a href="/tokopi/views/posts.php" class="btn btn-outline-espresso">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<form action="/tokopi/actions/post_save.php" method="POST" id="postForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    
    <div class="row g-4">
        <!-- Kolom Kiri: Konten Utama -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Judul Artikel <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control form-control-lg" required maxlength="200" value="<?= e($post['title']) ?>" placeholder="Masukkan judul yang menarik">
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label fw-semibold">Isi Artikel <span class="text-danger">*</span></label>
                        <textarea name="content" id="content" class="form-control" required><?= e($post['content']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Meta Data -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Publikasi</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft (Simpan Saja)</option>
                            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published (Tampilkan)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="published_at" class="form-label fw-semibold">Tanggal Publish</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>">
                        <div class="form-text small">Biarkan kosong untuk mengisi waktu sekarang (saat status diubah ke Published).</div>
                    </div>

                    <button type="submit" class="btn btn-espresso w-100 py-2" id="btnSavePost">
                        <i class="bi bi-save me-1"></i> Simpan Artikel
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">SEO & Meta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">Slug URL</label>
                        <input type="text" name="slug" id="slug" class="form-control" maxlength="200" value="<?= e($post['slug']) ?>" placeholder="auto-dari-judul">
                        <div class="form-text small">Kosongkan untuk otomatis dari judul.</div>
                    </div>
                    
                    <div class="mb-0">
                        <label for="excerpt" class="form-label fw-semibold">Ringkasan (Excerpt)</label>
                        <textarea name="excerpt" id="excerpt" class="form-control" rows="3" maxlength="300" placeholder="Ringkasan singkat untuk card preview..."><?= e($post['excerpt']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Summernote CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-id-ID.min.js"></script>

<script>
$(document).ready(function() {
    $('#content').summernote({
        height: 500, // Bisa tinggi karena ini halaman penuh, bukan modal
        lang: 'id-ID',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview']]
        ],
        callbacks: {
            onImageUpload: function(files) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#content').summernote('insertImage', e.target.result, files[0].name);
                };
                reader.readAsDataURL(files[0]);
            }
        }
    });
});

// Auto-generate slug
document.getElementById('title').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value || slugInput.dataset.auto === 'true') {
        slugInput.value = this.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        slugInput.dataset.auto = 'true';
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.auto = 'false';
});

// Loading state
document.getElementById('postForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSavePost');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>