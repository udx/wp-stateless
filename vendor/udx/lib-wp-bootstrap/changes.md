### 1.3.5

* Security hardening of the admin notice dismissal AJAX handler: require a valid nonce, require the `manage_options` capability, and restrict writable option keys to the `dismiss_*_notice` pattern.

### 1.3.4

* Fix notice about translations loaded too soon

### 1.3.3

* Require user to be logged in while dismissing Admin Panel notices.

### 1.3.2

* Improve security while processing AJAX requests in Admin Panel.

### 1.3.1

* Remove dependency from `udx/lib-utility`.

### 1.2.2

* Fixed `path` utility function: detecting if dir belongs to plugins directory by `WP_PLUGIN_DIR`, but not only by deprecated `PLUGINDIR`.