<?php
session_start();
if (isset($_GET['reset'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
// Inisialisasi struktur data array of objects (sesuai instruksi soal)
if (!isset($_SESSION['todos'])) {
    $_SESSION['todos'] = [
        ['id' => 1, 'title' => 'Belajar PHP', 'status' => 'belum'],
        ['id' => 2, 'title' => 'Kerjakan tugas UX', 'status' => 'selesai'],
    ];
    $_SESSION['next_id'] = 3;
}

// 1. TAMBAH TUGAS (CREATE)
if (isset($_POST['tambah'])) {
    $judulTugas = trim($_POST['title']);

    if ($judulTugas !== '') {
        $_SESSION['todos'][] = [
            'id'     => $_SESSION['next_id'],
            'title'  => $judulTugas,
            'status' => 'belum',
        ];
        $_SESSION['next_id']++;
    }

    header('Location: index.php');
    exit;
}

// 2. EDIT JUDUL TUGAS (UPDATE) - dipicu oleh form modal edit
if (isset($_POST['edit'])) {
    $idTugas   = (int) $_POST['id'];
    $judulBaru = trim($_POST['title']);

    if ($judulBaru !== '') {
        foreach ($_SESSION['todos'] as &$tugas) {
            if ($tugas['id'] === $idTugas) {
                $tugas['title'] = $judulBaru;
                break;
            }
        }
        unset($tugas);
    }

    header('Location: index.php');
    exit;
}

// 3. UBAH STATUS TUGAS (UPDATE) - dipicu oleh checkbox
if (isset($_GET['update'])) {
    $idTugas = (int) $_GET['id'];

    foreach ($_SESSION['todos'] as &$tugas) {
        if ($tugas['id'] === $idTugas) {
            $tugas['status'] = ($tugas['status'] === 'selesai') ? 'belum' : 'selesai';
            break;
        }
    }
    unset($tugas);

    header('Location: index.php');
    exit;
}

// 4. HAPUS TUGAS (DELETE)
if (isset($_GET['hapus'])) {
    $idTugas = (int) $_GET['hapus'];

    $_SESSION['todos'] = array_values(array_filter(
        $_SESSION['todos'],
        fn($tugas) => $tugas['id'] !== $idTugas
    ));

    header('Location: index.php');
    exit;
}

// 5. BACA DATA (READ)
$daftarTugas = $_SESSION['todos'];
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

                                    <!-- Simbol (ikon) untuk menghapus tugas -->
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
                <label class="form-label">Judul Tugas</label>
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