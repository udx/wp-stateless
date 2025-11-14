## Storage Usage
Storage module is developed for client-side persistant storing of key & value pairs.

```javascript

 // Initialize Storage.
 var Storage = require( 'udx.storage' ).create();

  // Set some values.
 Storage.setItem( 'name', 'Andy' );
 Storage.setItem( 'latitude', '34.4239' );
 Storage.setItem( 'longitude', '-77.5584' );

 // Later...
 console.log( 'Welcome', Storage.getItem( 'name' ), 'we have you located at', Storage.getItem( 'latitude' ), ', ', Storage.getItem( 'longitude' ) );

```

Storage attempts to save data into localStorage, if the browser supports it. Otherwise we fallback to Cookie storage.

## Usage Notes
* Root keys that start with __ will never be committed to storage.
* There are four types of storage types available - "option", "site_meta", "transient" and "site_transient".

## Settings Usage

```php
// Instantiate and load Settings.
$settings  = new Settings(array(
  "store" => "options",
  "key" => "settings_test",
  "format" => "object",
  "auto_commit" => true,
  "data" => array( 'initial data', 'blah' )
));

$settings->set( 'make', 'Chevy' );
$settings->set( 'model', 'Tahoe' );

$settings->set( 'features', array(
  'ac',
  'stuff'
  'dvd',
  'sunroof'
));

$settings->set( 'options', array(
  "gps" => 'standard',
  "rims" => '24',
  "towing" => true,
  "onstar" => 'active'
));
```

```php
// Initialize Settings.
$this->_settings = new Settings(array(
  "store" => "options",
  "key" => "ud:veneer",
));

// ElasticSearch Service Settings.
$this->set( 'documents', array(
  "host" => "localhost",
  "active" => true,
  "token" => "alsdkjflaksdjsadsdff",
  "port" => 9200
));

// Varnish Service Settings.
$this->set( 'varnish', array(
  "host" => "localhost",
  "active" => false
));

// CDN Service Settings.
$this->set( 'cdn', array(
  "active" => true,
  "provider" => "gcs",
  "subdomain" => "media",
  "key" => "alsdkjflaksdjf"
));

// Save Settings.
$this->_settings->commit();
```

## License

(The MIT License)

Copyright (c) 2013 Usability Dynamics, Inc. &lt;info@usabilitydynamics.com&gt;

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
