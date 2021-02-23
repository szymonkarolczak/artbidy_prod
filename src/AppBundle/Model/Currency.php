<?php

namespace AppBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class Currency
{
    public function parse(Array $currencies)
    {
        foreach ($currencies as &$currency) {
            if ($currency->getCode() == 'PLN') {
                $currency->bid = '';
                $currency->ask = '';
            } else {
                $currency->bid = $this->convertCurrency(1, strtoupper($currency->getCode()), 'PLN');
                $currency->ask = '';
            }
        }
        return $currencies;
    }

    public function convert($currency, $targetCurrency, $value)
    {
        if ($currency == $targetCurrency)
            $returnVal = $value;
        else
            $returnVal = $this->convertCurrency($value, $currency, $targetCurrency);

        if (strpos($returnVal, '.00') !== FALSE) {
            $returnVal = substr($returnVal, 0, -3);
        }

        return $returnVal;
    }

    public function getData($code)
    {
        if (!($content = file_get_contents('http://api.nbp.pl/api/exchangerates/rates/C/' . $code . '/?format=json')))
            return false;

        if (!($json = json_decode($content)))
            return false;

        return $json;
    }

    //17.04.2019 - zmiana na Yahoo API, Paweł Kowal (paulancer@o2.pl)
    private function convertCurrency($amount, $from, $to)
    {
        $currentRate = 0;
        $key = 'currencyrate_' . $from . '_new_' . $to;
        $session = new Session();
        if ($session->has($key)){
            $currentRate = $session->get($key);
        }

        if (empty($currentRate) || $currentRate === 0) {
            try {
                $result = file_get_contents('https://api.exchangerate-api.com/v4/latest/'.$from);
                $o = json_decode($result,false);

                $session->set($key, $o->rates->{$to});
                $currentRate = $o->rates->{$to};
            } catch (\Exception $e) {
                $currentRate = 0;
            }
        }
        $converted = $amount * $currentRate;

        return round($converted, 2);
    }

}