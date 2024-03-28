
<div class="metabox-holder">
  <div class="postbox">
	  <h2 class="hndle"><?php _e('Info', ud_get_stateless_media()->domain); ?></h2>

    <div class="inside">
      <div class="main">

        <table class="widefat striped stateless-info-table" role="presentation">
					<tbody>
            <?php foreach ($rows as $row ) : ?>
              <tr>
                <td class="label"><?php echo $row->label; ?></td>
                <td class="value"><?php echo $row->value; ?></td>
              </tr>
             <?php endforeach;?>
          </tbody>
				</table>

      </div>
    </div>
  </div>
</div>