<?php
define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();

echo "=== PADRONIZANDO CREDENCIAIS DE TESTE ===\n";

// 1. Admin
$stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1 WHERE usuario = 'admin'");
$stmt->execute([password_hash('Admin@123', PASSWORD_BCRYPT)]);
echo "[OK] Admin configurado: usuario 'admin' | senha 'Admin@123'\n";

// 2. Operador
$countOp = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE usuario = 'operador-maria'")->fetchColumn();
if ($countOp == 0) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['operador-maria', 'Maria Atendente', 'maria@cantina.com.br', password_hash('Operador@123', PASSWORD_BCRYPT), 'operador', 1]);
} else {
    $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1 WHERE usuario = 'operador-maria'");
    $stmt->execute([password_hash('Operador@123', PASSWORD_BCRYPT)]);
}
echo "[OK] Operador configurado: usuario 'operador-maria' | senha 'Operador@123'\n";

// 3. Responsável familiar com saldo (Carlos Eduardo Silva)
$stmt = $pdo->prepare("UPDATE responsaveis SET senha_hash = ?, termos_aceitos_em = NOW(), saldo_atual = 100.00, bloqueado_med = 0 WHERE cpf = '11122233344'");
$stmt->execute([password_hash('Familia@123', PASSWORD_BCRYPT)]);
echo "[OK] Responsavel configurado: CPF '111.222.333-44' | senha 'Familia@123' | Saldo R$ 100,00\n";

// 4. Responsável para primeiro acesso (Carlos Eduardo Ribeiro)
$stmt = $pdo->prepare("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '55566677788'");
$stmt->execute();
echo "[OK] Responsavel para Primeiro Acesso: CPF '555.666.777-88' (pendente de ativacao)\n";

echo "\nPronto! Base pronta para testes manuais sem atrito.\n";
