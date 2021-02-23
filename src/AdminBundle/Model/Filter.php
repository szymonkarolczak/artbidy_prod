<?php

namespace AdminBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class Filter
{

    private $request;
    private $uriFilter;
    private $configuration;
    private $query;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->uriFilter = $this->request->get('_filter');
    }

    public function setConfiguration(Array $config)
    {
        $this->configuration = $config;
    }

    public function parseQuery(QueryBuilder $qb, $prefix = 'a')
    {

        $qb->select($prefix);
        foreach($this->configuration as $field_name => $config)
        {
            foreach($config as $field_option_name => $field_option_value)
            {

                if(!isset($this->uriFilter[$field_name]))
                    continue;

                if($config['type'] == 'string')
                {
                    if(!empty($this->uriFilter[$field_name]))
                    {
                        $qb->andWhere($qb->expr()->like($prefix.'.'.$field_name, ':'.$field_name));
                        $qb->setParameter($field_name, '%'.$this->uriFilter[$field_name].'%');
                    }
                }
                elseif($config['type'] == 'boolean')
                {
                    if($this->uriFilter[$field_name] !== '')
                    {
                        $qb->andWhere($prefix.'.'.$field_name .'='. $this->uriFilter[$field_name]);
                    }
                }
                elseif($config['type'] == 'datetimerange')
                {
                    if(!empty($this->uriFilter[$field_name]))
                    {
                        $range = explode(' - ', $this->uriFilter[$field_name]);
                        $from = new \DateTime(trim($range[0]));
                        $to = new \DateTime(trim($range[1]));
                        $qb->andWhere($prefix.'.'.$field_name .'>='. ':'.$field_name.'_from');
                        $qb->andWhere($prefix.'.'.$field_name .'<='. ':'.$field_name.'_to');
                        $qb->setParameter($field_name.'_from', $from);
                        $qb->setParameter($field_name.'_to', $to);
                    }
                }
                elseif($config['type'] == 'select')
                {
                    if(!empty($this->uriFilter[$field_name]))
                    {
                        $qb->andWhere($prefix.'.'.$field_name.' = :'.$field_name);
                        $qb->setParameter($field_name, $this->uriFilter[$field_name]);
                    }
                }

            }
        }
        return $qb;
    }

    public function getFilters()
    {
        return $this->configuration;
    }

}