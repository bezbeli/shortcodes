<?php

namespace Bezbeli\Gallery;

class Gallery
{
    public function blueimpGallery($attr)
    {
        static $instance = 0;
        $instance++;

        $post = get_post();

        $id = $post->ID;
        $columns = $attr['columns'];
        $size = $attr['size'];
        $order = $attr['order'];
        $orderby = $attr['orderby'];
        $link = '';

        $columns = (12 % $columns == 0) ? $columns : 3;
        $grid = sprintf('col-sm-%1$s col-md-%1$s', 12/$columns);

        $attachments = get_children([
                'post_parent' => $id,
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'orderby' => $orderby,
                'order' => $order,
            ]);

        if (empty($attachments)) {
            return '';
        }

        $unique = (get_query_var('page')) ? $instance . '-p' . get_query_var('page') : $instance;
        $output = '';

        /*
        OUTPUT blueimp-gallery code ONLY ONCE, we dont need
        it more than ONCE if there are multiple galleries
        on same page
        */

        // if ($unique == 1) {
        $output .= '
                    <div id="blueimp-gallery-' . $instance . '" class="blueimp-gallery" data-use-bootstrap-modal="false">
                        <!-- The container for the modal slides -->
                        <div class="slides"></div>
                        <!-- Controls for the borderless lightbox -->
                        <h3 class="title"></h3>
                        <a class="prev">‹</a>
                        <a class="next">›</a>
                        <a class="close">×</a>
                        <a class="play-pause"></a>
                        <!-- The modal dialog, which will be used to wrap the lightbox content -->
                        <div class="modal fade">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title"></h4>
                                    </div>
                                    <div class="modal-body next"></div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default pull-left prev">
                                            <i class="glyphicon glyphicon-chevron-left"></i>
                                            Previous
                                        </button>
                                        <button type="button" class="btn btn-primary next">
                                            Next
                                            <i class="glyphicon glyphicon-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
        // }
        $output .= '<div id="#links-'. $id . '-' . $unique .'" class="gallery gallery-' . $id . '-' . $unique . '">';

        $i = 0;
        foreach ($attachments as $id => $attachment) {
            switch ($link) {
                case 'file':
                    $image = wp_get_attachment_link($id, $size, false, false);
                    break;
                case 'none':
                    $image = wp_get_attachment_image($id, $size, false, ['class' => 'thumbnail img-thumbnail']);
                    break;
                default:
                    $image = '<a href="' . wp_get_attachment_image_src($id, 'large')[0] . '" title="'.$attachment->post_title.'" data-gallery="#blueimp-gallery-' . $instance . '" data-description="'.$attachment->post_excerpt.'">';
                    $image .= wp_get_attachment_image($id, $size, false, ['class' => 'thumbnail img-thumbnail']);
                    $image .= '</a>';
                    break;
            }
            $output .= ($i % $columns == 0) ? '<div class="row">': '';
            $output .= '<div class="' . $grid .' mb-4">' . $image;

            if (trim($attachment->post_excerpt)) {
                $output .= '<div class="caption hidden">' . wptexturize($attachment->post_excerpt) . '</div>';
            }

            $output .= '</div>';
            $i++;
            $output .= ($i % $columns == 0) ? '</div>' : '';
        }

        $output .= ($i % $columns != 0) ? '</div>' : '';
        $output .= '</div>';
        return $output;
    }
}
