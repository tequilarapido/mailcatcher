<?php

namespace Alex\MailCatcher\Behat\MailCatcherExtension\Context\Initializer;

use Alex\MailCatcher\Behat\MailCatcherExtension\Context\MailCatcherAwareContext;
use Alex\MailCatcher\Client;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer as ContextInitializerInterface;

class ContextInitializer implements ContextInitializerInterface
{
    protected $client;
    protected $purgeBeforeScenario;

    public function __construct(Client $client, $purgeBeforeScenario = true)
    {
        $this->client = $client;
        $this->purgeBeforeScenario = $purgeBeforeScenario;
    }

    public function supports(Context $context)
    {
        return $context instanceof MailCatcherAwareContext;
    }

    public function initializeContext(Context $context)
    {
        if (!$context instanceof MailCatcherAwareContext) {
            return;
        }
        
        $context->setConfiguration($this->client, $this->purgeBeforeScenario);
    }
}
