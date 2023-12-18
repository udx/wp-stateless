<p>
  <?php _e('Some WordPress plugins override or provide additional features for the Media Library, image upload, and processing. These add-ons offer compatibility features to ensure smooth operation and support the behavior of other plugins alongside WP-Stateless.', ud_get_stateless_media()->domain); ?>
</p>
<p>
  <?php _e(
          sprintf(
            "If you experience any issues please contact us on <a href='%s' target='_blank' rel='noreferrer nofollow'>GitHub</a> or <a href='%s' target='_blank' rel='noreferrer nofollow'>Support page</a>.",
            'https://github.com/udx/wp-stateless/issues',
            'https://wordpress.org/support/plugin/wp-stateless/'
          ),
          ud_get_stateless_media()->domain
        ); 
  ?>
</p>

<div class="addons-filter">
  <ul class="subsubsub">
    <?php
      foreach ($filters as $id => $filter) {
        ?>

        <li class="<?php echo $id ?>">
          <a href="<?php printf($url, $id); ?>" <?php if ( $id == $current_filter ) echo 'class="current" aria-current="page"'; ?>>
            <?php printf($filter->title, $filter->count); ?>
          </a>
          <?php if ( $id !== 'inactive' ) echo '|'; ?>
        </li>

        <?php
      } 
    ?>
  </ul>
</div>

<div class="addons-list">
  <?php
    foreach ($addons as $id => $addon) {
      $card_class = $addon->active ? 'active' : ($addon->recommended ? 'recommended' : '');
      ?>

      <div class="addon-card <?php echo $card_class;?>">

        <?php if ($addon->active) : ?>
          <div class="addon-status">
            <?php _e('active', ud_get_stateless_media()->domain); ?>
          </div>
        <?php elseif ($addon->recommended) : ?>
          <div class="addon-status">
            <?php _e('recommended', ud_get_stateless_media()->domain); ?>
          </div>
        <?php endif; ?>

        <a href="<?php printf($addon_link, $id); ?>" class="addon-icon" target="_blank">
            <img src="<?php echo $addon->icon; ?>" alt="<?php echo $addon->title; ?>">
          </a>

        <div class="addon-head">
          <div class="addon-info">
            <h2 class="addon-title">
              <a href="<?php printf($addon_link, $id); ?>" target="_blank"><?php echo $addon->title; ?></a>
            </h2>
            
            <div class="addon-description"><?php printf($description, $addon->title) ?></div>
          </div>

          <div class="addon-actions">
            <a href="<?php printf($addon_link, $id); ?>" class="button-action button button-primary" target="_blank"><?php _e('Learn More', ud_get_stateless_media()->domain); ?></a>
          </div>
        </div>
      </div>

      <?php
    } 
  ?>
</div>