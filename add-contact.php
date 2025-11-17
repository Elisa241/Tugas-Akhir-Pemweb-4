<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

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
$groups = $data['groups'] ?? [];
$errors = [];
$formData = $_SESSION['temp_form_data'] ?? [];
$previewPhoto = $_SESSION['temp_photo'] ?? '';

// Photo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo_preview']) && $_FILES['photo_preview']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024;
    
    if (in_array($_FILES['photo_preview']['type'], $allowedTypes) && $_FILES['photo_preview']['size'] <= $maxSize) {
        $uploadDir = 'uploads/temp/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Hapus temp photo sebelumnya
        if (!empty($previewPhoto) && file_exists($uploadDir . $previewPhoto)) {
            unlink($uploadDir . $previewPhoto);
        }
        
        $fileName = 'temp_' . time() . '_' . $_FILES['photo_preview']['name'];
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['photo_preview']['tmp_name'], $targetFile)) {
            $_SESSION['temp_photo'] = $fileName;
            $_SESSION['temp_form_data'] = $_POST;
        }
    }
    
    // Redirect untuk refresh preview
    header("Location: add-contact.php");
    exit();
}

// Handle hapus photo preview
if (isset($_GET['remove_photo'])) {
    if (!empty($previewPhoto)) {
        $uploadDir = 'uploads/temp/';
        if (file_exists($uploadDir . $previewPhoto)) {
            unlink($uploadDir . $previewPhoto);
        }
        unset($_SESSION['temp_photo']);
    }
    header("Location: add-contact.php");
    exit();
}

// Handle form submit utama
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_contact'])) {
    // Validasi nama
    if (empty($_POST["name"])) {
        $errors['name'] = "Nama wajib diisi.";
    } else {
        $formData['name'] = trim($_POST["name"]);
        // Validasi: hanya huruf, spasi, dan titik
        if (!preg_match("/^[a-zA-Z\s\.]+$/", $formData['name'])) {
            $errors['name'] = "Nama hanya boleh mengandung huruf, spasi, dan titik.";
        }
        // Validasi: minimal 2 karakter
        elseif (strlen($formData['name']) < 2) {
            $errors['name'] = "Nama minimal 2 karakter.";
        }
    }

    // Validasi telepon
    if (empty($_POST["phone"])) {
        $errors['phone'] = "Nomor telepon wajib diisi.";
    } else {
        $formData['phone'] = trim($_POST["phone"]);
        // Hapus karakter selain angka
        $phone_clean = preg_replace('/[^0-9]/', '', $formData['phone']);
        
        // Validasi: harus angka
        if (!is_numeric($phone_clean)) {
            $errors['phone'] = "Nomor telepon harus berupa angka.";
        }
        // Validasi: minimal 12 digit
        elseif (strlen($phone_clean) < 10) {
            $errors['phone'] = "Nomor telepon minimal 10 digit.";
        }
        // Validasi: maksimal 15 digit
        elseif (strlen($phone_clean) > 15) {
            $errors['phone'] = "Nomor telepon maksimal 15 digit.";
        }
    }

    // Validasi email
    if (!empty($_POST["email"])) {
        $formData['email'] = trim($_POST["email"]);
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format email tidak valid.";
        }
    }

    // Data opsional
    $formData['company'] = trim($_POST["company"] ?? '');
    $formData['job_title'] = trim($_POST["job_title"] ?? '');
    $formData['address'] = trim($_POST["address"] ?? '');
    $formData['notes'] = trim($_POST["notes"] ?? '');
    $formData['group'] = $_POST["group"] ?? '';
    $formData['favorite'] = isset($_POST["favorite"]);
    $formData['created_at'] = date('Y-m-d H:i:s');

    // File upload
    if (!empty($previewPhoto)) {
        $tempDir = 'uploads/temp/';
        $finalDir = 'uploads/';
        $finalFileName = str_replace('temp_', '', $previewPhoto);
        $finalFile = $finalDir . $finalFileName;
        
        if (rename($tempDir . $previewPhoto, $finalFile)) {
            $formData['photo'] = $finalFileName;
        }
        
        // Cleanup session temp
        unset($_SESSION['temp_photo']);
        unset($_SESSION['temp_form_data']);
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $data['contacts'][] = $formData;
        saveData($data);
        
        header("Location: index.php?message=Kontak " . urlencode($formData['name']) . " berhasil ditambahkan!");
        exit();
    }
}

// Load temp data dari session
if (!empty($_SESSION['temp_form_data'])) {
    $formData = array_merge($formData, $_SESSION['temp_form_data']);
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kontak - PhoneBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 font-sans">

    <header class="bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200 transition duration-200">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Tambah Kontak Baru</h1>
                        <p class="text-sm text-gray-500">Lengkapi informasi kontak</p>
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
                <!-- Photo Upload-->
                <div class="text-center">
                    <div class="relative inline-block">
                        <?php if (!empty($previewPhoto)): ?>
                            <!-- Tampilkan photo preview -->
                            <div class="w-24 h-24 rounded-2xl overflow-hidden mb-4 mx-auto border-2 border-blue-500">
                                <img src="uploads/temp/<?php echo htmlspecialchars($previewPhoto); ?>" 
                                     alt="Preview Foto" 
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="absolute bottom-0 right-0 flex space-x-1">
                                <label for="photo_preview" class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-blue-600 transition duration-200 shadow-lg" title="Ganti Foto">
                                    <i class="fas fa-sync text-sm"></i>
                                </label>
                                <a href="add-contact.php?remove_photo=true" class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-white hover:bg-red-600 transition duration-200 shadow-lg" title="Hapus Foto">
                                    <i class="fas fa-times text-sm"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Tampilkan avatar default -->
                            <div class="w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl mb-4 mx-auto">
                                <?php echo isset($formData['name']) ? strtoupper(substr($formData['name'], 0, 1)) : '<i class="fas fa-camera"></i>'; ?>
                            </div>
                            <label for="photo_preview" class="absolute bottom-0 right-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-blue-600 transition duration-200 shadow-lg">
                                <i class="fas fa-camera text-sm"></i>
                            </label>
                        <?php endif; ?>
                        
                        <!-- Hidden file input untuk preview -->
                        <input type="file" id="photo_preview" name="photo_preview" accept="image/*" class="hidden" 
                               onchange="this.form.submit()">
                        
                        <!-- Hidden file input untuk final submit -->
                        <input type="hidden" name="has_photo" value="<?php echo !empty($previewPhoto) ? '1' : '0'; ?>">
                    </div>
                    
                    <p class="text-sm <?php echo !empty($previewPhoto) ? 'text-green-600 font-semibold' : 'text-gray-500'; ?>">
                        <?php echo !empty($previewPhoto) ? '✓ Foto sudah dipilih' : 'Klik ikon kamera untuk upload foto'; ?>
                    </p>
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
                                    value="<?php echo isset($formData['name']) ? htmlspecialchars($formData['name']) : ''; ?>" 
                                    required
                                    class="w-full px-4 py-3 border <?php echo isset($errors['name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Masukkan nama lengkap"
                                >
                                <?php if (isset($errors['name'])): ?>
                                    <p class="text-red-500 text-sm mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <?php echo htmlspecialchars($errors['name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="phone">
                                    Nomor Telepon *
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    value="<?php echo isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''; ?>" 
                                    required
                                    class="w-full px-4 py-3 border <?php echo isset($errors['phone']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Contoh: 6281234567890"
                                >
                                <?php if (isset($errors['phone'])): ?>
                                    <p class="text-red-500 text-sm mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <?php echo htmlspecialchars($errors['phone']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="text-gray-500 text-xs mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Format: angka saja, minimal 12 digit (contoh: 6281234567890)
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="email">
                                    Email
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>" 
                                    class="w-full px-4 py-3 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="email@contoh.com"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <p class="text-red-500 text-sm mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <?php echo htmlspecialchars($errors['email']); ?>
                                    </p>
                                <?php endif; ?>
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
                                    value="<?php echo isset($formData['company']) ? htmlspecialchars($formData['company']) : ''; ?>" 
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
                                    value="<?php echo isset($formData['job_title']) ? htmlspecialchars($formData['job_title']) : ''; ?>" 
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
                                        <option value="<?php echo htmlspecialchars($group['name']); ?>" <?php echo (isset($formData['group']) && $formData['group'] === $group['name']) ? 'selected' : ''; ?>>
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
                                            <?php echo (isset($formData['favorite']) && $formData['favorite']) ? 'checked' : ''; ?>
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
                                ><?php echo isset($formData['address']) ? htmlspecialchars($formData['address']) : ''; ?></textarea>
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
                                ><?php echo isset($formData['notes']) ? htmlspecialchars($formData['notes']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-6 border-t">
                    <button 
                        type="submit"
                        name="save_contact"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition duration-200 flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-save"></i>
                        <span>Simpan Kontak</span>
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