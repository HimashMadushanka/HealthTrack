<?php include 'db.php'; session_start(); ?>
<?php
$error = '';
$email_value = '';

if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $email_value = $email;
    
    // Server-side validation
    if(empty($email) || empty($password)){
        $error = "Please fill in all fields";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $user = $result->fetch_assoc();
            if(password_verify($password, $user['password'])){
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Welcome Back</title>
    <link rel="stylesheet" href="login.css">
   
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome Back</h2>
            <p>Login to continue to your account</p>
        </div>
        
        <div class="form-container">
            <?php if(!empty($error)): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="post" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" placeholder="Enter your email" 
                               value="<?php echo htmlspecialchars($email_value); ?>" required>
                        <span class="input-icon" id="emailIcon"></span>
                    </div>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" id="togglePassword">👁️</button>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>

                <button type="submit" name="login" id="submitBtn">Login</button>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
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

        // Password validation
        passwordInput.addEventListener('input', function() {
            const value = this.value;
            const error = document.getElementById('passwordError');
            
            if(value.length === 0) {
                this.classList.remove('error', 'success');
                error.classList.remove('show');
            } else if(value.length < 6) {
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Password is too short';
                error.classList.add('show');
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                error.classList.remove('show');
            }
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(!emailRegex.test(emailInput.value.trim())) {
                emailInput.classList.add('error');
                document.getElementById('emailError').textContent = 'Please enter a valid email';
                document.getElementById('emailError').classList.add('show');
                isValid = false;
            }
            
            // Validate password
            if(passwordInput.value.length === 0) {
                passwordInput.classList.add('error');
                document.getElementById('passwordError').textContent = 'Please enter your password';
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            }
            
            if(!isValid) {
                e.preventDefault();
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';
            }
        });

        // Remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        
        // Load saved email if exists
        if(localStorage.getItem('rememberedEmail')) {
            emailInput.value = localStorage.getItem('rememberedEmail');
            rememberCheckbox.checked = true;
        }
        
        // Save email when form is submitted
        form.addEventListener('submit', function() {
            if(rememberCheckbox.checked) {
                localStorage.setItem('rememberedEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });

        // Auto-focus on email input if empty, otherwise focus on password
        window.addEventListener('load', function() {
            if(emailInput.value.trim() === '') {
                emailInput.focus();
            } else {
                passwordInput.focus();
            }
        });

        // Prevent multiple submissions
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
        });

        // Clear error messages when user starts typing
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.remove('error');
            });
        });
    </script>
</body>
</html>