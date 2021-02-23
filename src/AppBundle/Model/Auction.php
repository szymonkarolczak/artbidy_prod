<?php

namespace AppBundle\Model;

use AppBundle\Entity\AuctionBid;

class Auction
{

    public function getMinNextPrice(Array $increment, $lastPrice)
    {
        $foundInc = 0;
        foreach ($increment as $price => $inc)
        {
            if($lastPrice > $price)
            {
                $foundInc = $inc;
            }
        }

        return $lastPrice + $foundInc;
    }

}