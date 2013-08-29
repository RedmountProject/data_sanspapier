<?php
/*  Copyright (C) 2013 DELABY Benoit

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
namespace SansPapier\UserDataBundle\Controller;

namespace SansPapier\UserDataBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations\View;
use SansPapier\UserDataBundle\Entity\ProductCart;
use Symfony\Component\HttpFoundation\Session;
use SansPapier\UserDataBundle\Entity\LogAction;

/**
 * @Route("/filters");
 */
class FiltersController extends ContainerAware {

    /**
     * @Route("/resetCategoriesMarkers.{_format}",  name="resetCategoriesMarkers" , defaults={"_format" = "json"})
     * @View()
     */
    public function resetCategoriesMarkersAction() {
        $session = $this->container->get('request')->getSession();
        $session->remove('customALaUneFirstTime');
        $session->remove('customGenresFirstTime');
        $session->remove('customPublishersFirstTime');
        $session->remove('customKeywordsFirstTime');
    }
    
    /**
     * @Route("/initAndGetFilters.{_format}",  name="initAndGetFilters" , defaults={"_format" = "json"})
     * @View()
     */
    public function initAndGetFiltersAction() {
        $session = $this->container->get('request')->getSession();
        $res = array();
        $session->remove('customALaUneFirstTime');
        $session->remove('customGenresFirstTime');
        $session->remove('customPublishersFirstTime');
        $session->remove('customKeywordsFirstTime');

        $filters = $this->getFilters($session);
        
        $session->set('lastCatDisplayed', '0');
        
        if (is_null($session->get('onOffTuto'))) {
            $session->set('onOffTuto', 'true');
        }
        
        if (is_null($session->get('country'))) {
            $country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
            if(!$country)
                $country = 'FR';
            
            $session->set('country', $country);
        }
        
        if (is_null($session->get('defaultCountry'))) {
            $session->set('defaultCountry', 'FR');
        }

        $lastCatDisplayed = $session->get('lastCatDisplayed');
        $onOffTuto = $session->get('onOffTuto');
        $res[] = $filters;
        $res[] = $lastCatDisplayed;
        $res[] = $onOffTuto;

        return $res;
    }

    /**
     * @Route("/setFilters_{_setPrice}_{_setEpub}_{_setPdf}_{_setMobile}_{_setDRM}.{_format}",  name="sanspapier_filters_set", defaults={"_format" = "json"})
     * @View()
     */
    public function setFiltersAction($_setPrice, $_setEpub, $_setPdf, $_setMobile, $_setDRM) {
        $session = $this->container->get('request')->getSession();

        $session->set('mainFilters', array('price' => $_setPrice, 'epub' => $_setEpub, 'pdf' => $_setPdf, 'mobile' => $_setMobile, 'drm' => $_setDRM));
    }

    /**
     * @Route("/getFilters.{_format}",  name="sanspapier_filters_get", defaults={"_format" = "json"})
     * @View()
     * @return type
     */
    public function getFiltersAction() {
        $session = $this->container->get('request')->getSession();

        $filters = $this->getFilters($session);
        return $filters;
    }

    private function getFilters($session) {
        if (!$session->get('mainFilters')) {
            $session->set('mainFilters', array('price' => 1000, 'epub' => 1, 'pdf' => 1, 'mobile' => 1, 'drm' => 1));
        }
        return $session->get('mainFilters');
    }
    
    
    
    /**
     * @Route("/bypassCustomALaUneCalling.{_format}/{_filters}",  name="bypassCustomALaUneCalling", defaults={"_filters" = "1000|1|1|1|1","_format" = "json"})
     * @View()
     */
    public function bypassCustomALaUneCallingAction($_filters) {
        $session = $this->container->get('request')->getSession();
        $firstTime = $session->get("customALaUneFirstTime");
        // process the filters
        $filter_arr = explode("|", $_filters);
        
        if(is_null($session->get('customALaUneLastFilters')))
        {
            $session->set('customALaUneLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));
            return true;
        }
        
        $customALaUneLastFilters = $session->get('customALaUneLastFilters');
        $sameFilters = true;
        foreach($filter_arr as $key => $filterValue)
        {
            if($customALaUneLastFilters[$key] != $filterValue)
            {
                $sameFilters = false;
                break;
            }
        }
        
        if (is_null($firstTime) || ($sameFilters == false)) {
            return true;
        } else {
            $session->set('customALaUneFirstTime', false);
            return false;
        }
    }

    /**
     * @Route("/bypassCustomGenresCalling.{_format}/{_filters}",  name="bypassCustomGenresCalling", defaults={"_filters" = "1000|1|1|1|1","_format" = "json"})
     * @View()
     */
    public function bypassCustomGenresCallingAction($_filters) {
        $session = $this->container->get('request')->getSession();
        $firstTime = $session->get("customGenresFirstTime");
        
        // process the filters
        $filter_arr = explode("|", $_filters);
        
        if(is_null($session->get('customGenresLastFilters'))) {
            $session->set('customGenresLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));
            return true;
        }
        
        $customGenresLastFilters = $session->get('customGenresLastFilters');
        $sameFilters = true;
        foreach($filter_arr as $key => $filterValue)
        {
            if($customGenresLastFilters[$key] != $filterValue)
            {
                $sameFilters = false;
                break;
            }
        }
        
        if (is_null($firstTime) || $sameFilters == false) {
            return true;
        } else {
            $session->set('customGenresFirstTime', false);
            return false;
        }
    }
    
    
    /**
     * @Route("/bypassCustomPublishersCalling.{_format}/{_filters}",  name="bypassCustomPublishersCalling", defaults={"_filters" = "1000|1|1|1|1","_format" = "json"})
     * @View()
     */
    public function bypassCustomPublishersCallingAction($_filters) {
        $session = $this->container->get('request')->getSession();
        $test = $session->get("customPublishersFirstTime");
        
        // process the filters
        $filter_arr = explode("|", $_filters);
        
        if(is_null($session->get('customPublishersLastFilters'))) {
            $session->set('customPublishersLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));
        }
        else
        {
            $customPublishersLastFilters = $session->get('customPublishersLastFilters');
            $sameFilters = true;
            foreach($filter_arr as $key => $filterValue)
            {
                if($customPublishersLastFilters[$key] != $filterValue)
                {
                    $sameFilters = false;
                    break;
                }
            }
        }
        
        if (is_null($test) || $sameFilters == false) {
            return true;
        } else {
            $session->set('customPublishersFirstTime', false);
            return false;
        }
    }
    
    /**
     * @Route("/bypassCustomKeywordsCalling.{_format}/{_filters}",  name="bypassCustomKeywordsCalling", defaults={"_filters" = "1000|1|1|1|1","_format" = "json"})
     * @View()
     */
    public function bypassCustomKeywordsCallingAction($_filters) {
        $session = $this->container->get('request')->getSession();
        $test = $session->get("customKeywordsFirstTime");

        // process the filters
        $filter_arr = explode("|", $_filters);
        
        if(is_null($session->get('customKeywordsLastFilters'))) {
            $session->set('customKeywordsLastFilters', array($filter_arr[0], $filter_arr[1], $filter_arr[2], $filter_arr[3], $filter_arr[4]));
        }
        else
        {
            $customKeywordsLastFilters = $session->get('customKeywordsLastFilters');
            $sameFilters = true;
            foreach($filter_arr as $key => $filterValue)
            {
                if($customKeywordsLastFilters[$key] != $filterValue)
                {
                    $sameFilters = false;
                    break;
                }
            }
        }
        
        if (is_null($test) || $sameFilters == false) {
            return true;
        } else {
            $session->set('customKeywordsFirstTime', false);
            return false;
        }
    }

    /**
     * @Route("/saveLastCustomId.{_format}/{_catId}",  name="saveLastCustomId", defaults={"_catId" = "0","_format" = "json"})
     * @View()
     */
    public function saveLastCustomIdAction($_catId) {
        $session = $this->container->get('request')->getSession();
        $session->set('lastCatDisplayed', $_catId);
    }
    
    /**
     * @Route("/onOffTuto_{_on_off}.{_format}",  name="onOffTuto", defaults={"_on_off" = true,"_format" = "json"})
     * @View()
     */
    public function onOffTutoAction($_on_off) {
        $session = $this->container->get('request')->getSession();

        $session->set('onOffTuto', $_on_off);
    }
    
    /**
     * @Route("/logAction_{_actionType}.{_format}",  name="logAction", defaults={"_on_off" = true,"_format" = "json"})
     * @View()
     */
    public function logAction($_actionType) {
//        if($_SERVER['REMOTE_ADDR'] != '78.248.89.121')
//        {
//            $userEm = $this->container->get('doctrine')->getEntityManager("user");
//            $action = new LogAction();
//            $action ->setAction($_actionType);
//            $userEm->persist($action);
//            $userEm->flush();
//        }
    }

}

?>
