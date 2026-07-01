<?php

return [

    'workflow' => [

        'NEW' => [
            'PROCESSING'
        ],

        'PROCESSING' => [
            'WAITING_CONFIRM',
            'REJECTED',
        ],

        'REJECTED' => [
            'REOPEN',
        ],

        'REOPEN' => [
            'PROCESSING',
        ],

        'WAITING_CONFIRM' => [
            'CONFIRMED',
        ],

        'CONFIRMED' => [
            'COMPLETED',
            'LATED',
        ],

        'LATED' => [

        ],

        'COMPLETED' => [

        ],

    ],

];