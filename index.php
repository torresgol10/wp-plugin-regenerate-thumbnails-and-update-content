<?php
/*
Plugin Name: Regenerate Thumbnails
Description: A plugin that allows you to regenerate all thumbnails and update their URLs in posts.
*/

function regenerate_thumbnails() {
    // Get all images
    $images = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1
    ));

    // Loop through all images
    foreach ($images as $image) {
        // Delete old thumbnails
        $metadata = wp_get_attachment_metadata($image->ID);
        $old_sizes = $metadata['sizes'];
        foreach ($old_sizes as $size) {
            $old_file = ABSPATH . $size['file'];
            unlink($old_file);
        }

        // Generate new thumbnails
        $intermediate_sizes = get_intermediate_image_sizes();
        foreach ($intermediate_sizes as $size) {
            $width = get_option("{$size}_size_w");
            $height = get_option("{$size}_size_h");
            if ($width > 0 && $height > 0) {
                image_make_intermediate_size(ABSPATH . $image->guid, $width, $height, true, $size);
            } else {
                // Use original image if size is 0 or negative
                $metadata = wp_get_attachment_metadata($image->ID);
                $file = ABSPATH . $metadata['file'];
                $new_file = ABSPATH . $size . '-' . $metadata['file'];
                copy($file, $new_file);
            }
        }

        // Update URLs in posts
        $new_metadata = wp_generate_attachment_metadata($image->ID, ABSPATH . $image->guid);
        wp_update_attachment_metadata($image->ID, $new_metadata);
    }
    wp_add_notice( "Todas las miniaturas han sido regeneradas", 'success' );
}

// Add button to admin interface
add_action('admin_notices', 'regenerate_thumbnails_button');
function regenerate_thumbnails_button() {
    echo '<a href="' . admin_url('admin.php?page=regenerate-thumbnails') . '" class="button-primary">Regenerate Thumbnails</a>';
}
