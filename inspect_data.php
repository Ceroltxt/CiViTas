<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = DB::table('funcionario')->select('matricula_funcionario', 'nome', 'sobrenome', 'email')->get();
echo "--- FUNCIONARIOS ---\n";
foreach ($users as $u) {
    echo "ID: {$u->matricula_funcionario} | {$u->nome} {$u->sobrenome} | {$u->email}\n";
}

$workspaces = DB::table('workspaces')->get();
echo "\n--- WORKSPACES ---\n";
foreach ($workspaces as $w) {
    echo "ID: {$w->id} | {$w->nome}\n";
}

$pivot = DB::table('workspace_funcionario')->get();
echo "\n--- WORKSPACE_FUNCIONARIO ---\n";
foreach ($pivot as $p) {
    echo "WS_ID: {$p->workspace_id} | USER_ID: {$p->matricula_funcionario} | ROLE: {$p->role}\n";
}
