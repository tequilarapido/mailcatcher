<?php

namespace Alex\MailCatcher\Behat\MailCatcherExtension\Context;

use Behat\Behat\Context\Context;
use Alex\MailCatcher\Client;

/**
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 * @author David Delevoye <daviddelevoye@gmail.com>
 */
interface MailCatcherContextInterface extends Context
{
    public function setConfiguration(Client $mailcatcher, $purgeBeforeScenario = true);
}
