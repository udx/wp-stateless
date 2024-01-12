<?php

/**
 * Returns the list of addons plugins.
 * 
 * Sample structure:
 *  'buddypress' => [ // ID of the addon is a slug for Addon page on https://stateless.udx.io/addons/
 *    'title'         => 'BuddyPress',
 *    'plugin_files'  => ['buddypress/bp-loader.php'], // if this is a plugin Addon
 *    'theme_name'    => 'Divi', // if this is a theme Addon
 *    'addon_file'    => 'wp-stateless-buddypress-addon/wp-stateless-buddypress-addon.php', // Addon plugin main file (to check it's presence and activation)
 *    'icon'          => 'https://ps.w.org/wp-stateless/assets/icon.svg', // icon URL
 *    'repo'          => 'udx/wp-stateless-buddyboss-addon', // (optional) link to GitHub
 *    'wp'            => 'https://wordpress.org/plugins/wp-stateless-buddyboss-addon/', // (optional) link to WordPress.org plugin page
 *    'hubspot_id'    => '123456789', // (optional) HubSpot ID for Download button
 *    'hubspot_link'  => 'https://cta-service-cms2.hubspot.com/web-interactives/...', // (optional) HubSpot link for Download button
 *  ],
 *
 * ID of the addon is a slug for Addon page on https://stateless.udx.io/addons/  
 */

return [
  /*
  'buddyboss' => [
    'title'           => 'BuddyBoss Platform',
    'plugin_files'    => ['buddyboss-platform/bp-loader.php'],
    'addon_file'      => 'wp-stateless-buddyboss-addon/wp-stateless-buddyboss-addon.php',
    'icon'            => 'https://www.buddyboss.com/wp-content/uploads/2022/04/bb-logo-1.png',
    'repo'            => 'udx/wp-stateless-buddyboss-addon', 
    'wp'              => 'https://wordpress.org/plugins/wp-stateless/', 
    'hubspot_id'      => '151481399845', 
    'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLIz%2BcFUMcIBKQ7Xqj0pOF0COKC9I0GezkxwgHqPgiPgyfhisc6veCbNsRloVLAajjD9D%2ByVhIPRFdsFfxJbmC96vdcpZbFUIqn%2F2qS7eXcpXHENalnSIMHrRy3vZ25OujO7MQ8WgbQMNJlTJJ9N0%2FyC6UbEjKMWdWjvjXnAPRh5giepyw2JtqMqgupq85f5rhzgYJgXJKOAzaOwja%2Bedw%3D%3D&amp;portalId=20504491', 
  ],
  */  

];
