<div class="ud-admin-notice <?php echo $data['class'];?> update-nag fade">
    <div>
        <p><?php echo $data['title'];?></p>
        <div><?php echo $data['message'];?></div>
    </div>
    <?php
    if( !empty( $data['action_links'] ) && is_array( $data['action_links'] ) ):
        echo '<p>' . implode( ' | ', $data['action_links'] ) . '</p>';
    endif;
    ?>
    <?php if(!empty($data['button']) && !empty($data['dismis_key'])):?>
        <a class="button-action button button-primary" data-key="button_secondary_<?php echo $data['dismis_key'];?>" href="#"><?php echo $data['button'];?></a>
    <?php endif;?>
    <?php if(!empty($data['dismis_key'])):?>
        <a class="dismiss-warning dismiss notice-dismiss" data-key="dismissed_notice_<?php echo $data['dismis_key'];?>" href="#"></a>
    <?php endif;?>
</div>
