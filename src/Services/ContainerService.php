<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Cfg;
use MetaRush\DataMapper;
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
            Cfg::class,
            DataMapper\Config::class,
            DataMapper\DataMapper::class
        ];

        return \in_array($id, $classes);
    }

    public function register(): void
    {
        $encryptedCreds = (string) \file_get_contents(__DIR__ . '/../../' . Cfg::credsPath);
        $credsYaml = \Zkwbbr\Utils\Decrypted::x($encryptedCreds, \App\Config\Key::getKey());
        $creds = (array) \Symfony\Component\Yaml\Yaml::parse($credsYaml);
        $appCfg = new \App\Config\App($creds);

        // ------------------------------------------------

        $prodDbDsn = \Nyholm\Dsn\DsnParser::parse($appCfg->getDbDsn());

        $dbName = \trim((string) $prodDbDsn->getPath(), '/');

        // ------------------------------------------------

        $this->getContainer()
            ->add(DataMapper\Config::class)
            ->addMethodCall('setDsn', ['mysql:host=' . $prodDbDsn->getHost() . ';dbname=' . $dbName])
            ->addMethodCall('setDbUser', [$prodDbDsn->getUser()])
            ->addMethodCall('setDbPass', [$prodDbDsn->getPassword()]);

        // ------------------------------------------------

        $this->getContainer()
            ->add(DataMapper\DataMapper::class)
            ->addArgument($this->getContainer()->get(DataMapper\Adapters\AtlasQuery::class));

        // ------------------------------------------------

        $this->getContainer()
            ->add(Cfg::class)
            ->addArgument($this->getContainer()->get(DataMapper\DataMapper::class))
            ->addArgument($creds);
    }

}