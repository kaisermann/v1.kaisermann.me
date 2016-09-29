$.fn.isOnScreen = function(offset, direction)
{
    offset = offset ? offset : 0;
    direction = direction ? direction : 0;

    var viewport = {};
    viewport.top = $(window).scrollTop();
    viewport.bottom = viewport.top + $(window).height();

    if(direction<0)
        viewport.top -= offset;

    if(direction>=0)
        viewport.bottom += offset;

    var bounds = {};
    bounds.top = this.offset().top;
    bounds.bottom = bounds.top + this.outerHeight();

    return ((bounds.top <= viewport.bottom) && (bounds.bottom >= viewport.top));
};