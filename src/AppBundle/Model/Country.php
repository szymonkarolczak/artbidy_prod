<?php

namespace AppBundle\Model;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Yaml\Yaml;

class Country
{

    static $countries_en = array();
    static $countries_pl = array();

    private $translator;
    private $kernelDir;

    public function __construct(Translator $translator, $kernelDir)
    {
        $this->translator = $translator;
        $this->kernelDir = $kernelDir;
    }

    public function convert($name, $locale)
    {
        $translated = $this->translator->trans($name, [], 'countries', $locale);
        if($translated == $name)
        {
            //Brak tłumaczenia w plikach
            $urlName = str_replace(' ', '%20', $name);
            $address = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$urlName.'&language='.strtoupper($locale);
            $result = json_decode(file_get_contents($address));
            if(isset($result->results[0]->address_components[0]->long_name))
            {
                $translatedSaveName = $result->results[0]->address_components[0]->long_name.'_trans';
            }
            else
            {
                $translatedSaveName = $name.'_trans';
            }

            $langFilePath = $this->kernelDir . DIRECTORY_SEPARATOR .
                'Resources' . DIRECTORY_SEPARATOR .
                'translations' . DIRECTORY_SEPARATOR .
                'countries.'.strtolower($locale).'.yml';

            $value = Yaml::parse(file_get_contents($langFilePath));
            $value[$name] = $translatedSaveName;
            file_put_contents($langFilePath, Yaml::dump($value));

            return substr($translatedSaveName, 0, -6);
        }

        return substr($translated, 0, -6);
    }

}