<?php

namespace ChronopostPickupPoint\EventListeners;


use ChronopostPickupPoint\ChronopostPickupPoint;
use ChronopostPickupPoint\Config\ChronopostPickupPointConst;
use ChronopostPickupPoint\Model\ChronopostPickupPointOrder;
use ChronopostPickupPoint\Model\AddressChronopostPickupPointQuery;
use ChronopostPickupPoint\Model\AddressChronopostPickupPoint;
use ChronopostPickupPoint\Controller\ChronopostPickupPointGetSpecificLocation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\Delivery\DeliveryPostageEvent;
use Thelia\Model\AddressQuery;
use Thelia\Model\Address;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\CountryQuery;


class SetDeliveryType implements EventSubscriberInterface
{
    /** @var Request */
    protected $request;

    /**
     * SetDeliveryType constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param $id
     * @return bool
     */
    protected function checkModule($id)
    {
        return $id == ChronopostPickupPoint::getModuleId();
    }

    /**
     * @param OrderEvent $orderEvent
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function saveChronopostPickupPointOrder(OrderEvent $orderEvent)
    {
        if ($this->checkModule($orderEvent->getOrder()->getDeliveryModuleId())) {
            $request = $this->getRequest();
            $chronopostOrder = new ChronopostPickupPointOrder();

            $orderId = $orderEvent->getOrder()->getId();

            $tmp_address = AddressChronopostPickupPointQuery::create()
                ->findPk($request->getSession()->get('ChronopostPickupPointDeliveryId'));

            if ($tmp_address === null) {
                throw new \ErrorException('Got an error with ChronopostPickupPoint module. Please try again to checkout.');
            }

            
            foreach (ChronopostPickupPointConst::CHRONOPOST_PICKUP_POINT_DELIVERY_CODES as $name => $code) {
                if (strtoupper($name) === $request->getSession()->get('ChronopostPickupPointDeliveryType')) {
                    $chronopostOrder
                        ->setDeliveryType($name)
                        ->setDeliveryCode($code)
                    ;
                }
            }
            $chronopostOrder
                ->setOrderId($orderId)
                ->save();
            
            $update = OrderAddressQuery::create()
                ->findPK($orderEvent->getOrder()->getDeliveryOrderAddressId())
                ->setCompany($tmp_address->getCompany())
                ->setAddress1($tmp_address->getAddress1())
                ->setAddress2($tmp_address->getAddress2())
                ->setAddress3($tmp_address->getAddress3())
                ->setZipcode($tmp_address->getZipcode())
                ->setCity($tmp_address->getCity())
                ->save()
            ;
        }
    }

    /**
     * @param OrderEvent $orderEvent
     * @return null
     */
    public function setChronopostPickupPointDeliveryAddress(OrderEvent $orderEvent){
        
    }
    public function setChronopostPickupPointDeliveryType(OrderEvent $event)
    {
         if ($this->checkModule($event->getDeliveryModule())) {
            $request = $this->getRequest();

            $address = AddressChronopostPickupPointQuery::create()
                ->findPk($event->getDeliveryAddress());

            $request->getSession()->set('ChronopostPickupPointDeliveryId', $event->getDeliveryAddress());
            if ($address === null) {
                $address = new AddressChronopostPickupPoint();
                $address->setId($event->getDeliveryAddress());
            }

            $response = ChronopostPickupPointGetSpecificLocation::getPointInfo($request->get('chronopostpointrelai'));

            if ($response !== null) {
                $customer_name = AddressQuery::create()
                    ->findPk($event->getDeliveryAddress());

                $address = AddressChronopostPickupPointQuery::create()
                    ->findPk($event->getDeliveryAddress());

                $request->getSession()->set('ChronopostPickupPointDeliveryId', $event->getDeliveryAddress());

                if ($address === null) {
                    $address = new AddressChronopostPickupPoint();
                    $address->setId($event->getDeliveryAddress());
                }
                $relayCountry = $customer_name->getCountry();

                $address
                    ->setCode($response->identifiantChronopost)
                    ->setType($response->typeDePoint)
                    ->setCompany($response->nomEnseigne)
                    ->setAddress1($response->adresse1)
                    ->setAddress2($response->adresse2)
                    ->setAddress3($response->adresse3)
                    ->setZipcode($response->codePostal)
                    ->setCity($response->localite)
                    ->setCellphone($customer_name->getCellphone())
                    ->setFirstname($customer_name->getFirstname())
                    ->setLastname($customer_name->getLastname())
                    ->setTitleId($customer_name->getTitleId())
                    ->setCountryId($relayCountry->getId())
                    ->save()
                ;
            } else {
                $message = Translator::getInstance()->trans('No pickup points were selected', [], ChronopostPickupPoint::DOMAIN);
                throw new \Exception($message);
            }
        }

        
        /* -_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_- */
        /*
        if ($this->checkModule($orderEvent->getDeliveryModule())) {
            $request = $this->getRequest();

             if($request->get('chronopostpointrelai')){
                // $address = AddressChronopostPickupPointQuery::create()->findPk($event->getDeliveryAddress());

                $request->getSession()->set('ChronopostPickupPointDeliveryId', $event->getDeliveryAddress());
                if ($address === null) {
                    $address = new AddressChronopostPickupPoint();
                    $address->setId($event->getDeliveryAddress());
                }

                $response = ChronopostPickupPointGetSpecificLocation::getPointInfo($request->get('chronopostpointrelai'));

                if ($response !== null) {
                    $customer_name = AddressQuery::create()
                        ->findPk($event->getDeliveryAddress());

                    $address = AddressChronopostPickupPointQuery::create()
                        ->findPk($event->getDeliveryAddress());

                    $request->getSession()->set('ChronopostPickupPointDeliveryId', $event->getDeliveryAddress());

                    if ($address === null) {
                        $address = new AddressChronopostPickupPoint();
                        $address->setId($event->getDeliveryAddress());
                    }

                    $relayCountry = CountryQuery::create()->findOneByIsoalpha2($response->codePays);

                    if ($relayCountry == null) {
                        $relayCountry = $customer_name->getCountry();
                    }

                    $address
                        ->setCode($response->identifiant)
                        ->setType($response->typeDePoint)
                        ->setCompany($response->nom)
                        ->setAddress1($response->adresse1)
                        ->setAddress2($response->adresse2)
                        ->setAddress3($response->adresse3)
                        ->setZipcode($response->codePostal)
                        ->setCity($response->localite)
                        ->setFirstname($customer_name->getFirstname())
                        ->setLastname($customer_name->getLastname())
                        ->setTitleId($customer_name->getTitleId())
                        ->setCountryId($relayCountry->getId())
                        ->save()
                    ;
                } else {
                    $message = Translator::getInstance()->trans('No pickup points were selected', [], ChronopostPickupPoint::DOMAIN);
                    throw new \Exception($message);
                }
                 
                 
                // echo $request->get('chronopostpointrelai');
                // If the relay point service was chosen, we store the address of the chosen relay point in
                //    the DeliveryPostageEvent in order for Thelia to recalculate the postage cost from this address.
                $response = ChronopostPickupPointGetSpecificLocation::getPointInfo($request->get('chronopostpointrelai'));

                if ($response !== null) {
                    $address = new Address();
               
                    $relayCountry = CountryQuery::create()->findOneByIsoalpha2('FR'); // $response->codePays

                    $address
                        ->setCompany($response->nom)
                        ->setAddress1($response->adresse1)
                        ->setAddress2($response->adresse2)
                        ->setAddress3($response->adresse3)
                        ->setZipcode($response->codePostal)
                        ->setCity($response->localite)
                        ->setCountryId($relayCountry->getId())
                    ;
                    $event->setAddress($address);
                }
            }
            
            
            $request->getSession()->set('ChronopostAddressId', $orderEvent->getDeliveryAddress());
            $request->getSession()->set('ChronopostPickupPointDeliveryType', $request->get('chronopost-pickup-point-delivery-mode'));
        }

        return ;
        */
    }

    public function getPostageRelayPoint(DeliveryPostageEvent $event)
    {
        if ($this->checkModule($event->getModule()->getModuleModel()->getId())) {
            $request = $this->getRequest();

            if($request->get('chronopostpointrelai')){
                // echo $request->get('chronopostpointrelai');
                // If the relay point service was chosen, we store the address of the chosen relay point in
                //    the DeliveryPostageEvent in order for Thelia to recalculate the postage cost from this address.
                $response = ChronopostPickupPointGetSpecificLocation::getPointInfo($request->get('chronopostpointrelai'));

                if ($response !== null) {
                    $address = new Address();
                /*    echo '<pre>';
                    print_r($response);
                    echo '</pre>';
                    echo $response->codePays;
                    exit; */
                    $relayCountry = CountryQuery::create()->findOneByIsoalpha2('FR'); // $response->codePays

                    $address
                        ->setCompany($response->nom)
                        ->setAddress1($response->adresse1)
                        ->setAddress2($response->adresse2)
                        ->setAddress3($response->adresse3)
                        ->setZipcode($response->codePostal)
                        ->setCity($response->localite)
                        ->setCountryId($relayCountry->getId())
                    ;
                    $event->setAddress($address);
                }
            }
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::ORDER_SET_DELIVERY_ADDRESS => array('setChronopostPickupPointDeliveryAddress', 64),
            TheliaEvents::ORDER_SET_DELIVERY_MODULE => array('setChronopostPickupPointDeliveryType', 64),
            TheliaEvents::ORDER_BEFORE_PAYMENT => array('saveChronopostPickupPointOrder', 256),
            TheliaEvents::MODULE_DELIVERY_GET_POSTAGE => array('getPostageRelayPoint', 257)
        );
    }
}