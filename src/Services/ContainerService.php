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
        $encryptedCreds = $this->encryptedCreds();
        $credsYaml = \Zkwbbr\Utils\Decrypted::x($encryptedCreds, \App\Config\Key::getKey());
        $creds = (array) \Symfony\Component\Yaml\Yaml::parse($credsYaml);
        $appCfg = new \App\Config\App($creds);

        // ------------------------------------------------

        $dbDsn = \Nyholm\Dsn\DsnParser::parse($appCfg->getDbDsn());

        $dbName = \trim((string) $dbDsn->getPath(), '/');

        // ------------------------------------------------

        $this->getContainer()
            ->add(DataMapper\Config::class)
            ->addMethodCall('setDsn', ['mysql:host=' . $dbDsn->getHost() . ';dbname=' . $dbName])
            ->addMethodCall('setDbUser', [$dbDsn->getUser()])
            ->addMethodCall('setDbPass', [$dbDsn->getPassword()]);

        // ------------------------------------------------

        $this->getContainer()
            ->add(DataMapper\DataMapper::class)
            ->addArgument(DataMapper\Adapters\AtlasQuery::class);

        // ------------------------------------------------

        $this->getContainer()
            ->add(Cfg::class)
            ->addArgument(DataMapper\DataMapper::class)
            ->addArgument($creds);
    }

    /**
     * Get encrypted creds in env vars or in file
     * Note: In prod, use env vars if using VPS/Docker, only use file if on shared server or in dev mode
     *
     * @return string
     */
    private function encryptedCreds(): string
    {
        $encryptedCreds = \getenv('APP_ENC_CREDS');

        if ($encryptedCreds !== false)
            return $encryptedCreds;

        return (string) \file_get_contents(__DIR__ . '/../../' . Cfg::credsPath);
    }

}