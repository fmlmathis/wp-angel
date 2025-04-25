<?php
/**
 * FAQ Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */

// Create id attribute allowing for custom "anchor" value
$id = 'faq-' . $block['id'];
if (!empty($block['anchor'])) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values
$classes = 'faq-block';
if (!empty($block['className'])) {
    $classes .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $classes .= ' align' . $block['align'];
}

// Load values and assign defaults
$title = get_field('faq_title') ?: 'Foire aux questions';
$items = get_field('faq_items') ?: [];
$is_example = isset($block['data']['is_example']) ? $block['data']['is_example'] : false;

// Exemple de contenu pour l'aperçu dans l'éditeur
if ($is_example || empty($items)) {
    $items = [
        [
            'question' => 'Comment puis-je utiliser ce bloc FAQ ?',
            'answer'   => 'Ajoutez simplement ce bloc à votre page et remplissez les champs pour les questions et réponses.'
        ],
        [
            'question' => 'Puis-je personnaliser l\'apparence de ce bloc ?',
            'answer'   => 'Oui, vous pouvez modifier les classes Tailwind dans le template ou ajouter des classes personnalisées via l\'éditeur de blocs.'
        ]
    ];
}
?>

<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>">
    <div class="max-w-4xl mx-auto p-4">
        <?php if ($title) : ?>
            <h2 class="text-3xl font-bold mb-8 text-center"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($items as $index => $item) : ?>
                <div class="faq-item border border-gray-200 rounded-lg overflow-hidden">
                    <button class="faq-question w-full flex justify-between items-center p-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors duration-200" 
                            data-index="<?php echo esc_attr($index); ?>"
                            aria-expanded="false">
                        <span class="font-medium text-lg"><?php echo esc_html($item['question']); ?></span>
                        <svg class="faq-icon w-5 h-5 transform transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="faq-answer bg-white p-4 hidden">
                        <div class="prose prose-sm max-w-none">
                            <?php echo wp_kses_post($item['answer']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>