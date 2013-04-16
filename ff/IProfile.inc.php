<?php

/**
 * Interface of Profile Storage
 */
interface IProfile
{
    /**
     * Reset profile properties
     */
    function resetProfile();

    /**
     * Copy properties from one profile object to another
     * @param object $obj - source profile object
     * @return bool
     */
    function copyProfile($obj);
}
