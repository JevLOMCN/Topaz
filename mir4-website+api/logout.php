<?php
session_start(); // Inicia a sessão

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se é desejado terminar a sessão completamente, apague também o cookie de sessão.
// Isso irá destruir a sessão, e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Por fim, destrói a sessão
session_destroy();

// Redireciona para a página de login ou para a página inicial
header('Location: /');
exit;
?>