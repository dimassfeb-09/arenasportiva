<?php
require_once 'db_connect.php';

function registerUser($name, $phone, $email, $password) {
    global $mysqli;
    
    // Check if phone number already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Nomor telepon sudah terdaftar!'];
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email sudah terdaftar!'];
    }
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $mysqli->prepare("INSERT INTO users (name, phone, email, password, username, role) VALUES (?, ?, ?, ?, ?, 'user')");
    $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999); // Generate username from name
    $stmt->bind_param("sssss", $name, $phone, $email, $hashed_password, $username);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Gagal mendaftar. Silakan coba lagi.'];
    }
}

function loginUser($phone, $password) {
    global $mysqli;
    
    // Check if user exists
    $stmt = $mysqli->prepare("SELECT id, name, phone, email, password, username, role FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Start session and store user data
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            // ...hapus session balance...
            
            $stmt->close();
            return ['success' => true, 'message' => 'Login berhasil!', 'role' => $user['role']];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Password salah!'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Nomor telepon tidak ditemukan!'];
    }
}

function loginAdmin($username, $password) {
    global $mysqli;
    
    // Check if admin exists
    $stmt = $mysqli->prepare("SELECT id, name, phone, email, password, username, role FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Start session and store user data
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            // ...hapus session balance...
            
            $stmt->close();
            return ['success' => true, 'message' => 'Login admin berhasil!'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Password salah!'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Username tidak ditemukan atau bukan admin!'];
    }
}
?>
