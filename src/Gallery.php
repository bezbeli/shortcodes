<?php

namespace Bezbeli\Gallery;

class Gallery
{
    public function __construct()
    {
        add_filter('next_posts_link_attributes', [$this, 'postsLinkAttributes']);
        add_filter('previous_posts_link_attributes', [$this, 'postsLinkAttributes']);
    }

    public function postsLinkAttributes()
    {
        return 'class="page-link"';
    }

    public function test()
    {
        return 'evo me';
    }
}
