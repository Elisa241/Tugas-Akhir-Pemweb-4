<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === "admin" && $password === "admin123") {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header("Location: index.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-zinc-500 to-zinc-600 flex items-center justify-center p-4">
    <div class="bg-white/90 backdrop-blur-sm rounded-3xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="w-24 h-24 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-address-book text-white text-4xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Kontak</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="username">
                        <i class="fas fa-user mr-2 text-blue-500"></i>Username
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                            required
                            class="w-full px-4 py-4 pl-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-lg"
                            placeholder="Masukkan username"
                        >
                        <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-4 pl-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-lg"
                            placeholder="Masukkan password"
                        >
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <button 
                type="submit"
                class="w-full bg-gradient-to-r from-slate-500 to-slate-600 text-white py-4 px-4 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition duration-200 transform hover:-translate-y-1 shadow-lg"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>Masuk 
            </button>
        </form>

        <div class="mt-8 text-center">
            <div class="bg-blue-50 rounded-xl p-4">
                <p class="text-blue-800 text-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Demo Login:</strong><br>
                    Username: <strong>admin</strong><br>
                    Password: <strong>admin123</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>