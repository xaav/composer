<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Command;

use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Solver;
use Composer\Repository\PlatformRepository;
use Composer\Package\MemoryPackage;
use Composer\Package\LinkConstraint\VersionConstraint;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class InstallCommand
{
    protected $composer;

    public function install($composer)
    {
        $this->composer = $composer;

        $config = $this->loadConfig();

        foreach ($config['repositories'] as $name => $spec) {
            $composer->addRepository($name, $spec);
        }

        $pool = new Pool;

        $repoInstalled = new PlatformRepository;
        $pool->addRepository($repoInstalled);
        // TODO check the lock file to see what's currently installed
        // $repoInstalled->addPackage(new MemoryPackage('C', '1.0'));

        foreach ($composer->getRepositories() as $repository) {
            $pool->addRepository($repository);
        }

        $request = new Request($pool);

        // TODO there should be an update flag or dedicated update command
        // TODO check lock file to remove packages that disappeared from the requirements
        foreach ($config['require'] as $name => $version) {
            if ('latest' === $version) {
                $request->install($name);
            } else {
                preg_match('#^([>=<~]*)([\d.]+.*)$#', $version, $match);
                if (!$match[1]) {
                    $match[1] = '=';
                }
                $constraint = new VersionConstraint($match[1], $match[2]);
                $request->install($name, $constraint);
            }
        }

        $policy = new DefaultPolicy;
        $solver = new Solver($policy, $pool, $repoInstalled);
        $result = $solver->solve($request);

        var_dump($result);die;

        $lock = array();

        // TODO fix this
        foreach ($result as $action) {
            $downloader = $composer->getDownloader($package->getSourceType());
            $installer = $composer->getInstaller($package->getType());
            $lock[$name] = $installer->install($package, $downloader);
            echo '> '.$name.' installed'.PHP_EOL;
        }

        $this->storeLockFile($lock);
    }

    protected function loadConfig()
    {
        if (!file_exists('composer.json')) {
            throw new \UnexpectedValueException('composer.json config file not found in '.getcwd());
        }
        $config = json_decode(file_get_contents('composer.json'), true);
        if (!$config) {
            throw new \UnexpectedValueException('Incorrect composer.json file');
        }
        return $config;
    }

    protected function storeLockFile(array $content)
    {
        file_put_contents('composer.lock', json_encode($content)."\n");
        echo '> composer.lock dumped'.PHP_EOL;
    }
}