<?php
require_once '../includes/config.php';

// If already logged in, redirect to dashboard
$auth = new Auth();
if ($auth->isAdminLoggedIn()) {
    Helper::redirect(APP_URL . '/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    $result = $auth->loginAdmin($username, $password);

    if ($result['success']) {
        if ($rememberMe) {
            $_SESSION['remember_me'] = 1;
            setcookie('admin_username', $username, time() + (86400 * 30), '/');
        }
        Helper::redirect(APP_URL . '/admin/dashboard.php');
    } else {
        $error = $result['message'];
    }
}

// Check for remembered username
$rememberedUsername = $_COOKIE['admin_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?>- Admin Login</title>
    <link rel="icon" href="pabson-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 35%, #2563eb 70%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            pointer-events: none;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(30, 58, 138, 0.15);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            pointer-events: none;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25), 0 0 100px rgba(30, 58, 138, 0.2);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
        }
        
        .logo {
            font-size: 3.5rem;
            margin-bottom: 15px;
            display: inline-block;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .competition-title {
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.95;
            margin-bottom: 10px;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 0.95rem;
            opacity: 0.95;
            margin: 0;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #999;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0;
            cursor: pointer;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .form-check-input:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .form-check-label {
            margin-left: 10px;
            cursor: pointer;
            color: #555;
            margin-bottom: 0;
        }
        
        .btn-signin {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.4);
            color: white;
        }
        
        .btn-signin:active {
            transform: translateY(0);
        }
        
        .error-alert {
            background: #fee;
            border: 2px solid #f5576c;
            color: #c00;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-alert i {
            font-size: 1.2rem;
        }
        
        .credentials-info {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(59, 130, 246, 0.08) 100%);
            border-left: 4px solid #2563eb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 0.85rem;
        }
        
        .credentials-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .credentials-info strong {
            color: #1e3a8a;
            font-weight: 600;
        }
        
        .credentials-label {
            display: inline-block;
            min-width: 85px;
            font-weight: 600;
            color: #2563eb;
        }
        
        .team-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            display: none;
        }
        
        .team-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .team-link a:hover {
            color: #1e3a8a;
        }
        
        @media (max-width: 576px) {
            .login-header {
                padding: 35px 20px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <img src="<?php echo APP_URL; ?>/assets/images/pabson-logo.svg" alt="PABSON Logo" style="width: 80px; height: 80px; object-fit: contain;">
            </div>
            <div class="competition-title">PABSON Inter School</div>
            <h1>Quiz Competition</h1>
            <p>Admin Control Panel</p>
        </div>
        
        <!-- Body -->
        <div class="login-body">
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user-shield"></i> Admin Username
                    </label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        placeholder="Enter admin username"
                        value="<?php echo htmlspecialchars($rememberedUsername); ?>"
                        required
                        autocomplete="off"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="off"
                    >
                </div>
                
                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="remember_me" 
                        name="remember_me"
                        <?php echo $rememberedUsername ? 'checked' : ''; ?>
                    >
                    <label class="form-check-label" for="remember_me">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn-signin">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
