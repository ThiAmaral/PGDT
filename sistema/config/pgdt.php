<?php

return [

    /*
    | Domínio aceito no cadastro de estudantes (decisão da Fase 0).
    */
    'dominio_estudante' => 'edu.ufes.br',

    /*
    | Administrador inicial criado pelo AdminSeeder. Sem ADMIN_PASSWORD,
    | o seeder gera uma senha aleatória e mostra no terminal.
    */
    'admin' => [
        'nome' => env('ADMIN_NOME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@pgdt.local'),
        'senha' => env('ADMIN_PASSWORD'),
    ],

];
