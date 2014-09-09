## http://git.rtcamp.com/rtbiz/process/blob/master/content/testing/phpunit.md

# before-script
export PLUGIN_DIR=$(pwd)
export PLUGIN_SLUG=$(basename $(pwd) | sed 's/^wp-//')
pear config-set auto_discover 1
pear install PHP_CodeSniffer
cd $(pear config-get php_dir)/PHP/CodeSniffer/Standards/
git clone git://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git
cd $(pear config-get php_dir)/PHP/CodeSniffer/Standards/
ln -s WordPress-Coding-Standards/WordPress WordPress
rm -rf WordPress/Sniffs/VIP
phpenv rehash

# Test to be conducted
function run_test ()
{
    # PHP Setup Code

    if [ -e phpunit.xml ] || [ -e phpunit.xml.dist ]; then bash bin/install-wp-tests.sh wordpress_test root '' localhost $WP_VERSION; fi

    #script
    find . -path ./bin -prune -o \( -name '*.php' -o -name '*.inc' \) -exec php -lf {} \;
    if [ -e phpunit.xml ] || [ -e phpunit.xml.dist ]; then phpunit || return 1; fi
    phpcs --ignore='tests/*' --standard=WordPress $(find . -name '*.php') || return 1
    jshint . || return 1
}

#
function display_op()
{
    echo -e "\t$1\t$2\t$3\t$4\t$5"
}

# main_script
for PHP_VERSION in 5.2 5.3 5.4 5.5 5.6; do
    for WP_VERSION in 4.0 3.9; do
        for WP_MULTISITE in 0 1; do
            LOG_FILE="${CI_BUILD_REF_NAME}_php-${PHP_VERSION}_wp-${WP_VERSION}_m-${WP_MULTISITE}.log"
            run_test > $LOG_FILE
            if [ $? -eq 0 ]; then
                STATUS="PASS"
            else
                STATUS="FAIL"
            fi
            display_op $STATUS $PHP_VERSION $WP_VERSION $WP_MULTISITE $LOG_FILE
        done
    done
done
