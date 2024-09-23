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
  <?php foreach ($addons as $id => $addon) : ?>

      <div class="addon-card <?php echo $addon->card_class;?>">

        <?php if ($addon->status) : ?>
          <div class="addon-status"><?php echo $addon->status; ?></div>
        <?php endif; ?>

        <div class="addon-head">
          <div class="addon-icon">
            <a href="<?php echo $addon->link ?>" target="_blank">
              <img src="<?php echo $addon->icon; ?>" alt="<?php echo $addon->title; ?>">
            </a>
          </div>

          <div class="addon-info">
            <h2 class="addon-title">
              <a href="<?php echo $addon->link; ?>" target="_blank"><?php echo $addon->title; ?></a>
            </h2>
            
            <div class="addon-description"><?php echo $addon->description; ?></div>
          </div>
        </div>

        <div class="addon-actions">
          <div class="hs-web-interactive-inline hs-wrap" style="" data-hubspot-wrapper-cta-id="<?php echo $addon->hubspot_id?>">
            <?php if ( !empty($addon->activate_link) ) : ?>
              <a href="<?php echo $addon->activate_link?>" 
                class="button-action button button-primary <?php if ( $addon->active ) echo 'disabled'; ?>"
              >Activate</a> 
            <?php else : ?>

              <?php if ( !empty($addon->hubspot_link) ) : ?>
                <a href="<?php echo $addon->hubspot_link?>" 
                  class="hs-inline-web-interactive-<?php echo $addon->hubspot_id?> button-action button button-primary" 
                  data-hubspot-cta-id="<?php echo $addon->hubspot_id?>"
                  target="_blank" rel="noopener" crossorigin="anonymous" onerror="this.style.display='none'"
                >Download</a> 
              <?php else : // HubSpot does not work as expected ?>
                <a href="<?php echo $addon->wp?>" 
                  class="button-action button button-primary" 
                  target="_blank" rel="noopener" crossorigin="anonymous"
                >Download</a> 
              <?php endif; ?>  

            <?php endif; ?>  
          </div>

          <div class="addon-secondary-actions">
            <a href="<?php echo $addon->link?>" class="addon-secondary-link" target="_blank" rel="noopener" crossorigin="anonymous">
              <span class="dashicons dashicons-book-alt"></span> <?php _e('Docs', ud_get_stateless_media()->domain)?>
            </a>

            <?php if ($addon->wp) : ?>
              <a href="<?php echo $addon->wp?>" class="addon-secondary-link" target="_blank" rel="noopener" crossorigin="anonymous">
                <span class="dashicons dashicons-wordpress"></span> <?php _e('WordPress', ud_get_stateless_media()->domain)?>
              </a>
            <?php endif; ?>  

            <?php if ($addon->repo) : ?>
              <a href="https://github.com/<?php echo $addon->repo?>" class="addon-secondary-link" target="_blank" rel="noopener" crossorigin="anonymous">
                <img src="https://github.githubassets.com/favicons/favicon.svg" class="gh-icon"></span> <?php _e('GitHub', ud_get_stateless_media()->domain)?>
              </a>
            <?php endif; ?>  
          </div>
        </div>

      </div>

  <?php endforeach; ?>
</div>

