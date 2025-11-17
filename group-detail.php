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

$data = getData();
$contacts = $data['contacts'] ?? [];
$groups = $data['groups'] ?? [];

$groupName = $_GET['group'] ?? '';
if (empty($groupName)) {
    header("Location: groups.php");
    exit();
}

// Filter kontak berdasarkan grup
$groupContacts = array_filter($contacts, function($contact) use ($groupName) {
    return ($contact['group'] ?? '') === $groupName;
});

// Cari info grup
$groupInfo = null;
foreach ($groups as $group) {
    if ($group['name'] === $groupName) {
        $groupInfo = $group;
        break;
    }
}

if (!$groupInfo) {
    header("Location: groups.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($groupName); ?> - PhoneBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <a href="groups.php" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200 transition duration-200">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-semibold" style="background-color: <?php echo $groupInfo['color']; ?>">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($groupName); ?></h1>
                            <p class="text-sm text-gray-500"><?php echo count($groupContacts); ?> kontak</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (empty($groupContacts)): ?>
            <div class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada kontak</h3>
                <p class="text-gray-600 mb-6">Belum ada kontak dalam grup ini</p>
                <a href="index.php" class="bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-600 transition duration-200 inline-flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Kontak</span>
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <?php foreach ($groupContacts as $index => $contact): ?>
                <div class="border-b last:border-b-0 hover:bg-gray-50 transition duration-200">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg">
                                <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($contact['name']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($contact['phone']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200 transition duration-200">
                                <i class="fas fa-phone"></i>
                            </a>
                            <a href="view-contact.php?id=<?php echo $index; ?>" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200 transition duration-200">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>