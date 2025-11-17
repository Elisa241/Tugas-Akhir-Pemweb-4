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

$data = getData();
$contacts = $data['contacts'] ?? [];

if (!isset($_GET['id']) || !isset($contacts[$_GET['id']])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$contact = $contacts[$id];

// Handle actions
if (isset($_GET['toggle_favorite'])) {
    $contacts[$id]['favorite'] = !($contacts[$id]['favorite'] ?? false);
    $data['contacts'] = $contacts;
    file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
    header("Location: view-contact.php?id=" . $id);
    exit();
}

// Add to call history
$data['call_history'][] = [
    'contact_name' => $contact['name'],
    'phone' => $contact['phone'],
    'type' => 'viewed',
    'timestamp' => date('Y-m-d H:i:s'),
    'duration' => '0:00'
];
file_put_contents('contacts.json', json_encode($data, JSON_PRETTY_PRINT));
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contact['name']); ?> - Kontak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200 transition duration-200">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Detail Kontak</h1>
                        <p class="text-sm text-gray-500">Informasi lengkap</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <a href="edit-contact.php?id=<?php echo $id; ?>" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200 transition duration-200" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="index.php?toggle_favorite=<?php echo $id; ?>" class="w-10 h-10 <?php echo ($contact['favorite'] ?? false) ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-600'; ?> rounded-xl flex items-center justify-center hover:bg-yellow-200 transition duration-200" title="<?php echo ($contact['favorite'] ?? false) ? 'Hapus dari Favorit' : 'Tambah ke Favorit'; ?>">
                        <i class="fas fa-star"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Contact Header -->
        <div class="bg-white rounded-2xl shadow-sm border p-8 text-center mb-6">
            <div class="w-32 h-32 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-4xl mx-auto mb-6 shadow-lg">
                <?php if (isset($contact['photo'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($contact['photo']); ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>" class="w-32 h-32 rounded-2xl object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            
            <h2 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($contact['name']); ?></h2>
            
            <div class="flex items-center justify-center space-x-4 mb-4">
                <?php if ($contact['favorite'] ?? false): ?>
                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                        <i class="fas fa-star mr-1"></i> Favorit
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($contact['group'])): ?>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                        <?php echo htmlspecialchars($contact['group']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="flex justify-center space-x-4 mt-6">
                <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="bg-green-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-green-600 transition duration-200 flex items-center space-x-2">
                    <i class="fas fa-phone"></i>
                    <span>Telepon</span>
                </a>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contact['phone']); ?>" class="bg-green-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-green-700 transition duration-200 flex items-center space-x-2" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </a>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden mb-6">
            <!-- Phone -->
            <div class="border-b p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-phone text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Telepon</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($contact['phone']); ?></p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 hover:bg-green-200 transition duration-200">
                            <i class="fas fa-phone"></i>
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contact['phone']); ?>" class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center text-white hover:bg-green-700 transition duration-200" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <?php if (!empty($contact['email'])): ?>
            <div class="border-b p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-envelope text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Email</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($contact['email']); ?></p>
                        </div>
                    </div>
                    <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 hover:bg-blue-200 transition duration-200">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Company & Job -->
            <?php if (!empty($contact['company']) || !empty($contact['job_title'])): ?>
            <div class="border-b p-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-briefcase text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Pekerjaan</h3>
                        <p class="text-gray-600">
                            <?php if (!empty($contact['company']) && !empty($contact['job_title'])): ?>
                                <?php echo htmlspecialchars($contact['job_title']); ?> di <?php echo htmlspecialchars($contact['company']); ?>
                            <?php elseif (!empty($contact['company'])): ?>
                                <?php echo htmlspecialchars($contact['company']); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($contact['job_title']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Address -->
            <?php if (!empty($contact['address'])): ?>
            <div class="border-b p-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Alamat</h3>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($contact['address'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($contact['notes'])): ?>
            <div class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-sticky-note text-gray-600"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-2">Catatan</h3>
                        <p class="text-gray-600 whitespace-pre-wrap"><?php echo htmlspecialchars($contact['notes']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-4">
            <a href="edit-contact.php?id=<?php echo $id; ?>" class="bg-blue-500 text-white py-3 px-4 rounded-xl font-semibold text-center hover:bg-blue-600 transition duration-200 flex items-center justify-center space-x-2">
                <i class="fas fa-edit"></i>
                <span>Edit Kontak</span>
            </a>
            <a href="index.php?delete=<?php echo $id; ?>" class="bg-red-500 text-white py-3 px-4 rounded-xl font-semibold text-center hover:bg-red-600 transition duration-200 flex items-center justify-center space-x-2"
               onclick="return confirm('Yakin ingin menghapus kontak <?php echo htmlspecialchars(addslashes($contact['name'])); ?>?')">
                <i class="fas fa-trash"></i>
                <span>Hapus Kontak</span>
            </a>
        </div>
    </main>
</body>
</html>