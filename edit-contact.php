<?php
session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

// Fungsi untuk membaca dan menyimpan data
function getData() {
    if (!file_exists('contacts.json')) {
        return ['contacts' => [], 'groups' => [], 'call_history' => [], 'settings' => []];
    }
    $data = file_get_contents('contacts.json');
    return json_decode($data, true) ?: ['contacts' => [], 'groups' => [], 'call_history' => [], 'settings' => []];
}

function saveData($data) {
    file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
}

$data = getData();
$contacts = $data['contacts'] ?? [];
$groups = $data['groups'] ?? [];
$errors = [];

// Validasi ID
if (!isset($_GET['id']) || !isset($contacts[$_GET['id']])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$contactData = $contacts[$id];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi nama
    if (empty($_POST["name"])) {
        $errors[] = "Nama harus diisi";
    } else {
        $contactData['name'] = trim($_POST["name"]);
        if (!preg_match("/^[a-zA-Z\s\.]+$/", $contactData['name'])) {
            $errors[] = "Nama hanya boleh mengandung huruf, spasi, dan titik";
        }
    }

    // Validasi telepon
    if (empty($_POST["phone"])) {
        $errors[] = "Telepon harus diisi";
    } else {
        $contactData['phone'] = trim($_POST["phone"]);
        if (!preg_match("/^[0-9+\-\s\(\)]+$/", $contactData['phone'])) {
            $errors[] = "Format telepon tidak valid";
        }
    }

    // Validasi email
    if (!empty($_POST["email"])) {
        $contactData['email'] = trim($_POST["email"]);
        if (!filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid";
        }
    } else {
        $contactData['email'] = '';
    }

    // Data opsional
    $contactData['company'] = trim($_POST["company"] ?? '');
    $contactData['job_title'] = trim($_POST["job_title"] ?? '');
    $contactData['address'] = trim($_POST["address"] ?? '');
    $contactData['notes'] = trim($_POST["notes"] ?? '');
    $contactData['group'] = $_POST["group"] ?? '';
    $contactData['favorite'] = isset($_POST["favorite"]);
    $contactData['updated_at'] = date('Y-m-d H:i:s');

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxSize) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Hapus foto lama jika ada
            if (!empty($contactData['photo']) && file_exists($uploadDir . $contactData['photo'])) {
                unlink($uploadDir . $contactData['photo']);
            }
            
            $fileName = time() . '_' . uniqid() . '_' . $_FILES['photo']['name'];
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                $contactData['photo'] = $fileName;
            } else {
                $errors[] = "Gagal mengupload foto";
            }
        } else {
            $errors[] = "Foto harus JPG/PNG/GIF dan maksimal 2MB";
        }
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $contacts[$id] = $contactData;
        $data['contacts'] = $contacts;
        saveData($data);
        
        header("Location: index.php?message=Kontak " . urlencode($contactData['name']) . " berhasil diupdate!");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kontak - PhoneBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200 transition duration-200">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Edit Kontak</h1>
                        <p class="text-sm text-gray-500">Perbarui informasi kontak</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-semibold">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span class="font-semibold">Perbaiki error berikut:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <!-- Photo Upload -->
                <div class="text-center">
                    <div class="relative inline-block">
                        <?php if (isset($contactData['photo'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($contactData['photo']); ?>" alt="<?php echo htmlspecialchars($contactData['name']); ?>" class="w-24 h-24 rounded-2xl object-cover mx-auto mb-4">
                        <?php else: ?>
                            <div class="w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl mb-4 mx-auto">
                                <?php echo strtoupper(substr($contactData['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <label for="photo" class="absolute bottom-0 right-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-camera text-sm"></i>
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*" class="hidden">
                    </div>
                    <p class="text-sm text-gray-500">Klik ikon kamera untuk ganti foto</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informasi Utama -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                            Informasi Utama
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="name">
                                    Nama Lengkap *
                                </label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="<?php echo htmlspecialchars($contactData['name']); ?>" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Masukkan nama lengkap"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="phone">
                                    Nomor Telepon *
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    value="<?php echo htmlspecialchars($contactData['phone']); ?>" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="+62 812-3456-7890"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="email">
                                    Email
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($contactData['email'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="email@contoh.com"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Tambahan -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-green-500"></i>
                            Informasi Tambahan
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="company">
                                    Perusahaan
                                </label>
                                <input 
                                    type="text" 
                                    id="company" 
                                    name="company" 
                                    value="<?php echo htmlspecialchars($contactData['company'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Nama perusahaan"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="job_title">
                                    Posisi
                                </label>
                                <input 
                                    type="text" 
                                    id="job_title" 
                                    name="job_title" 
                                    value="<?php echo htmlspecialchars($contactData['job_title'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Jabatan pekerjaan"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Pengelompokan -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-tags mr-2 text-purple-500"></i>
                            Pengelompokan
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="group">
                                    Grup
                                </label>
                                <select 
                                    id="group" 
                                    name="group"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                >
                                    <option value="">Pilih Grup</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo htmlspecialchars($group['name']); ?>" 
                                            <?php echo (isset($contactData['group']) && $contactData['group'] === $group['name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($group['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <div class="relative">
                                        <input 
                                            type="checkbox" 
                                            id="favorite" 
                                            name="favorite" 
                                            <?php echo ($contactData['favorite'] ?? false) ? 'checked' : ''; ?>
                                            class="sr-only"
                                        >
                                        <div class="w-12 h-6 bg-gray-200 rounded-full transition duration-200 ease-in-out"></div>
                                        <div class="absolute left-0 top-0 w-6 h-6 bg-white rounded-full transition-transform duration-200 ease-in-out transform translate-x-0 shadow-md"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 flex items-center">
                                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                                        Tambah ke Favorit
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Lainnya -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-sticky-note mr-2 text-orange-500"></i>
                            Informasi Lainnya
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="address">
                                    Alamat
                                </label>
                                <textarea 
                                    id="address" 
                                    name="address" 
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Masukkan alamat lengkap"
                                ><?php echo htmlspecialchars($contactData['address'] ?? ''); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="notes">
                                    Catatan
                                </label>
                                <textarea 
                                    id="notes" 
                                    name="notes" 
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Tambahkan catatan tentang kontak ini"
                                ><?php echo htmlspecialchars($contactData['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-6 border-t">
                    <button 
                        type="submit"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition duration-200 flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-save"></i>
                        <span>Update Kontak</span>
                    </button>
                    <a 
                        href="index.php" 
                        class="flex-1 bg-gray-100 text-gray-700 py-4 px-6 rounded-xl font-bold text-lg hover:bg-gray-200 transition duration-200 text-center flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-times"></i>
                        <span>Batal</span>
                    </a>
                </div>
            </form>
        </div>
    </main>

    <style>
        input:checked + div {
            background-color: #10B981;
        }
        input:checked + div > div {
            transform: translateX(100%);
        }
    </style>
</body>
</html>