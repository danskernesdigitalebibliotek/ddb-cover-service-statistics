<?php

/**
 * @file
 * Contains a response subscriber that removes entries that have been delivered to Faktor.
 */

namespace App\EventSubscriber;

use App\Document\Entry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ResponseSubscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface
{
    private $dispatcher;
    private $documentManager;

    /**
     * ResponseSubscriber constructor.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     *   The event dispatcher
     * @param DocumentManager $documentManager
     *   Entry repository
     */
    public function __construct(EventDispatcherInterface $dispatcher, DocumentManager $documentManager)
    {
        $this->dispatcher = $dispatcher;
        $this->documentManager = $documentManager;
    }

    /**
     * Mark all entries that have been delivered from the database as extracted.
     *
     * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
     *
     * @throws \Exception
     */
    public function onResponseEvent(ResponseEvent $event)
    {
        $request = $event->getRequest();

        $extractionDatetime = new \DateTime();

        // If requesting Event collection, mark each element as already retrieved.
        if ('api_entries_get_collection' === $request->attributes->get('_route') &&
            Entry::class === $request->attributes->get('_api_resource_class')) {
            $results = json_decode($event->getResponse()->getContent());

            $ids = array_reduce($results, function ($carry, $element) {
                $carry[] = $element->id;

                return $carry;
            }, []);

            $this->dispatcher->addListener(
                KernelEvents::TERMINATE,
                function (TerminateEvent $event) use ($ids, $extractionDatetime) {
                    foreach ($ids as $id) {
                        /* @var Entry $entry */
                        $entry = $this->documentManager->getRepository(Entry::class)->find($id);
                        $entry->setExtracted(true);
                        $entry->setExtractionDate($extractionDatetime);
                    }

                    $this->documentManager->flush();
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => 'onResponseEvent',
        ];
    }
}
