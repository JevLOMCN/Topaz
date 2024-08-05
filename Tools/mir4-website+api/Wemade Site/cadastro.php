<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro - NexusMIR4 Alpha</title>
<script src="https://www.google.com/recaptcha/api.js" defer></script>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('mir4_background.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    height: 100vh;
  }
  .container {
    max-width: 400px;
    margin: 50px auto;
    background-color: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  }
  h2 {
    text-align: center;
    margin-bottom: 20px;
  }
  label {
    font-weight: bold;
  }
  input[type="text"],
  input[type="email"],
  input[type="password"],
  select {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
  }
  input[type="submit"] {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 15px 20px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
  }
  input[type="submit"]:hover {
    background-color: #0056b3;
  }
  .password-strength {
    font-size: 12px;
    margin-top: 5px;
  }
  .weak {
    color: red;
  }
  .medium {
    color: orange;
  }
  .strong {
    color: green;
  }
</style>
</head>
<body>
<div class="container">
  <h2>Cadastro - Nexus MIR4 Alpha</h2>
  <!-- Formulário corrigido -->
  <form id="registrationForm" action="cadastrar.php" method="post">
    <label for="username">Nome de Usuário (mínimo 4 letras):</label>
    <input type="text" id="username" name="username" required>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Senha:</label>
    <input type="password" id="password" name="password" required>
    <div class="password-strength" id="passwordStrength"></div>
    <label for="question">Pergunta secreta:</label>
    <select id="question" name="question" required>
      <option value="">Selecione uma pergunta</option>
      <option value="q1">Qual é o nome do seu animal de estimação?</option>
      <option value="q2">Qual é o nome da sua cidade natal?</option>
      <option value="q3">Qual é o nome do seu melhor amigo de infância?</option>
    </select>
    <label for="answer">Resposta:</label>
    <input type="text" id="answer" name="answer" required>
    <label for="class">Classe Favorita:</label>
    <select id="class" name="class" required>
      <option value="">Selecione uma classe</option>
      <option value="guerreiro">Guerreiro</option>
      <option value="arqueira">Arqueira</option>
      <option value="taoista">Taoista</option>
      <option value="lanceiro">Lanceiro</option>
      <option value="maga">Maga</option>
      <option value="soturna">Soturna</option>
    </select>
    <div class="g-recaptcha" data-sitekey="6Le9XoUpAAAAAG-yK7KwO8bUTH1J42wItksHehWY"></div>
    <br>
    <input type="submit" value="Cadastrar">
  </form>
</div>

<script>
document.getElementById("registrationForm").addEventListener("submit", function(event) {
  var username = document.getElementById("username").value;
  if (username.length < 4) {
    alert("O nome de usuário deve ter no mínimo 4 letras.");
    event.preventDefault();
  }

  var password = document.getElementById("password").value;
  var passwordStrength = document.getElementById("passwordStrength");
  var strength = 0;

  // Se a senha contém pelo menos 8 caracteres, a força é aumentada
  if (password.length >= 8) {
    strength += 1;
  }
  // Se a senha contém pelo menos um número, a força é aumentada
  if (password.match(/\d+/)) {
    strength += 1;
  }
  // Se a senha contém pelo menos uma letra minúscula e uma letra maiúscula, a força é aumentada
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
    strength += 1;
  }
  // Se a senha contém pelo menos um caractere especial, a força é aumentada
  if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
    strength += 1;
  }

  // Exibir a força da senha
  if (strength < 2) {
    passwordStrength.innerHTML = "Força da senha: <span class='weak'>Fraca</span>";
  } else if (strength == 2) {
    passwordStrength.innerHTML = "Força da senha: <span class='medium'>Média</span>";
  } else {
    passwordStrength.innerHTML = "Força da senha: <span class='strong'>Forte</span>";
  }
});
</script>

</body>
</html>
