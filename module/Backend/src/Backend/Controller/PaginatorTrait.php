<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Backend\Controller;

trait PaginatorTrait
{

    private function _determinePagination ($totalNumberRecords, $limit = null, $current = null, $adjacents = null)
    {
        $result = array();

        if (isset($totalNumberRecords,$limit) === true)
        {
            $maxPage = ceil($totalNumberRecords / $limit);
            $result = range(1, $maxPage);

            if (isset($current, $adjacents) === true)
            {
                if (($adjacents = floor($adjacents / 2) * 2 + 1) >= 1)
                {
                    $result = array_slice($result, max(0, min(count($result) - $adjacents, intval($current) - ceil($adjacents / 2))), $adjacents);

                }
            }
        }


        // foreach($result as $page){
        //     if($current < $maxPage)
        //         array_unshift($result, $current-1);
        //     if($current = $maxPage)
        //         array_push($result, $current+1);
        // }



        return $result;

    }

}