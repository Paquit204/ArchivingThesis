<?php
// Register Functions File

function handleRegistration($conn, $post) {
    // Get and sanitize inputs
    $role_id = (int)($post['role_id'] ?? 0);
    $first_name = trim($post['first_name'] ?? '');
    $last_name = trim($post['last_name'] ?? '');
    $email = trim($post['email'] ?? '');
    $username = trim($post['username'] ?? '');
    $password = $post['password'] ?? '';
    $cpassword = $post['cpassword'] ?? '';
    $department_id = (int)($post['department_id'] ?? 0);
    $birth_date = $post['birth_date'] ?? null;
    $contact_number = trim($post['contact_number'] ?? '');
    $address = trim($post['address'] ?? '');
    
    // ============================================================
    // VALIDATION
    // ============================================================
    
    // 1. Valid roles - SAKTO NA NGA ROLE MAPPING
    $valid_roles = [1, 2, 3, 4, 5, 6];
    if (!in_array($role_id, $valid_roles)) {
        return ['success' => false, 'message' => 'Invalid role selected. Please choose a valid role.'];
    }
    
    // 2. Check if all required fields are filled
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password) || empty($cpassword)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }
    
    // 3. Check if password and confirm password match
    if ($password !== $cpassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }
    
    // 4. Check password length
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }
    
    // 5. Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    // 6. Check if username already exists
    $check_username = $conn->prepare("SELECT user_id FROM user_table WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $username_result = $check_username->get_result();
    if ($username_result->num_rows > 0) {
        $check_username->close();
        return ['success' => false, 'message' => 'Username already exists. Please choose another username.'];
    }
    $check_username->close();
    
    // 7. Check if email already exists
    $check_email = $conn->prepare("SELECT user_id FROM user_table WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $email_result = $check_email->get_result();
    if ($email_result->num_rows > 0) {
        $check_email->close();
        return ['success' => false, 'message' => 'Email already registered. Please use another email.'];
    }
    $check_email->close();
    
    // 8. Check contact number format
    if (!empty($contact_number) && !preg_match('/^09[0-9]{9}$/', $contact_number)) {
        return ['success' => false, 'message' => 'Contact number must be 11 digits starting with 09 (e.g., 09123456789).'];
    }
    
    // ============================================================
    // INSERT USER
    // ============================================================
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into user_table
    $insert_query = "INSERT INTO user_table (username, password, first_name, last_name, email, role_id, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("sssssi", $username, $hashed_password, $first_name, $last_name, $email, $role_id);
    
    if ($insert_stmt->execute()) {
        $user_id = $insert_stmt->insert_id;
        $insert_stmt->close();
        
        // ============================================================
        // INSERT INTO SPECIFIC TABLES BASED ON ROLE
        // ============================================================
        
        // For Student (role_id = 2)
        if ($role_id == 2) {
            $student_query = "INSERT INTO student_table (user_id, department_id, birth_date, contact_number, address) 
                              VALUES (?, ?, ?, ?, ?)";
            $student_stmt = $conn->prepare($student_query);
            $student_stmt->bind_param("iisss", $user_id, $department_id, $birth_date, $contact_number, $address);
            $student_stmt->execute();
            $student_stmt->close();
        }
        
        // For Faculty (role_id = 3)
        elseif ($role_id == 3) {
            $faculty_query = "INSERT INTO faculty_table (user_id, department_id, specialization) 
                              VALUES (?, ?, 'General')";
            $faculty_stmt = $conn->prepare($faculty_query);
            $faculty_stmt->bind_param("ii", $user_id, $department_id);
            $faculty_stmt->execute();
            $faculty_stmt->close();
        }
        
        // For Dean (role_id = 4)
        elseif ($role_id == 4) {
            $dean_query = "INSERT INTO dean_table (user_id, department_id, position) 
                           VALUES (?, ?, 'Department Dean')";
            $dean_stmt = $conn->prepare($dean_query);
            $dean_stmt->bind_param("ii", $user_id, $department_id);
            $dean_stmt->execute();
            $dean_stmt->close();
        }
        
        // For Librarian (role_id = 5)
        elseif ($role_id == 5) {
            $librarian_query = "INSERT INTO librarian_table (user_id, department_id, position) 
                                VALUES (?, ?, 'Librarian')";
            $librarian_stmt = $conn->prepare($librarian_query);
            $librarian_stmt->bind_param("ii", $user_id, $department_id);
            $librarian_stmt->execute();
            $librarian_stmt->close();
        }
        
        // For Coordinator (role_id = 6)
        elseif ($role_id == 6) {
            $coordinator_query = "INSERT INTO department_coordinator (user_id, department_id, position, assigned_date) 
                                  VALUES (?, ?, 'Research Coordinator', NOW())";
            $coordinator_stmt = $conn->prepare($coordinator_query);
            $coordinator_stmt->bind_param("ii", $user_id, $department_id);
            $coordinator_stmt->execute();
            $coordinator_stmt->close();
        }
        
        return ['success' => true, 'message' => 'Registration successful! Please wait for admin approval.'];
        
    } else {
        $insert_stmt->close();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}
?>