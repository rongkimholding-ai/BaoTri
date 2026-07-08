<?php

return [

    'workflow' => [

        'NEW' => [
            'PROCESSING'
        ],

        'PROCESSING' => [
            'WAITING_CONFIRM',
            'PENDING',
            'PENDING_CONTRACTOR',
            'REJECTED',
        ],

        'PENDING' => [
            'CONTINUE_PROCESSING',
        ],

        'PENDING_CONTRACTOR' => [
            'CONTINUE_PROCESSING',
        ],

        'REJECTED' => [
            'REOPEN',
        ],

        'REOPEN' => [
            'PROCESSING',
        ],

        'CONTINUE_PROCESSING' => [
            'WAITING_CONFIRM',
            'PENDING',
            'PENDING_CONTRACTOR',
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