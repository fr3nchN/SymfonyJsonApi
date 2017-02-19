<?php

namespace Pilotabai\CompetitionDbBundle\Serializer;

use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Pilotabai\CompetitionDbBundle\Annotation\Link;
use Pilotabai\CompetitionDbBundle\Entity\Category;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\RouterInterface;

class LinkSerializationSubscriber implements EventSubscriberInterface
{
    private $router;

    private $annotationReader;

    private $expressionLanguage;

    public function __construct(RouterInterface $router, Reader $annotationReader)
    {
        $this->router = $router;
        $this->annotationReader = $annotationReader;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();

//        /** @var Category $category */
//        $category = $event->getObject();
//        if ($category->getId() > 0) {
//            $visitor->setData(
//                'uri',
//                $this->router->generate('api_categories_show', [
//                    'id' => $category->getId()
//                ])
//            );
//        }

        $object = $event->getObject();
        $annotations = $this->annotationReader
            ->getClassAnnotations(new \ReflectionObject($object));

        $links = array();
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Link) {
                $uri = $this->router->generate(
                    $annotation->route,
                    $this->resolveParams($annotation->params, $object)
                );
                $links[$annotation->name] = $uri;
            }
        }

        if ($links) {
            $visitor->setData('_links', $links);
        }
    }

    private function resolveParams(array $params, $object)
    {
        foreach ($params as $key => $param) {
            $params[$key] = $this->expressionLanguage
                ->evaluate($param, array('object' => $object));
        }
        return $params;
    }

    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
                'format' => 'json',
//                'class' => 'Pilotabai\CompetitionDbBundle\Entity\Category'
            )
        );
    }
}