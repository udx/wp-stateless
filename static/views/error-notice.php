<div id="stateless-notice-<?php if( !empty($data['key']) ) echo $data['key'];?>" class="stateless-admin-notice ud-admin-notice <?php echo $data['class'];?> update-nag fade">
    <div>
        <div class="title"><?php /* The $data is already escaped */ _e($data['title']);?></div>
        <div class="description"><?php /* The $data is already escaped */ _e($data['message']);?></div>
    </div>
    <?php
    if( !empty( $data['action_links'] ) && is_array( $data['action_links'] ) ):
        echo '<p>' . esc_html(implode( ' | ', $data['action_links'] )) . '</p>';
    endif;
    ?>
    <div class="buttons-container">
    <?php if ( !empty($data['button']) && !empty($data['key']) ) : ?>
        <a class="button-action button button-primary" data-action="sm_enable_notice" data-key="<?php esc_html_e($data['key']);?>" href="<?php esc_html_e($data['button_link']);?>"><?php esc_html_e($data['button']);?></a>
    <?php endif; ?>
    <?php if ( !empty($data['key']) && (!isset($data['dismiss']) || $data['dismiss'] !== false) ) : ?>
        <a class="dismiss-warning dismiss notice-dismiss" data-key="dismissed_notice_<?php esc_html_e($data['key']);?>" href="#"></a>
    <?php endif; ?>
    </div>
</div>
