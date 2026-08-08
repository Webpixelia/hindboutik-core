<?php
/** @var array $vars */
$whatsapp_url   = $vars['whatsapp_url']   ?? '';
$display_text   = $vars['display_text']   ?? 'Une question ?';
$icon_url       = $vars['icon_url']       ?? '';
?>

<style id="ht-ctc-entry-animations">
.ht_ctc_entry_animation { animation-duration: 1s; animation-fill-mode: both; animation-delay: 0s; animation-iteration-count: 1; }
@keyframes center { from { transform: scale(0); } to { transform: scale(1); } }
.ht_ctc_an_entry_center { animation: center .25s; }
</style>

<div class="ht-ctc ht-ctc-chat ctc-analytics ctc_wp_desktop style-4 ht_ctc_entry_animation ht_ctc_an_entry_center ht_ctc_animation no-animation"
     id="ht-ctc-chat"
     style="position: fixed; bottom: 20px; right: 16px; cursor: pointer; z-index: 99999999; --side: right;">

    <div class="ht_ctc_style ht_ctc_chat_style">
        <span class="ht_ctc_notification" style="display:none; padding:0; margin:0; position:relative; float:right; z-index:9999999;">
            <span class="ht_ctc_badge" style="position:absolute; top:-11px; right:-11px; font-size:12px; font-weight:600; height:22px; width:22px; box-sizing:border-box; border-radius:50%; background:#d4065c; color:#fff; display:flex; justify-content:center; align-items:center;">1</span>
        </span>

        <div class="ctc_chip ctc-analytics ctc_s_4 ctc_nb"
             style="display:flex; justify-content:center; align-items:center; background-color:#f7eff3; color:#941b51; padding:0 12px; border-radius:25px; font-size:13px; line-height:32px;"
             data-nb_top="-10px" data-nb_right="-10px">

            <img class="s4_img"
                 style="margin:0 8px 0 -12px; order:0; height:24px; width:24px; border-radius:50%; object-fit:contain;"
                 src="<?php echo esc_url($icon_url ?: HINDBOUTIK_CORE_URL . 'assets/img/whatsapp-icon.png'); ?>"
                 alt="WhatsApp">

            <span class="ctc_cta">
                <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($display_text); ?>
                </a>
            </span>
        </div>
    </div>
</div>