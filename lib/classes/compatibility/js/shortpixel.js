/**
 * Overriding sliderUpdate() function of shortpixel-image-optimiser\res\js\short-pixel.js
 * We need to replace sites url with GCS url 
 */

// backing up original function
var _bk_sliderUpdate = sliderUpdate;

// overriding the original function
sliderUpdate = function sliderUpdate(id, thumb, bkThumb, percent, filename) {
  // replacing sites url with GCS url.
  thumb = thumb.replace(
    _stateless_short_pixel.baseurl,
    _stateless_short_pixel.bucketLink
  );
  // using original function aftr altering thumb url.
  _bk_sliderUpdate(id, thumb, bkThumb, percent, filename);
};