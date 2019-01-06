### UD API Client for WooCommerce API Manager

[![Stories](https://badge.waffle.io/usabilitydynamics/lib-ud-api-client.png?label=ready&title=Ready)](https://waffle.io/usabilitydynamics/lib-ud-api-client)
[![Dependency](https://gemnasium.com/UsabilityDynamics/lib-ud-api-client.svg)](https://gemnasium.com/UsabilityDynamics/lib-ud-api-client)
[![Scrutinizer](http://img.shields.io/scrutinizer/g/UsabilityDynamics/lib-ud-api-client.svg)](httpshttps://scrutinizer-ci.com/g/UsabilityDynamics/lib-ud-api-client)
[![Scrutinizer Coverage](http://img.shields.io/scrutinizer/coverage/g/UsabilityDynamics/lib-ud-api-client.svg)](https://scrutinizer-ci.com/g/UsabilityDynamics/lib-ud-api-client)

### API

Every API request is being done only for every product separately. So, e.g., if user has installed and activated four products, it does four API requests to UD to determine current status for every product separately. 

#### Status

Determines if product is activated on UD or not.
In case product is not activated on UD it will be deactivated on client's site.

Request is being called in the following cases:
* on loading 'Installed Plugins' page once per 12 hours.
* on loading 'Add-ons' ( licenses ) page every time.

Note, 'Status' request on loading 'Add-ons' ( licenses ) page must be called every time to be synced with UD server since user can directly remove activated license on their account on UD site. 

```php
$api = new UsabilityDynamics\UD_API\API( $args );

$api->status( array(
	// Unique product ID. See composer.json extra:schemas:licenses:product:product_id
	'product_id' => $product_id,
	// Unique hash, which generated at once on site and belongs to specific product.
	// In general instance used on UD to relate current license to specific domain.
	'instance' => $instance,
	// Not used anymore. But can be included reverted to be used if needed.
	'email' => $email,
	// Client's License key
	'licence_key' => $license_key,
) );
```

#### Activate

Request is being called in the following case:
- on product activating on 'Add-ons' ( licenses ) page.

```php
$api = new UsabilityDynamics\UD_API\API( $args );

$api->activate( array(
	// Unique product ID. See composer.json extra:schemas:licenses:product:product_id
	'product_id' => $product_id,
	// Unique hash, which generated at once on site and belongs to specific product.
  // In general instance used on UD to relate current license to specific domain.
  'instance' => $instance,
	// Version of product
	'software_version'  => $product_version,
	// Client's License key
	'licence_key' => $license_key,
	// Not used anymore. But can be included reverted to be used if needed.
	'email'             => $email,
), $product );
```

#### Deactivate

Request is being called in the following case:
- on product deactivating on 'Add-ons' ( licenses ) page.

```php
$api = new UsabilityDynamics\UD_API\API( $args );

$api->deactivate( array(
	// Unique product ID. See composer.json extra:schemas:licenses:product:product_id
	'product_id' => $product_id,
	// Unique hash, which generated at once on site and belongs to specific product.
  // In general instance used on UD to relate current license to specific domain.
  'instance' => $instance,
	// Client's License key
	'licence_key' => $license_key,
	// Not used anymore. But can be included reverted to be used if needed.
	'email'             => $email,
), $product );
```

#### Update Checker

Update Checker is being initialized only for installed and activated plugins / theme.

Responses are being cached via transient up to one hour.

Attention, be sure, that temp download link expires more then in one hour ( UD generates temp link to Amazon for downloading the product ).

##### Plugin

Adds the following filters
```php
$update_checker = new UsabilityDynamics\UD_API\Update_Checker($args);

//** Check For Plugin Updates */
add_filter( 'pre_set_site_transient_update_plugins', array( $update_checker, 'update_check' ) );
//** Check For Plugin Information to display on the update details page */
add_filter( 'plugins_api', array( $update_checker, 'request' ), 10, 3 );
```

##### Theme

Adds the following filters
```php
$update_checker = new UsabilityDynamics\UD_API\Update_Checker($args);

//** Check For Plugin Updates */
add_filter( 'pre_set_site_transient_update_themes', array( $update_checker, 'update_check' ) );
