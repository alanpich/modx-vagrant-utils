#!/usr/bin/env php
<?php
$force = false;

array_shift($argv);
if(in_array('-f',$argv)){
    $force = true;
}


// -----------------------------------------------------------------------------------------

class Output {
    const RED = "\033[0;31m";
    const YELLOW = "\033[0;33m";
    const GREEN  = "\033[0;32m";
    const BLUE  = "\033[0;34m";
    const RESET  = "\033[0m";

    public static function info($msg){
        echo self::BLUE . '[INFO]    ' . self::RESET . $msg . "\n";
    }
    public static function warn($msg){
        echo self::YELLOW . '[WARN]    ' . self::RESET . $msg . "\n";
    }
    public static function error($msg){
        echo self::RED . '[ERROR]   ' . self::RESET . $msg . "\n";
    }
    public static function success($msg){
        echo self::GREEN . '[SUCCESS] ' . self::RESET . $msg . "\n";
    }
}

// -----------------------------------------------------------------------------------------

/**
 * Base configuration
 */
$HERE = dirname(__FILE__);
$config = [
    'git_repo' => 'https://github.com/modxcms/revolution.git',
    'git_branch' => 'master',
    'base_path' => '/var/www/modx',
    'base_url'  => '/'
];

// -----------------------------------------------------------------------------------------

/**
 * Check for install flag and prevent duplicate installs
 */
if(is_readable($HERE.'/installer-has-run') && ! $force){
    Output::error("Installer has already been run. Use -f to force continue");
    exit(1);
}

// -----------------------------------------------------------------------------------------

/**
 * Look for a modx.vagrant.ini file in the vagrant mount to
 * provide additional config for provisioning
 */
$file = '/vagrant/modx.vagrant.ini';
if(is_readable($file)){
    $config = array_merge($config,parse_ini_file($file));
    Output::info("Configuration overrides loaded from local project");
}
$PATH = $config['base_path'];
$REPO = $config['git_repo'];
$BRANCH = $config['git_branch'];

// -----------------------------------------------------------------------------------------

/**
 * Stop apache server
 */
Output::info('Stopping apache2 service');
exec('sudo service apache2 stop');

// -----------------------------------------------------------------------------------------

/**
 * Clone the git repo into place
 */
Output::info("Cloning git repository from {$config['git_repo']}");
exec("
    cd /var/www/modx &&
    git init && 
    git add origin {$REPO} && 
    git fetch && 
    git checkout -t origin/{$BRANCH}
")

// -----------------------------------------------------------------------------------------

/**
 * Copy setup/installer files into place
 */
Output::info("Setting up installer configuration");
copy($HERE.'/templates/build.config.php',"{$PATH}/_build/build.config.php");
copy($HERE.'/templates/build.properties.php',"{$PATH}/_build/build.properties.php");
$content = file_get_contents("{$HERE}/templates/config.xml.template");
foreach($config as $key => $value){
    $key = '{!'.strtoupper($key).'!}';
    $content = str_replace($key,$value,$content);
}
file_put_contents("{$PATH}/setup/config.xml",$content);

// -----------------------------------------------------------------------------------------

/**
 * Forcibly set owner of all files
 */
Output::info("Forcibly setting file owner to vagrant");
exec("chown -R vagrant:vagrant {$PATH}");

// -----------------------------------------------------------------------------------------

/**
 * Build the core package
 */
Output::info("Building core package");
Output::info(shell_exec("cd {$PATH}/_build && php transport.core.php"));

// -----------------------------------------------------------------------------------------


/**
 * Drop and re-create the database (just to be on the safe side)
 */
Output::info("Creating the database");
shell_exec('echo "DROP DATABASE IF EXISTS modx; CREATE DATABASE modx" | mysql -u root -ppassword');

/**
 * Run the installer
 */
 Output::info("Running MODX installer");
 Output::warn(shell_exec("cd {$PATH}/setup && php index.php --installmode=new"));

// -----------------------------------------------------------------------------------------

/**
 * Add file to system to say that this script has run
 */
touch($HERE.'/installer-has-run');

// -----------------------------------------------------------------------------------------

/**
 * Re-start apache
 */
Output::info('Starting apache');
exec('sudo service apache2 start');

// -----------------------------------------------------------------------------------------

Output::success("Installation complete");
