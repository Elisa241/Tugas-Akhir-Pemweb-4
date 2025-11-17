<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

function getData() {
    if (!file_exists('contacts.json')) {
        return ['contacts' => []];
    }
    $data = file_get_contents('contacts.json');
    return json_decode($data, true) ?: ['contacts' => []];
}

$data = getData();
$contacts = $data['contacts'] ?? [];

// Filter favorites
$favorites = array_filter($contacts, function($contact) {
    return ($contact['favorite'] ?? false) === true;
});

// Handle toggle favorite
if (isset($_GET['toggle_favorite'])) {
    $id = (int)$_GET['toggle_favorite'];
    if (isset($contacts[$id])) {
        $contacts[$id]['favorite'] = !($contacts[$id]['favorite'] ?? false);
        $data['contacts'] = $contacts;
        file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
        header("Location: favorites.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Favorit - PhoneBook</title>
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
                        <h1 class="text-xl font-bold text-gray-900">Kontak Favorit</h1>
                        <p class="text-sm text-gray-500"><?php echo count($favorites); ?> kontak favorit</p>
                    </div>
                </div>
                
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-yellow-600"></i>
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
                <a href="favorites.php" class="flex flex-col items-center text-yellow-600">
                    <i class="fas fa-star text-xl mb-1"></i>
                    <span class="text-xs font-semibold">Favorit</span>
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
        <?php if (empty($favorites)): ?>
            <div class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                <div class="w-24 h-24 bg-yellow-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-star text-yellow-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada favorit</h3>
                <p class="text-gray-600 mb-6">Tambahkan kontak ke favorit untuk mengaksesnya dengan cepat</p>
                <a href="index.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition duration-200 inline-flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Kontak</span>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $favoriteIndexes = array_keys($favorites);
                foreach ($favoriteIndexes as $originalIndex): 
                    $contact = $contacts[$originalIndex];
                ?>
                <div class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition duration-200">
                    <div class="text-center mb-4">
                        <div class="w-20 h-20 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-3 text-white font-bold text-2xl">
                            <?php if (isset($contact['photo'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($contact['photo']); ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>" class="w-20 h-20 rounded-2xl object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-bold text-gray-900 text-lg mb-1"><?php echo htmlspecialchars($contact['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($contact['phone']); ?></p>
                        <?php if (!empty($contact['group'])): ?>
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                <?php echo htmlspecialchars($contact['group']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-center space-x-3">
                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200 transition duration-200" title="Telepon">
                            <i class="fas fa-phone"></i>
                        </a>
                        <a href="view-contact.php?id=<?php echo $originalIndex; ?>" class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200 transition duration-200" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="favorites.php?toggle_favorite=<?php echo $originalIndex; ?>" class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 hover:bg-yellow-200 transition duration-200" title="Hapus dari Favorit">
                            <i class="fas fa-star"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>