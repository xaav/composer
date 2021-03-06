<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\DependencyResolver;

/**
 * @author Nils Adermann <naderman@naderman.de>
 */
class RuleSet
{
    // highest priority => lowest number
    const TYPE_PACKAGE = 0;
    const TYPE_JOB = 1;
    const TYPE_UPDATE = 2;
    const TYPE_FEATURE = 3;
    const TYPE_WEAK = 4;
    const TYPE_LEARNED = 5;

    protected static $types = array(
        -1 => 'UNKNOWN',
        self::TYPE_PACKAGE => 'PACKAGE',
        self::TYPE_FEATURE => 'FEATURE',
        self::TYPE_UPDATE => 'UPDATE',
        self::TYPE_JOB => 'JOB',
        self::TYPE_WEAK => 'WEAK',
        self::TYPE_LEARNED => 'LEARNED',
    );

    protected $rules;

    public function __construct()
    {
        foreach ($this->getTypes() as $type) {
            $this->rules[$type] = array();
        }
    }

    public function add(Rule $rule, $type)
    {
        if (!isset(self::$types[$type]))
        {
            throw OutOfBoundsException('Unknown rule type: ' . $type);
        }

        if (!isset($this->rules[$type]))
        {
            $this->rules[$type] = array();
        }

        $this->rules[$type][] = $rule;
        $rule->setType($type);
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getIterator()
    {
        return new RuleSetIterator($this->getRules());
    }

    public function getIteratorFor($types)
    {
        if (!is_array($types))
        {
            $types = array($types);
        }

        $allRules = $this->getRules();
        $rules = array();

        foreach ($types as $type)
        {
            $rules[$type] = $allRules[$type];
        }

        return new RuleSetIterator($rules);
    }


    public function getIteratorWithout($types)
    {
        if (!is_array($types))
        {
            $types = array($types);
        }

        $rules = $this->getRules();

        foreach ($types as $type)
        {
            unset($rules[$type]);
        }

        return new RuleSetIterator($rules);
    }

    public function getTypes()
    {
        $types = self::$types;
        unset($types[-1]);
        return array_keys($types);
    }

    public function __toString()
    {
        $string = "\n";
        foreach ($this->rules as $type => $rules) {
            $string .= str_pad(self::$types[$type], 8, ' ') . ": ";
            foreach ($rules as $rule) {
                $string .= $rule;
            }
            $string .= "\n";
        }

        return $string;
    }
}
