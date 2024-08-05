<?php

require_once 'config/config.php';
require_once 'config/base.php';

?>



<body>

    <div class="container">
        <div id="logomir4"></div>
        <h2>Login - Topaz MIR4 Alpha</h2>


        <div class="container halfsection">
            <form  action="login" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
                <?php if(isset($error)): ?>
                    <p style="color:rgb(245, 108, 108); font-size:12px;"><?php echo $error; ?></p>
                    <?php endif; ?>

                <br><br><br>

                <input type="submit" name="submit" value="Sign In">

                <br><br>

                <div id="backhome"><a class="backhome" href="/">« Back to home</a></div>

            </form>
        </div>


    </div>

</body>

