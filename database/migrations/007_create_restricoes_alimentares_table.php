<?php
/**
 * Migration 007 — Criação da tabela restricoes_alimentares
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `restricoes_alimentares` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(50) NOT NULL UNIQUE,
            `descricao` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // Insere os 8 alérgenos oficiais padronizados conforme especificado no docs/FSD.md
        $count = $pdo->query("SELECT COUNT(*) FROM `restricoes_alimentares`")->fetchColumn();
        if ($count == 0) {
            $alergenos = [
                ['Glúten', 'Presente em derivados de trigo, centeio, cevada e aveia contaminada.'],
                ['Lactose', 'Açúcar natural presente no leite de vaca e derivados lácteos.'],
                ['Amendoim', 'Leguminosa com alto potencial alergênico e choque anafilático.'],
                ['Ovos', 'Proteínas da clara ou gema comumente presentes em massas e recheios.'],
                ['Soja', 'Presente em lecitinas, óleos compostos e recheios processados.'],
                ['Frutos do Mar', 'Crustáceos, moluscos e peixes.'],
                ['Nozes e Castanhas', 'Oleaginosas em geral, incluindo castanha de caju e nozes.'],
                ['Corante Tartrazina', 'Corante artificial amarelo comumente associado a crises alérgicas.'],
            ];

            $stmt = $pdo->prepare("INSERT INTO `restricoes_alimentares` (`nome`, `descricao`) VALUES (?, ?)");
            foreach ($alergenos as $item) {
                $stmt->execute([$item[0], $item[1]]);
            }
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `restricoes_alimentares`;");
    }
};
