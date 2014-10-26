<?php

namespace Alex\MailCatcher\Behat\MailCatcherExtension\Context;

use Alex\MailCatcher\Client;
use Alex\MailCatcher\Message;
use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Context class for mail browsing and manipulation.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 * @author David Delevoye <daviddelevoye@gmail.com>
 */
class MailCatcherContext implements MailCatcherAwareContext, MinkAwareContext
{
    /**
     * @var Client|null
     */
    protected $client;

    /**
     * @var boolean
     */
    protected $purgeBeforeScenario;

    /**
     * @var Message|null
     */
    protected $currentMessage;

    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @var array
     */
    protected $minkParameters;

    /**
     * Sets Mink instance.
     *
     * @param Mink $mink Mink session manager
     */
    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    /**
     * Sets parameters provided for Mink.
     *
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->minkParameters = $parameters;
    }

    /**
     * Sets configuration of the context.
     *
     * @param Client  $client client to use for API.
     * @param boolean $purgeBeforeScenario set false if you don't want context to purge before scenario
     */
    public function setConfiguration(Client $client, $purgeBeforeScenario = true)
    {
        $this->client              = $client;
        $this->purgeBeforeScenario = $purgeBeforeScenario;
    }

    /**
     * Method used to chain calls. Throws exception if client is missing.
     *
     * @return client
     *
     * @throws RuntimeException client if missing from context
     */
    public function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException(sprintf('Client is missing from MailCatcherContext'));
        }

        return $this->client;
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        if (!$this->purgeBeforeScenario) {
            return;
        }

        $this->currentMessage = null;
        $this->getClient()->purge();
    }


    /**
     * @When /^I purge mails$/
     */
    public function purge()
    {
        $this->getClient()->purge();
    }

    /**
     * @When /^I open mail (from|with subject|to|containing) "([^"]+)"$/
     */
    public function openMail($type, $value)
    {
        if ($type === 'with subject') {
            $type = 'subject';
        } elseif ($type === 'containing') {
            $type = 'contains';
        }
        $criterias = array($type => $value);

        $message = $this->getClient()->searchOne($criterias);

        if (null === $message) {
            throw new \InvalidArgumentException(sprintf('Unable to find a message with criterias "%s".', json_encode($criterias)));
        }

        $this->currentMessage = $message;
    }

    /**
     * @Then /^I should see "([^"]+)" in mail$/
     */
    public function seeInMail($text)
    {
        $message = $this->getCurrentMessage();
        if (false === strpos($this->getMessageContent($message), $text)) {
            throw new \InvalidArgumentException(sprintf("Unable to find text \"%s\" in current message:\n%s", $text, $message->getContent()));
        }
    }

    /**
     * @Then /^I click (?:on )?"([^"]+)" in mail$/
     */
    public function clickInMail($text)
    {
        $message = $this->getCurrentMessage();

        $links = $this->getCrawler($message)
            ->filter('a')->each(function ($link) {
                return array(
                    'href' => $link->attr('href'),
                    'text' => $link->text()
                );
            });

        $href = null;
        foreach ($links as $link) {
            if (false !== strpos($link['text'], $text)) {
                $href = $link['href'];

                break;
            }
        }

        if (null === $href) {
            throw new \RuntimeException(sprintf('Unable to find link "%s" in those links: "%s".', $text, implode('", "', array_map(function ($link) {
                return $link['text'];
            }, $links))));
        }

        return $this->mink->getSession($this->mink->getDefaultSessionName())->visit($href);
    }

    /**
     * @Then /^(?P<count>\d+) mails? should be sent$/
     */
    public function verifyMailsSent($count)
    {
        $count  = (int)$count;
        $actual = $this->getClient()->getMessageCount();

        if ($count !== $actual) {
            throw new \InvalidArgumentException(sprintf('Expected %d mails to be sent, got %d.', $count, $actual));
        }
    }

    private function getCurrentMessage()
    {
        if (null === $this->currentMessage) {
            throw new \RuntimeException('No message selected');
        }

        return $this->currentMessage;
    }

    private function getCrawler(Message $message)
    {
        if (!class_exists('Symfony\Component\DomCrawler\Crawler')) {
            throw new \RuntimeException('Can\'t crawl HTML: Symfony DomCrawler component is missing from autoloading.');
        }

        return new Crawler($this->getMessageContent($message));
    }

    private function getMessageContent(Message $message)
    {
        if (!$message->isMultipart()) {
            return $message->getContent();
        } elseif ($message->hasPart('text/html')) {
            return $this->getCrawler($message)->text();
        } elseif ($message->hasPart('text/plain')) {
            return $message->getPart('text/plain')->getContent();
        } else {
            throw new \RuntimeException(sprintf('Unable to read mail'));
        }
    }
}
