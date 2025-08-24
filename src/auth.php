<?php
require_once 'db_connect.php';

function registerUser($username, $phone, $email, $password) {
    global $mysqli;
    
    // Check if username already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Username sudah terdaftar!'];
    }
    $stmt->close();
    
    // Check if phone number already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Nomor telepon sudah terdaftar!'];
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Email sudah terdaftar!'];
    }
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Use username as name for backward compatibility
    $name = $username;
    
    // Insert new user with provided username
    $stmt = $mysqli->prepare("INSERT INTO users (name, phone, email, password, username, role) VALUES (?, ?, ?, ?, ?, 'user')");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Registrasi gagal: Terjadi kesalahan sistem'];
    }
    $stmt->bind_param("sssss", $name, $phone, $email, $hashed_password, $username);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Registrasi berhasil!'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Gagal mendaftar. Silakan coba lagi.'];
    }
}


function loginUser($username, $password) {
    global $mysqli;
    
    // Check if user exists by username
    $stmt = $mysqli->prepare("SELECT id, name, phone, email, password, username, role FROM users WHERE username = ?");
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
            return ['success' => true, 'message' => 'Login berhasil!', 'role' => $user['role']];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'id atau password salah'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'id atau password salah'];
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
            return ['success' => false, 'message' => 'id atau password salah'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'id atau password salah'];
    }
}
?>
