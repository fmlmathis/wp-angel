<?php
/**
 * Configuration for Pricing Block
 */
return [
    'title'       => 'Tarifs',
    'description' => 'Un bloc pour afficher vos offres et tarifs',
    'category'    => 'formatting',
    'icon'        => 'money-alt',
    'keywords'    => ['prix', 'tarifs', 'offres', 'pricing'],
    'supports'    => [
        'align'   => true,
        'mode'    => false,
        'jsx'     => true,
    ],
    'example'     => [
        'attributes' => [
            'mode' => 'preview',
            'data' => [
                'is_example' => true
            ]
        ]
    ],
    'enqueue_style'  => false, // Nous utilisons Tailwind, pas besoin de CSS supplémentaire
    'enqueue_script' => false, // Pas besoin de JavaScript pour ce bloc
];