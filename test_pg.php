<?php
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=flock_sense', 'postgres', '12345678');
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename")->fetchAll(PDO::FETCH_COLUMN);
echo "Total tables: " . count($tables) . "\n\n";
foreach ($tables as $t) {
    echo "  $t\n";
}
