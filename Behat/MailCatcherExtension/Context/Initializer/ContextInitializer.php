<?php

namespace Alex\MailCatcher\Behat\MailCatcherExtension\Context\Initializer;

use Alex\MailCatcher\Behat\MailCatcherExtension\Context\MailCatcherContextInterface;
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

    public function supports(ContextInterface $context)
    {
        return $context instanceof MailCatcherContextInterface;
    }

    public function initializeContext(ContextInterface $context)
    {
        if (!$context instanceof MailCatcherContextInterface) {
            return;
        }
        
        $context->setConfiguration($this->client, $this->purgeBeforeScenario);
    }
}
