<?php

// Sesion & Inisialisasi
session_start();

// Inisialisasi struktur data array
if (!isset($_SESSION['todos'])) {
    $_SESSION['todos'] = [
        ['id' => 1, 'title' => 'Belajar PHP', 'status' => 'belum'],
        ['id' => 2, 'title' => 'Kerjakan tugas UX', 'status' => 'selesai'],
    ];
    $_SESSION['next_id'] = 3;
}

// FUNGSI HELPER 

/**
 * Helper: mencari POSISI (index) tugas di dalam array $_SESSION['todos']
 * berdasarkan ID-nya.
 *
 * Fungsi ini dipakai bersama oleh editTugas() dan toggleStatusTugas(),
 * supaya logika "cari tugas berdasarkan ID" tidak ditulis berulang-ulang
 * di banyak tempat.
 *
 * Mengembalikan index (int) jika ditemukan, atau null jika tidak ada
 * tugas dengan ID tersebut.
 */
function cariIndexTugas(int $id): ?int
{
    foreach ($_SESSION['todos'] as $index => $tugas) {
        if ($tugas['id'] === $id) {
            return $index;
        }
    }
    return null;
}

/**
 * Helper: redirect kembali ke index.php lalu hentikan eksekusi.
 *
 * Dipakai di semua blok routing (tambah/edit/update/hapus) yang tadinya
 * masing-masing menulis "header('Location: index.php'); exit;" sendiri-sendiri.
 * Pola ini disebut PRG (Post/Redirect/Get) - mencegah data terkirim ulang
 * saat user menekan tombol refresh pada browser.
 */
function redirectKeIndex(): void
{
    header('Location: index.php');
    exit;
}

// FUNGSI LOGIKA CRUD 

/**
 * CREATE - Menambahkan tugas baru ke dalam session.
 * Judul kosong (setelah di-trim) akan diabaikan, tidak jadi ditambahkan.
 */
function tambahTugas(string $title): void
{
    $title = trim($title);
    if ($title === '') {
        return;
    }

    $_SESSION['todos'][] = [
        'id'     => $_SESSION['next_id'],
        'title'  => $title,
        'status' => 'belum',
    ];
    $_SESSION['next_id']++;
}

/**
 * UPDATE - Mengubah judul tugas berdasarkan ID.
 * Menggunakan cariIndexTugas() untuk menemukan posisi tugas,
 * lalu langsung mengubah nilainya lewat index (tanpa reference/&).
 */
function editTugas(int $id, string $title): void
{
    $title = trim($title);
    if ($title === '') {
        return;
    }

    $index = cariIndexTugas($id);
    if ($index !== null) {
        $_SESSION['todos'][$index]['title'] = $title;
    }
}

/**
 * UPDATE - Toggle status tugas: selesai <-> belum.
 * Sama seperti editTugas(), memakai cariIndexTugas() agar tidak
 * menduplikasi logika pencarian tugas berdasarkan ID.
 */
function toggleStatusTugas(int $id): void
{
    $index = cariIndexTugas($id);
    if ($index !== null) {
        $statusSekarang = $_SESSION['todos'][$index]['status'];
        $_SESSION['todos'][$index]['status'] = ($statusSekarang === 'selesai') ? 'belum' : 'selesai';
    }
}

/**
 * DELETE - Menghapus tugas berdasarkan ID.
 * array_filter() membuang item yang cocok, lalu array_values()
 * dipakai untuk merapikan ulang index array (0,1,2,...) karena
 * array_filter() tidak melakukan reindex otomatis.
 */
function hapusTugas(int $id): void
{
    $_SESSION['todos'] = array_values(array_filter(
        $_SESSION['todos'],
        fn($tugas) => $tugas['id'] !== $id
    ));
}

/**
 * READ - Mengambil seluruh daftar tugas dari session untuk ditampilkan.
 */
function ambilSemuaTugas(): array
{
    return $_SESSION['todos'];
}

// MEMANGGILAN FUNGSI

// 1. TAMBAH TUGAS
if (isset($_POST['tambah'])) {
    tambahTugas($_POST['title']);
    redirectKeIndex();
}

// 2. EDIT JUDUL TUGAS
if (isset($_POST['edit'])) {
    editTugas((int) $_POST['id'], $_POST['title']);
    redirectKeIndex();
}

// 3. UBAH STATUS TUGAS
if (isset($_GET['update'])) {
    toggleStatusTugas((int) $_GET['id']);
    redirectKeIndex();
}

// 4. HAPUS TUGAS
if (isset($_GET['hapus'])) {
    hapusTugas((int) $_GET['hapus']);
    redirectKeIndex();
}

// 5. BACA DATA (untuk ditampilkan di HTML)
$daftarTugas = ambilSemuaTugas();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">

    <!-- Header -->
    <header class="mb-4">
        <h2>Aplikasi To-Do List</h2>
    </header>

    <!-- Form Tambah Tugas -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="d-flex gap-2">
                <input
                    type="text"
                    name="title"
                    class="form-control"
                    placeholder="Tulis tugas baru..."
                    required
                >
                <button type="submit" name="tambah" class="btn btn-primary">
                    Tambah
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Tugas -->
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Judul Tugas</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($daftarTugas)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada tugas</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarTugas as $nomor => $tugas): ?>
                        <tr>
                            <td><?= $nomor + 1 ?></td>
                            <td><?= htmlspecialchars($tugas['title']) ?></td>
                            <td>
                                <?php if ($tugas['status'] === 'selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Belum</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-3">

                                    <!-- Checkbox untuk mengubah status tugas -->
                                    <input
                                        type="checkbox"
                                        class="form-check-input m-0"
                                        <?= $tugas['status'] === 'selesai' ? 'checked' : '' ?>
                                        onchange="window.location.href='?update=1&id=<?= $tugas['id'] ?>'"
                                    >

                                    <!-- Ikon untuk mengubah judul tugas -->
                                    <a
                                        href="#"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit tugas"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        onclick="isiFormEdit('<?= $tugas['id'] ?>', '<?= htmlspecialchars(addslashes($tugas['title'])) ?>')"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Ikon untuk menghapus tugas -->
                                    <a
                                        href="?hapus=<?= $tugas['id'] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus tugas"
                                        onclick="return confirm('Yakin hapus tugas ini?')"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Edit Tugas -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editId">
                <label for="editTitle" class="form-label">Judul Tugas</label>
                <input
                    type="text"
                    name="title"
                    id="editTitle"
                    class="form-control"
                    required
                >
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function isiFormEdit(id, title) {
        document.getElementById('editId').value = id;
        document.getElementById('editTitle').value = title;
    }
</script>
</body>
</html>