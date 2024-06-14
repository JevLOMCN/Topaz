<?php
// Inicia a sessão
session_start();

// Inclui o arquivo de configuração
require_once 'config/config.php';

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém os dados do formulário
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Hash da senha usando SHA-256
    $hashed_password = strtoupper(hash('sha256', $password));

    try {
        // Inicia a transação
        $pdo->beginTransaction();

        // Prepara a consulta para verificar se o nome de usuário já existe no banco de dados
        $stmt = $pdo->prepare("SELECT * FROM user_tb WHERE Username = :username OR Email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Nome de usuário ou email já existem, armazena a mensagem de erro na sessão
            $_SESSION['error'] = "Username or email already exists. Please try again.";
            header("Location: register");
            exit();
        } else {
            // Nome de usuário e email não existem, insere os dados no banco de dados

            // Obtém o maior AccountUID atual e adiciona 1 a ele
            $stmt = $pdo->prepare("SELECT MAX(AccountUID) AS maxAccountUID FROM user_tb");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $accountUID = $row['maxAccountUID'] + 1;

            $stmt = $pdo->prepare("INSERT INTO user_tb (AccountUID, Username, Email, PasswordHash) VALUES (:accountUID, :username, :email, :hashed_password)");
            $stmt->execute(['accountUID' => $accountUID, 'username' => $username, 'email' => $email, 'hashed_password' => $hashed_password]);

            // Confirma a transação
            $pdo->commit();

            if ($stmt->rowCount() > 0) {
                // Registro concluído com sucesso, redireciona para a página de sucesso
                header("Location: success");
                exit();
            } else {
                // Erro ao inserir dados, armazena a mensagem de erro na sessão
                $_SESSION['error'] = "There was an error registering. Please try again in a few moments.";
                header("Location: register");
                exit();
            }
        }
    } catch (Exception $e) {
        // Alguma coisa deu errado, reverte a transação
        $pdo->rollBack();
        // Armazena a mensagem de erro na sessão
        $_SESSION['error'] = "There was an error registering. Please try again in a few moments.";
        header("Location: register");
        exit();
    }
} else {
    // Redireciona para a página de erro se o formulário não foi enviado
    header("Location: erro");
    exit();
}
