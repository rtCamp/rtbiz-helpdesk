## http://git.rtcamp.com/rtbiz/process/blob/master/content/testing/phpunit.md

# before-script
source ~/.phpbrew/bashrc
export PLUGIN_DIR=$(pwd)
export PLUGIN_SLUG=$(basename $(pwd) | sed 's/^wp-//')
pear config-set auto_discover 1
final_op=""
OLD_PATH=$PATH

cd ../

rm -rf phpcs
mkdir phpcs
cd phpcs
export PHPCS_DIR=$(pwd)
export PHPCS_GITHUB_SRC=squizlabs/PHP_CodeSniffer
export PHPCS_GIT_TREE=master
export WPCS_GITHUB_SRC=WordPress-Coding-Standards/WordPress-Coding-Standards
export WPCS_GIT_TREE=master
export WPCS_STANDARD=WordPress
export PHPCS_IGNORE=tests/*

curl -L https://github.com/$PHPCS_GITHUB_SRC/archive/$PHPCS_GIT_TREE.tar.gz | tar xz --strip-components=1 -C $PHPCS_DIR
mkdir -p $PHPCS_DIR/CodeSniffer/Standards/WordPress-Coding-Standards && curl -L https://github.com/$WPCS_GITHUB_SRC/archive/$WPCS_GIT_TREE.tar.gz | tar xz --strip-components=1 -C $PHPCS_DIR/CodeSniffer/Standards/WordPress-Coding-Standards
ln -s $PHPCS_DIR/CodeSniffer/Standards/WordPress-Coding-Standards/WordPress $PHPCS_DIR/CodeSniffer/Standards/WordPress
rm -rf $PHPCS_DIR/CodeSniffer/Standards/WordPress-Coding-Standards/WordPress/Sniffs/VIP
cd ../

rm -rf rtbiz
git clone git@git.rtcamp.com:rtbiz/rtbiz.git
cd rtbiz
git checkout develop
cd ../

rm -rf posts-to-posts
wget -nv -O posts-to-posts.zip http://downloads.wordpress.org/plugin/posts-to-posts.1.6.3.zip
unzip -q posts-to-posts.zip

cd $PLUGIN_DIR

# Test to be conducted
function run_test ()
{
    # PHP Setup Code

    #script
    find ./app \( -name "*.php" -o -name "*.inc" \)  ! -path "./app/assets/*" ! -path "./app/vendor/*" ! -path "./app/lib/*" ! -path "./app/schema/*" -exec php -lf {} \;
    
    if [ -e phpunit.xml ] || [ -e phpunit.xml.dist ]; then phpunit || return 1; fi
    $PHPCS_DIR/scripts/phpcs --standard=$WPCS_STANDARD $(if [ -n "$PHPCS_IGNORE" ]; then echo --ignore=$PHPCS_IGNORE; fi) $(find ./app -name "*.php" ! -path "./app/assets/*" ! -path "./app/vendor/*" ! -path "./app/lib/*" ! -path "./app/schema/*" ) || return 1
    jshint . || return 1
}

#
function display_op()
{
    echo -e "\t$1\t$2\t$3\t$4\t$5\n"
}

# main_script
for WP_VERSION in 4.0 3.9; do

    if [ -e phpunit.xml ] || [ -e phpunit.xml.dist ]; then bash bin/install-wp-tests.sh wordpress_test_db wptestuser wptestpass localhost $WP_VERSION; fi

    for PHP_VERSION in 5.2.17 5.3.29 5.4.32 5.5.16 5.6.0; do

        export PATH=/opt/phpbrew/php/php-${PHP_VERSION}/bin:$OLD_PATH
        
        php --version

        for WP_MULTISITE in 0 1; do
            LOG_FILE="/home/gitlab_ci_runner/log/${CI_BUILD_ID}_php-${PHP_VERSION}_wp-${WP_VERSION}_m-${WP_MULTISITE}.log"
            run_test > $LOG_FILE
            if [ $? -eq 0 ]; then
                STATUS="PASS"
            else
                STATUS="FAIL"
            fi
            display_op $STATUS $PHP_VERSION $WP_VERSION $WP_MULTISITE $LOG_FILE
            final_op=$final_op."\n$STATUS $PHP_VERSION $WP_VERSION $WP_MULTISITE $LOG_FILE"
        done
    done
done

echo ==========================================================================
echo -e "$final_op"
