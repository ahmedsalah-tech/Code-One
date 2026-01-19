<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\Models\Category
     */
    protected $category;
}
