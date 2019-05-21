node {
    stage("composer_install") {
        // Run `composer update` as a shell script
        bat 'composer install'
    }
    stage("phpunit") {
        // Run PHPUnit
        bat '.vendor\bin\phpunit'
    }
}
