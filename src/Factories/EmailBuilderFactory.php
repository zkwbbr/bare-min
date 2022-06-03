<?php

declare(strict_types=1);

namespace App\Factories;

use MetaRush\EmailFallback\Builder;
use MetaRush\EmailFallback\Server;

class EmailBuilderFactory
{
    public function getInstance(): Builder
    {
        $appCfg = AppCfg::getInstance();

        $providers = $appCfg->getSmtpProviders();

        $servers = [];
        foreach ($providers as $k => $v)
            $servers[$k] = (new Server)
                ->setHost($v['host'])
                ->setUser($v['user'])
                ->setPass($v['pass'])
                ->setPort($v['port'])
                ->setEncr($v['prot']);

        // ------------------------------------------------

        return (new Builder)
                ->setServers($servers)
                ->setAdminEmails($appCfg->getAdminEmails())
                ->setNotificationFromEmail($appCfg->getNotificationFromEmail())
                ->setFromEmail($appCfg->getNoReplyEmail())
                ->setAppName($appCfg->getAppName());
    }

}