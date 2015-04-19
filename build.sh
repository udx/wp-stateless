#!/bin/bash

############################################################################################
#
# Automatic Distribution Build and Tag creating on GitHub
#
############################################################################################
#
# Script by default does the following steps:
# - creates temp directory
# - clones git repository there
# - creates temp branch
# - installs composer and nodes dependencies
# - adds vendor directory ( composer dependencies ) to commit
# - clears out build
# - commits build to temp branch
# - creates new tag
# - removes temp branch
# - removes temp directory
#
############################################################################################
#
# Options:
# - $1 ( $RELEASE_VERSION ) - Required. Tag version which will be created for current build
#
############################################################################################
#
# Features:
# - The current script generates new Tag on GitHub for your build (Distributive).
# - circleci compatible. It can use the latest commit log for creating new tag via CircleCI. 
# Log message should contain [release:{tag}] shortcode
#
############################################################################################
#
# Examples:
#
# Run remote sh file:
# curl -s https://url-to-release-sh-file.sh | RELEASE_VERSION=1.2.3 sh
#
# Run local sh file
# sh build.sh 1.2.3
#
# Run grunt task ( see information about gruntfile.js below )
# grunt release:1.2.3
#
############################################################################################
#
# CircleCi
# The current script can be triggered on CircleCi.
# Add the following settings to your circle.yml file:
# 
# deployment:
#   production:
#     branch: master
#       commands:
#         - sh build.sh
#
# Notes: 
# - Log ( commit ) message should contain [release:{tag}] shortcode for running script.
# - script will be triggered only on successful (green) build for 'master' branch in 
# current example.
# - in random cases gist file is not available on curl request, I suggest to 
# download script and call it directly.
#
# More details about CircleCi deployment:
# https://circleci.com/docs/configuration#deployment
#
############################################################################################
#
# Gruntfile.js
#
# module.exports = function release( grunt ) {
#
#  grunt.initConfig( {
#
#    shell: {
#      release: {
#        command: function( tag ) {
#          return 'sh build.sh ' + tag;
#        },
#        options: {
#          encoding: 'utf8',
#          stderr: true,
#          stdout: true
#        }
#      }
#     }
#     
#   } );
#
#   grunt.registerTask( 'release', 'Run release tasks.', function( tag ) {
#     if ( tag == null ) grunt.warn( 'Release tag must be specified, like release:1.0.0' );
#     grunt.task.run( 'shell:release:' + tag );
#   });
#
# }
#
#
######################################################################################

echo " "
echo "Running build script..."
echo "---"

if [ -z $RELEASE_VERSION ] ; then
 
  # Try to get Tag version which should be created.
  if [ -z $1 ] ; then
    echo "Tag version parameter is not passed."
    echo "Determine if we have [release:{version}] shortcode to deploy new release"
    RELEASE_VERSION="$( git log -1 --pretty=%s | sed -n 's/.*\[release\:\(.*\)\].*/\1/p' )"  
  else
    echo "Tag version parameter is "$1
    RELEASE_VERSION=$1
  fi
  
else 
 
  echo "Tag version parameter is "$RELEASE_VERSION
 
fi

echo "---"

if [ -z $RELEASE_VERSION ] ; then

  echo "No [release:{tag}] shortcode found."
  echo "Finish process."
  exit 0
  
else

  echo "Determine current branch:"
  if [ -z $CIRCLE_BRANCH ]; then
    CIRCLE_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  fi
  echo $CIRCLE_BRANCH
  echo "---"
    
  # Remove temp directory if it already exists to prevent issues before proceed
  if [ -d temp-build-$RELEASE_VERSION ]; then
    rm -rf temp-build-$RELEASE_VERSION
  fi
  
  echo "Create temp directory"
  mkdir temp-build-$RELEASE_VERSION
  cd temp-build-$RELEASE_VERSION
  
  echo "Do production build from scratch to temp directory"
  ORIGIN_URL="$( git config --get remote.origin.url )"
  git clone $ORIGIN_URL
  cd "$( basename `git rev-parse --show-toplevel` )"
  # Be sure we are on the same branch
  git checkout $CIRCLE_BRANCH
  echo "---"
  
  #echo "Clean up structure ( remove composer relations )"
  #rm -rf composer.lock
  #rm -rf vendor
  
  #echo "Running: composer install --no-dev --no-interaction"
  #composer install --no-dev --no-interaction --quiet
  #echo "---"
  
  echo "Create local and remote temp branch temp-automatic-branch-"$RELEASE_VERSION
  git checkout -b temp-branch-$RELEASE_VERSION
  git push origin temp-branch-$RELEASE_VERSION
  git branch --set-upstream-to=origin/temp-branch-$RELEASE_VERSION temp-branch-$RELEASE_VERSION
  echo "---"

  # It's used only by CircleCi. Should not be called directly.
  #
  #echo "Set configuration to proceed"
  #git config --global push.default simple
  #git config --global user.email "$( git log -1 --pretty=%an )"
  #git config --global user.name "$( git log -1 --pretty=%ae )"
  #echo "---"

  echo "Be sure we do not add node and other specific files needed only for development"
  rm -rf vendor/composer/installers
  rm -rf coverage.clover
  rm -rf ocular.phar
  rm -rf build
  rm -rf node_modules
  rm -rf composer.lock
  rm -rf .scrutinizer.yml
  rm -rf circle.yml
  rm -rf build.sh
  rm -rf gruntfile.js
  rm -rf makefile
  rm -rf package.json
  rm -rf test
  echo "Be sure we do not add .git directories"
  find ./vendor -name .git -exec rm -rf '{}' \;
  echo "Be sure we do not add .svn directories"
  find ./vendor -name .svn -exec rm -rf '{}' \;
  echo "Git Add"
  git add --all
  echo "Be sure we added vendor directory"
  git add -f vendor
  echo "---"
  
  echo "Now commit our build to remote branch"
  git commit -m "[ci skip] Distributive Auto Build" --quiet
  git pull
  git push --quiet
  echo "---"

  echo "Finally, create tag "$RELEASE_VERSION
  git tag -a $RELEASE_VERSION -m "v"$RELEASE_VERSION" - Distributive Auto Build"
  git push origin $RELEASE_VERSION
  echo "---"

  echo "Remove local and remote temp branches, but switch to previous branch before"
  git checkout $CIRCLE_BRANCH
  git push origin --delete temp-branch-$RELEASE_VERSION
  git branch -D temp-branch-$RELEASE_VERSION
  echo "---"
  
  # Remove temp directory.
  echo "Remove temp directory"
  cd ../..
  rm -rf temp-build-$RELEASE_VERSION
  echo "---"
  
  echo "Done"

fi 
