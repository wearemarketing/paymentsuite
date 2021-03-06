<?php

/*
 * This file is part of the PaymentSuite package.
 *
 * Copyright (c) 2013-2016 Marc Morera
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

namespace PaymentSuite\PaymentCoreBundle\ValueObject;
use PaymentSuite\PaymentCoreBundle\ValueObject\Interfaces\RedirectionRouteInterface;

/**
 * Class RedirectionRoute.
 */
final class RedirectionRoute implements RedirectionRouteInterface
{
    /**
     * @var string
     *
     * Route
     */
    private $route;

    /**
     * @var bool
     *
     * Append
     */
    private $append;

    /**
     * @var string
     *
     * Append field
     */
    private $appendField;

    /**
     * Construct.
     *
     * @param string $route       Route
     * @param bool   $append      Append
     * @param string $appendField Append field
     */
    public function __construct(
        $route,
        $append,
        $appendField
    ) {
        $this->route = $route;
        $this->append = $append;
        $this->appendField = $appendField;
    }

    /**
     * Get route.
     *
     * @return string Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Get route attributes.
     *
     * @param mixed $appendValue Value for appending
     *
     * @return array Route attributes
     */
    public function getRouteAttributes($appendValue = null)
    {
        return $this->append
            ? [
                $this->appendField => $appendValue,
            ]
            : [];
    }
}
