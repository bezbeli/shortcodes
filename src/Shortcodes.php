<?php

namespace Bezbeli;

use wp_query;

/**
 * Test shortcode class.
 */
class Shortcodes
{
    public function __construct()
    {
        remove_shortcode('gallery');
        // add_filter('use_default_gallery_style', '__return_null');

        add_shortcode('gallery', [$this, 'gallery']);
        add_shortcode('subpages', [$this, 'subpages']);
        add_shortcode('sections', [$this, 'sections']);
        add_shortcode('booking', [$this, 'booking']);
        add_shortcode('google_map', [$this, 'googleMap']);
        add_shortcode('soundcloud', [$this, 'soundcloud']);
        add_shortcode('events', [$this, 'events']);
        add_shortcode('attachments', [$this, 'attachments']);
    }

    public function events()
    {
        return '<div class="row mb-5"><div class="col"><h2>What\'s happening next</h2></div></div>';
    }

    public function googleMap()
    {
        return '<div class="row mb-5"><div class="col"><div class="ratio ratio-2x1 border">Google map</div></div></div>';
    }

    public function booking()
    {
        return '<div class="row mb-5"><div class="col">Booking</div></div>';
    }

    public function soundcloud()
    {
        return '<div class="row mb-5"><div class="col">Soundcloud</div></div>';
    }

    public function sections($attr)
    {
        extract(
            shortcode_atts(
                [
                    'title'      => 'Ovo je default title',
                    'order'      => 'ASC',
                    'orderby'    => 'menu_order ID',
                    'id'         => $post->ID,
                    'itemtag'    => '',
                    'icontag'    => '',
                    'captiontag' => '',
                    'columns'    => '',
                    'size'       => 'thumbnail',
                    'include'    => '',
                    'exclude'    => get_post_thumbnail_id($post->ID),
                    'link'       => 'none',
                ],
                $attr
            )
        );

        var_export($attr);
    }

    public function gallery($attr)
    {
        $post = get_post();

        static $instance = 0;
        ++$instance;

        if (!empty($attr['ids'])) {
            if (empty($attr['orderby'])) {
                $attr['orderby'] = 'post__in';
            }
            $attr['include'] = $attr['ids'];
        }

        $output = apply_filters('post_gallery', '', $attr);

        if ('' != $output) {
            return $output;
        }

        if (isset($attr['orderby'])) {
            $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
            if (!$attr['orderby']) {
                unset($attr['orderby']);
            }
        }

        extract(
            shortcode_atts(
                [
                    'order'      => 'ASC',
                    'orderby'    => 'menu_order ID',
                    'id'         => $post->ID,
                    'itemtag'    => '',
                    'icontag'    => '',
                    'captiontag' => '',
                    'columns'    => 4,
                    'size'       => 'thumbnail',
                    'include'    => '',
                    'exclude'    => '',
                    'link'       => '',
                    'blueimp'    => true,
                ],
                $attr
            )
        );

        $id = intval($id);
        $columns = (0 == 12 % $columns) ? $columns : 3;
        $grid = sprintf('col-sm-%1$s col-lg-%1$s', 12 / $columns);

        if ('RAND' === $order) {
            $orderby = 'none';
        }

        if (!empty($include)) {
            $_attachments = get_posts(
                [
                    'include'        => $include,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );

            $attachments = [];
            foreach ($_attachments as $key => $val) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif (!empty($exclude)) {
            $attachments = get_children(
                [
                    'post_parent'    => $id,
                    'exclude'        => $exclude,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );
        } else {
            $attachments = get_children(
                [
                    'post_parent'    => $id,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );
        }

        if (empty($attachments)) {
            return '';
        }

        if (is_feed()) {
            $output = "\n";
            foreach ($attachments as $att_id => $attachment) {
                $output .= wp_get_attachment_link($att_id, $size, true)."\n";
            }

            return $output;
        }

        $unique = (get_query_var('page')) ? $instance.'-p'.get_query_var('page') : $instance;
        $output = '';
        if ($blueimp) {
            $output .= '
            <div id="blueimp-gallery" class="blueimp-gallery">
                <div class="slides"></div>
                <h3 class="title"></h3>
                <a class="prev">‹</a>
                <a class="next">›</a>
                <a class="close">×</a>
                <a class="play-pause"></a>
                <ol class="indicator"></ol>
            </div>
            ';
        }

        $output .= '<div id="links">';

        $i = 0;
        foreach ($attachments as $id => $attachment) {
            $imgUrl = wp_get_attachment_image_src($id, 'large')[0];
            $title = $attachment->post_title;
            switch ($link) {
                case 'none':
                    $image = wp_get_attachment_image($id, $size, false, ['class' => 'img-fluid']);
                    break;
                case 'external':
                    $image = '<a target="_blank" href="'.$attachment->post_excerpt.'" title="'.$title.'">';
                    $image .= wp_get_attachment_image($id, $size, false, ['class' => 'img-fluid']);
                    $image .= '</a>';
                    break;
                default:
                    $image = '<a data-gallery="" href="'.$imgUrl.'" title="'.$title.'" data-description="'.$attachment->post_excerpt.'">';
                    $image .= wp_get_attachment_image($id, $size, false, ['class' => 'img-fluid']);
                    $image .= '</a>';
                    break;
            }
            $output .= (0 == $i % $columns) ? '<div class="row d-flex justify-content-center align-items-center gallery-row mb-3">' : '';
            $output .= '<div class="'.$grid.'">'.$image;

            if ($link != 'external' && trim($attachment->post_excerpt)) {
                $output .= '<div class="caption hidden">'.wptexturize($attachment->post_excerpt).'</div>';
            }

            $output .= '</div>';
            ++$i;
            $output .= (0 == $i % $columns) ? '</div>' : '';
        }

        $output .= (0 != $i % $columns) ? '</div>' : '';
        $output .= '</div>';

        return $output;
    }

    public function attachments($attr)
    {
        $post = get_post();

        static $instance = 0;
        ++$instance;

        if (!empty($attr['ids'])) {
            if (empty($attr['orderby'])) {
                $attr['orderby'] = 'post__in';
            }
            $attr['include'] = $attr['ids'];
        }

        $output = apply_filters('post_gallery', '', $attr);

        if ('' != $output) {
            return $output;
        }

        if (isset($attr['orderby'])) {
            $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
            if (!$attr['orderby']) {
                unset($attr['orderby']);
            }
        }

        extract(
            shortcode_atts(
                [
                    'title'      => '',
                    'order'      => 'ASC',
                    'orderby'    => 'menu_order ID',
                    'id'         => $post->ID,
                    'itemtag'    => '',
                    'icontag'    => '',
                    'captiontag' => '',
                    'columns'    => '',
                    'size'       => 'thumbnail',
                    'include'    => '',
                    'exclude'    => get_post_thumbnail_id($post->ID),
                    'link'       => 'none',
                ],
                $attr
            )
        );

        $id = intval($id);
        $grid = $columns ? sprintf('col-%1$s', 12 / $columns) : 'col';

        if ('RAND' === $order) {
            $orderby = 'none';
        }

        if (!empty($include)) {
            $_attachments = get_posts(
                [
                    'include'        => $include,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );

            $attachments = [];
            foreach ($_attachments as $key => $val) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif (!empty($exclude)) {
            $attachments = get_children(
                [
                    'post_parent'    => $id,
                    'exclude'        => $exclude,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );
        } else {
            $attachments = get_children(
                [
                    'post_parent'    => $id,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'order'          => $order,
                    'orderby'        => $orderby,
                ]
            );
        }

        if (empty($attachments)) {
            return '';
        }

        if (is_feed()) {
            $output = "\n";
            foreach ($attachments as $att_id => $attachment) {
                $output .= wp_get_attachment_link($att_id, $size, true)."\n";
            }

            return $output;
        }

        $output = '';
        $output .= '<h4 class="text-center mb-3">'.$title.'</h4>';

        $output .= '<div class="row justify-content-center align-items-start">';

        foreach ($attachments as $id => $attachment) {
            $imgUrl = wp_get_attachment_image_src($id, 'large')[0];
            $title = $attachment->post_title;
            $image = wp_get_attachment_image($id, $size, false, ['class' => 'img-fluid']);

            $output .= '<div class="'.$grid.' mb-4">';
            $output .= $image;
            $output .= '<h6 class="mt-2">'.$title.'</h6>';

            if (trim($attachment->post_content)) {
                $output .= '<p class="small">'.wptexturize($attachment->post_content).'</p>';
            }

            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function subpages($attr)
    {
        extract(
            shortcode_atts(
                [
                    'order'   => 'ASC',
                    'orderby' => 'menu_order',
                    'id'      => get_the_id(),
                    'columns' => 4,
                    'size'    => 'medium',
                ],
                $attr
            )
        );

        $col = 12 / $columns;

        $output = '';

        $subpage_args = [
            'post_type'      => 'page',
            'post_parent'    => get_the_id(),
            'orderby'        => 'menu_order',
            'order'          => 'desc',
            'posts_per_page' => 12,
        ];

        $subpages = new wp_query($subpage_args);

        if ($subpages->have_posts()) :
            $output .= '<div class="row justify-content-start">';
        while ($subpages->have_posts()) :
                $subpages->the_post();
        $thumb_url = get_the_post_thumbnail_url(get_the_id(), $size);

        // $output .= '<div class="col-md-'.$col.' mb-4 d-flex align-items-stretch">';
        // $output .= '<div class="card w-100">';
        // $output .= '<a href="'.get_permalink().'">';
        // $output .= '<div class="ratio ratio-1x1">';
        // $output .= '<div class="image lazy" data-src="'.$thumb_url.'"></div>';
        // $output .= '</div>';
        // $output .= '</a>';
        // $output .= '<div class="card-body">';
        // $output .= '<a href="'.get_permalink().'">';
        // $output .= get_the_title();
        // $output .= '</a>';
        // $output .= '</div>';
        // $output .= '</div>';
        // $output .= '</div>';

        $output .= '<div class="d-flex col-6 col-md-' . $col . ' align-items-stretch px-2 mb-4">';
        $output .= '<div class="blog-item w-100 border">';
        $output .= '<a class="ratio ratio-1x1 photo mb-2" href="' . get_permalink() . '" style="background-image:url(' . $thumb_url . '"></a>';
        $output .= '<a href="' . get_permalink() . '">';
        $output .= '<h5 class="p-3">' . get_the_title() . '</h5>';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div>';


        endwhile;

        $output .= '</div>';
        endif;

        return $output;
    }
}
