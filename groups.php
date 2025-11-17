<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

function getData() {
    if (!file_exists('contacts.json')) {
        return ['contacts' => [], 'groups' => []];
    }
    $data = file_get_contents('contacts.json');
    return json_decode($data, true) ?: ['contacts' => [], 'groups' => []];
}

function saveData($data) {
    file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
}

$data = getData();
$contacts = $data['contacts'] ?? [];
$groups = $data['groups'] ?? [];

$errors = [];
$success = '';
$showModal = false;

// Handle buka modal
if (isset($_GET['show_modal'])) {
    $showModal = true;
}

// Handle form tambah grup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $groupName = trim($_POST['group_name'] ?? '');
    $groupColor = $_POST['group_color'] ?? '#3B82F6';

    // Validasi
    if (empty($groupName)) {
        $errors[] = "Nama grup harus diisi";
    } else if (strlen($groupName) < 2) {
        $errors[] = "Nama grup minimal 2 karakter";
    } else {
        // Cek apakah nama grup sudah ada
        foreach ($groups as $group) {
            if (strtolower($group['name']) === strtolower($groupName)) {
                $errors[] = "Grup dengan nama '$groupName' sudah ada";
                break;
            }
        }
    }

    if (empty($errors)) {
        // Generate ID baru
        $newId = 1;
        if (!empty($groups)) {
            $newId = max(array_column($groups, 'id')) + 1;
        }

        // Tambah grup baru
        $newGroup = [
            'id' => $newId,
            'name' => $groupName,
            'color' => $groupColor,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $groups[] = $newGroup;
        $data['groups'] = $groups;
        saveData($data);

        $success = "Grup '$groupName' berhasil dibuat!";
        $showModal = false;
        
        // Refresh data
        $data = getData();
        $groups = $data['groups'] ?? [];
    } else {
        $showModal = true;
    }
}

// Handle hapus grup
if (isset($_GET['delete_group'])) {
    $groupId = (int)$_GET['delete_group'];
    
    // Cari grup
    $groupIndex = null;
    $groupName = '';
    foreach ($groups as $index => $group) {
        if ($group['id'] === $groupId) {
            $groupIndex = $index;
            $groupName = $group['name'];
            break;
        }
    }

    if ($groupIndex !== null) {
        // Hapus grup dari array
        array_splice($groups, $groupIndex, 1);
        $data['groups'] = $groups;
        
        // Update kontak yang menggunakan grup ini
        foreach ($contacts as $index => $contact) {
            if (($contact['group'] ?? '') === $groupName) {
                $contacts[$index]['group'] = '';
            }
        }
        $data['contacts'] = $contacts;
        
        saveData($data);
        $success = "Grup '$groupName' berhasil dihapus!";
        
        // Refresh data
        $data = getData();
        $groups = $data['groups'] ?? [];
        $contacts = $data['contacts'] ?? [];
    }
}

// Hitung jumlah kontak per grup
$groupCounts = [];
foreach ($groups as $group) {
    $groupCounts[$group['name']] = 0;
}

foreach ($contacts as $contact) {
    if (!empty($contact['group']) && isset($groupCounts[$contact['group']])) {
        $groupCounts[$contact['group']]++;
    }
}

// Kontak tanpa grup
$ungroupedContacts = array_filter($contacts, function($contact) {
    return empty($contact['group']);
});
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grup Kontak - PhoneBook</title>
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
                        <h1 class="text-xl font-bold text-gray-900">Grup Kontak</h1>
                        <p class="text-sm text-gray-500"><?php echo count($groups); ?> grup tersedia</p>
                    </div>
                </div>
                
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation -->
    <nav class="bg-white border-t fixed bottom-0 w-full z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-around items-center py-3">
                <a href="index.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-user-friends text-xl mb-1"></i>
                    <span class="text-xs">Kontak</span>
                </a>
                <a href="favorites.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-star text-xl mb-1"></i>
                    <span class="text-xs">Favorit</span>
                </a>
                <a href="groups.php" class="flex flex-col items-center text-blue-600">
                    <i class="fas fa-users text-xl mb-1"></i>
                    <span class="text-xs font-semibold">Grup</span>
                </a>
                <a href="call-history.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-history text-xl mb-1"></i>
                    <span class="text-xs">Riwayat</span>
                </a>
                <a href="settings.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-cog text-xl mb-1"></i>
                    <span class="text-xs">Pengaturan</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="font-semibold">Error:</span>
                </div>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Modal Tambah Grup - Tampil berdasarkan PHP condition -->
        <?php if ($showModal): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Grup Baru</h3>
                    <a href="groups.php" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Grup</label>
                        <input 
                            type="text" 
                            name="group_name" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Masukkan nama grup"
                            maxlength="20"
                            value="<?php echo isset($_POST['group_name']) ? htmlspecialchars($_POST['group_name']) : ''; ?>"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Warna Grup</label>
                        <div class="grid grid-cols-5 gap-2">
                            <?php 
                            $colorOptions = [
                                '#EF4444' => 'Merah',
                                '#F59E0B' => 'Kuning',
                                '#10B981' => 'Hijau', 
                                '#3B82F6' => 'Biru',
                                '#8B5CF6' => 'Ungu',
                                '#EC4899' => 'Pink',
                                '#6B7280' => 'Abu',
                                '#84CC16' => 'Hijau Muda',
                                '#F97316' => 'Oranye',
                                '#06B6D4' => 'Cyan'
                            ];
                            $selectedColor = $_POST['group_color'] ?? '#3B82F6';
                            foreach ($colorOptions as $color => $name): 
                            ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="group_color" value="<?php echo $color; ?>" 
                                           class="sr-only peer" <?php echo $color === $selectedColor ? 'checked' : ''; ?>>
                                    <div class="w-8 h-8 rounded-lg border-2 border-transparent peer-checked:border-gray-800 transition-all duration-200" 
                                         style="background-color: <?php echo $color; ?>"
                                         title="<?php echo $name; ?>"></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 pt-4">
                        <a href="groups.php" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition duration-200 text-center">
                            Batal
                        </a>
                        <button type="submit" name="create_group" class="flex-1 bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-600 transition duration-200">
                            Buat Grup
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Grup Default -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-layer-group mr-2 text-blue-500"></i>
                    Grup Kontak
                </h2>
                <a href="groups.php?show_modal=true" class="bg-blue-500 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-600 transition duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Grup</span>
                </a>
            </div>
            
            <?php if (empty($groups)): ?>
                <div class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada grup</h3>
                    <p class="text-gray-600 mb-6">Buat grup pertama untuk mengorganisir kontak Anda</p>
                    <a href="groups.php?show_modal=true" class="bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-600 transition duration-200 inline-flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Buat Grup Pertama</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($groups as $group): ?>
                    <div class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition duration-200">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-semibold" style="background-color: <?php echo $group['color']; ?>">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($group['name']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo $groupCounts[$group['name']] ?? 0; ?> kontak</p>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="group-detail.php?group=<?php echo urlencode($group['name']); ?>" class="flex-1 bg-blue-100 text-blue-600 py-2 px-3 rounded-lg text-sm font-semibold text-center hover:bg-blue-200 transition duration-200">
                                Lihat Kontak
                            </a>
                            <a href="groups.php?delete_group=<?php echo $group['id']; ?>" 
                               class="px-3 bg-red-100 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-200 transition duration-200 flex items-center justify-center"
                               title="Hapus Grup"
                               onclick="return confirm('Yakin ingin menghapus grup <?php echo htmlspecialchars(addslashes($group['name'])); ?>? Kontak dalam grup ini akan menjadi tanpa grup.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kontak Tanpa Grup -->
        <?php if (!empty($ungroupedContacts)): ?>
        <div>
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-question-circle mr-2 text-gray-500"></i>
                Kontak Tanpa Grup
                <span class="ml-2 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-sm">
                    <?php echo count($ungroupedContacts); ?> kontak
                </span>
            </h2>
            
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <?php 
                $ungroupedIndexes = array_keys($ungroupedContacts);
                foreach ($ungroupedIndexes as $index): 
                    $contact = $ungroupedContacts[$index];
                ?>
                <div class="border-b last:border-b-0 hover:bg-gray-50 transition duration-200">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-gray-400 to-gray-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg">
                                <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 text-lg truncate"><?php echo htmlspecialchars($contact['name']); ?></h3>
                                <p class="text-gray-600 text-sm truncate"><?php echo htmlspecialchars($contact['phone']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <a href="view-contact.php?id=<?php echo $index; ?>" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200 transition duration-200" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit-contact.php?id=<?php echo $index; ?>" class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200 transition duration-200" title="Edit untuk Tambah Grup">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>