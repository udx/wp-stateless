<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div id="col-container" class="<?php //about-wrap ?>">
  <div>
    <?php
      $this->list_table = new UsabilityDynamics\UD_API\More_Products_Table( array(
        'name' => $this->name,
        'domain' => $this->domain,
        'page' => $this->menu_slug,
      ) );
      $this->list_table->data = $this->more_products;
      $this->list_table->prepare_items();
      $this->list_table->display();
    ?>
  </div><!--/.col-wrap-->
</div><!--/#col-container-->