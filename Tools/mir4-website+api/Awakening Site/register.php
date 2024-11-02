<?php
require_once 'config/base.php';
?>


<body>
        <div class="container">
        <div id="logomir4"></div>
        <h2>Register - AVA MIR4 Alpha</h2>
            <div class="container halfsection">
            
            <!-- Formulário corrigido -->
            <form id="registrationForm" action="formsend" method="post">
                <label for="username">Username (minimum 4 letters):</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <div id="usernameError"></div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" autocomplete="email" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
                <div id="passwordError"></div>
                <div class="password-strength" id="passwordStrength"></div>
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" autocomplete="new-password" required>
                <div id="confirmPasswordError"></div>
                <div id="confirmMessage"></div>
                <br>
                <!--<div id="usernameError"></div>
      <div id="confirmMessage"></div>
      <div id="passwordError"></div>-->
                <div id="ErrorCaptcha">
                    <?php
                    // Exibe a mensagem de erro do captcha, se houver
                    if (isset($_SESSION['captchaError'])) {
                        echo $_SESSION['captchaError'];
                        unset($_SESSION['captchaError']);
                    }
                    ?>
                </div>
                <div id="serverError">
                    <?php
                    // Exibe a mensagem de erro, se houver
                    if (isset($_SESSION['error'])) {
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    }
                    ?>
                </div>
                <br>
                <div class="h-captcha" data-sitekey="ae922e43-5b52-4534-ac51-4d371520090d"></div>
                <br>
                <input type="submit" id="registerButton" value="Register">

                <br><br>
                <div id="backhome"><a class="backhome" href="index">« Back to home</a></div>
            </form>
        </div>
                </div>
</body>

<script>
    document.getElementById("registrationForm").addEventListener("input", function(event) {
        var username = document.getElementById("username").value;
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirmPassword").value;
        var registerButton = document.getElementById("registerButton");
        var usernameError = document.getElementById("usernameError");
        var passwordError = document.getElementById("passwordError");
        var confirmPasswordError = document.getElementById("confirmPasswordError");
        var confirmMessage = document.getElementById("confirmMessage");

        // Verifica o campo de nome de usuário
        if (username.length < 4) {
            usernameError.textContent = "Username must be at least 4 letters long.";
        } else {
            usernameError.textContent = "";
        }

        // Verifica o campo de senha
        if (password.length === 0) {
            passwordError.textContent = "Password is required.";
        } else {
            passwordError.textContent = "";
        }

        // Verifica o campo de confirmação de senha
        if (password && confirmPassword.length === 0) {
            confirmMessage.textContent = "Confirm password";
            confirmPasswordError.textContent = "";
        } else if (password && confirmPassword && password !== confirmPassword) {
            confirmPasswordError.textContent = "Passwords do not match.";
            confirmMessage.textContent = "";
        } else {
            confirmPasswordError.textContent = "";
            confirmMessage.textContent = "";
        }

        var passwordStrength = document.getElementById("passwordStrength");
        var strength = 0;

        // If the password contains at least 8 characters, the strength is increased
        if (password.length >= 8) {
            strength += 1;
        }
        // If the password contains at least one number, the strength is increased
        if (password.match(/\d+/)) {
            strength += 1;
        }
        // If the password contains at least one lowercase letter and one uppercase letter, the strength is increased
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
            strength += 1;
        }
        // If the password contains at least one special character, the strength is increased
        if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
            strength += 1;
        }

        // Display password strength
        if (strength < 2) {
            passwordStrength.innerHTML = "Password strength: <span class='weak'>Weak</span>";
        } else if (strength == 2) {
            passwordStrength.innerHTML = "Password strength: <span class='medium'>Medium</span>";
        } else {
            passwordStrength.innerHTML = "Password strength: <span class='strong'>Strong</span>";
        }

        // Enable or disable the submit button based on form validity
        var isValidForm = username && password && confirmPassword && password === confirmPassword;
        registerButton.disabled = !isValidForm;

        // Change the button color based on form validity
        if (isValidForm) {
            registerButton.classList.remove("disabled-button");
        } else {
            registerButton.classList.add("disabled-button");
        }
    });
    document.getElementById("registrationForm").addEventListener("submit", function(event) {
        var captchaResponse = hcaptcha.getResponse();
        if (captchaResponse.length === 0) {
            event.preventDefault();
            var captcha = document.querySelector('.h-captcha');
            captcha.style.border = 'solid 1px red'; // Adiciona a borda vermelha
            var errorField = document.getElementById('ErrorCaptcha');
            errorField.innerHTML = 'Você precisa completar o captcha.';
            var count = 0;
            var interval = setInterval(function() {
                captcha.style.borderColor = count % 2 ? 'red' : '';
                if (++count === 8) {
                    clearInterval(interval);
                }
            }, 500);
        }
    });
</script>

