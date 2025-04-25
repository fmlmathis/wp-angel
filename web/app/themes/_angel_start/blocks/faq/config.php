<?php
/**
 * Configuration for FAQ Block
 */
return [
    'title'       => 'FAQ',
    'description' => 'Un bloc pour afficher une liste de questions fréquemment posées',
    'category'    => 'formatting',
    'icon'        => 'editor-help',
    'keywords'    => ['faq', 'questions', 'réponses'],
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
    'enqueue_script' => true,  // Pour gérer l'ouverture/fermeture des questions
];