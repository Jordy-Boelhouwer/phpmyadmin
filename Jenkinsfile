node {
    stage("composer_install") {
        // Run `composer update` as a shell script
        curl -sS https://getcomposer.org/installer | php
        bat 'composer install'
    }
    stage("phpunit") {
        // Run PHPUnit
        bat 'vendor/bin/phpunit'
    }
}
