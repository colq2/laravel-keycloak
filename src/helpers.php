<?php

if (! function_exists('socialite')) {

    /**
     * Get the available socialite instance.
     *
     * @return \Laravel\Socialite\SocialiteManager
     */
    function socialite()
    {

        return app(\Laravel\Socialite\Contracts\Factory::class);
    }
}