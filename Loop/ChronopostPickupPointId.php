<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace ChronopostPickupPoint\Loop;

use ChronopostPickupPoint\Controller\ChronopostPickupPointRelayController;
use ErrorException;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Address;
use Thelia\Model\AddressQuery;
use Thelia\Model\CountryQuery;

/**
 * Class ChronopostPickupPointGetRelay
 * @package ChronopostPickupPoint\Loop
 *
 * @method string getOrderWeight
 * @method string getZipcode
 * @method string getCity
 * @method string getCountryid
 * @method string getAddress
 */
class ChronopostPickupPointId extends BaseLoop implements ArraySearchLoopInterface
{
    /**
     * @inheritdoc
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument('id')
        );
    }

    /**
     * @return array|mixed
     * @throws ErrorException
     * @throws PropelException
     */
    public function buildArray()
    {
        // Find the address ... To find ! \m/
        $id = $this->getId();

        $search = AddressQuery::create();

        $customer = $this->securityContext->getCustomerUser();
        if ($customer !== null) {
            $search->filterByCustomerId($customer->getId());
            $search->filterByIsDefault('1');
        } else {
            throw new ErrorException('Customer not connected.');
        }

        $search = $search->findOne();
        $address['orderweight'] = $orderWeight;
        $address['zipcode'] = $search->getZipcode();
        $address['city'] = $search->getCity();
        $address['address'] = $search->getAddress1();
        $address['countrycode'] = $search->getCountry()->getIsoalpha2();


        // Then ask the Web Service
        $request = new ChronopostPickupPointRelayController();
        try {
            $response = $request->findByCodeId($id);

        } catch (InvalidArgumentException $e) {
            $response = array();
        } catch (\Exception $e) {
            $response = array();
        }

        if (!is_array($response) && $response !== null) {
            $newResponse[] = $response;
            $response = $newResponse;
        }

        return $response;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $item) {
            $loopResultRow = new LoopResultRow();
            echo '<pre>';
            print_r($item);
            echo '</pre>';  
            
            foreach ($item as $key => $value) {
                $loopResultRow->set(strtoupper($key), $value);
            }

            // format distance
            $distance = (string) $loopResultRow->get('DISTANCEENMETRE');
            if (strlen($distance) < 4) {
                $distance .= ' m';
            } else {
                $distance = (string)(float)$distance / 1000;
                while (substr($distance, strlen($distance) - 1, 1) == "0") {
                    $distance = substr($distance, 0, strlen($distance) - 1);
                }
                $distance = str_replace('.', ',', $distance) . ' km';
            }
            $loopResultRow->set('distance', $distance);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}