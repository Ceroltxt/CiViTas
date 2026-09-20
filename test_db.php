<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
\ = \App\Models\Identity\Funcionario::select('matricula_funcionario', 'nome', 'sobrenome')->get();
echo json_encode(\);
