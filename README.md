# Deployer Bundle for Symfony

Bundle for Symfony for easier Deployer configuration.

This bundle is compatible with Symfony 4.1 and Symfony 5.0. Symfony 3.4 compatibility abandoned.

## Installation

This bundle can be installed by Composer:

```bash
$ composer require m-adamski/symfony-deployer-bundle
$ composer require deployer/deployer --dev
```

## Deployer Configuration

In addition to installing this package, you must also create configuration files that it will use.
You can use the prepared script or create configuration files manually.

**Automatically**

Run the command from the root project directory:
```bash
$ vendor/bin/create-config
```

**Manually**

Create ``.deployer`` directory in your project's root directory. Then create four files in the newly added directory:

* config.php
* deployer.php
* hosts.php
* tasks.php

Complete the files with default values and then adapt them to your requirements.

``config.php``
```php
<?php

namespace Deployer;

/**
 * Global Configuration
 */
set("application", "example-application");
set("repository", "git@github.com:m-adamski/example-application.git");
set("ssh_multiplexing", false);
set("git_tty", false);

set("shared_dirs", ["var/log"]);
set("shared_files", [".env.local"]);
set("writable_dirs", ["var"]);

set("sudo_password", function () {
    return askHiddenResponse("[sudo] password: ");
});

set("allow_anonymous_stats", false);
```

``deployer.php``
```php
<?php

namespace Deployer;

require_once "recipe/symfony4.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/hosts.php";
require_once __DIR__ . "/tasks.php";

/**
 * Additional workflow events
 */
before("deploy:writable", "deploy:writable:prepare");
after("deploy:cache:warmup", "deploy:restart:php");
after("deploy:failed", "deploy:unlock");
```

``hosts.php``
```php
<?php

namespace Deployer;

/**
 * Hosts
 */
host("example.com")
    ->stage("production")
    ->user("deployer")
    ->identityFile(__DIR__ . "/.ssh/production/deployer_rsa")
    ->addSshOption("UserKnownHostsFile", "/dev/null")
    ->addSshOption("StrictHostKeyChecking", "no")
    ->set("php_version", "php7.2-fpm")
    ->set("deploy_path", "/var/www/vhosts/example.com")
    ->set("branch", "master");
```

``tasks.php``
```php
<?php

namespace Deployer;

task("deploy:restart:php", function () {
    run("echo '{{ sudo_password }}' | sudo -S service {{ php_version }} restart");
})->desc("Restart PHP");

task("deploy:writable:prepare", function () {
    $dirs = join(" ", get("writable_dirs"));

    cd("{{ release_path }}");
    run("echo '{{ sudo_password }}' | sudo -S setfacl -RL -m mask:rwX $dirs");
})->desc("Prepare writable");
```

The last step is to make changes to the configuration files according to own needs.

## License

MIT
