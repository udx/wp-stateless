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
  
  'buddyboss' => [
    'title'           => 'BuddyBoss Platform',
    'plugin_files'    => ['buddyboss-platform/bp-loader.php'],
    'addon_file'      => 'wp-stateless-buddyboss-addon/wp-stateless-buddyboss-addon.php',
    'icon'            => 'https://www.buddyboss.com/wp-content/uploads/2022/04/bb-logo-1.png',
    'repo'            => 'udx/wp-stateless-buddyboss-addon', 
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-buddyboss-platform-addon/', 
    'hubspot_id'      => '151481399845', 
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLIz%2BcFUMcIBKQ7Xqj0pOF0COKC9I0GezkxwgHqPgiPgyfhisc6veCbNsRloVLAajjD9D%2ByVhIPRFdsFfxJbmC96vdcpZbFUIqn%2F2qS7eXcpXHENalnSIMHrRy3vZ25OujO7MQ8WgbQMNJlTJJ9N0%2FyC6UbEjKMWdWjvjXnAPRh5giepyw2JtqMqgupq85f5rhzgYJgXJKOAzaOwja%2Bedw%3D%3D&amp;portalId=20504491', 
  ],

  'elementor' => [
    'title'           => 'Elementor Website Builder',
    'plugin_files'    => ['elementor/elementor.php'],
    'addon_file'      => 'wp-stateless-elementor-addon/wp-stateless-elementor-addon.php',
    'icon'            => 'https://ps.w.org/elementor/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-elementor-website-builder-addon', 
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-elementor-website-builder-addon/', 
    'hubspot_id'      => '151481399819', 
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLKR8B2Z9422V%2Fh9SGpptZeq1UWUETejTC8i1C7YoBj8TRWSG2Yij36fQHaj37NIgIU0OgWeZ9SAaTb9lL%2BlPaEKwWJ1WcQNWv%2FLFWh1Y8LTEIUGRvPzShNKyv0yIC5Z3Hu6YWGYp46iXXI6nLLBfbt2fHytn3mHX7Ic3%2ByuAF3Cz2rmMusOMD3XSJGTAYobOOXuyHJzeHzztZAimflHRg%3D%3D&amp;portalId=20504491', 
  ],

  'woocommerce' => [
    'title'           => 'WooCommerce',
    'plugin_files'    => ['woocommerce/woocommerce.php'],
    'addon_file'      => 'wp-stateless-woocommerce-addon/wp-stateless-woocommerce-addon.php',
    'icon'            => 'https://ps.w.org/woocommerce/assets/icon-128x128.gif',
    'repo'            => 'udx/wp-stateless-woocommerce-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-woocommerce-addon/',
    'hubspot_id'      => '151478251047',
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLKJr1PcJ%2BBZGWmWGTx%2Bc7Sh4FacNlnvMTTQNjX%2BUmtx5f1v6gkfcoZGfadciwzLMdGM0sFedlfakugWH%2FdNwCHb4nNp4YBkN0R4jfQIC8RM6ksptsyoPhr2Ws0%2BMkaYVtUkujGU99Pu8r1LBsLY1UJ5vWPU5k5pOEoNGDrw8Y%2FsUwi7oiF5ws2lHdL53NqfZZ7wrybTx7J5ZBpn7ZYiSLWE&amp;portalId=20504491', 
  ],

  'gravity-forms' => [
    'title'           => 'Gravity Forms',
    'plugin_files'    => ['gravityforms/gravityforms.php'],
    'addon_file'      => 'wp-stateless-gravity-forms-addon/wp-stateless-gravity-forms-addon.php',
    'icon'            => 'https://cdn2.hubspot.net/hub/4148022/hubfs/Artboard%20Copy%2011.png',
    'repo'            => 'udx/wp-stateless-gravity-forms-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-gravity-forms-addon/',
    'hubspot_id'      => '151481399808',
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLJ2A%2FS%2FrBjbH%2B2zEjfR3T%2B1mWtJnXEtorbr5Olt8GrxTFwKcTV490FD%2F%2FywcQ4Yp944qIl%2FI1BcwoIIsFaYC1Z5v8FygDdJrSd%2FeqTr3jQ2I0tVfJaErezy9f%2BRdTPLQClqa28wG%2FNPQu%2F%2ByD08wtdQhaZMTW7s%2FCwLYFl9RLFqXcc4gW2vCI20%2Bx9NxFAVkMeqRN18USGE7M5iwl8AoOwI1tA%3D&amp;portalId=20504491', 
  ],

  'gravity-forms-signature' => [
    'title'           => 'Gravity Forms Signature',
    'plugin_files'    => ['gravityformssignature/signature.php'],
    'addon_file'      => 'wp-stateless-gravity-forms-signature-addon/wp-stateless-gravity-forms-signature-addon.php',
    'icon'            => 'https://cdn2.hubspot.net/hub/4148022/hubfs/Artboard%20Copy%2011.png',
    'repo'            => 'udx/wp-stateless-gravity-forms-signature-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-gravity-forms-signature-addon/',
    'hubspot_id'      => '151481399724',
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLLr66o8tFz1owH%2F8niAXobMUrxwU7SenenLgUzTIQiYLyvaqx%2FBVmvE60MJI3oMg%2BKtMZzfMsvk6Uedvxv1E2mUg7VwJtc9DxdcCgS8uJh58x6fiXwdUPNDkeKlLWeoNtvEj7zhNdDrsJf6kEo6t7vIFpF7aT2%2F8N3RGUCmwUBDRMDt6t7Fdko0LUhAnePm4PAby7S3kmVUZ0POP0bwh%2BjJXTL6eRK4VsTdvaDZ&amp;portalId=20504491',
  ],

  'divi' => [
    'title'           => 'Divi Theme',
    'theme_name'      => 'Divi',
    'addon_file'      => 'wp-stateless-divi-theme-addon/wp-stateless-divi-theme-addon.php',
    'icon'            => 'https://www.elegantthemes.com/images/favicon/favicon-divi-128.png',
    'repo'            => 'udx/wp-stateless-divi-theme-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-divi-theme-addon/',
    'hubspot_id'      => '151478250935',
    // 'hubspot_link'    => 'https://cta-service-cms2.hubspot.com/web-interactives/public/v1/track/click?encryptedPayload=AVxigLKBdXT3iKKssnyU5oSlJCOfudt7i6nV%2F5ojUf63FZt1MjnlQ%2BEP2JTU9DfhHfCCMUS9eZkfBezgAUKkinw0pOw0yKUWSYGw89SIF9OyVci07g94GTyYx0j17tt3LC7jZ34Nhe4GfQQHXvkad%2FNNTRoumwcNoV5csmLHPEnEwq7XWsYPSN73GTzxEwySOMWBk%2FTt%2BROYZDCM3Ks%3D&amp;portalId=20504491',
  ],

  'siteorigin-css' => [
    'title'           => 'SiteOrigin CSS',
    'plugin_files'    => ['so-css/so-css.php'],
    'addon_file'      => 'wp-stateless-siteorigin-css-addon/wp-stateless-siteorigin-css-addon.php',
    'icon'            => 'https://ps.w.org/so-css/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-siteorigin-css-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-siteorigin-css-addon/',
    'hubspot_id'      => '151480507684',
  ],

  'siteorigin-widgets-bundle' => [
    'title'           => 'SiteOrigin Widgets Bundle',
    'plugin_files'    => ['so-widgets-bundle/so-widgets-bundle.php'],
    'addon_file'      => 'wp-stateless-siteorigin-widgets-bundle-addon/wp-stateless-siteorigin-widgets-bundle-addon.php',
    'icon'            => 'https://ps.w.org/so-widgets-bundle/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-siteorigin-widgets-bundle-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-siteorigin-widgets-bundle-addon/',
    'hubspot_id'      => '151480507657',
  ],

  'wpforms' => [
    'title'           => 'WPForms',
    'plugin_files'    => ['wpforms-lite/wpforms.php', 'wpforms/wpforms.php'],
    'addon_file'      => 'wp-stateless-wpforms-addon/wp-stateless-wpforms-addon.php',
    'icon'            => 'https://ps.w.org/wpforms-lite/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-wpforms-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-wpforms-addon/',
    'hubspot_id'      => '151481399840',
  ],

  'edd' => [
    'title'           => 'Easy Digital Downloads',
    'plugin_files'    => ['easy-digital-downloads/easy-digital-downloads.php'],
    'addon_file'      => 'wp-stateless-easy-digital-downloads-addon/wp-stateless-easy-digital-downloads-addon.php',
    'icon'            => 'https://ps.w.org/easy-digital-downloads/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-easy-digital-downloads-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-easy-digital-downloads-addon/',
    'hubspot_id'      => '151481399833',
  ],

  'lite-speed-cache' => [
    'title'           => 'LiteSpeed Cache',
    'plugin_files'    => ['litespeed-cache/litespeed-cache.php'],
    'addon_file'      => 'wp-stateless-litespeed-cache-addon/wp-stateless-litespeed-cache-addon.php',
    'icon'            => 'https://ps.w.org/litespeed-cache/assets/icon-128x128.png',
    'repo'            => 'udx/wp-stateless-litespeed-cache-addon',
    'wp'              => 'https://wordpress.org/plugins/wp-stateless-litespeed-cache-addon/',
    'hubspot_id'      => '151480507763',
  ],

  'buddypress' => [
    'title'           => 'BuddyPress',
    'plugin_files'    => ['buddypress/bp-loader.php'],
    'addon_file'      => 'wp-stateless-buddypress-addon/wp-stateless-buddypress-addon.php',
    'icon'            => 'https://ps.w.org/buddypress/assets/icon.svg',
    'repo'            => 'udx/wp-stateless-buddypress-addon',
    'wp'              => 'https://wordpress.org/support/plugin/wp-stateless-buddypress-addon/',
    'hubspot_id'      => '151478250924',
  ],

];
