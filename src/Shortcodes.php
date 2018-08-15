<?php

namespace Bezbeli;

/**
 * Test shortcode class.
 */
class Shortcodes
{
    public function __construct()
    {
        remove_shortcode('gallery');
        // add_filter('use_default_gallery_style', '__return_null');

        add_shortcode('gallery', [$this, 'blueimpGallery']);
        add_shortcode('test', [$this, 'saySomethingElseShortcode']);
        add_shortcode('hello', [$this, 'sayHelloShortcode']);
    }

    public function sayHelloShortcode($attr)
    {
        return 'I am happy to say hello!';
    }

    public function saySomethingElseShortcode($attr)
    {
        return 'I am happy to say Something Else!';
    }

    public function blueimpGallery($attr)
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
                'order' => 'ASC',
                'orderby' => 'menu_order ID',
                'id' => $post->ID,
                'itemtag' => '',
                'icontag' => '',
                'captiontag' => '',
                'columns' => 4,
                'size' => 'thumbnail',
                'include' => '',
                'exclude' => '',
                'link' => '',
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
                'include' => $include,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'order' => $order,
                'orderby' => $orderby,
                ]
            );

            $attachments = [];
            foreach ($_attachments as $key => $val) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif (!empty($exclude)) {
            $attachments = get_children(
                [
                'post_parent' => $id,
                'exclude' => $exclude,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'order' => $order,
                'orderby' => $orderby,
                ]
            );
        } else {
            $attachments = get_children(
                [
                'post_parent' => $id,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'order' => $order,
                'orderby' => $orderby,
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

        $output .= '<div id="links">';

        $i = 0;
        foreach ($attachments as $id => $attachment) {
            $imgUrl = wp_get_attachment_image_src($id, 'large')[0];
            $title = $attachment->post_title;
            switch ($link) {
                case 'none':
                    $image = wp_get_attachment_image($id, $size, false, ['class' => 'thumbnail img-fluid']);
                    break;
                default:
                    $image = '<a data-gallery="" href="'.$imgUrl.'" title="'.$title.'" data-description="'.$attachment->post_excerpt.'">';
                    $image .= wp_get_attachment_image($id, $size, false, ['class' => 'thumbnail img-fluid']);
                    $image .= '</a>';
                    break;
            }
            $output .= (0 == $i % $columns) ? '<div class="row gallery-row">' : '';
            $output .= '<div class="'.$grid.'">'.$image;

            if (trim($attachment->post_excerpt)) {
                $output .= '<div class="caption hidden">'.wptexturize($attachment->post_excerpt).'</div>';
            }

            $output .= '</div>';
            ++$i;
            $output .= (0 == $i % $columns) ? '</div>' : '';
        }

        $output .= (0 != $i % $columns) ? '</div>' : '';
        $output .= '</div>'.$instance;

        return $output;
    }
}
