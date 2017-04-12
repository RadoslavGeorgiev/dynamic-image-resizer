# Dynamic Image Resizer
This plugin changes the behavior of WordPress when it comes to image-size generation.

If an image is uploaded while the plugin is active, custom image sizes will be not automatically generated. Instead, the plugin uses the `image_downsize` hook to generate cropped versions on the fly, when requested through functions like `wp_get_attachment_image`, `wp_get_attachment_image_src` and etc.

## Benefits
1. Image __upload is much faster__, because PHP does not need to generate additional sizes immediately. The more sizes created through `add_image_size()`, the more you will experience this effect.
2. There will be __no unused files__. In most websites, different image sizes serve different purposes and quite often, an image would normally be cropped to a size, which will be never used. By changing that, the plugin can drastically decrease your storage space usage.
3. You can now not only add a new image size, but even change the existing ones, without needing to use plugins like Regenerate Thumbnails.

## Known issues
1. If you remove an image size from your code, the cropped versions of images, associated with it will not be deleted.
2. WordPress uses all sizes with the same proportions as the current ones for the `srcset` attribute. Since image sizes are not generated until really needed, there will be no `srcset` attribute. This is a feature, which I'm looking to improve.

## Usage
The plugin is plug and play - just download and activate. No settings needed.
