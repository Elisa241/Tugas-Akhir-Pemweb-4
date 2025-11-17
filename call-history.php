<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

function getData() {
    if (!file_exists('contacts.json')) {
        return ['call_history' => []];
    }
    $data = file_get_contents('contacts.json');
    return json_decode($data, true) ?: ['call_history' => []];
}

function saveData($data) {
    file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
}

$data = getData();
$call_history = $data['call_history'] ?? [];
$message = '';

// Handle hapus semua riwayat
if (isset($_GET['clear_history'])) {
    $data['call_history'] = [];
    saveData($data);
    $message = "Semua riwayat panggilan berhasil dihapus!";
    
    // Refresh data
    $data = getData();
    $call_history = $data['call_history'] ?? [];
}

// Handle hapus riwayat per item
if (isset($_GET['delete_call'])) {
    $callId = $_GET['delete_call'];
    
    // Cari riwayat berdasarkan timestamp (lebih aman daripada index)
    $new_history = [];
    $deleted = false;
    
    foreach ($call_history as $call) {
        if ($call['timestamp'] != $callId) {
            $new_history[] = $call;
        } else {
            $deleted = true;
        }
    }
    
    if ($deleted) {
        $data['call_history'] = $new_history;
        saveData($data);
        $message = "Riwayat panggilan berhasil dihapus!";
        
        // Refresh data
        $data = getData();
        $call_history = $data['call_history'] ?? [];
    }
}

// Group by date (tanpa reverse untuk menghindari masalah index)
$groupedHistory = [];
foreach ($call_history as $call) {
    $date = date('Y-m-d', strtotime($call['timestamp']));
    if (!isset($groupedHistory[$date])) {
        $groupedHistory[$date] = [];
    }
    $groupedHistory[$date][] = $call;
}

// Sort dates descending (yang terbaru di atas)
krsort($groupedHistory);

// Sort calls within each date descending
foreach ($groupedHistory as $date => &$calls) {
    usort($calls, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat</title>
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
                        <h1 class="text-xl font-bold text-gray-900">Riwayat Aktivitas</h1>
                        <p class="text-sm text-gray-500"><?php echo count($call_history); ?> aktivitas</p>
                    </div>
                </div>
                
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-history text-purple-600"></i>
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
                <a href="groups.php" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
                    <i class="fas fa-users text-xl mb-1"></i>
                    <span class="text-xs">Grup</span>
                </a>
                <a href="call-history.php" class="flex flex-col items-center text-purple-600">
                    <i class="fas fa-history text-xl mb-1"></i>
                    <span class="text-xs font-semibold">Riwayat</span>
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
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl mb-6 flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($call_history)): ?>
            <div class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                <div class="w-24 h-24 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-purple-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada riwayat</h3>
                <p class="text-gray-600 mb-6">Riwayat panggilan dan aktivitas akan muncul di sini</p>
                <a href="index.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition duration-200 inline-flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Kontak</span>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($groupedHistory as $date => $calls): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-calendar-day mr-2 text-blue-500"></i>
                        <?php 
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        
                        if ($date === $today) {
                            echo 'Hari Ini';
                        } elseif ($date === $yesterday) {
                            echo 'Kemarin';
                        } else {
                            echo date('d F Y', strtotime($date));
                        }
                        ?>
                    </h3>
                    
                    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                        <?php foreach ($calls as $call): ?>
                        <div class="border-b last:border-b-0 hover:bg-gray-50 transition duration-200">
                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center space-x-4 flex-1">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg 
                                        <?php echo $call['type'] === 'outgoing' ? 'bg-green-500' : ($call['type'] === 'incoming' ? 'bg-blue-500' : 'bg-gray-500'); ?>">
                                        <i class="fas fa-<?php echo $call['type'] === 'outgoing' ? 'phone-alt' : ($call['type'] === 'incoming' ? 'phone' : 'eye'); ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($call['contact_name']); ?></h4>
                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($call['phone']); ?></p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-xs text-gray-500 capitalize">
                                                <?php 
                                                if ($call['type'] === 'outgoing') echo 'Panggilan Keluar';
                                                elseif ($call['type'] === 'incoming') echo 'Panggilan Masuk';
                                                else echo 'Dilihat';
                                                ?>
                                            </span>
                                            <?php if (!empty($call['duration']) && $call['duration'] !== '0:00'): ?>
                                                <span class="text-xs text-gray-500">•</span>
                                                <span class="text-xs text-gray-500"><?php echo $call['duration']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-right flex items-center space-x-2">
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500 whitespace-nowrap">
                                            <?php echo date('H:i', strtotime($call['timestamp'])); ?>
                                        </p>
                                        <?php if ($call['type'] !== 'viewed'): ?>
                                        <a href="tel:<?php echo htmlspecialchars($call['phone']); ?>" class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200 transition duration-200 mt-2" title="Telepon">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Modal konfirmasi hapus tanpa JavaScript -->
                                    <div class="relative">
                                        <a href="#delete-<?php echo urlencode($call['timestamp']); ?>" 
                                           class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 hover:bg-red-200 transition duration-200"
                                           title="Hapus Riwayat">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                        
                                        <!-- Modal konfirmasi -->
                                        <div id="delete-<?php echo urlencode($call['timestamp']); ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                                            <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
                                                <div class="text-center mb-4">
                                                    <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                                                    </div>
                                                    <h3 class="text-lg font-bold text-gray-900">Hapus Riwayat?</h3>
                                                    <p class="text-gray-600 mt-2">Riwayat panggilan dengan <strong><?php echo htmlspecialchars($call['contact_name']); ?></strong> akan dihapus.</p>
                                                </div>
                                                <div class="flex space-x-3">
                                                    <a href="call-history.php" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold text-center hover:bg-gray-200 transition duration-200">
                                                        Batal
                                                    </a>
                                                    <a href="call-history.php?delete_call=<?php echo urlencode($call['timestamp']); ?>" 
                                                       class="flex-1 bg-red-500 text-white py-3 rounded-xl font-semibold text-center hover:bg-red-600 transition duration-200">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Clear History Button dengan Modal -->
            <div class="mt-8 text-center">
                <a href="#clear-all" 
                   class="bg-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-600 transition duration-200 inline-flex items-center space-x-2">
                    <i class="fas fa-trash"></i>
                    <span>Hapus Semua Riwayat</span>
                </a>
                
                <!-- Modal Hapus Semua -->
                <div id="clear-all" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Hapus Semua Riwayat?</h3>
                            <p class="text-gray-600 mt-2">Semua <?php echo count($call_history); ?> riwayat panggilan akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="call-history.php" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold text-center hover:bg-gray-200 transition duration-200">
                                Batal
                            </a>
                            <a href="call-history.php?clear_history=true" 
                               class="flex-1 bg-red-500 text-white py-3 rounded-xl font-semibold text-center hover:bg-red-600 transition duration-200">
                                Hapus Semua
                            </a>
                        </div>
                    </div>
                </div>
                
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Total: <?php echo count($call_history); ?> riwayat
                </p>
            </div>
        <?php endif; ?>
    </main>

    <style>
        /* CSS untuk modal tanpa JavaScript */
        .hidden {
            display: none;
        }
        
        /* Tampilkan modal ketika target */
        :target {
            display: flex;
        }
        
        /* Close modal ketika klik di luar */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        
        :target .modal-overlay {
            display: flex;
        }
    </style>
</body>
</html>