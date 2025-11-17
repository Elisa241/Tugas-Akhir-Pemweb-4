<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

function getData() {
    if (!file_exists('contacts.json')) {
        return ['settings' => ['theme' => 'light']];
    }
    $data = file_get_contents('contacts.json');
    return json_decode($data, true) ?: ['settings' => ['theme' => 'light']];
}

function saveData($data) {
    file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
}

$data = getData();
$settings = $data['settings'] ?? ['theme' => 'light'];
$contacts = $data['contacts'] ?? [];
$message = '';

// Handle toggle theme
if (isset($_GET['toggle_theme'])) {
    $newTheme = $settings['theme'] === 'dark' ? 'light' : 'dark';
    $settings['theme'] = $newTheme;
    $data['settings'] = $settings;
    saveData($data);
    
    // Set session untuk theme
    $_SESSION['theme'] = $newTheme;
    
    header("Location: settings.php");
    exit();
}

// Handle toggle notifications
if (isset($_GET['toggle_notifications'])) {
    $settings['notifications'] = !($settings['notifications'] ?? true);
    $data['settings'] = $settings;
    saveData($data);
    
    header("Location: settings.php");
    exit();
}

// Handle toggle sync
if (isset($_GET['toggle_sync'])) {
    $settings['sync'] = !($settings['sync'] ?? false);
    $data['settings'] = $settings;
    saveData($data);
    
    header("Location: settings.php");
    exit();
}

// Get current theme from session or settings
$currentTheme = $_SESSION['theme'] ?? $settings['theme'] ?? 'light';
$notificationsEnabled = $settings['notifications'] ?? true;
$syncEnabled = $settings['sync'] ?? false;
?>
<!DOCTYPE html>
<html lang="id" class="h-full <?php echo $currentTheme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PhoneBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 font-sans transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700 sticky top-0 z-10 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200">
                        <i class="fas fa-arrow-left text-gray-600 dark:text-gray-300"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pengaturan</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelola Kontak</p>
                    </div>
                </div>
                
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cog text-gray-600 dark:text-gray-300"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation -->
    <nav class="bg-white dark:bg-gray-800 border-t dark:border-gray-700 fixed bottom-0 w-full z-10 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-around items-center py-3">
                <a href="index.php" class="flex flex-col items-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-user-friends text-xl mb-1"></i>
                    <span class="text-xs">Kontak</span>
                </a>
                <a href="favorites.php" class="flex flex-col items-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-star text-xl mb-1"></i>
                    <span class="text-xs">Favorit</span>
                </a>
                <a href="groups.php" class="flex flex-col items-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-users text-xl mb-1"></i>
                    <span class="text-xs">Grup</span>
                </a>
                <a href="call-history.php" class="flex flex-col items-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                    <i class="fas fa-history text-xl mb-1"></i>
                    <span class="text-xs">Riwayat</span>
                </a>
                <a href="settings.php" class="flex flex-col items-center text-gray-600 dark:text-gray-300">
                    <i class="fas fa-cog text-xl mb-1"></i>
                    <span class="text-xs font-semibold">Pengaturan</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24">
        <?php if ($message): ?>
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-2xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border dark:border-gray-700 p-6 mb-6 transition-colors duration-200">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                Profil Pengguna
            </h2>
            
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                    <p class="text-gray-600 dark:text-gray-400">Administrator</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500">Login: <?php echo $_SESSION['login_time']; ?></p>
                </div>
            </div>
        </div>

        <!-- App Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border dark:border-gray-700 p-6 mb-6 transition-colors duration-200">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-sliders-h mr-2 text-green-500"></i>
                Pengaturan Aplikasi
            </h2>
            
            <div class="space-y-4">

                <!-- Notifications Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-colors duration-200">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Notifikasi</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo $notificationsEnabled ? 'Aktif' : 'Nonaktif'; ?></p>
                    </div>
                    <a href="settings.php?toggle_notifications=true" 
                       class="relative inline-flex items-center cursor-pointer">
                        <div class="w-12 h-6 <?php echo $notificationsEnabled ? 'bg-green-500' : 'bg-gray-300'; ?> rounded-full transition-colors duration-200"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform duration-200 transform <?php echo $notificationsEnabled ? 'translate-x-6' : 'translate-x-0'; ?> shadow-md"></div>
                    </a>
                </div>

                <!-- Sync Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-colors duration-200">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Sinkronisasi Otomatis</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo $syncEnabled ? 'Aktif' : 'Nonaktif'; ?></p>
                    </div>
                    <a href="settings.php?toggle_sync=true" 
                       class="relative inline-flex items-center cursor-pointer">
                        <div class="w-12 h-6 <?php echo $syncEnabled ? 'bg-purple-500' : 'bg-gray-300'; ?> rounded-full transition-colors duration-200"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform duration-200 transform <?php echo $syncEnabled ? 'translate-x-6' : 'translate-x-0'; ?> shadow-md"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border dark:border-gray-700 p-6 mb-6 transition-colors duration-200">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-database mr-2 text-orange-500"></i>
                Manajemen Data
            </h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl transition-colors duration-200">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Total Kontak</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo count($contacts); ?> kontak tersimpan</p>
                    </div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo count($contacts); ?></div>
                </div>

                <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-xl transition-colors duration-200">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Kontak Favorit</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Kontak yang ditandai</p>
                    </div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        <?php echo count(array_filter($contacts, function($c) { return $c['favorite'] ?? false; })); ?>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl transition-colors duration-200">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Grup Kontak</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Kategori yang dibuat</p>
                    </div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        <?php echo count($data['groups'] ?? []); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border dark:border-gray-700 p-6 transition-colors duration-200">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-tools mr-2 text-red-500"></i>
                Aksi
            </h2>

                <a href="logout.php" class="w-full bg-red-500 hover:bg-red-600 text-white py-3 px-4 rounded-xl font-semibold transition duration-200 flex items-center justify-center space-x-2 block text-center">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>