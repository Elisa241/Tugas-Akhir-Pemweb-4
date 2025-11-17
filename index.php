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
$contacts = $data['contacts'] ?? [];
$groups = $data['groups'] ?? [];
$call_history = $data['call_history'] ?? [];

// Handle actions
if (isset($_GET['toggle_favorite'])) {
    $id = (int)$_GET['toggle_favorite'];
    if (isset($contacts[$id])) {
        $contacts[$id]['favorite'] = !($contacts[$id]['favorite'] ?? false);
        $data['contacts'] = $contacts;
        saveData($data);
        header("Location: index.php");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($contacts[$id])) {
        $contactName = $contacts[$id]['name'];
        unset($contacts[$id]);
        $data['contacts'] = array_values($contacts);
        saveData($data);
        header("Location: index.php?message=Kontak " . urlencode($contactName) . " berhasil dihapus!");
        exit();
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $filteredContacts = [];
    foreach ($contacts as $contact) {
        if (stripos($contact['name'] ?? '', $search) !== false || 
            stripos($contact['phone'] ?? '', $search) !== false ||
            stripos($contact['email'] ?? '', $search) !== false) {
            $filteredContacts[] = $contact;
        }
    }
    $contacts = $filteredContacts;
}

// Get favorites and recent contacts
$favorites = array_filter($contacts, function($contact) {
    return ($contact['favorite'] ?? false) === true;
});
$recentContacts = array_slice($contacts, 0, 5);

// Group contacts by first letter A-Z
$grouped = [];
foreach ($contacts as $i => $c) {
    $letter = strtoupper(substr($c["name"], 0, 1));
    if (!preg_match('/[A-Z]/', $letter)) $letter = '#';

    $grouped[$letter][] = ["index" => $i, "data" => $c];
}

ksort($grouped); // urutkan A-Z

// Get all available letters for navigation
$availableLetters = array_keys($grouped);

?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contacts-main-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            height: 60vh; /* Ganti max-height jadi height */
            overflow: hidden;
            position: relative;
        }
        .contacts-wrapper {
            display: flex;
            height: 100%; /* Pastikan wrapper mengambil full height */
        }
        .contact-container {
            flex: 1;
            overflow-y: auto; /* Scroll vertikal */
            height: 100%;
            scroll-behavior: smooth;
        }
        .alphabet-sidebar {
            width: 50px;
            background: #f8fafc;
            border-left: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 4px;
            overflow-y: auto; /* Scroll vertikal */
            height: 100%;
        }
        .alphabet-sidebar a {
            display: block;
            text-align: center;
            width: 32px;
            height: 32px;
            line-height: 32px;
            font-size: 13px;
            font-weight: bold;
            color: #3b82f6;
            margin: 3px 0;
            border-radius: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .alphabet-sidebar a:hover {
            background-color: #3b82f6;
            color: white;
        }
        .alphabet-sidebar span {
            display: block;
            text-align: center;
            width: 32px;
            height: 32px;
            line-height: 32px;
            font-size: 13px;
            color: #d1d5db;
            margin: 3px 0;
        }
        .section-header {
            position: sticky;
            top: 0;
            background: #dbeafe;
            z-index: 10;
        }
        /* Custom scrollbar */
        .contact-container::-webkit-scrollbar,
        .alphabet-sidebar::-webkit-scrollbar {
            width: 8px;
        }
        .contact-container::-webkit-scrollbar-track,
        .alphabet-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .contact-container::-webkit-scrollbar-thumb,
        .alphabet-sidebar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        .contact-container::-webkit-scrollbar-thumb:hover,
        .alphabet-sidebar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-address-book text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Kontak</h1>
                        <p class="text-sm text-gray-500"><?php echo count($contacts); ?> kontak</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <p class="text-xs text-gray-500">Online</p>
                    </div>
                    
                    <!-- Tombol Logout -->
                    <div class="relative group">
                        <div class="w-12 h-12 bg-gradient-to-r from-zinc-500 to-zinc-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-lg cursor-pointer">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border py-2 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                                <p class="text-xs text-gray-500">Login: <?php echo $_SESSION['login_time']; ?></p>
                            </div>
                            <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition duration-200">
                                <i class="fas fa-sign-out-alt w-4"></i>
                                <span>Keluar</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation -->
    <nav class="bg-white border-t fixed bottom-0 w-full z-10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-around items-center py-3">
                <a href="index.php" class="flex flex-col items-center text-blue-600">
                    <i class="fas fa-user-friends text-xl mb-1"></i>
                    <span class="text-xs font-semibold">Kontak</span>
                </a>
                <a href="favorites.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-star text-xl mb-1"></i>
                    <span class="text-xs">Favorit</span>
                </a>
                <a href="groups.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-users text-xl mb-1"></i>
                    <span class="text-xs">Grup</span>
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
        <?php if (isset($_GET['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <span><?php echo htmlspecialchars($_GET['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="add-contact.php" class="bg-white rounded-2xl shadow-sm border p-4 text-center hover:shadow-md transition duration-200">
                <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-user-plus text-green-600 text-xl"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">Tambah Kontak</span>
            </a>
            
            <a href="favorites.php" class="bg-white rounded-2xl shadow-sm border p-4 text-center hover:shadow-md transition duration-200">
                <div class="w-12 h-12 bg-yellow-100 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-star text-yellow-600 text-xl"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">Favorit (<?php echo count($favorites); ?>)</span>
            </a>
            
            <a href="groups.php" class="bg-white rounded-2xl shadow-sm border p-4 text-center hover:shadow-md transition duration-200">
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">Grup (<?php echo count($groups); ?>)</span>
            </a>
            
            <a href="call-history.php" class="bg-white rounded-2xl shadow-sm border p-4 text-center hover:shadow-md transition duration-200">
                <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-history text-purple-600 text-xl"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">Riwayat</span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded-2xl shadow-sm border p-4 mb-6">
            <form method="GET" action="" class="flex space-x-3">
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Cari kontak..." 
                        class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <?php if ($search): ?>
                    <a 
                        href="index.php" 
                        class="bg-gray-100 text-gray-700 px-4 py-3 rounded-xl font-semibold hover:bg-gray-200 transition duration-200 flex items-center"
                    >
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- All Contacts in Single Box with A-Z Grouping -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-users mr-2 text-gray-600"></i>
                    Semua Kontak
                </h2>
                <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                    <?php echo count($contacts); ?> kontak
                </span>
            </div>

            <?php if (empty($contacts)): ?>
                <div class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        <?php echo $search ? 'Kontak tidak ditemukan' : 'Belum ada kontak'; ?>
                    </h3>
                    <p class="text-gray-600 mb-6">
                        <?php echo $search ? 'Coba dengan kata kunci lain' : 'Mulai dengan menambahkan kontak pertama Anda'; ?>
                    </p>
                    <a href="add-contact.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition duration-200 inline-flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Kontak Pertama</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- Main Container dengan Scroll Bersama -->
                <div class="contacts-main-container">
                    <div class="contacts-wrapper">
                        <!-- Main Contacts Container -->
                        <div class="contact-container">
                            <?php foreach ($grouped as $letter => $items): ?>
                                <!-- Header Huruf Abjad -->
                                <div id="section-<?= $letter ?>" class="section-header px-4 py-2 border-b">
                                    <h3 class="text-blue-700 font-bold text-lg">
                                        <?= $letter ?>
                                    </h3>
                                </div>

                                <?php foreach ($items as $entry): 
                                    $index = $entry["index"];
                                    $contact = $entry["data"];
                                ?>
                                <div class="border-b last:border-b-0 hover:bg-gray-50 transition duration-200">
                                    <div class="flex items-center justify-between p-4">
                                        <!-- KIRI -->
                                        <div class="flex items-center space-x-4 flex-1">
                                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg">
                                                <?= strtoupper(substr($contact['name'], 0, 1)); ?>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <h3 class="font-semibold text-gray-900 text-lg truncate">
                                                        <?= htmlspecialchars($contact['name']); ?>
                                                    </h3>
                                                    <?php if ($contact['favorite'] ?? false): ?>
                                                        <i class="fas fa-star text-yellow-500 text-sm"></i>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-gray-600 text-sm truncate">
                                                    <?= htmlspecialchars($contact['phone']); ?>
                                                </p>

                                                <?php if (!empty($contact['group'])): ?>
                                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mt-1">
                                                        <?= htmlspecialchars($contact['group']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- KANAN -->
                                        <div class="flex items-center space-x-2">
                                            <a href="tel:<?= htmlspecialchars($contact['phone']); ?>" class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                            <a href="view-contact.php?id=<?= $index; ?>" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?toggle_favorite=<?= $index; ?>" class="w-10 h-10 <?= ($contact['favorite'] ?? false) ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-600'; ?> rounded-xl flex items-center justify-center hover:bg-yellow-200">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Alphabet Navigation Sidebar -->
                        <div class="alphabet-sidebar">
                            <?php foreach (range('A', 'Z') as $letter): ?>
                                <?php if (in_array($letter, $availableLetters)): ?>
                                    <a href="#section-<?= $letter ?>"><?= $letter ?></a>
                                <?php else: ?>
                                    <span><?= $letter ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (in_array('#', $availableLetters)): ?>
                                <a href="#section-#">#</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Action Button -->
    <a href="add-contact.php" class="fixed bottom-24 right-6 w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg hover:shadow-xl transition duration-200 transform hover:scale-110 z-20">
        <i class="fas fa-plus text-2xl"></i>
    </a>
</body>
</html>