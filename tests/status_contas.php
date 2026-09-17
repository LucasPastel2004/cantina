<?php
define('CANTINA_SISTEMA', true);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();

echo "=== USUÁRIOS DA EQUIPE (PAINEL DA CANTINA) ===\n";
$stmt = $pdo->query("SELECT id, usuario, nome, perfil, ativo FROM usuarios");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Usuário: {$row['usuario']} | Perfil: {$row['perfil']} | Ativo: {$row['ativo']}\n";
}

echo "\n=== RESPONSÁVEIS FAMILIARES ===\n";
$stmt = $pdo->query("SELECT id, cpf, nome, whatsapp, (CASE WHEN senha_hash != '' THEN 'DEFINIDA' ELSE 'PENDENTE' END) as status_senha, (CASE WHEN termos_aceitos_em IS NOT NULL THEN 'ACEITOS' ELSE 'PENDENTE' END) as status_termos, saldo_atual FROM responsaveis");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "CPF: {$row['cpf']} | Nome: {$row['nome']} | WhatsApp: {$row['whatsapp']} | Senha: {$row['status_senha']} | Termos: {$row['status_termos']} | Saldo: R$ {$row['saldo_atual']}\n";
}
