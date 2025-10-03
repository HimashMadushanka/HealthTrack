<?php include 'db.php'; ?>
<?php
if(isset($_POST['register'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Server-side validation
    $errors = [];
    
    if(strlen($name) < 2){
        $errors[] = "Name must be at least 2 characters";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Invalid email format";
    }
    
    if(strlen($password) < 8){
        $errors[] = "Password must be at least 8 characters";
    }
    
    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();
    
    if($check_email->num_rows > 0){
        $errors[] = "Email already registered";
    }
    
    if(empty($errors)){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password_hash);
        
        if($stmt->execute()){
            $success = true;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
    $check_email->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Create Account</title>
    <link rel="stylesheet" href="register.css">
   
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Create Account</h2>
            <p>Join us today and get started</p>
        </div>
        
        <div class="form-container">
            <?php if(isset($success) && $success): ?>
                <div class="alert alert-success">
                    ✓ Registration successful! <a href='login.php'>Login here</a>
                </div>
            <?php endif; ?>
            
            <?php if(isset($errors) && !empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach($errors as $error): ?>
                        • <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registerForm" novalidate>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="name" id="name" placeholder="Enter your full name" required>
                        <span class="input-icon" id="nameIcon"></span>
                    </div>
                    <span class="error-message" id="nameError"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" placeholder="Enter your email" required>
                        <span class="input-icon" id="emailIcon"></span>
                    </div>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                        <button type="button" class="toggle-password" id="togglePassword">👁️</button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill"></div>
                        </div>
                        <span class="strength-text"></span>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <button type="submit" name="register" id="submitBtn">Create Account</button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('registerForm');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        // Name validation
        nameInput.addEventListener('input', function() {
            const value = this.value.trim();
            const icon = document.getElementById('nameIcon');
            const error = document.getElementById('nameError');
            
            if(value.length === 0) {
                this.classList.remove('error', 'success');
                icon.classList.remove('show');
                error.classList.remove('show');
            } else if(value.length < 2) {
                this.classList.add('error');
                this.classList.remove('success');
                icon.className = 'input-icon show error';
                icon.textContent = '✕';
                error.textContent = 'Name must be at least 2 characters';
                error.classList.add('show');
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                icon.className = 'input-icon show success';
                icon.textContent = '✓';
                error.classList.remove('show');
            }
        });

        // Email validation
        emailInput.addEventListener('input', function() {
            const value = this.value.trim();
            const icon = document.getElementById('emailIcon');
            const error = document.getElementById('emailError');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if(value.length === 0) {
                this.classList.remove('error', 'success');
                icon.classList.remove('show');
                error.classList.remove('show');
            } else if(!emailRegex.test(value)) {
                this.classList.add('error');
                this.classList.remove('success');
                icon.className = 'input-icon show error';
                icon.textContent = '✕';
                error.textContent = 'Please enter a valid email address';
                error.classList.add('show');
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                icon.className = 'input-icon show success';
                icon.textContent = '✓';
                error.classList.remove('show');
            }
        });

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const value = this.value;
            const strengthContainer = document.getElementById('passwordStrength');
            const error = document.getElementById('passwordError');
            
            if(value.length === 0) {
                strengthContainer.className = 'password-strength';
                strengthContainer.querySelector('.strength-text').textContent = '';
                this.classList.remove('error', 'success');
                error.classList.remove('show');
                return;
            }
            
            let strength = 0;
            
            // Length check
            if(value.length >= 8) strength++;
            if(value.length >= 12) strength++;
            
            // Character variety checks
            if(/[a-z]/.test(value) && /[A-Z]/.test(value)) strength++;
            if(/[0-9]/.test(value)) strength++;
            if(/[^a-zA-Z0-9]/.test(value)) strength++;
            
            const strengthText = strengthContainer.querySelector('.strength-text');
            
            if(strength <= 2) {
                strengthContainer.className = 'password-strength strength-weak';
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Password must be at least 8 characters';
                error.classList.add('show');
            } else if(strength <= 4) {
                strengthContainer.className = 'password-strength strength-medium';
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#ffc107';
                this.classList.remove('error');
                this.classList.add('success');
                error.classList.remove('show');
            } else {
                strengthContainer.className = 'password-strength strength-strong';
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
                this.classList.remove('error');
                this.classList.add('success');
                error.classList.remove('show');
            }
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate name
            if(nameInput.value.trim().length < 2) {
                nameInput.classList.add('error');
                document.getElementById('nameError').textContent = 'Name must be at least 2 characters';
                document.getElementById('nameError').classList.add('show');
                isValid = false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(!emailRegex.test(emailInput.value.trim())) {
                emailInput.classList.add('error');
                document.getElementById('emailError').textContent = 'Please enter a valid email';
                document.getElementById('emailError').classList.add('show');
                isValid = false;
            }
            
            // Validate password
            if(passwordInput.value.length < 8) {
                passwordInput.classList.add('error');
                document.getElementById('passwordError').textContent = 'Password must be at least 8 characters';
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            }
            
            if(!isValid) {
                e.preventDefault();
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating Account...';
            }
        });

        // Prevent multiple submissions
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>