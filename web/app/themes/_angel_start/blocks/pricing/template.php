<?php
/**
 * Pricing Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */

// Create id attribute allowing for custom "anchor" value
$id = 'pricing-' . $block['id'];
if (!empty($block['anchor'])) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values
$classes = 'pricing-block';
if (!empty($block['className'])) {
    $classes .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $classes .= ' align' . $block['align'];
}

// Load values and assign defaults
$title = get_field('pricing_title') ?: 'Nos tarifs';
$subtitle = get_field('pricing_subtitle') ?: 'Choisissez l\'offre qui vous convient';
$plans = get_field('pricing_plans') ?: [];
$is_example = isset($block['data']['is_example']) ? $block['data']['is_example'] : false;

// Exemple de contenu pour l'aperçu dans l'éditeur
if ($is_example || empty($plans)) {
    $plans = [
        [
            'name' => 'Basique',
            'price' => '19',
            'period' => 'par mois',
            'description' => 'Parfait pour les débutants',
            'features' => "Fonctionnalité 1\nFonctionnalité 2\nSupport par email",
            'button_text' => 'Commencer',
            'button_url' => '#',
            'is_featured' => false
        ],
        [
            'name' => 'Pro',
            'price' => '49',
            'period' => 'par mois',
            'description' => 'Pour les professionnels',
            'features' => "Tout ce qui est inclus dans Basique\nFonctionnalité 3\nFonctionnalité 4\nSupport prioritaire",
            'button_text' => 'Essayer maintenant',
            'button_url' => '#',
            'is_featured' => true
        ],
        [
            'name' => 'Entreprise',
            'price' => '99',
            'period' => 'par mois',
            'description' => 'Solution complète',
            'features' => "Tout ce qui est inclus dans Pro\nFonctionnalité 5\nFonctionnalité 6\nSupport dédié 24/7",
            'button_text' => 'Contacter les ventes',
            'button_url' => '#',
            'is_featured' => false
        ]
    ];
}
?>

<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>">
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <?php if ($title || $subtitle) : ?>
            <div class="text-center mb-12">
                <?php if ($title) : ?>
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>
                <?php if ($subtitle) : ?>
                    <p class="mt-4 text-xl text-gray-600"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($plans as $plan) : 
                $featured_classes = $plan['is_featured'] ? 'ring-2 ring-primary shadow-xl' : 'border border-gray-200';
                $button_classes = $plan['is_featured'] ? 'bg-primary hover:bg-primary-dark text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-800';
                $features = explode("\n", $plan['features']);
            ?>
                <div class="flex flex-col rounded-lg <?php echo esc_attr($featured_classes); ?> overflow-hidden">
                    <div class="px-6 py-8 bg-white sm:p-10 sm:pb-6">
                        <div>
                            <h3 class="inline-flex px-4 py-1 rounded-full text-sm font-semibold tracking-wide uppercase bg-gray-100 text-gray-700">
                                <?php echo esc_html($plan['name']); ?>
                            </h3>
                        </div>
                        <div class="mt-4 flex items-baseline text-6xl font-extrabold">
                            <?php echo esc_html($plan['price']); ?>€
                            <span class="ml-1 text-2xl font-medium text-gray-500"><?php echo esc_html($plan['period']); ?></span>
                        </div>
                        <p class="mt-5 text-lg text-gray-500"><?php echo esc_html($plan['description']); ?></p>
                    </div>
                    <div class="flex-1 flex flex-col justify-between px-6 pt-6 pb-8 bg-gray-50 space-y-6 sm:p-10 sm:pt-6">
                        <ul class="space-y-4">
                            <?php foreach ($features as $feature) : ?>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-base text-gray-700"><?php echo esc_html(trim($feature)); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="rounded-md shadow">
                            <a href="<?php echo esc_url($plan['button_url']); ?>" class="flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md <?php echo esc_attr($button_classes); ?> transition-colors duration-150">
                                <?php echo esc_html($plan['button_text']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>