<?php
require_once 'db_connect.php';

function registerUser($name, $phone, $email, $username, $password) {
    global $mysqli;

    // Cek nomor telepon sudah dipakai
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Nomor telepon sudah terdaftar!'];
    }
    $stmt->close();

    // Cek email sudah dipakai
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email sudah terdaftar!'];
    }
    $stmt->close();

    // Cek username sudah dipakai
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Username sudah dipakai!'];
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user baru
    $stmt = $mysqli->prepare(
        "INSERT INTO users (name, phone, email, username, password, role) 
         VALUES (?, ?, ?, ?, ?, 'user')"
    );
    $stmt->bind_param("sssss", $name, $phone, $email, $username, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Gagal mendaftar. Silakan coba lagi.'];
    }
}

function loginUser($username, $password) {
    global $mysqli;

    // Cari user berdasarkan username
    $stmt = $mysqli->prepare("SELECT id, name, phone, email, password, username, role 
                              FROM users 
                              WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['name']      = $user['name'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['phone']     = $user['phone'];
            $_SESSION['role']      = $user['role'];

            $stmt->close();
            return ['success' => true, 'message' => 'Login berhasil!', 'role' => $user['role']];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Password salah!'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Username tidak ditemukan!'];
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
