<?php
/**
 * @var string $main_image_url
 * @var int[]  $gallery_image_ids
 */
?>

<div class="custom-product-image-grid">

    <div class="grid-item">
        <img
            src="<?php echo esc_url($main_image_url); ?>"
            alt="<?php esc_attr_e('Image principale du produit', 'hindboutik-core'); ?>"
        >
    </div>

    <?php foreach ($gallery_image_ids as $image_id) :

        $image = wp_get_attachment_image_src($image_id, 'full');

        if (!$image) {
            continue;
        }

        ?>

        <div class="grid-item">
            <img
                src="<?php echo esc_url($image[0]); ?>"
                alt="<?php esc_attr_e('Image de la galerie', 'hindboutik-core'); ?>"
            >
        </div>

    <?php endforeach; ?>

</div>