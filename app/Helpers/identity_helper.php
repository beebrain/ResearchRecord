<?php

use App\Libraries\UserIdentity;

if (! function_exists('current_user_email')) {
    function current_user_email(): string
    {
        return UserIdentity::sessionEmail();
    }
}

if (! function_exists('current_user')) {
    /**
     * @return array<string,mixed>|null
     */
    function current_user(): ?array
    {
        return UserIdentity::sessionUser();
    }
}
