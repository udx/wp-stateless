## Build Plugin
##
##

NAME = stateless-media

# Default Install Action
default:
	npm install
  
# Install project
# - Removes composer.lock, vendor
# - Runs composer install --no-dev
# - Removes extra files.
install:
	echo Install $(NAME).
	make default
	grunt install

# Creates Release with Build Distribution
# Example: 
# make TAG=1.0.0 release
release:
	@echo Releasing $(NAME).
	make default
	sh build.sh $(TAG)