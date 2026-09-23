<?php
/**
 * @var int   $product_id
 * @var array $matched_guide
 */

$tableau = $matched_guide['tableau'] ?? [];
?>

<style>

</style>

<div class="size-guide-modal" id="size-guide-modal-<?php echo esc_attr($product_id); ?>" data-remodal-id="size-guide-modal-<?php echo esc_attr($product_id); ?>" style="display:none;">
    <div class="modal-overlay"></div>

    <div class="md-size-chart-modal-body medium">

        <div class="md-size-chart-close">
            <h2 class="md-modal-title">
                <?php esc_html_e('Guide des tailles', 'hindboutik-core'); ?>
            </h2>

            <button
                data-remodal-action="close"
                class="remodal-close"
                aria-label="<?php esc_attr_e('Fermer', 'hindboutik-core'); ?>">
            </button>
        </div>

        <div class="chart-container">
            <div class="size-guide">

                <table id="size-chart" class="scfw-chart-table modern">
                    <tbody>

                    <tr>
                        <th><?php esc_html_e('Size', 'hindboutik-core'); ?></th>
                        <th><?php esc_html_e('Size FR', 'hindboutik-core'); ?></th>
                    </tr>

                    <?php foreach ($tableau as $row) : ?>

                        <tr>
                            <td><?php echo esc_html($row['taille'] ?? ''); ?></td>
                            <td><?php echo esc_html($row['taille_fr'] ?? ''); ?></td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>
        </div>

    </div>
</div>