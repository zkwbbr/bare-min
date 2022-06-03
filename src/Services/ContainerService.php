<?php

declare(strict_types=1);

namespace App\Services;

use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 *
 * https://container.thephpleague.com/4.x/service-providers/
 */
final class ContainerService extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $classes = [
            \App\Config\Cfg::class,
            \MetaRush\DataMapper\Config::class,
            \MetaRush\DataMapper\DataMapper::class
        ];

        return \in_array($id, $classes);
    }

    public function register(): void
    {
        $credsPath = APP_DEVELOPMENT_MODE ? APP_CREDS_PATH_DEV : APP_CREDS_PATH_PROD; // @phpstan-ignore-line
        $encryptedCreds = (string) \file_get_contents($credsPath);
        $credsYaml = \Zkwbbr\Utils\Decrypted::x($encryptedCreds, APP_CREDS_KEY);
        $creds = (array) \Symfony\Component\Yaml\Yaml::parse($credsYaml);
        $appCfg = new \App\Config\App($creds);

        // ------------------------------------------------

        $prodDbDsn = \Nyholm\Dsn\DsnParser::parse($appCfg->getDbDsn());

        $dbName = \trim((string) $prodDbDsn->getPath(), '/');

        // ------------------------------------------------

        $this->getContainer()
            ->add(\MetaRush\DataMapper\Config::class)
            ->addMethodCall('setDsn', ['mysql:host=' . $prodDbDsn->getHost() . ';dbname=' . $dbName])
            ->addMethodCall('setDbUser', [$prodDbDsn->getUser()])
            ->addMethodCall('setDbPass', [$prodDbDsn->getPassword()]);

        // ------------------------------------------------

        $this->getContainer()
            ->add(\MetaRush\DataMapper\DataMapper::class)
            ->addArgument($this->getContainer()->get(\MetaRush\DataMapper\Adapters\AtlasQuery::class));

        // ------------------------------------------------

        $this->getContainer()
            ->add(\App\Config\Cfg::class)
            ->addArgument($this->getContainer()->get(\MetaRush\DataMapper\DataMapper::class))
            ->addArgument($creds);
    }

}